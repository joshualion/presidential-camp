<?php

namespace App\Services\Notifications;

use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\PollingUnit;
use App\Models\PollingUnitAgentAssignment;
use App\Models\PollingUnitResult;
use App\Models\User;
use App\Models\VideoEvidence;
use Illuminate\Database\Eloquent\Model;

class NotificationContext
{
    public function __construct(
        public readonly ?int $regionId = null,
        public readonly ?int $stateId = null,
        public readonly ?int $senatorialDistrictId = null,
        public readonly ?int $federalConstituencyId = null,
        public readonly ?int $lgaId = null,
        public readonly ?int $wardId = null,
        public readonly ?int $pollingUnitId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            regionId: self::nullableInt($data['region_id'] ?? null),
            stateId: self::nullableInt($data['state_id'] ?? null),
            senatorialDistrictId: self::nullableInt($data['senatorial_district_id'] ?? null),
            federalConstituencyId: self::nullableInt($data['federal_constituency_id'] ?? null),
            lgaId: self::nullableInt($data['lga_id'] ?? null),
            wardId: self::nullableInt($data['ward_id'] ?? null),
            pollingUnitId: self::nullableInt($data['polling_unit_id'] ?? null),
        );
    }

    public function toDatabaseColumns(): array
    {
        return [
            'region_id' => $this->regionId,
            'state_id' => $this->stateId,
            'senatorial_district_id' => $this->senatorialDistrictId,
            'federal_constituency_id' => $this->federalConstituencyId,
            'lga_id' => $this->lgaId,
            'ward_id' => $this->wardId,
            'polling_unit_id' => $this->pollingUnitId,
        ];
    }

    public function isEmpty(): bool
    {
        foreach ($this->toDatabaseColumns() as $value) {
            if ($value !== null) {
                return false;
            }
        }

        return true;
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
