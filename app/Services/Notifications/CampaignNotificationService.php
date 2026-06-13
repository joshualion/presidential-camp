<?php

namespace App\Services\Notifications;

use App\Events\CampaignNotificationCreated;
use App\Models\CampaignNotification;
use App\Models\CampaignNotificationRecipient;
use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\PollingUnitAgentAssignment;
use App\Models\PollingUnitResult;
use App\Models\User;
use App\Models\VideoEvidence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CampaignNotificationService
{
    public function __construct(
        private readonly NotificationContextResolver $contextResolver,
        private readonly NotificationRecipientResolver $recipientResolver,
    ) {
    }

    public function create(
        string $type,
        ?User $actor = null,
        ?Model $subject = null,
        ?NotificationContext $context = null,
        array $payload = [],
        bool $excludeActor = true,
    ): ?CampaignNotification {
        $context = $this->contextResolver->resolve($subject, $context);
        $recipients = $this->recipientResolver->resolve($type, $actor, $context, $excludeActor);

        if ($recipients->isEmpty()) {
            return null;
        }

        $message = $this->messageFor($type, $subject, $payload);

        return DB::transaction(function () use ($type, $actor, $subject, $context, $payload, $recipients, $message) {
            $notification = CampaignNotification::create(array_merge([
                'type' => $type,
                'title' => $payload['title'] ?? $message['title'],
                'message' => $payload['message'] ?? $message['message'],
                'actor_id' => $actor?->id,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'action_url' => $payload['action_url'] ?? $message['action_url'],
                'severity' => $payload['severity'] ?? $message['severity'],
                'metadata' => $payload['metadata'] ?? [],
                'occurred_at' => $payload['occurred_at'] ?? now(),
                'expires_at' => $payload['expires_at'] ?? now()->addHours(24),
            ], $context->toDatabaseColumns()));

            $now = now();
            $rows = $recipients->map(fn (User $recipient) => [
                'notification_id' => $notification->id,
                'user_id' => $recipient->id,
                'delivered_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            CampaignNotificationRecipient::insertOrIgnore($rows);

            CampaignNotificationRecipient::query()
                ->where('notification_id', $notification->id)
                ->with('notification')
                ->get()
                ->each(fn (CampaignNotificationRecipient $recipient) => $this->broadcastRecipient($recipient));

            return $notification;
        });
    }

    public function unreadCount(User $user): int
    {
        return CampaignNotificationRecipient::query()
            ->forUser($user)
            ->active()
            ->unread()
            ->count();
    }

    private function broadcastRecipient(CampaignNotificationRecipient $recipient): void
    {
        try {
            event(new CampaignNotificationCreated($recipient));
        } catch (Throwable $exception) {
            Log::warning('Campaign notification broadcast failed.', [
                'notification_id' => $recipient->notification_id,
                'recipient_id' => $recipient->id,
                'user_id' => $recipient->user_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function messageFor(string $type, ?Model $subject, array $payload): array
    {
        return match ($type) {
            'user.created' => $this->userCreatedMessage($subject),
            'result.uploaded' => $this->resultUploadedMessage($subject),
            'incident.created' => $this->incidentCreatedMessage($subject),
            'evidence.uploaded' => $this->evidenceUploadedMessage($subject),
            'agent.request.submitted' => $this->agentRequestMessage($subject),
            'result.review.requested' => [
                'title' => 'Result requires review',
                'message' => 'A polling unit result is pending review or verification.',
                'severity' => 'warning',
                'action_url' => null,
            ],
            'incident.escalated' => [
                'title' => 'Incident escalated',
                'message' => 'An election incident requires escalation or urgent review.',
                'severity' => 'danger',
                'action_url' => null,
            ],
            'admin.access.requested' => [
                'title' => 'New admin access request',
                'message' => 'A user requested admin or executive access.',
                'severity' => 'info',
                'action_url' => null,
            ],
            default => [
                'title' => $payload['title'] ?? 'Campaign notification',
                'message' => $payload['message'] ?? 'A campaign event requires your attention.',
                'severity' => $payload['severity'] ?? 'info',
                'action_url' => $payload['action_url'] ?? null,
            ],
        };
    }

    private function userCreatedMessage(?Model $subject): array
    {
        $name = $subject instanceof User ? trim(($subject->firstname ?? '') . ' ' . ($subject->lastname ?? '')) : 'A new member';

        return [
            'title' => 'New member added',
            'message' => trim($name) !== '' ? "{$name} was added to the campaign." : 'A new member was added to the campaign.',
            'severity' => 'info',
            'action_url' => null,
        ];
    }

    private function resultUploadedMessage(?Model $subject): array
    {
        $pollingUnit = $subject instanceof PollingUnitResult ? $subject->pollingUnit?->name : null;

        return [
            'title' => $pollingUnit ? "New result uploaded for {$pollingUnit}" : 'New polling unit result uploaded',
            'message' => 'A polling unit result has been uploaded and may require review.',
            'severity' => 'info',
            'action_url' => null,
        ];
    }

    private function incidentCreatedMessage(?Model $subject): array
    {
        $severity = $subject instanceof ElectionIncident && in_array($subject->severity, ['danger', 'critical', 'high'], true)
            ? 'danger'
            : 'warning';

        return [
            'title' => 'New incident reported',
            'message' => 'An election incident was recorded in your jurisdiction.',
            'severity' => $severity,
            'action_url' => null,
        ];
    }

    private function evidenceUploadedMessage(?Model $subject): array
    {
        $kind = $subject instanceof PictureEvidence ? 'Picture evidence' : ($subject instanceof VideoEvidence ? 'Video evidence' : 'Evidence');

        return [
            'title' => "{$kind} uploaded",
            'message' => 'Evidence was attached to a reported incident.',
            'severity' => 'warning',
            'action_url' => null,
        ];
    }

    private function agentRequestMessage(?Model $subject): array
    {
        $isSelfRequest = $subject instanceof PollingUnitAgentAssignment
            && $subject->source === PollingUnitAgentAssignment::SOURCE_SELF_REQUEST;

        return [
            'title' => 'Polling unit agent request submitted',
            'message' => $isSelfRequest
                ? 'A user submitted a polling unit agent request.'
                : 'A polling unit agent assignment was submitted.',
            'severity' => 'info',
            'action_url' => null,
        ];
    }
}
