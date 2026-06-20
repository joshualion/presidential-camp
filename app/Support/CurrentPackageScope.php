<?php

namespace App\Support;

class CurrentPackageScope
{
    public function __construct(
        public readonly ?string $deployment_mode,
        public readonly ?string $package_type,
        public readonly ?string $scope_type,
        public readonly ?string $scope_name,
        public readonly ?int $state_id,
        public readonly ?string $state_name,
        public readonly ?int $senatorial_district_id,
        public readonly ?string $senatorial_district_name,
        public readonly ?int $federal_constituency_id,
        public readonly ?string $federal_constituency_name,
        public readonly ?int $lga_id,
        public readonly ?string $lga_name,
        public readonly array $modules = [],
        public readonly bool $fallback_used = false,
        public readonly ?string $license_status = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'deployment_mode' => $this->deployment_mode,
            'package_type' => $this->package_type,
            'scope_type' => $this->scope_type,
            'scope_name' => $this->scope_name,
            'state_id' => $this->state_id,
            'state_name' => $this->state_name,
            'senatorial_district_id' => $this->senatorial_district_id,
            'senatorial_district_name' => $this->senatorial_district_name,
            'federal_constituency_id' => $this->federal_constituency_id,
            'federal_constituency_name' => $this->federal_constituency_name,
            'lga_id' => $this->lga_id,
            'lga_name' => $this->lga_name,
            'modules' => $this->modules,
            'fallback_used' => $this->fallback_used,
            'license_status' => $this->license_status,
        ];
    }
}
