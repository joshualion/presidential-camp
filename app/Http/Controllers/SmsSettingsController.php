<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\Sms\PortalSmsClient;
use App\Services\Sms\SmsAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SmsSettingsController extends Controller
{
    public function index(PortalSmsClient $portal)
    {
        $settings = SystemSetting::firstOrCreate([]);
        $wallet = $settings->portal_sms_enabled ? $portal->getWallet() : null;
        return view('backend.sms.settings', ['profileData' => auth()->user(), 'settings' => $settings, 'wallet' => $wallet, 'pageTitle' => 'Portal SMS Settings']);
    }

    public function update(Request $request, SmsAuditService $audit)
    {
        $request->merge(['portal_sms_base_url' => rtrim((string) $request->input('portal_sms_base_url'), '/')]);
        $data = $request->validate([
            'portal_sms_enabled' => ['nullable', 'boolean'], 'portal_sms_base_url' => ['required', 'url', 'max:255', 'ends_with:/api/core/v1'],
            'portal_sms_client_key' => ['required', 'string', 'max:255'], 'portal_sms_secret' => ['nullable', 'string', 'min:16', 'max:1000'],
            'portal_sms_default_route' => ['required', 'in:regular,priority'], 'portal_sms_system_route_enabled' => ['nullable', 'boolean'],
            'portal_sms_sender_id_request_enabled' => ['nullable', 'boolean'], 'portal_sms_sending_enabled' => ['nullable', 'boolean'],
            'portal_sms_topup_enabled' => ['nullable', 'boolean'], 'portal_sms_credit_request_enabled' => ['nullable', 'boolean'],
        ]);
        foreach (['portal_sms_enabled', 'portal_sms_system_route_enabled', 'portal_sms_sender_id_request_enabled', 'portal_sms_sending_enabled', 'portal_sms_topup_enabled', 'portal_sms_credit_request_enabled'] as $field) $data[$field] = $request->boolean($field);
        $settings = SystemSetting::firstOrCreate([]);
        if ($data['portal_sms_enabled'] && blank($data['portal_sms_secret'] ?? null) && blank($settings->getRawOriginal('portal_sms_secret'))) {
            throw ValidationException::withMessages(['portal_sms_secret' => 'A portal SMS client secret is required before SMS can be enabled.']);
        }
        if (blank($data['portal_sms_secret'] ?? null)) unset($data['portal_sms_secret']);
        $settings->update($data);
        $audit->record('sms.settings.updated', $settings, ['fields' => array_values(array_diff(array_keys($data), ['portal_sms_secret']))]);
        return back()->with(['message' => 'Portal SMS settings updated. The secret is stored encrypted and cannot be displayed.', 'alert-type' => 'success']);
    }

    public function test(PortalSmsClient $portal, SmsAuditService $audit)
    {
        $result = $portal->diagnoseConnection(); $settings = SystemSetting::firstOrCreate([]);
        $connectionStatus = ($result['ok'] ?? false) ? (data_get($result, 'data.wallet_available', true) ? 'connected' : 'connected_wallet_missing') : ($result['code'] ?? 'failed');
        $settings->update(['portal_sms_last_connection_status' => $connectionStatus, 'portal_sms_last_synced_at' => ($result['ok'] ?? false) ? now() : $settings->portal_sms_last_synced_at]);
        $audit->record('sms.portal.connection_tested', $settings, ['ok' => (bool) ($result['ok'] ?? false), 'code' => $result['code'] ?? null]);
        if (($result['ok'] ?? false) && data_get($result, 'data.wallet_available', false)) $audit->record('sms.wallet.synced', $settings, ['currency' => data_get($result, 'data.currency'), 'routes' => collect((array) data_get($result, 'data.active_route_prices', []))->pluck('route')->values()->all()]);
        return back()->with(['message' => $result['message'], 'alert-type' => ($result['ok'] ?? false) ? 'success' : 'error']);
    }
}
