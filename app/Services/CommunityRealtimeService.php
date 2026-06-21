<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Support\SafeDatabase;

class CommunityRealtimeService
{
    public const MODULE_KEY = 'community';

    public function __construct(
        private readonly ModuleGateService $moduleGate,
    ) {
    }

    public function moduleKey(): string
    {
        return self::MODULE_KEY;
    }

    public function moduleAvailable(): bool
    {
        return $this->moduleGate->enabled(self::MODULE_KEY);
    }

    public function enabled(): bool
    {
        if (!$this->moduleAvailable() || !SafeDatabase::hasTable('system_settings')) {
            return false;
        }

        return (bool) SystemSetting::query()->value('frontend_community');
    }

    public function mode(): string
    {
        return $this->realtimeAvailable() ? 'realtime' : 'polling';
    }

    public function modeLabel(): string
    {
        return $this->realtimeAvailable() ? 'Realtime / Reverb' : 'Polling';
    }

    public function statusMessage(): string
    {
        if (!$this->moduleAvailable()) {
            return 'Community Forum is not enabled for this license.';
        }

        if (!$this->enabled()) {
            return 'Community Forum is available for this installation and is currently disabled.';
        }

        if ($this->realtimeAvailable()) {
            return 'Realtime updates are available through Reverb/WebSockets.';
        }

        return 'Realtime updates are disabled. The community module will use polling mode.';
    }

    public function pollingIntervals(): array
    {
        return [
            'notifications' => (int) config('campaign.community.polling.notifications', 30000),
            'feed' => (int) config('campaign.community.polling.feed', 45000),
            'message_thread' => (int) config('campaign.community.polling.message_thread', 7000),
            'conversation_list' => (int) config('campaign.community.polling.conversation_list', 20000),
            'message_badge' => (int) config('campaign.community.polling.message_badge', 30000),
        ];
    }

    public function realtimeAvailable(): bool
    {
        if (config('broadcasting.default') !== 'reverb') {
            return false;
        }

        return filled(config('broadcasting.connections.reverb.key'))
            && filled(config('broadcasting.connections.reverb.secret'))
            && filled(config('broadcasting.connections.reverb.app_id'))
            && filled(config('broadcasting.connections.reverb.options.host'));
    }
}
