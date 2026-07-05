<?php

namespace App\Jobs;

use App\Models\SmsBatch;
use App\Services\Sms\SmsSettlementSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SyncPortalSmsBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public array $backoff = [60, 300, 900];
    public function __construct(public int $batchId) {}
    public function handle(SmsSettlementSyncService $sync): void
    {
        $batch = SmsBatch::find($this->batchId);
        if (!$batch?->portal_batch_reference) return;
        $result = $sync->sync($batch);
        if (!($result['ok'] ?? false)) throw new RuntimeException('Portal SMS batch synchronization is pending retry.');
    }
}
