<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncPortalSmsAllocations;
use App\Jobs\SyncPortalSmsBatch;
use App\Models\SmsBatch;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('notifications:prune-expired')->hourly();

$smsEnabled = fn () => Schema::hasTable('sms_allocations')
    && Schema::hasColumn('system_settings', 'portal_sms_enabled')
    && (bool) SystemSetting::query()->first()?->portal_sms_enabled;

Schedule::job(new SyncPortalSmsAllocations)->everyFiveMinutes()->withoutOverlapping()->when($smsEnabled);
Schedule::call(function () {
    SmsBatch::query()
        ->whereNotNull('portal_batch_reference')
        ->where(fn ($query) => $query->whereNull('last_synced_at')->orWhere('last_synced_at', '<=', now()->subMinutes(10)))
        ->where(function ($query) {
            $query->whereNotIn('status', ['failed', 'completed', 'cancelled', 'settled'])
                ->orWhere(fn ($completed) => $completed->where('status', 'completed')->whereNull('messages_acknowledged_at'));
        })
        ->orderBy('id')
        ->limit(200)
        ->pluck('id')
        ->each(fn (int $id) => SyncPortalSmsBatch::dispatch($id));
})->everyTenMinutes()->name('portal-sms-batch-sync')->withoutOverlapping()->when($smsEnabled);
