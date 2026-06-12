<?php

namespace App\Http\Controllers;

use App\Models\CampaignNotification;
use App\Models\CampaignNotificationRecipient;
use App\Services\Notifications\CampaignNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = CampaignNotificationRecipient::query()
            ->with('notification')
            ->forUser($user)
            ->active()
            ->whereNull('dismissed_at')
            ->whereHas('notification')
            ->orderByDesc(
                CampaignNotification::select('occurred_at')
                    ->whereColumn('campaign_notifications.id', 'campaign_notification_recipients.notification_id')
                    ->limit(1)
            );

        if ($request->boolean('unread', true)) {
            $query->unread();
        }

        $notifications = $query
            ->limit((int) $request->integer('limit', 20))
            ->get()
            ->map(fn (CampaignNotificationRecipient $recipient) => $this->serializeRecipient($recipient));

        return response()->json([
            'data' => $notifications,
            'unread_count' => app(CampaignNotificationService::class)->unreadCount($user),
        ]);
    }

    public function unreadCount(Request $request, CampaignNotificationService $service)
    {
        return response()->json([
            'unread' => $service->unreadCount($request->user()),
        ]);
    }

    public function markRead(Request $request, CampaignNotification $notification, CampaignNotificationService $service)
    {
        $recipient = $this->recipientFor($request, $notification);

        if (!$recipient->read_at) {
            $recipient->forceFill(['read_at' => now()])->save();
        }

        return response()->json([
            'status' => 'ok',
            'unread_count' => $service->unreadCount($request->user()),
            'action_url' => $notification->action_url,
        ]);
    }

    public function readAll(Request $request, CampaignNotificationService $service)
    {
        CampaignNotificationRecipient::query()
            ->forUser($request->user())
            ->active()
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'status' => 'ok',
            'unread_count' => $service->unreadCount($request->user()),
        ]);
    }

    public function dismiss(Request $request, CampaignNotification $notification, CampaignNotificationService $service)
    {
        $recipient = $this->recipientFor($request, $notification);

        $recipient->forceFill([
            'read_at' => $recipient->read_at ?? now(),
            'dismissed_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'ok',
            'unread_count' => $service->unreadCount($request->user()),
        ]);
    }

    public function clearRead(Request $request)
    {
        CampaignNotificationRecipient::query()
            ->forUser($request->user())
            ->active()
            ->whereNotNull('read_at')
            ->whereNull('dismissed_at')
            ->update(['dismissed_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    private function recipientFor(Request $request, CampaignNotification $notification): CampaignNotificationRecipient
    {
        return CampaignNotificationRecipient::query()
            ->forUser($request->user())
            ->active()
            ->where('notification_id', $notification->id)
            ->whereHas('notification')
            ->firstOrFail();
    }

    private function serializeRecipient(CampaignNotificationRecipient $recipient): array
    {
        $notification = $recipient->notification;

        return [
            'id' => $notification->id,
            'recipient_id' => $recipient->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'severity' => $notification->severity,
            'action_url' => $notification->action_url,
            'occurred_at' => $notification->occurred_at?->toIso8601String(),
            'expires_at' => $notification->expires_at?->toIso8601String(),
            'read_at' => $recipient->read_at?->toIso8601String(),
            'dismissed_at' => $recipient->dismissed_at?->toIso8601String(),
        ];
    }
}
