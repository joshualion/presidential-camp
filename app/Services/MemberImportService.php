<?php

namespace App\Services;

use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\MemberImportBatch;
use App\Models\MemberImportRowError;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

class MemberImportService
{
    public const MAX_ROWS = 5000;

    private array $columnCache = [];

    public const HEADERS = [
        'name',
        'firstname',
        'lastname',
        'username',
        'email',
        'phone',
        'gender',
        'access_level',
        'role',
        'region',
        'state',
        'senatorial_district',
        'federal_constituency',
        'lga',
        'ward',
        'polling_unit',
        'valid_voter',
        'vin',
        'address',
        'occupation',
        'qualification',
    ];

    public function __construct(
        private readonly CampaignPackageRoleService $roles,
        private readonly CampaignPackageLocationFormService $locationForms,
        private readonly LicensedScopeQueryService $licensedScope,
        private readonly StructuralLocationAccessService $structuralAccess,
    ) {
    }

    public function templateRows(User $actor): array
    {
        return [
            ['firstname', 'lastname', 'phone', 'email'],
            ['Amina', 'Okafor', '08030000000', ''],
        ];
    }

    public function import(User $actor, UploadedFile $file, array $context = []): MemberImportBatch
    {
        $context = $this->prepareContext($actor, $context);

        $batch = MemberImportBatch::query()->create([
            'uuid' => (string) Str::uuid(),
            'uploaded_by' => $actor->id,
            'original_filename' => $this->sanitizeFilename($file->getClientOriginalName()),
            'status' => 'running',
            'started_at' => now(),
        ]);

        $seen = [
            'username' => [],
            'email' => [],
            'phone' => [],
        ];

        try {
            $headers = null;
            $rowNumber = 0;

            foreach ($this->rows($file) as $raw) {
                $rowNumber++;

                if ($this->isBlankRow($raw)) {
                    continue;
                }

                if ($headers === null) {
                    $headers = $this->normalizeHeaders($raw);
                    $this->validateHeaders($headers);
                    $duplicates = collect($headers)->filter()->duplicates()->unique()->values()->all();
                    if ($duplicates !== []) {
                        throw new RuntimeException('Duplicate columns are not allowed: '.implode(', ', $duplicates));
                    }
                    continue;
                }

                if ($batch->total_rows >= self::MAX_ROWS) {
                    $batch->increment('failed_rows');
                    $this->recordError($batch, $rowNumber, '', 'Row limit exceeded. Please split the file and upload a smaller batch.', []);
                    break;
                }

                $batch->increment('total_rows');
                $row = $this->combineRow($headers, $raw);
                $result = $this->validateAndBuild($actor, $row, $seen, $context);

                if ($result['errors'] !== []) {
                    $batch->increment($result['duplicate'] ? 'skipped_rows' : 'failed_rows');
                    $this->recordError($batch, $rowNumber, $this->identifier($row), implode(' ', $result['errors']), $this->safeRowData($row));
                    continue;
                }

                try {
                    DB::transaction(function () use ($result): void {
                        $user = User::query()->create($result['payload']);
                        $user->syncRoles([$result['role']->name]);
                    });
                } catch (QueryException $exception) {
                    $duplicate = $this->isDuplicateException($exception);
                    $batch->increment($duplicate ? 'skipped_rows' : 'failed_rows');
                    $message = $duplicate
                        ? 'A member with the same username, email, or phone was created concurrently. This row was skipped.'
                        : 'The member could not be saved because one or more values were rejected by the database.';
                    $this->recordError($batch, $rowNumber, $this->identifier($row), $message, $this->safeRowData($row));
                    continue;
                }

                $this->markSeen($seen, $result['payload']);
                $batch->increment('imported_rows');
            }

            if ($headers === null) {
                throw new RuntimeException('The import file is empty or does not contain a header row.');
            }

            $batch->update([
                'status' => $batch->failed_rows > 0 ? 'completed_with_errors' : 'completed',
                'error_summary' => $this->summary($batch),
                'completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $batch->update([
                'status' => 'failed',
                'error_summary' => [
                    'message' => $exception instanceof RuntimeException
                        ? $this->safeMessage($exception->getMessage())
                        : 'Import stopped unexpectedly'.($rowNumber > 0 ? " while processing row {$rowNumber}" : '').'. Please verify the file and try again.',
                ],
                'completed_at' => now(),
            ]);
        }

        return $batch->fresh(['rowErrors' => fn ($query) => $query->orderBy('row_number')->limit(50)]);
    }

    public function validateAndBuild(User $actor, array $row, array &$seen, array $context = []): array
    {
        $errors = [];
        $duplicate = false;
        [$firstname, $lastname] = $this->personName($row);
        $phone = $this->normalizePhone($row['phone'] ?? '');
        $suppliedEmail = strtolower($this->clean($row['email'] ?? ''));
        $gender = strtolower($this->clean($row['gender'] ?? ''));
        $rowAccessLevel = strtolower($this->clean($row['access_level'] ?? ''));
        $rowRoleName = $this->clean($row['role'] ?? '');
        $accessLevel = $context['access_level'] ?? ($rowAccessLevel ?: 'user');
        $role = $context['role'] ?? $this->resolveRole($actor, $accessLevel, $row['role'] ?? null);

        if ($firstname === '') {
            $errors[] = 'name is required.';
        }
        if ($phone === '' && $suppliedEmail === '') {
            $errors[] = 'Provide at least a phone number or email address.';
        }
        if ($suppliedEmail !== '' && !filter_var($suppliedEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email must be a valid email address.';
        }
        if ($gender !== '' && !in_array($gender, ['male', 'female'], true)) {
            $errors[] = 'gender must be male or female when supplied.';
        }
        if ($accessLevel === 'superadmin' || !$this->roles->canAssignAccessLevel($actor, $accessLevel)) {
            $errors[] = 'The selected access level is not available to this administrator.';
        }
        if (isset($context['access_level']) && $rowAccessLevel !== '' && $rowAccessLevel !== $accessLevel) {
            $errors[] = 'The row access level does not match the selected batch access level.';
        }
        if (!$role || !$this->roles->canAssignRole($actor, $role, $accessLevel)) {
            $errors[] = 'The selected role is not valid for the batch access level.';
        }
        if (isset($context['role']) && $rowRoleName !== '' && $role && strcasecmp($rowRoleName, $role->name) !== 0) {
            $errors[] = 'The row role does not match the selected batch role.';
        }

        foreach (['email' => $suppliedEmail, 'phone' => $phone] as $field => $value) {
            if ($value === '') {
                continue;
            }
            $key = strtolower($value);
            if (isset($seen[$field][$key])) {
                $duplicate = true;
                $errors[] = "{$field} is duplicated inside this file.";
            }
            if (User::query()->where($field, $value)->exists()) {
                $duplicate = true;
                $errors[] = "{$field} already belongs to an existing member and was skipped.";
            }
        }

        $locations = $this->resolveLocations($actor, $row, $context);
        $errors = array_merge($errors, $locations['errors']);
        if ($locations['errors'] === []) {
            $errors = array_merge($errors, array_values($this->licensedScope->payloadErrors($locations['payload'])));
        }

        if ($errors !== []) {
            return ['errors' => array_values(array_unique($errors)), 'duplicate' => $duplicate, 'payload' => [], 'role' => null];
        }

        $email = $suppliedEmail ?: $this->placeholderEmail($phone, $seen);
        $username = $this->uniqueUsername(
            $this->clean($row['username'] ?? ''),
            $firstname,
            $lastname,
            $email,
            $phone,
            $seen
        );

        return [
            'errors' => [],
            'duplicate' => false,
            'role' => $role,
            'payload' => [
                'firstname' => $firstname,
                'lastname' => $lastname ?: null,
                'username' => $username,
                'email' => $email,
                'phone' => $phone ?: null,
                'gender' => $gender ?: null,
                'password' => Hash::make($context['password'] ?? 'password'),
                'access_level' => $accessLevel,
                'country_id' => $locations['payload']['country_id'] ?? null,
                'region_id' => $locations['payload']['region_id'] ?? null,
                'state_id' => $locations['payload']['state_id'] ?? null,
                'senatorial_district_id' => $locations['payload']['senatorial_district_id'] ?? null,
                'federal_constituency_id' => $locations['payload']['federal_constituency_id'] ?? null,
                'lga_id' => $locations['payload']['lga_id'] ?? null,
                'ward_id' => $locations['payload']['ward_id'] ?? null,
                'polling_unit_id' => $locations['payload']['polling_unit_id'] ?? null,
                'validVoter' => $this->yesNo($row['valid_voter'] ?? 'no'),
                'vin' => $this->clean($row['vin'] ?? '') ?: null,
                'address' => $this->clean($row['address'] ?? '') ?: null,
                'occupation' => $this->clean($row['occupation'] ?? '') ?: null,
                'qualification' => $this->clean($row['qualification'] ?? '') ?: null,
                'status' => 'active',
                'requires_update' => true,
            ],
        ];
    }

    private function prepareContext(User $actor, array $context): array
    {
        $accessLevel = strtolower($this->clean($context['access_level'] ?? 'user'));
        $role = !empty($context['role_id'])
            ? Role::query()->find($context['role_id'])
            : $this->roles->rolesForAccessLevel($actor, $accessLevel)->first();
        $errors = [];

        if ($accessLevel === '' || $accessLevel === 'superadmin' || !$this->roles->canAssignAccessLevel($actor, $accessLevel)) {
            $errors['access_level'] = 'Select an access level you are permitted to assign for this campaign package.';
        }
        if (!$role || !$this->roles->canAssignRole($actor, $role, $accessLevel)) {
            $errors['role_id'] = 'Select a role that is allowed for the selected access level.';
        }

        $location = $this->locationForms->mergeFixedPayload($this->actorScopePayload($actor));
        $location['country_id'] ??= $this->systemCountryId();
        $selectedRegionId = !empty($context['region_id']) ? (int) $context['region_id'] : null;
        $selectedStateId = !empty($context['state_id']) ? (int) $context['state_id'] : null;
        $region = $selectedRegionId ? $this->scopedLocation($actor, Region::query(), 'regions', $selectedRegionId) : null;
        $state = $selectedStateId ? $this->scopedLocation($actor, State::query(), 'states', $selectedStateId) : null;

        if ($selectedRegionId && !$region) {
            $errors['region_id'] = 'The selected region is outside your licensed or administrative scope.';
        }
        if ($selectedStateId && !$state) {
            $errors['state_id'] = 'The selected state is outside your licensed or administrative scope.';
        }
        if ($state && $region && (int) $state->region_id !== (int) $region->id) {
            $errors['state_id'] = 'The selected state does not belong to the selected region.';
        }

        if ($state) {
            $region = $state->region;
            $location['state_id'] = $state->id;
            $location['region_id'] = $state->region_id;
            $location['country_id'] ??= $region?->country_id;
        } elseif ($region) {
            $location['region_id'] = $region->id;
            $location['country_id'] ??= $region->country_id;
        }

        if ($accessLevel === 'regionaladmin' && empty($location['region_id'])) {
            $errors['region_id'] = 'A target region is required for Regional Admin imports.';
        }
        if ($accessLevel === 'stateadmin' && empty($location['state_id'])) {
            $errors['state_id'] = 'A target state is required for State Admin imports.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'access_level' => $accessLevel,
            'role' => $role,
            'password' => trim((string) ($context['default_password'] ?? '')) !== ''
                ? (string) $context['default_password']
                : 'password',
            'location' => $location,
            'selected_region' => $region?->id,
            'selected_state' => $state?->id,
        ];
    }

    private function scopedLocation(User $actor, $query, string $subject, int $id): mixed
    {
        $this->structuralAccess->applyScope($query, $actor, $subject);

        return $query->whereKey($id)->first();
    }

    private function systemCountryId(): ?int
    {
        $countryName = SystemSetting::query()->whereKey(1)->value('system_country');

        return $countryName ? Country::query()->where('name', $countryName)->value('id') : null;
    }

    private function personName(array $row): array
    {
        $firstname = $this->clean($row['firstname'] ?? '');
        $lastname = $this->clean($row['lastname'] ?? '');
        $name = $this->clean($row['name'] ?? '');

        if ($firstname === '' && $name !== '') {
            $parts = preg_split('/\s+/', $name, 2) ?: [];
            $firstname = $parts[0] ?? '';
            $lastname = $lastname ?: ($parts[1] ?? '');
        }

        return [$firstname, $lastname];
    }

    private function placeholderEmail(string $phone, array $seen): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        $base = $digits !== '' ? 'phone'.$digits : 'member-'.Str::lower((string) Str::uuid());
        $candidate = $base.'@example.com';
        $suffix = 1;

        while (isset($seen['email'][strtolower($candidate)]) || User::query()->where('email', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix++.'@example.com';
        }

        return $candidate;
    }

    private function uniqueUsername(
        string $requested,
        string $firstname,
        string $lastname,
        string $email,
        string $phone,
        array $seen
    ): string
    {
        $nameBased = trim($firstname.' '.$lastname);
        $base = Str::slug($requested ?: $nameBased, '_')
            ?: Str::slug(Str::before($email, '@'), '_')
            ?: Str::slug($phone, '_')
            ?: 'member';
        $base = Str::limit($base, 170, '');
        $candidate = $base;
        $suffix = 1;

        while (isset($seen['username'][strtolower($candidate)]) || User::query()->where('username', $candidate)->exists()) {
            $candidate = $base.'_'.$suffix++;
        }

        return $candidate;
    }

    private function resolveLocations(User $actor, array $row, array $context = []): array
    {
        $errors = [];
        $payload = $context['location'] ?? $this->locationForms->mergeFixedPayload($this->actorScopePayload($actor));

        $region = !empty($context['selected_region'])
            ? Region::query()->find($context['selected_region'])
            : $this->resolveNamed($actor, Region::query(), 'regions', $row['region'] ?? null);
        if (($row['region'] ?? '') !== '' && !$region) {
            $errors[] = 'region was not found.';
        }
        if ($region) {
            $payload['region_id'] = $region->id;
            $payload['country_id'] ??= $region->country_id;
        }

        $state = !empty($context['selected_state'])
            ? State::query()->find($context['selected_state'])
            : $this->resolveState($actor, $row['state'] ?? null, !empty($payload['region_id']) ? ['region_id' => $payload['region_id']] : []);
        if (($row['state'] ?? '') !== '' && !$state) {
            $errors[] = 'state was not found.';
        }
        if ($state) {
            $payload['state_id'] = $state->id;
            $payload['region_id'] = $state->region_id;
        }

        $senatorial = $this->resolveNamed($actor, SenatorialDistrict::query(), 'senatorial_districts', $row['senatorial_district'] ?? null, $state ? ['state_id' => $state->id] : []);
        if (($row['senatorial_district'] ?? '') !== '' && !$senatorial) {
            $errors[] = 'senatorial_district was not found.';
        }
        if ($senatorial) {
            $payload['senatorial_district_id'] = $senatorial->id;
            $payload['state_id'] ??= $senatorial->state_id;
        }

        $federalWhere = array_filter([
            'state_id' => $payload['state_id'] ?? null,
            'senatorial_district_id' => $payload['senatorial_district_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
        $federal = $this->resolveNamed($actor, FederalConstituency::query(), 'federal_constituencies', $row['federal_constituency'] ?? null, $federalWhere);
        if (($row['federal_constituency'] ?? '') !== '' && !$federal) {
            $errors[] = 'federal_constituency was not found.';
        }
        if ($federal) {
            $payload['federal_constituency_id'] = $federal->id;
            $payload['senatorial_district_id'] ??= $federal->senatorial_district_id;
            $payload['state_id'] ??= $federal->state_id;
        }

        $lgaWhere = array_filter([
            'state_id' => $payload['state_id'] ?? null,
            'senatorial_district_id' => $payload['senatorial_district_id'] ?? null,
            'federal_constituency_id' => $payload['federal_constituency_id'] ?? null,
        ], fn ($value, $column) => $value !== null && $value !== '' && $this->hasColumn('local_government_areas', $column), ARRAY_FILTER_USE_BOTH);
        $lga = $this->resolveNamed($actor, LocalGovernmentArea::query(), 'local_government_areas', $row['lga'] ?? null, $lgaWhere);
        if (($row['lga'] ?? '') !== '' && !$lga) {
            $errors[] = 'lga was not found.';
        }
        if ($lga) {
            $payload['lga_id'] = $lga->id;
            $payload['state_id'] ??= $lga->state_id;
            $payload['senatorial_district_id'] ??= $lga->senatorial_district_id;
            $payload['federal_constituency_id'] ??= $lga->federal_constituency_id;
        }

        $ward = $this->resolveNamed($actor, Ward::query(), 'wards', $row['ward'] ?? null, $payload['lga_id'] ?? null ? ['lga_id' => $payload['lga_id']] : []);
        if (($row['ward'] ?? '') !== '' && !$ward) {
            $errors[] = 'ward was not found.';
        }
        if ($ward) {
            $payload['ward_id'] = $ward->id;
            $payload['lga_id'] ??= $ward->lga_id;
        }

        $pollingUnit = $this->resolvePollingUnit($actor, $row['polling_unit'] ?? null, $payload['ward_id'] ?? null);
        if (($row['polling_unit'] ?? '') !== '' && !$pollingUnit) {
            $errors[] = 'polling_unit was not found.';
        }
        if ($pollingUnit) {
            $payload['polling_unit_id'] = $pollingUnit->id;
            $payload['pu_id'] = $pollingUnit->id;
            $payload['ward_id'] ??= $pollingUnit->ward_id;
            $payload['senatorial_district_id'] ??= $pollingUnit->senatorial_district_id;
            $payload['federal_constituency_id'] ??= $pollingUnit->federal_constituency_id;
        }

        $this->hydrateParents($payload);
        $errors = array_merge($errors, $this->locationConsistencyErrors($payload));

        foreach ($this->recordsForScopeChecks($payload) as $record) {
            if (!$this->structuralAccess->isWithinScope($actor, $record)) {
                $errors[] = 'One or more locations are outside your administrative scope.';
                break;
            }
        }

        return ['payload' => $payload, 'errors' => array_values(array_unique($errors))];
    }

    private function hydrateParents(array &$payload): void
    {
        if (!empty($payload['polling_unit_id'])) {
            $pu = PollingUnit::with('ward.localGovernmentArea.state')->find($payload['polling_unit_id']);
            $payload['ward_id'] ??= $pu?->ward_id;
            $payload['senatorial_district_id'] ??= $pu?->senatorial_district_id;
            $payload['federal_constituency_id'] ??= $pu?->federal_constituency_id;
        }

        if (!empty($payload['ward_id'])) {
            $ward = Ward::with('localGovernmentArea.state')->find($payload['ward_id']);
            $payload['lga_id'] ??= $ward?->lga_id;
        }

        if (!empty($payload['lga_id'])) {
            $lga = LocalGovernmentArea::with('state')->find($payload['lga_id']);
            $payload['state_id'] ??= $lga?->state_id;
            $payload['senatorial_district_id'] ??= $lga?->senatorial_district_id;
            $payload['federal_constituency_id'] ??= $lga?->federal_constituency_id;
        }

        if (!empty($payload['state_id'])) {
            $payload['region_id'] ??= State::query()->whereKey($payload['state_id'])->value('region_id');
        }

        if (!empty($payload['region_id'])) {
            $payload['country_id'] ??= Region::query()->whereKey($payload['region_id'])->value('country_id');
        }
    }

    private function locationConsistencyErrors(array $payload): array
    {
        $errors = [];

        if (!empty($payload['state_id']) && !empty($payload['region_id']) && (int) State::query()->whereKey($payload['state_id'])->value('region_id') !== (int) $payload['region_id']) {
            $errors[] = 'state does not belong to the selected region.';
        }

        if (!empty($payload['ward_id']) && !empty($payload['lga_id']) && (int) Ward::query()->whereKey($payload['ward_id'])->value('lga_id') !== (int) $payload['lga_id']) {
            $errors[] = 'ward does not belong to the selected LGA.';
        }

        if (!empty($payload['lga_id']) && !empty($payload['state_id']) && (int) LocalGovernmentArea::query()->whereKey($payload['lga_id'])->value('state_id') !== (int) $payload['state_id']) {
            $errors[] = 'lga does not belong to the selected state.';
        }

        if (!empty($payload['polling_unit_id']) && !empty($payload['ward_id']) && (int) PollingUnit::query()->whereKey($payload['polling_unit_id'])->value('ward_id') !== (int) $payload['ward_id']) {
            $errors[] = 'polling_unit does not belong to the selected ward.';
        }

        return $errors;
    }

    private function recordsForScopeChecks(array $payload): array
    {
        return array_filter([
            !empty($payload['state_id']) ? State::find($payload['state_id']) : null,
            !empty($payload['senatorial_district_id']) ? SenatorialDistrict::find($payload['senatorial_district_id']) : null,
            !empty($payload['federal_constituency_id']) ? FederalConstituency::find($payload['federal_constituency_id']) : null,
            !empty($payload['lga_id']) ? LocalGovernmentArea::find($payload['lga_id']) : null,
            !empty($payload['ward_id']) ? Ward::find($payload['ward_id']) : null,
            !empty($payload['polling_unit_id']) ? PollingUnit::find($payload['polling_unit_id']) : null,
        ]);
    }

    private function resolveState(User $actor, ?string $value, array $where = []): ?State
    {
        return $this->resolveNamed($actor, State::query(), 'states', $value, $where);
    }

    private function resolveNamed(User $actor, $query, string $subject, ?string $value, array $where = [])
    {
        $value = $this->clean($value);
        if ($value === '') {
            return null;
        }

        foreach ($where as $column => $expected) {
            $query->where($column, $expected);
        }

        $this->structuralAccess->applyScope($query, $actor, $subject);

        if (ctype_digit($value)) {
            $byId = (clone $query)->whereKey((int) $value)->first();
            if ($byId) {
                return $byId;
            }
        }

        return $query->get()
            ->first(fn ($record) => $this->normalize($record->name) === $this->normalize($value));
    }

    private function resolvePollingUnit(User $actor, ?string $value, ?int $wardId = null): ?PollingUnit
    {
        $value = $this->clean($value);
        if ($value === '') {
            return null;
        }

        $query = PollingUnit::query();
        if ($wardId) {
            $query->where('ward_id', $wardId);
        }
        $this->structuralAccess->applyScope($query, $actor, 'polling_units');

        if ($this->hasColumn('polling_units', 'inec_full_code')) {
            $byFullCode = (clone $query)->where('inec_full_code', $value)->first();
            if ($byFullCode) {
                return $byFullCode;
            }
        }

        if ($this->hasColumn('polling_units', 'inec_pu_code')) {
            $byCode = (clone $query)->where('inec_pu_code', $value)->first();
            if ($byCode) {
                return $byCode;
            }
        }

        return $query->get()
            ->first(fn ($record) => $this->normalize($record->name) === $this->normalize($value));
    }

    private function resolveRole(User $actor, string $accessLevel, ?string $value): ?Role
    {
        $value = $this->clean($value);
        if ($value === '') {
            return $this->roles->rolesForAccessLevel($actor, $accessLevel)->first();
        }

        return $this->roles
            ->rolesForAccessLevel($actor, $accessLevel)
            ->first(fn (Role $role) => $this->normalize($role->name) === $this->normalize($value) || (string) $role->id === $value);
    }

    private function rows(UploadedFile $file): iterable
    {
        $path = $file->getRealPath();
        if (!$path || !is_readable($path)) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['csv', 'txt'], true)) {
            yield from $this->csvRows($path);

            return;
        }

        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            throw new RuntimeException('Unsupported import format. Upload a CSV, XLSX, or XLS file.');
        }

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
        } catch (\Throwable $exception) {
            throw new RuntimeException('The Excel workbook could not be read. Confirm that it is a valid XLSX or XLS file and try again.', 0, $exception);
        }

        try {
            foreach ($spreadsheet->getActiveSheet()->toArray('', false, true, false) as $row) {
                yield array_map(fn ($value) => is_scalar($value) ? (string) $value : '', $row);
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function csvRows(string $path): iterable
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new RuntimeException('The uploaded CSV file could not be opened.');
        }

        try {
            $sample = fgets($handle);
            if ($sample === false) {
                return;
            }

            $delimiter = collect([',', ';', "\t"])
                ->sortByDesc(fn (string $candidate) => count(str_getcsv($sample, $candidate)))
                ->first() ?: ',';
            rewind($handle);

            while (($row = fgetcsv($handle, null, $delimiter)) !== false) {
                yield $row;
            }
        } finally {
            fclose($handle);
        }
    }

    private function actorScopePayload(User $actor): array
    {
        $fields = match ($actor->access_level) {
            'regionaladmin', 'regionadmin' => ['country_id', 'region_id'],
            'stateadmin' => ['country_id', 'region_id', 'state_id'],
            'senatorialadmin' => ['country_id', 'region_id', 'state_id', 'senatorial_district_id'],
            'federaladmin' => ['country_id', 'region_id', 'state_id', 'senatorial_district_id', 'federal_constituency_id'],
            'lgaadmin' => ['country_id', 'region_id', 'state_id', 'senatorial_district_id', 'federal_constituency_id', 'lga_id'],
            'wardadmin' => ['country_id', 'region_id', 'state_id', 'senatorial_district_id', 'federal_constituency_id', 'lga_id', 'ward_id'],
            'puadmin', 'pollingunitadmin' => ['country_id', 'region_id', 'state_id', 'senatorial_district_id', 'federal_constituency_id', 'lga_id', 'ward_id', 'polling_unit_id'],
            default => [],
        };

        return collect([
            'country_id' => $actor->country_id,
            'region_id' => $actor->region_id,
            'state_id' => $actor->state_id,
            'senatorial_district_id' => $actor->senatorial_district_id,
            'federal_constituency_id' => $actor->federal_constituency_id,
            'lga_id' => $actor->lga_id,
            'ward_id' => $actor->ward_id,
            'polling_unit_id' => $actor->polling_unit_id,
        ])->only($fields)->filter(fn ($value) => $value !== null && $value !== '')->all();
    }

    private function markSeen(array &$seen, array $payload): void
    {
        foreach (['username', 'email', 'phone'] as $field) {
            $value = strtolower(trim((string) ($payload[$field] ?? '')));
            if ($value !== '') {
                $seen[$field][$value] = true;
            }
        }
    }

    private function isDuplicateException(QueryException $exception): bool
    {
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return in_array($driverCode, [19, 1062, 2067], true)
            || str_contains(strtolower($exception->getMessage()), 'duplicate')
            || str_contains(strtolower($exception->getMessage()), 'unique constraint');
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table.'.'.$column;

        return $this->columnCache[$key] ??= Schema::hasColumn($table, $column);
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $header = strtolower(trim($header));
        $header = str_replace([' ', '-', '.'], '_', $header);

        return match ($header) {
            'first_name' => 'firstname',
            'last_name' => 'lastname',
            'full_name', 'member_name' => 'name',
            'phone_number', 'mobile', 'mobile_number', 'telephone' => 'phone',
            'email_address' => 'email',
            'voter_card_status', 'validvoter' => 'valid_voter',
            'pu', 'polling_unit_code', 'polling_unit_name' => 'polling_unit',
            'geopolitical_region', 'geo_political_zone', 'zone' => 'region',
            'senatorial' => 'senatorial_district',
            'federal' => 'federal_constituency',
            default => $header,
        };
    }

    private function validateHeaders(array $headers): void
    {
        if (!in_array('name', $headers, true) && !in_array('firstname', $headers, true)) {
            throw new RuntimeException('Missing required name column. Use name or firstname.');
        }

        if (!in_array('phone', $headers, true) && !in_array('email', $headers, true)) {
            throw new RuntimeException('Missing contact column. Include phone, email, or both.');
        }
    }

    private function combineRow(array $headers, array $raw): array
    {
        $row = [];
        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }
            $row[$header] = $this->clean($raw[$index] ?? '');
        }

        return array_merge(array_fill_keys(self::HEADERS, ''), $row);
    }

    private function isBlankRow(array $raw): bool
    {
        return collect($raw)->every(fn ($value) => trim((string) $value) === '');
    }

    private function recordError(MemberImportBatch $batch, int $rowNumber, string $identifier, string $message, array $row): void
    {
        MemberImportRowError::query()->create([
            'member_import_batch_id' => $batch->id,
            'row_number' => $rowNumber,
            'identifier' => Str::limit($identifier, 191, ''),
            'error_message' => $this->safeMessage($message),
            'row_data' => $row,
        ]);
    }

    private function safeRowData(array $row): array
    {
        return collect($row)
            ->only(['name', 'firstname', 'lastname', 'username', 'email', 'phone', 'region', 'state', 'senatorial_district', 'federal_constituency', 'lga', 'ward', 'polling_unit'])
            ->map(fn ($value) => $this->escapeCsvInjection((string) $value))
            ->all();
    }

    private function identifier(array $row): string
    {
        return $row['email'] ?: ($row['phone'] ?: ($row['name'] ?: ($row['username'] ?: 'Row')));
    }

    private function clean(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? '';

        return Str::limit($value, 250, '');
    }

    private function normalizePhone(?string $value): string
    {
        return preg_replace('/[\s().-]+/', '', $this->clean($value)) ?? '';
    }

    private function normalize(string $value): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($value)) ?? '');
    }

    private function yesNo(?string $value): string
    {
        return in_array(strtolower($this->clean($value)), ['yes', 'y', 'true', '1', 'valid'], true) ? 'yes' : 'no';
    }

    private function sanitizeFilename(?string $filename): ?string
    {
        return $filename ? Str::limit(basename($filename), 191, '') : null;
    }

    private function safeMessage(string $message): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', $message) ?? 'Import failed.', 500, '');
    }

    private function escapeCsvInjection(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }

    private function summary(MemberImportBatch $batch): array
    {
        return [
            'imported' => $batch->imported_rows,
            'skipped' => $batch->skipped_rows,
            'failed' => $batch->failed_rows,
        ];
    }
}
