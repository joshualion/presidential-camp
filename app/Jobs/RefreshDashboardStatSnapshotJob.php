<?php

namespace App\Jobs;

use App\Services\DashboardStatSnapshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshDashboardStatSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public string $accessLevel,
        public string $scopeType,
        public ?int $scopeId = null
    ) {
    }

    public function handle(DashboardStatSnapshotService $snapshotService): void
    {
        $snapshotService->refreshForScope($this->accessLevel, $this->scopeType, $this->scopeId);
    }
}
