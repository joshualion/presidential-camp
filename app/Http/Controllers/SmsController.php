<?php

namespace App\Http\Controllers;

use App\Models\SmsAllocation;
use App\Models\SmsCreditRequest;
use App\Models\SmsSenderId;
use App\Models\SmsTopup;
use App\Models\SmsWallet;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Sms\PortalSmsClient;
use App\Services\Sms\SmsAccessService;
use App\Services\Sms\SmsAllocationSyncService;
use App\Services\Sms\SmsAuditService;
use App\Services\Sms\SmsWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class SmsController extends Controller
{
    public function wallet(SmsWalletService $wallets, SmsAccessService $access)
    {
        $user = auth()->user(); $wallet = $wallets->userWallet($user); $organization = $access->allows($user, 'sms.organization_wallet.use') ? $wallets->organizationWallet($user->id) : null;
        return view('backend.sms.wallet', ['profileData' => $user, 'wallet' => $wallet, 'organizationWallet' => $organization, 'transactions' => $wallet->transactions()->latest()->paginate(30), 'topups' => SmsTopup::where('created_by', $user->id)->latest()->limit(20)->get(), 'allocations' => SmsAllocation::where('sms_wallet_id', $wallet->id)->latest()->limit(20)->get(), 'creditRequests' => SmsCreditRequest::where('created_by', $user->id)->latest()->limit(20)->get(), 'transferUsers' => collect(), 'pageTitle' => 'My SMS Wallet']);
    }

    public function organizationWallet(SmsWalletService $wallets)
    {
        $wallet = $wallets->organizationWallet(auth()->id());
        return view('backend.sms.wallet', ['profileData' => auth()->user(), 'wallet' => $wallet, 'organizationWallet' => null, 'transactions' => $wallet->transactions()->latest()->paginate(50), 'topups' => SmsTopup::where('sms_wallet_id', $wallet->id)->latest()->limit(20)->get(), 'allocations' => SmsAllocation::where('sms_wallet_id', $wallet->id)->latest()->limit(20)->get(), 'creditRequests' => SmsCreditRequest::where('sms_wallet_id', $wallet->id)->latest()->limit(20)->get(), 'transferUsers' => User::where('id', '!=', auth()->id())->where('access_level', '!=', 'user')->orderBy('firstname')->limit(250)->get(), 'pageTitle' => 'Organization SMS Wallet']);
    }

    public function initiateTopup(Request $request, SmsWalletService $wallets, PortalSmsClient $portal, SmsAccessService $access, SmsAuditService $audit)
    {
        abort_unless(SystemSetting::first()?->portal_sms_topup_enabled, 403, 'SMS top-ups are disabled.');
        $data = $request->validate(['funding_type' => ['required', 'in:user_wallet,organization_wallet'], 'amount' => ['required', 'numeric', 'min:100'], 'payment_method' => ['nullable', 'in:paystack,manual_bank']]);
        $user = $request->user();
        if ($data['funding_type'] === 'organization_wallet' && !$access->allows($user, 'sms.organization_wallet.use')) abort(403);
        $wallet = $data['funding_type'] === 'organization_wallet' ? $wallets->organizationWallet($user->id) : $wallets->userWallet($user);
        $idempotency = 'core-topup-'.Str::uuid();
        $context = $this->userContext($user, $wallet);
        $payload = [
            'allocation_type' => $data['funding_type'], 'amount' => (string) $data['amount'], 'currency' => $wallet->currency,
            'core_user_reference' => $context['core_user_reference'], 'core_wallet_reference' => $context['core_wallet_reference'],
            'payer_name' => $context['display_name'], 'payer_email' => $context['email'], 'payer_phone' => $context['phone'],
            'return_url' => route($user->access_level.'.sms.wallet'),
            'metadata' => ['access_level' => $context['access_level'], 'scope' => $context['scope_metadata'], 'requested_payment_method' => $data['payment_method'] ?? 'paystack'],
        ];
        $topup = SmsTopup::create(['sms_wallet_id' => $wallet->id, 'funding_type' => $data['funding_type'], 'amount' => $data['amount'], 'currency' => $wallet->currency, 'status' => 'initiating', 'idempotency_key' => $idempotency, 'created_by' => $user->id]);
        $result = $portal->initiateTopup($payload, $idempotency);
        if (!($result['ok'] ?? false)) {
            $topup->update(['status' => ($result['status'] ?? 0) >= 400 && ($result['status'] ?? 0) < 500 ? 'failed' : 'reconciliation_required']);
            return back()->withInput()->with(['message' => $result['message'], 'alert-type' => 'error']);
        }
        $topup->update(['portal_reference' => data_get($result, 'data.payment_session_reference'), 'payment_reference' => data_get($result, 'data.payment_reference'), 'status' => data_get($result, 'data.status', 'pending'), 'payment_url' => $this->safePaymentUrl(data_get($result, 'data.payment_url')), 'payment_instructions' => data_get($result, 'data.payment_instructions')]);
        $audit->record('sms.topup.initiated', $topup, ['wallet_reference' => $wallet->wallet_reference, 'amount' => $data['amount']]);
        return back()->with(['message' => 'Top-up initiated. Your local wallet will be credited only after portal confirmation is synchronized.', 'alert-type' => 'success', 'payment_url' => $topup->payment_url]);
    }

    public function createCreditRequest(Request $request, SmsWalletService $wallets, PortalSmsClient $portal, SmsAccessService $access, SmsAuditService $audit)
    {
        abort_unless(SystemSetting::first()?->portal_sms_credit_request_enabled, 403, 'SMS credit requests are disabled.');
        $data = $request->validate(['wallet_type' => ['required', 'in:user_wallet,organization_wallet'], 'amount' => ['required', 'numeric', 'min:1'], 'reason' => ['required', 'string', 'max:2000'], 'urgency' => ['required', 'in:low,normal,high,urgent']]);
        $user = $request->user(); if ($data['wallet_type'] === 'organization_wallet' && !$access->allows($user, 'sms.organization_wallet.use')) abort(403);
        $wallet = $data['wallet_type'] === 'organization_wallet' ? $wallets->organizationWallet($user->id) : $wallets->userWallet($user);
        $context = $this->userContext($user, $wallet); $localReference = (string) Str::uuid(); $idempotency = 'core-credit-'.$localReference;
        $record = SmsCreditRequest::create(['local_reference' => $localReference, 'sms_wallet_id' => $wallet->id, 'amount' => $data['amount'], 'currency' => $wallet->currency, 'reason' => $data['reason'], 'urgency' => $data['urgency'], 'status' => 'submitting', 'idempotency_key' => $idempotency, 'created_by' => $user->id]);
        $result = $portal->createCreditRequest([
            'allocation_type' => $data['wallet_type'], 'core_user_reference' => $context['core_user_reference'],
            'core_wallet_reference' => $context['core_wallet_reference'], 'requested_amount' => (string) $data['amount'],
            'currency' => $wallet->currency, 'reason' => $data['reason'], 'urgency' => $data['urgency'],
            'metadata' => ['display_name' => $context['display_name'], 'email' => $context['email'], 'phone' => $context['phone'], 'access_level' => $context['access_level'], 'scope' => $context['scope_metadata']],
        ], $idempotency);
        if (!($result['ok'] ?? false)) {
            $record->update(['status' => ($result['status'] ?? 0) >= 400 && ($result['status'] ?? 0) < 500 ? 'failed' : 'reconciliation_required']);
            return back()->withInput()->with(['message' => $result['message'], 'alert-type' => 'error']);
        }
        $record->update(['portal_reference' => data_get($result, 'data.request_reference'), 'amount' => data_get($result, 'data.requested_amount', $data['amount']), 'status' => data_get($result, 'data.status', 'pending')]);
        $audit->record('sms.credit_request.created', $record, ['wallet_reference' => $wallet->wallet_reference]);
        return back()->with(['message' => 'SMS credit request submitted. Fulfilment credits the wallet through allocation sync.', 'alert-type' => 'success']);
    }

    public function transfer(Request $request, SmsWalletService $wallets)
    {
        $data = $request->validate(['direction' => ['required', 'in:organization_to_user,user_to_organization'], 'user_id' => ['required', 'exists:users,id'], 'amount' => ['required', 'numeric', 'min:0.0001'], 'reason' => ['required', 'string', 'max:1000']]);
        $userWallet = $wallets->userWallet(User::findOrFail($data['user_id']), auth()->id()); $organization = $wallets->organizationWallet(auth()->id());
        try { $wallets->transfer($data['direction'] === 'organization_to_user' ? $organization : $userWallet, $data['direction'] === 'organization_to_user' ? $userWallet : $organization, (string) $data['amount'], auth()->user(), $data['reason']); }
        catch (RuntimeException $e) { return back()->withInput()->with(['message' => $e->getMessage() === 'insufficient_local_wallet_balance' ? 'The source wallet has insufficient available balance.' : 'The transfer could not be completed.', 'alert-type' => 'error']); }
        return back()->with(['message' => 'Local SMS allocation transfer completed.', 'alert-type' => 'success']);
    }

    public function syncAllocations(SmsAllocationSyncService $sync) { $result = $sync->sync(); return back()->with(['message' => $result['message'], 'alert-type' => ($result['ok'] ?? false) ? 'success' : 'error']); }

    public function senderIds(PortalSmsClient $portal, SmsAuditService $audit)
    {
        $result = $portal->listSenderIds();
        if ($result['ok'] ?? false) foreach ((array) data_get($result, 'data', []) as $item) SmsSenderId::updateOrCreate(['sender_id' => data_get($item, 'requested_sender_id')], ['portal_reference' => (string) data_get($item, 'id'), 'status' => data_get($item, 'status', 'pending'), 'rejection_reason' => data_get($item, 'rejection_reason'), 'portal_payload' => $item, 'last_synced_at' => now()]);
        if ($result['ok'] ?? false) $audit->record('sms.sender_ids.synced', null, ['count' => SmsSenderId::count()]);
        return view('backend.sms.sender-ids', ['profileData' => auth()->user(), 'senderIds' => SmsSenderId::latest()->get(), 'syncError' => ($result['ok'] ?? false) ? null : $result['message'], 'senderRequestEnabled' => (bool) SystemSetting::first()?->portal_sms_sender_id_request_enabled, 'pageTitle' => 'SMS Sender IDs']);
    }

    public function requestSenderId(Request $request, PortalSmsClient $portal, SmsAuditService $audit)
    {
        abort_unless(SystemSetting::first()?->portal_sms_sender_id_request_enabled, 403, 'Sender ID requests are disabled.');
        $data = $request->validate(['sender_id' => ['required', 'string', 'min:3', 'max:11', 'regex:/^[A-Za-z0-9 ]+$/'], 'purpose' => ['required', 'string', 'max:1000']]);
        $settings = SystemSetting::first();
        $senderPayload = [
            'requested_sender_id' => $data['sender_id'], 'organization_name' => $settings?->system_name ?: config('app.name'),
            'purpose' => $data['purpose'], 'route_preference' => $settings?->portal_sms_default_route ?: 'regular',
            'supporting_note' => 'Requested by core user '.($request->user()->uuid ?: $request->user()->id).'.',
        ];
        $idempotency = 'core-sender-'.hash('sha256', ($request->user()->uuid ?: $request->user()->id).'|'.json_encode($senderPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $sender = SmsSenderId::updateOrCreate(['sender_id' => $data['sender_id']], ['idempotency_key' => $idempotency, 'status' => 'submitting', 'purpose' => $data['purpose'], 'requested_by' => auth()->id()]);
        $result = $portal->requestSenderId($senderPayload, $idempotency);
        if (!($result['ok'] ?? false)) {
            $sender->update(['status' => ($result['status'] ?? 0) >= 400 && ($result['status'] ?? 0) < 500 ? 'failed' : 'reconciliation_required']);
            return back()->withInput()->with(['message' => $result['message'], 'alert-type' => 'error']);
        }
        $sender->update(['sender_id' => data_get($result, 'data.requested_sender_id', $data['sender_id']), 'portal_reference' => (string) data_get($result, 'data.id'), 'status' => data_get($result, 'data.status', 'submitted'), 'portal_payload' => $result['data'], 'last_synced_at' => now()]);
        $audit->record('sms.sender_id.requested', $sender); return back()->with(['message' => 'Sender ID request submitted.', 'alert-type' => 'success']);
    }

    private function userContext(User $user, SmsWallet $wallet): array
    {
        return ['core_user_reference' => $user->uuid ?: (string) $user->id, 'core_wallet_reference' => $wallet->wallet_reference, 'display_name' => trim($user->firstname.' '.$user->lastname), 'email' => $user->email, 'phone' => $user->phone, 'access_level' => $user->access_level, 'scope_metadata' => array_filter(['state_id' => $user->state_id, 'lga_id' => $user->lga_id, 'ward_id' => $user->ward_id, 'polling_unit_id' => $user->polling_unit_id])];
    }

    private function safePaymentUrl(mixed $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) return null;
        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true) ? $url : null;
    }
}
