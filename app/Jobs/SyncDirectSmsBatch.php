<?php

namespace App\Jobs;

use App\Models\SmsDirectBatch;
use App\Services\Sms\Direct\DirectSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SyncDirectSmsBatch implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;

    public int $tries = 1;

    public int $timeout = 75;

    public function __construct(public int $batchId) {}

    public function handle(DirectSmsService $service): void
    {
        $batch = SmsDirectBatch::find($this->batchId);
        if (! $batch) {
            return;
        }
        $lock = Cache::lock('direct-sms-reports-'.$batch->id, 80);
        if (! $lock->get()) {
            return;
        }
        try {
            $service->sync($batch);
        } finally {
            $lock->release();
        }
    }
}
