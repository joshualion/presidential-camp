<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Notifications\CampaignNotificationService;
use App\Services\Notifications\NotificationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateCampaignNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $type,
        public ?User $actor = null,
        public ?Model $subject = null,
        public ?NotificationContext $context = null,
        public array $payload = [],
        public bool $excludeActor = true,
    ) {
    }

    public function handle(CampaignNotificationService $service): void
    {
        $service->create(
            type: $this->type,
            actor: $this->actor,
            subject: $this->subject,
            context: $this->context,
            payload: $this->payload,
            excludeActor: $this->excludeActor,
        );
    }
}
