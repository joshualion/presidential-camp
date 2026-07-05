<?php

namespace App\Jobs;

use App\Services\Sms\SmsAllocationSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SyncPortalSmsAllocations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public array $backoff = [60, 300, 900];
    public function handle(SmsAllocationSyncService $sync): void
    {
        $result = $sync->sync();
        if (!($result['ok'] ?? false)) throw new RuntimeException('Portal SMS allocation synchronization is pending retry.');
    }
}
