<?php

namespace App\Observers;

use App\Services\DashboardStatSnapshotService;
use Illuminate\Database\Eloquent\Model;

class DashboardStatSnapshotObserver
{
    public function saved(Model $model): void
    {
        $this->queueRefresh($model);

        if ($model->wasChanged()) {
            app(DashboardStatSnapshotService::class)->queueAffectedRefreshesForAttributes($model->getOriginal());
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
        app(DashboardStatSnapshotService::class)->queueAffectedRefreshes($model);
    }
}
