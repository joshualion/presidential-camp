<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\User;
use App\Support\SafeDatabase;

class PackageGovernanceService
{
    public const PRESIDENTIAL = 'presidential';
    public const GOVERNORSHIP = 'governorship';
    public const SENATORIAL = 'senatorial';
    public const FEDERAL = 'federal_constituency';
    public const CHAIRMANSHIP = 'chairmanship';

    public function package(): string
    {
        if (SafeDatabase::hasTable('local_licenses')) {
            $localPackage = \App\Models\LocalLicense::query()->latest('id')->value('package_type');
            if ($localPackage) {
                return $this->normalize($localPackage);
            }
        }

        if (SafeDatabase::hasTable('system_settings')) {
            return $this->normalize(SystemSetting::query()->value('package') ?? env('CAMPAIGN_PACKAGE_TYPE', self::PRESIDENTIAL));
        }

        return $this->normalize(env('CAMPAIGN_PACKAGE_TYPE', self::PRESIDENTIAL));
    }

    public function normalize(?string $package): string
    {
        $package = strtolower(trim((string) $package));

        return match ($package) {
            'presidential', 'national' => self::PRESIDENTIAL,
            'senatorial', 'senatorial_district', 'senatorial district' => self::SENATORIAL,
            'federal', 'federal_constituency', 'federal constituency' => self::FEDERAL,
            'chairmanship', 'lga', 'ward', 'pu', 'local_government', 'local government' => self::CHAIRMANSHIP,
            'state', 'governorship', 'governoship', 'governor' => self::GOVERNORSHIP,
            default => self::PRESIDENTIAL,
        };
    }

    public function label(): string
    {
        return match ($this->package()) {
            self::PRESIDENTIAL => 'Presidential Campaign',
            self::GOVERNORSHIP => 'Governorship Campaign',
            self::SENATORIAL => 'Senatorial District Campaign',
            self::FEDERAL => 'Federal Constituency Campaign',
            self::CHAIRMANSHIP => 'Chairmanship Campaign',
        };
    }

    public function options(): array
    {
        return [
            self::PRESIDENTIAL => 'Presidential Campaign',
            self::GOVERNORSHIP => 'Governorship Campaign',
            self::SENATORIAL => 'Senatorial District Campaign',
            self::FEDERAL => 'Federal Constituency Campaign',
            self::CHAIRMANSHIP => 'Chairmanship Campaign',
        ];
    }

    public function finalDisputeAuthorityAccessLevel(): string
    {
        return match ($this->package()) {
            self::PRESIDENTIAL => 'nationaladmin',
            self::GOVERNORSHIP => 'stateadmin',
            self::SENATORIAL => 'senatorialadmin',
            self::FEDERAL => 'federaladmin',
            self::CHAIRMANSHIP => 'lgaadmin',
        };
    }

    public function canManageDisputes(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->access_level === 'superadmin'
            || $user->access_level === $this->finalDisputeAuthorityAccessLevel();
    }

    public function canVerifyResults(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->access_level === 'superadmin'
            || $user->can('election-review')
            || $user->can('election.review')
            || $user->can('verify election results')
            || $user->can('verify-election-results');
    }

    public function context(): array
    {
        return [
            'package' => $this->package(),
            'label' => $this->label(),
            'final_dispute_authority' => $this->finalDisputeAuthorityAccessLevel(),
        ];
    }
}
