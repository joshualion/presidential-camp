<?php

namespace App\Jobs;

use App\Mail\CampaignEmailNotification;
use App\Models\EmailNotificationCampaign;
use App\Models\EmailNotificationRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailNotificationCampaignJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;
    public int $uniqueFor = 1800;

    public function __construct(public int $campaignId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->campaignId;
    }

    public function handle(): void
    {
        $campaign = EmailNotificationCampaign::query()->find($this->campaignId);
        if (!$campaign || in_array($campaign->status, ['sent', 'failed', 'partially_failed'], true)) {
            return;
        }

        if ($campaign->status === 'sending') {
            // A previous attempt stopped after claiming these rows. Do not resend them:
            // SMTP may have accepted the message before the worker was interrupted.
            $campaign->recipients()
                ->where('status', 'processing')
                ->update([
                    'status' => 'failed',
                    'error_message' => 'Delivery was interrupted after processing began; it was not retried to prevent a duplicate email.',
                    'updated_at' => now(),
                ]);
        }

        $campaign->forceFill(['status' => 'sending', 'started_at' => $campaign->started_at ?? now()])->save();
        $branding = CampaignEmailNotification::branding();

        EmailNotificationRecipient::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->eachById(function (EmailNotificationRecipient $recipient) use ($campaign, $branding) {
                $claimed = EmailNotificationRecipient::query()
                    ->whereKey($recipient->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'processing', 'updated_at' => now()]);
                if ($claimed !== 1) {
                    return;
                }

                try {
                    Mail::to($recipient->email)->send(new CampaignEmailNotification($campaign, $branding));
                    EmailNotificationRecipient::query()
                        ->whereKey($recipient->id)
                        ->where('status', 'processing')
                        ->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null, 'updated_at' => now()]);
                } catch (Throwable $exception) {
                    EmailNotificationRecipient::query()
                        ->whereKey($recipient->id)
                        ->where('status', 'processing')
                        ->update([
                            'status' => 'failed',
                            'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                            'updated_at' => now(),
                        ]);
                }
            });

        $sent = $campaign->recipients()->where('status', 'sent')->count();
        $failed = $campaign->recipients()->where('status', 'failed')->count();
        $outstanding = $campaign->recipients()->whereIn('status', ['pending', 'processing'])->count();
        if ($outstanding > 0) {
            $campaign->forceFill(['status' => 'sending', 'sent_count' => $sent, 'failed_count' => $failed])->save();
            return;
        }
        $status = $failed === 0 ? 'sent' : ($sent === 0 ? 'failed' : 'partially_failed');

        $campaign->forceFill([
            'status' => $status,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'completed_at' => now(),
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        $campaign = EmailNotificationCampaign::query()->find($this->campaignId);
        if (!$campaign) {
            return;
        }

        $campaign->recipients()
            ->whereIn('status', ['pending', 'processing'])
            ->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'updated_at' => now(),
            ]);

        $sent = $campaign->recipients()->where('status', 'sent')->count();
        $failed = $campaign->recipients()->where('status', 'failed')->count();
        $status = $failed === 0 && $sent === (int) $campaign->total_recipients
            ? 'sent'
            : ($sent > 0 ? 'partially_failed' : 'failed');

        $campaign->forceFill([
            'status' => $status,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'completed_at' => now(),
        ])->save();
    }
}
