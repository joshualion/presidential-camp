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

class NotificationContextResolver
{
    public function resolve(?Model $subject, ?NotificationContext $fallback = null): NotificationContext
    {
        if (!$subject) {
            return $fallback ?? new NotificationContext();
        }

        $context = match (true) {
            $subject instanceof User => $this->fromLocationCarrier($subject),
            $subject instanceof PollingUnitResult => $this->fromResult($subject),
            $subject instanceof ElectionIncident => $this->fromIncident($subject),
            $subject instanceof PictureEvidence => $this->resolve($subject->relationLoaded('incident') ? $subject->incident : $subject->incident()->first(), $fallback),
            $subject instanceof VideoEvidence => $this->resolve($subject->relationLoaded('incident') ? $subject->incident : $subject->incident()->first(), $fallback),
            $subject instanceof PollingUnitAgentAssignment => $this->fromAgentAssignment($subject),
            default => $this->fromLocationCarrier($subject),
        };

        return !$context->isEmpty() ? $context : ($fallback ?? $context);
    }

    private function fromLocationCarrier(Model $model): NotificationContext
    {
        return NotificationContext::fromArray([
            'region_id' => $model->getAttribute('region_id'),
            'state_id' => $model->getAttribute('state_id'),
            'senatorial_district_id' => $model->getAttribute('senatorial_district_id'),
            'federal_constituency_id' => $model->getAttribute('federal_constituency_id'),
            'lga_id' => $model->getAttribute('lga_id'),
            'ward_id' => $model->getAttribute('ward_id'),
            'polling_unit_id' => $model->getAttribute('polling_unit_id'),
        ]);
    }

    private function fromResult(PollingUnitResult $result): NotificationContext
    {
        $context = $this->fromLocationCarrier($result);

        if ($context->regionId || !$result->state_id) {
            return $context;
        }

        $result->loadMissing('state');

        return NotificationContext::fromArray(array_merge($context->toDatabaseColumns(), [
            'region_id' => $result->state?->region_id,
        ]));
    }

    private function fromIncident(ElectionIncident $incident): NotificationContext
    {
        $context = $this->fromLocationCarrier($incident);

        if ($context->regionId || !$incident->state_id) {
            return $context;
        }

        $incident->loadMissing('state');

        return NotificationContext::fromArray(array_merge($context->toDatabaseColumns(), [
            'region_id' => $incident->state?->region_id,
        ]));
    }

    private function fromAgentAssignment(PollingUnitAgentAssignment $assignment): NotificationContext
    {
        $assignment->loadMissing('pollingUnit.ward.localGovernmentArea.state');

        return $this->fromPollingUnit($assignment->pollingUnit);
    }

    private function fromPollingUnit(?PollingUnit $pollingUnit): NotificationContext
    {
        if (!$pollingUnit) {
            return new NotificationContext();
        }

        $pollingUnit->loadMissing('ward.localGovernmentArea.state');
        $ward = $pollingUnit->ward;
        $lga = $ward?->localGovernmentArea;
        $state = $lga?->state;

        return NotificationContext::fromArray([
            'region_id' => $state?->region_id,
            'state_id' => $state?->id,
            'senatorial_district_id' => $pollingUnit->senatorial_district_id,
            'federal_constituency_id' => $pollingUnit->federal_constituency_id,
            'lga_id' => $lga?->id,
            'ward_id' => $ward?->id,
            'polling_unit_id' => $pollingUnit->id,
        ]);
    }
}
