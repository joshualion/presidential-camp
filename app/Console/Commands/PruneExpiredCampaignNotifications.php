<?php

namespace App\Console\Commands;

use App\Models\CampaignNotification;
use App\Models\CampaignNotificationRecipient;
use Illuminate\Console\Command;

class PruneExpiredCampaignNotifications extends Command
{
    protected $signature = 'notifications:prune-expired';

    protected $description = 'Delete expired campaign notification recipients and orphaned notification rows.';

    public function handle(): int
    {
        $expiredIds = CampaignNotification::query()
            ->where('expires_at', '<=', now())
            ->pluck('id');

        if ($expiredIds->isEmpty()) {
            $this->info('No expired campaign notifications found.');

            return self::SUCCESS;
        }

        $recipientCount = CampaignNotificationRecipient::query()
            ->whereIn('notification_id', $expiredIds)
            ->delete();

        $notificationCount = CampaignNotification::query()
            ->whereIn('id', $expiredIds)
            ->doesntHave('recipients')
            ->delete();

        $this->info("Deleted {$recipientCount} recipient rows and {$notificationCount} notifications.");

        return self::SUCCESS;
    }
}
