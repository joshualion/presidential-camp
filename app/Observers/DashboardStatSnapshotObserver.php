<?php

namespace App\Observers;

use App\Services\DashboardStatSnapshotService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardStatSnapshotObserver
{
    public function saved(Model $model): void
    {
        $this->queueRefresh($model);

        try {
            if ($model->wasChanged()) {
                app(DashboardStatSnapshotService::class)->queueAffectedRefreshesForAttributes($model->getOriginal());
            }
        } catch (Throwable $e) {
            $this->reportNonCriticalFailure($e);
        }
    }

    public function deleted(Model $model): void
    {
        $this->queueRefresh($model);
    }

    public function restored(Model $model): void
    {
        $this->queueRefresh($model);
    }

    private function queueRefresh(Model $model): void
    {
        try {
            app(DashboardStatSnapshotService::class)->queueAffectedRefreshes($model);
        } catch (Throwable $e) {
            $this->reportNonCriticalFailure($e);
        }
    }

    private function reportNonCriticalFailure(Throwable $e): void
    {
        Log::warning('Dashboard stat snapshot observer skipped a non-critical refresh.', [
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);
    }
}
