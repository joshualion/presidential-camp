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

class SendDirectSmsBatch implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $batchId) {}

    public function handle(DirectSmsService $service): void
    {
        $batch = SmsDirectBatch::find($this->batchId);
        if (! $batch) {
            return;
        }
        $lock = Cache::lock('direct-sms-send-'.$batch->account_id, 65);
        if (! $lock->get()) {
            return;
        }
        try {
            $service->process($this->batchId);
        } finally {
            $lock->release();
        }
        if ($batch->fresh()->status === 'queued' && $batch->messages()->where('status', 'queued')->exists()) {
            self::dispatch($this->batchId)->delay(now()->addSeconds(2))->onQueue('sms');
        }
    }
}
