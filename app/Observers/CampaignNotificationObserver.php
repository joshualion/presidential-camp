<?php

namespace App\Observers;

use App\Jobs\CreateCampaignNotificationJob;
use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\PollingUnitAgentAssignment;
use App\Models\PollingUnitResult;
use App\Models\User;
use App\Models\VideoEvidence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CampaignNotificationObserver
{
    public function created(Model $model): void
    {
        $type = match (true) {
            $model instanceof User => 'user.created',
            $model instanceof PollingUnitResult => 'result.uploaded',
            $model instanceof ElectionIncident => 'incident.created',
            $model instanceof PictureEvidence, $model instanceof VideoEvidence => 'evidence.uploaded',
            $model instanceof PollingUnitAgentAssignment
                && $model->source === PollingUnitAgentAssignment::SOURCE_SELF_REQUEST => 'agent.request.submitted',
            default => null,
        };

        if (!$type) {
            return;
        }

        CreateCampaignNotificationJob::dispatch(
            type: $type,
            actor: Auth::user(),
            subject: $model,
        )->afterCommit();
    }
}
