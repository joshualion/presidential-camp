<?php

namespace App\Services\Sms;

use App\Models\PollingUnitAgentAssignment;
use App\Models\PollingUnit;
use App\Models\User;
use App\Models\Ward;
use App\Services\EmailNotificationAudienceService;
use App\Services\LocationScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SmsRecipientService
{
    public function __construct(
        private LocationScopeService $locationScope,
        private EmailNotificationAudienceService $audiences,
        private SmsPhoneNormalizer $phones,
    ) {}

    public function resolve(User $sender, array $selection): array
    {
        $source = (string) ($selection['recipient_source'] ?? 'members');
        $records = $source === 'manual'
            ? $this->manualRecords((string) ($selection['manual_recipients'] ?? ''))
            : $this->databaseRecords($sender, $source, (array) ($selection['filters'] ?? []));

        $prepared = $this->phones->prepare($records);
        $limit = (int) config('portal_sms.compose.max_batch_recipients', 0);
        if ($limit > 0 && $prepared['valid'] > $limit) {
            throw ValidationException::withMessages(['recipient_source' => 'This audience exceeds the configured SMS batch limit of '.number_format($limit).' chargeable recipients.']);
        }
        $prepared['preview'] = collect($prepared['recipients'])->take(10)->values()->all();
        $prepared['scope_summary'] = $source === 'manual'
            ? ['scope' => 'Manually supplied recipients', 'locations' => []]
            : $this->scopeSummary($sender, (array) ($selection['filters'] ?? []));
        $prepared['source'] = $source;
        $prepared['source_label'] = $this->sourceLabels()[$source] ?? ucfirst($source);

        return $prepared;
    }

    public function sourceLabels(): array
    {
        return [
            'members' => 'Members',
            'agents' => 'Polling-unit agents',
            'admins' => 'Executives and administrators',
            'manual' => 'Pasted phone numbers',
        ];
    }

    public function allowedAccessLevels(User $sender): array
    {
        return $this->audiences->accessLevelsFor($sender);
    }

    public function locationOptions(User $sender, string $type, array $filters = []): Collection
    {
        return $this->audiences->locationOptions($sender, $type, $filters);
    }

    private function databaseRecords(User $sender, string $source, array $filters): array
    {
        if (!in_array($source, ['members', 'agents', 'admins'], true)) {
            throw ValidationException::withMessages(['recipient_source' => 'Select a supported SMS recipient source.']);
        }

        $this->validateLocations($sender, $filters);
        $allowedLevels = array_keys($this->allowedAccessLevels($sender));
        $query = User::query()
            ->select(['users.id', 'users.uuid', 'users.firstname', 'users.lastname', 'users.phone', 'users.access_level'])
            ->whereKeyNot($sender->getKey())
            ->whereIn('users.access_level', $allowedLevels);

        if ($source !== 'agents') $this->locationScope->applyScope($query, $sender, 'users', 'users');

        match ($source) {
            'members' => $query->where('users.access_level', 'user'),
            'admins' => $query->where('users.access_level', '!=', 'user'),
            'agents' => $query->whereHas('pollingUnitAgentAssignments', function (Builder $assignment) use ($filters, $sender) {
                $assignment->where('status', $filters['agent_status'] ?? PollingUnitAgentAssignment::STATUS_APPROVED);
                $assignment->whereHas('pollingUnit', function (Builder $pollingUnit) use ($filters, $sender) {
                    $this->locationScope->applyScope($pollingUnit, $sender, 'polling_units', 'polling_units');
                    $this->applyPollingUnitBoundary($pollingUnit, $filters);
                });
            }),
        };

        if (($filters['access_level'] ?? null) && in_array($filters['access_level'], $allowedLevels, true)) {
            $query->where('users.access_level', $filters['access_level']);
        } elseif ($filters['access_level'] ?? null) {
            throw ValidationException::withMessages(['filters.access_level' => 'The selected access level is outside your permitted audience.']);
        }

        match ($filters['member_status'] ?? 'active') {
            'active' => $query->where('users.status', 'active'),
            'inactive' => $query->where('users.status', '!=', 'active'),
            'all' => null,
            default => throw ValidationException::withMessages(['filters.member_status' => 'Select a valid account status filter.']),
        };

        match ($filters['onboarding_status'] ?? 'all') {
            'complete' => $query->where('users.requires_update', false),
            'incomplete' => $query->where('users.requires_update', true),
            'all' => null,
            default => throw ValidationException::withMessages(['filters.onboarding_status' => 'Select a valid onboarding filter.']),
        };

        match ($filters['phone_availability'] ?? 'all') {
            'available' => $query->whereNotNull('users.phone')->whereRaw("TRIM(users.phone) <> ''"),
            'missing' => $query->where(fn (Builder $q) => $q->whereNull('users.phone')->orWhereRaw("TRIM(users.phone) = ''")),
            'all' => null,
            default => throw ValidationException::withMessages(['filters.phone_availability' => 'Select a valid phone availability filter.']),
        };

        if ($source !== 'agents') $this->applyLocationBoundary($query, $filters);

        return $query->orderBy('users.id')->get()->map(fn (User $user) => [
            'phone' => $user->phone,
            'name' => trim($user->firstname.' '.$user->lastname),
            'local_reference' => $user->uuid ?: (string) $user->id,
            'metadata' => ['core_user_reference' => $user->uuid ?: (string) $user->id, 'access_level' => $user->access_level],
        ])->all();
    }

    private function manualRecords(string $input): array
    {
        $values = preg_split('/[\s,;]+/', trim($input), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_map(fn (string $phone) => ['phone' => $phone, 'metadata' => ['source' => 'manual']], $values);
    }

    private function validateLocations(User $sender, array $filters): void
    {
        $state = $filters['state_id'] ?? null;
        $lga = $filters['lga_id'] ?? null;
        $ward = $filters['ward_id'] ?? null;
        $pollingUnit = $filters['polling_unit_id'] ?? null;

        if ($state && !$this->locationOptions($sender, 'states')->contains('id', (int) $state)) $this->invalidLocation('state_id');
        if ($lga && !$this->locationOptions($sender, 'lgas', ['state_id' => $state])->contains('id', (int) $lga)) $this->invalidLocation('lga_id');
        if ($ward && (
            !$this->locationOptions($sender, 'wards', ['lga_id' => $lga])->contains('id', (int) $ward)
            || !Ward::query()->whereKey($ward)
                ->when($lga, fn (Builder $query) => $query->where('lga_id', $lga))
                ->when($state && !$lga, fn (Builder $query) => $query->whereHas('localGovernmentArea', fn (Builder $lgaQuery) => $lgaQuery->where('state_id', $state)))
                ->exists()
        )) $this->invalidLocation('ward_id');
        if ($pollingUnit && (
            !$this->locationOptions($sender, 'polling_units', ['ward_id' => $ward])->contains('id', (int) $pollingUnit)
            || !PollingUnit::query()->whereKey($pollingUnit)
                ->when($ward, fn (Builder $query) => $query->where('ward_id', $ward))
                ->when($lga && !$ward, fn (Builder $query) => $query->whereHas('ward', fn (Builder $wardQuery) => $wardQuery->where('lga_id', $lga)))
                ->when($state && !$lga && !$ward, fn (Builder $query) => $query->whereHas('ward.localGovernmentArea', fn (Builder $lgaQuery) => $lgaQuery->where('state_id', $state)))
                ->exists()
        )) $this->invalidLocation('polling_unit_id');
    }

    private function applyLocationBoundary(Builder $query, array $filters): void
    {
        foreach ([
            'polling_unit_id' => LocationScopeService::POLLING_UNIT,
            'ward_id' => LocationScopeService::WARD,
            'lga_id' => LocationScopeService::LGA,
            'state_id' => LocationScopeService::STATE,
        ] as $field => $type) {
            if ($filters[$field] ?? null) {
                $this->locationScope->applyBoundaryFilter($query, $type, (int) $filters[$field], 'users', 'users');
                return;
            }
        }
    }

    private function applyPollingUnitBoundary(Builder $query, array $filters): void
    {
        foreach ([
            'polling_unit_id' => LocationScopeService::POLLING_UNIT,
            'ward_id' => LocationScopeService::WARD,
            'lga_id' => LocationScopeService::LGA,
            'state_id' => LocationScopeService::STATE,
        ] as $field => $type) {
            if ($filters[$field] ?? null) {
                $this->locationScope->applyBoundaryFilter($query, $type, (int) $filters[$field], 'polling_units', 'polling_units');
                return;
            }
        }
    }

    private function scopeSummary(User $sender, array $filters): array
    {
        $summary = [
            'scope' => $this->audiences->sendingScopeLabel($sender),
            'scope_type' => $this->locationScope->getScopeType($sender),
            'scope_id' => $this->locationScope->getScopeId($sender),
            'locations' => [],
        ];
        foreach ([
            'state_id' => ['states', 'State'],
            'lga_id' => ['lgas', 'LGA'],
            'ward_id' => ['wards', 'Ward'],
            'polling_unit_id' => ['polling_units', 'Polling Unit'],
        ] as $field => [$type, $label]) {
            if ($filters[$field] ?? null) {
                $summary['locations'][$label] = $this->locationOptions($sender, $type)->firstWhere('id', (int) $filters[$field])?->name;
            }
        }
        return $summary;
    }

    private function invalidLocation(string $field): never
    {
        throw ValidationException::withMessages(['filters.'.$field => 'The selected location is outside your jurisdiction or licensed campaign scope.']);
    }
}
