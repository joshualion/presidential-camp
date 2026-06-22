<?php

namespace App\Services;

use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\MemberImportBatch;
use App\Models\MemberImportRowError;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class MemberImportService
{
    public const MAX_ROWS = 5000;

    public const HEADERS = [
        'firstname',
        'lastname',
        'username',
        'email',
        'phone',
        'gender',
        'access_level',
        'role',
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
        $defaultAccess = array_key_exists('user', $this->roles->assignableAccessLevels($actor))
            ? 'user'
            : array_key_first($this->roles->assignableAccessLevels($actor));

        $fixed = $this->locationForms->context($actor)['fixed_labels'] ?? [];

        return [
            self::HEADERS,
            [
                'Amina',
                'Okafor',
                'amina.okafor',
                'amina.okafor@example.com',
                '08030000000',
                'female',
                $defaultAccess ?: 'user',
                optional($this->roles->defaultRole($actor))->name ?: 'Member',
                $fixed['state_id'] ?? '',
                $fixed['senatorial_district_id'] ?? '',
                $fixed['federal_constituency_id'] ?? '',
                $fixed['lga_id'] ?? '',
                '',
                '',
                'yes',
                '',
                'Sample address',
                'Organizer',
                '',
            ],
        ];
    }

    public function import(User $actor, UploadedFile $file): MemberImportBatch
    {
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
            $path = $file->getRealPath();
            if (!$path || !is_readable($path)) {
                throw new RuntimeException('The uploaded file could not be read.');
            }

            $handle = fopen($path, 'rb');
            if (!$handle) {
                throw new RuntimeException('The uploaded file could not be opened.');
            }

            $headers = null;
            $rowNumber = 0;

            while (($raw = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->isBlankRow($raw)) {
                    continue;
                }

                if ($headers === null) {
                    $headers = $this->normalizeHeaders($raw);
                    $missing = array_diff($this->requiredHeaders(), $headers);
                    if ($missing !== []) {
                        throw new RuntimeException('Missing required columns: '.implode(', ', $missing));
                    }
                    continue;
                }

                if ($batch->total_rows >= self::MAX_ROWS) {
                    $this->recordError($batch, $rowNumber, '', 'Row limit exceeded. Please split the file and upload a smaller batch.', []);
                    break;
                }

                $batch->increment('total_rows');
                $row = $this->combineRow($headers, $raw);
                $result = $this->validateAndBuild($actor, $row, $seen);

                if ($result['errors'] !== []) {
                    $batch->increment($result['duplicate'] ? 'skipped_rows' : 'failed_rows');
                    $this->recordError($batch, $rowNumber, $this->identifier($row), implode(' ', $result['errors']), $this->safeRowData($row));
                    continue;
                }

                DB::transaction(function () use ($actor, $result): void {
                    $payload = $result['payload'];
                    $role = $result['role'];

                    $user = User::query()->create($payload);
                    if ($role) {
                        $user->syncRoles([$role->name]);
                    } elseif ($defaultRole = $this->roles->defaultRole($actor)) {
                        $user->syncRoles([$defaultRole->name]);
                    }
                });

                $batch->increment('imported_rows');
            }

            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }

            $batch->update([
                'status' => $batch->failed_rows > 0 ? 'completed_with_errors' : 'completed',
                'error_summary' => $this->summary($batch),
                'completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }

            $batch->update([
                'status' => 'failed',
                'error_summary' => ['message' => $this->safeMessage($exception->getMessage())],
                'completed_at' => now(),
            ]);
        }

        return $batch->fresh(['rowErrors' => fn ($query) => $query->orderBy('row_number')->limit(50)]);
    }

    public function validateAndBuild(User $actor, array $row, array &$seen): array
    {
        $errors = [];
        $duplicate = false;

        $firstname = $this->clean($row['firstname'] ?? '');
        $lastname = $this->clean($row['lastname'] ?? '');
        $username = $this->clean($row['username'] ?? '');
        $email = strtolower($this->clean($row['email'] ?? ''));
        $phone = $this->clean($row['phone'] ?? '');
        $gender = strtolower($this->clean($row['gender'] ?? ''));
        $accessLevel = strtolower($this->clean($row['access_level'] ?? 'user')) ?: 'user';

        foreach (['firstname' => $firstname, 'lastname' => $lastname, 'username' => $username, 'email' => $email, 'gender' => $gender] as $field => $value) {
            if ($value === '') {
                $errors[] = "{$field} is required.";
            }
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email must be a valid email address.';
        }

        if ($gender !== '' && !in_array($gender, ['male', 'female'], true)) {
            $errors[] = 'gender must be male or female.';
        }

        if ($accessLevel === 'superadmin') {
            $errors[] = 'superadmin users cannot be imported.';
        }

        if (!$this->roles->canAssignAccessLevel($actor, $accessLevel)) {
            $errors[] = 'access_level is not available for this user or licensed package.';
        }

        foreach (['username' => $username, 'email' => $email, 'phone' => $phone] as $field => $value) {
            if ($value === '') {
                continue;
            }
            $key = strtolower($value);
            if (isset($seen[$field][$key])) {
                $duplicate = true;
                $errors[] = "{$field} is duplicated inside this file.";
            }
            $seen[$field][$key] = true;

            if (User::query()->where($field, $value)->exists()) {
                $duplicate = true;
                $errors[] = "{$field} already exists and was skipped.";
            }
        }

        $role = $this->resolveRole($actor, $accessLevel, $row['role'] ?? null);
        if (($row['role'] ?? '') !== '' && !$role) {
            $errors[] = 'role is not valid for the selected access level or package.';
        }

        $locations = $this->resolveLocations($actor, $row);
        $errors = array_merge($errors, $locations['errors']);

        if ($locations['errors'] === []) {
            $scopeErrors = $this->licensedScope->payloadErrors($locations['payload']);
            foreach ($scopeErrors as $message) {
                $errors[] = $message;
            }
        }

        $errors = array_merge($errors, $this->requiredLocationErrors($accessLevel, $locations['payload']));

        if ($errors !== []) {
            return ['errors' => $errors, 'duplicate' => $duplicate, 'payload' => [], 'role' => null];
        }

        $password = Str::password(16);

        return [
            'errors' => [],
            'duplicate' => false,
            'role' => $role,
            'payload' => [
                'uuid' => (string) Str::uuid(),
                'firstname' => $firstname,
                'lastname' => $lastname,
                'username' => $username,
                'email' => $email,
                'phone' => $phone ?: null,
                'gender' => $gender,
                'password' => Hash::make($password),
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

    private function resolveLocations(User $actor, array $row): array
    {
        $errors = [];
        $payload = $this->locationForms->mergeFixedPayload([]);

        $state = $this->resolveState($row['state'] ?? null);
        if (($row['state'] ?? '') !== '' && !$state) {
            $errors[] = 'state was not found.';
        }
        if ($state) {
            $payload['state_id'] = $state->id;
            $payload['region_id'] = $state->region_id;
        }

        $senatorial = $this->resolveNamed(SenatorialDistrict::query(), $row['senatorial_district'] ?? null, $state ? ['state_id' => $state->id] : []);
        if (($row['senatorial_district'] ?? '') !== '' && !$senatorial) {
            $errors[] = 'senatorial_district was not found.';
        }
        if ($senatorial) {
            $payload['senatorial_district_id'] = $senatorial->id;
            $payload['state_id'] ??= $senatorial->state_id;
        }

        $federal = $this->resolveNamed(FederalConstituency::query(), $row['federal_constituency'] ?? null, $state ? ['state_id' => $state->id] : []);
        if (($row['federal_constituency'] ?? '') !== '' && !$federal) {
            $errors[] = 'federal_constituency was not found.';
        }
        if ($federal) {
            $payload['federal_constituency_id'] = $federal->id;
            $payload['senatorial_district_id'] ??= $federal->senatorial_district_id;
            $payload['state_id'] ??= $federal->state_id;
        }

        $lga = $this->resolveNamed(LocalGovernmentArea::query(), $row['lga'] ?? null, $payload['state_id'] ?? null ? ['state_id' => $payload['state_id']] : []);
        if (($row['lga'] ?? '') !== '' && !$lga) {
            $errors[] = 'lga was not found.';
        }
        if ($lga) {
            $payload['lga_id'] = $lga->id;
            $payload['state_id'] ??= $lga->state_id;
            $payload['senatorial_district_id'] ??= $lga->senatorial_district_id;
            $payload['federal_constituency_id'] ??= $lga->federal_constituency_id;
        }

        $ward = $this->resolveNamed(Ward::query(), $row['ward'] ?? null, $payload['lga_id'] ?? null ? ['lga_id' => $payload['lga_id']] : []);
        if (($row['ward'] ?? '') !== '' && !$ward) {
            $errors[] = 'ward was not found.';
        }
        if ($ward) {
            $payload['ward_id'] = $ward->id;
            $payload['lga_id'] ??= $ward->lga_id;
        }

        $pollingUnit = $this->resolvePollingUnit($row['polling_unit'] ?? null, $payload['ward_id'] ?? null);
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
    }

    private function locationConsistencyErrors(array $payload): array
    {
        $errors = [];

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

    private function requiredLocationErrors(string $accessLevel, array $payload): array
    {
        return match ($accessLevel) {
            'regionaladmin' => empty($payload['region_id']) ? ['region is required for regional admins.'] : [],
            'stateadmin' => empty($payload['state_id']) ? ['state is required for state admins.'] : [],
            'senatorialadmin' => empty($payload['senatorial_district_id']) ? ['senatorial_district is required for senatorial admins.'] : [],
            'federaladmin' => empty($payload['federal_constituency_id']) ? ['federal_constituency is required for federal admins.'] : [],
            'lgaadmin' => empty($payload['lga_id']) ? ['lga is required for LGA admins.'] : [],
            'wardadmin' => empty($payload['ward_id']) ? ['ward is required for ward admins.'] : [],
            'puadmin', 'user' => empty($payload['polling_unit_id']) ? ['polling_unit is required for this access level.'] : [],
            default => [],
        };
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

    private function resolveState(?string $value): ?State
    {
        return $this->resolveNamed(State::query(), $value);
    }

    private function resolveNamed($query, ?string $value, array $where = [])
    {
        $value = $this->clean($value);
        if ($value === '') {
            return null;
        }

        foreach ($where as $column => $expected) {
            $query->where($column, $expected);
        }

        if (ctype_digit($value)) {
            $byId = (clone $query)->whereKey((int) $value)->first();
            if ($byId) {
                return $byId;
            }
        }

        return $query->get()
            ->first(fn ($record) => $this->normalize($record->name) === $this->normalize($value));
    }

    private function resolvePollingUnit(?string $value, ?int $wardId = null): ?PollingUnit
    {
        $value = $this->clean($value);
        if ($value === '') {
            return null;
        }

        $query = PollingUnit::query();
        if ($wardId) {
            $query->where('ward_id', $wardId);
        }

        if (Schema::hasColumn('polling_units', 'inec_full_code')) {
            $byFullCode = (clone $query)->where('inec_full_code', $value)->first();
            if ($byFullCode) {
                return $byFullCode;
            }
        }

        if (Schema::hasColumn('polling_units', 'inec_pu_code')) {
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
            return $this->roles->defaultRole($actor);
        }

        return $this->roles
            ->rolesForAccessLevel($actor, $accessLevel)
            ->first(fn (Role $role) => $this->normalize($role->name) === $this->normalize($value) || (string) $role->id === $value);
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace([' ', '-', '.'], '_', $header);

        return match ($header) {
            'first_name' => 'firstname',
            'last_name' => 'lastname',
            'voter_card_status', 'validvoter' => 'valid_voter',
            'pu', 'polling_unit_code', 'polling_unit_name' => 'polling_unit',
            'senatorial' => 'senatorial_district',
            'federal' => 'federal_constituency',
            default => $header,
        };
    }

    private function requiredHeaders(): array
    {
        return ['firstname', 'lastname', 'username', 'email', 'gender', 'access_level'];
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
            ->only(['firstname', 'lastname', 'username', 'email', 'phone', 'access_level', 'role', 'state', 'lga', 'ward', 'polling_unit'])
            ->map(fn ($value) => $this->escapeCsvInjection((string) $value))
            ->all();
    }

    private function identifier(array $row): string
    {
        return $row['username'] ?: ($row['email'] ?: ($row['phone'] ?: 'Row'));
    }

    private function clean(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? '';

        return Str::limit($value, 250, '');
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
