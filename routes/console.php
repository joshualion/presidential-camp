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

Schedule::command('campaign:updates-check')->daily()->withoutOverlapping()
    ->when(fn () => config('updates.enabled') && app(\App\Services\InstallationStateService::class)->installed());

Schedule::command('campaign:portal-sync')->hourly()->withoutOverlapping()
    ->when(function () {
        $state = app(\App\Services\DeploymentRegistrationService::class)->summary();
        return app(\App\Services\InstallationStateService::class)->installed()
            && (!empty($state['registered']) || !empty($state['pending_revoke']));
    });

$smsEnabled = fn () => config('direct_sms.portal_visible') && Schema::hasTable('sms_allocations')
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


Schedule::call(function () {
 if (!Schema::hasTable('sms_direct_batches')) return;
 \App\Models\SmsDirectBatch::where('status','queued')->where(fn($q)=>$q->whereNull('scheduled_at')->orWhere('scheduled_at','<=',now()))->orderBy('id')->limit(100)->pluck('id')->each(fn($id)=>\App\Jobs\SendDirectSmsBatch::dispatch($id)->onQueue('sms'));
})->everyMinute()->name('direct-sms-dispatch')->withoutOverlapping();
Schedule::call(function () {
 if (!Schema::hasTable('sms_direct_messages')) return;
 \App\Models\SmsDirectBatch::whereHas('messages',fn($q)=>$q->where('status','accepted')->where('submitted_at','>=',now()->subDays(7))->where(fn($q)=>$q->whereNull('last_synced_at')->orWhere('last_synced_at','<',now()->subMinutes(10))))->orderBy('id')->limit(20)->each(fn($batch)=>\App\Jobs\SyncDirectSmsBatch::dispatch($batch->id)->onQueue('sms'));
})->everyTenMinutes()->name('direct-sms-delivery-sync')->withoutOverlapping();
