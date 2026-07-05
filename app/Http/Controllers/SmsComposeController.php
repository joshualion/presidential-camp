<?php

namespace App\Http\Controllers;

use App\Models\SmsBatch;
use App\Models\SmsSenderId;
use App\Models\SystemSetting;
use App\Services\Sms\PortalSmsClient;
use App\Services\Sms\SmsAccessService;
use App\Services\Sms\SmsAuditService;
use App\Services\Sms\SmsBatchService;
use App\Services\Sms\SmsRecipientService;
use App\Services\Sms\SmsReportAccessService;
use App\Services\Sms\SmsWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SmsComposeController extends Controller
{
    public function dashboard(Request $request, SmsWalletService $wallets, SmsAccessService $access, SmsReportAccessService $reports)
    {
        $user = $request->user();
        $canViewWallet = $access->allows($user, 'sms.wallet.view');
        $canViewReports = $access->allows($user, 'sms.reports');
        $wallet = $canViewWallet ? $wallets->userWallet($user) : null;
        $organization = $access->allows($user, 'sms.organization_wallet.view') ? $wallets->organizationWallet($user->id) : null;
        return view('backend.sms.dashboard', [
            'profileData' => $user,
            'wallet' => $wallet,
            'organizationWallet' => $organization,
            'approvedSenderCount' => SmsSenderId::where('status', 'approved')->count(),
            'recentBatches' => $canViewReports ? $reports->query($user)->with('createdBy')->latest()->limit(8)->get() : collect(),
            'canCompose' => $access->allows($user, 'sms.compose'),
            'canViewWallet' => $canViewWallet,
            'canViewReports' => $canViewReports,
            'pageTitle' => 'SMS Dashboard',
        ]);
    }

    public function create(Request $request, SmsRecipientService $recipients, SmsWalletService $wallets, SmsAccessService $access)
    {
        return view('backend.sms.compose', $this->viewData($request, $recipients, $wallets, $access));
    }

    public function locations(Request $request, string $type, SmsRecipientService $recipients): JsonResponse
    {
        abort_unless(in_array($type, ['states', 'lgas', 'wards', 'polling_units'], true), 404);
        $filters = $request->validate([
            'state_id' => ['nullable', 'integer'], 'lga_id' => ['nullable', 'integer'], 'ward_id' => ['nullable', 'integer'],
        ]);
        $items = $recipients->locationOptions($request->user(), $type, $filters)
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])->values();
        return response()->json(['data' => $items]);
    }

    public function estimate(
        Request $request,
        SmsRecipientService $recipients,
        SmsWalletService $wallets,
        SmsAccessService $access,
        PortalSmsClient $portal,
        SmsAuditService $audit,
    ) {
        $data = $this->validated($request);
        $sender = SmsSenderId::where('sender_id', $data['sender_id'])->where('status', 'approved')->first();
        if (!$sender) throw ValidationException::withMessages(['sender_id' => 'Select an approved sender ID.']);

        $audience = $recipients->resolve($request->user(), $data);
        if ($audience['valid'] === 0) throw ValidationException::withMessages(['recipient_source' => 'No valid recipients were found in the selected audience.']);
        $wallet = $this->wallet($request, $data['wallet_type'], $wallets, $access);
        abort_unless($wallet->status === 'active', 422, 'The selected SMS wallet is not active.');

        $estimate = $portal->estimate([
            'route' => $data['route'], 'sender_id' => $sender->sender_id,
            'message' => $data['message'], 'recipients_count' => $audience['valid'],
        ]);
        if (!($estimate['ok'] ?? false)) {
            return back()->withInput()->with(['message' => $estimate['message'], 'alert-type' => 'error']);
        }

        $estimatedTotal = (string) data_get($estimate, 'data.estimated_total', '0');
        $estimateCurrency = strtoupper((string) data_get($estimate, 'data.currency', $wallet->currency));
        if ($estimateCurrency !== strtoupper($wallet->currency)) {
            return back()->withInput()->with(['message' => 'The portal estimate currency does not match the selected local wallet.', 'alert-type' => 'error']);
        }
        try {
            if ($wallets->compare($estimatedTotal, '0') <= 0) throw new \InvalidArgumentException;
            $localAffordable = $wallets->compare($wallet->available_balance, $estimatedTotal) >= 0;
        } catch (\InvalidArgumentException) {
            return back()->withInput()->with(['message' => 'The portal returned an invalid SMS estimate.', 'alert-type' => 'error']);
        }
        $portalAffordable = (bool) data_get($estimate, 'data.can_afford', true);
        $sendingEnabled = (bool) SystemSetting::query()->first()?->portal_sms_sending_enabled;
        $token = (string) Str::uuid();
        $confirmation = [
            'token' => $token, 'user_id' => $request->user()->id, 'created_at' => now()->timestamp,
            'selection' => $data, 'audience' => collect($audience)->except(['recipients', 'invalid_rows'])->all(),
            'estimate' => $estimate['data'], 'wallet_id' => $wallet->id,
            'can_send' => $localAffordable && $portalAffordable && $sendingEnabled,
            'local_affordable' => $localAffordable, 'portal_affordable' => $portalAffordable,
            'sending_enabled' => $sendingEnabled,
        ];
        $request->session()->put('sms.compose.confirmations.'.$token, $confirmation);
        $audit->record('sms.compose.estimate_requested', null, [
            'recipient_source' => $data['recipient_source'], 'valid_recipients' => $audience['valid'],
            'invalid_recipients' => $audience['invalid'], 'duplicates' => $audience['duplicates'],
            'wallet_reference' => $wallet->wallet_reference, 'estimated_total' => $estimatedTotal,
        ]);

        return view('backend.sms.compose', $this->viewData($request, $recipients, $wallets, $access) + [
            'form' => $data, 'audience' => $audience, 'estimate' => $estimate['data'],
            'confirmation' => $confirmation, 'selectedWallet' => $wallet,
        ]);
    }

    public function send(
        Request $request,
        SmsRecipientService $recipients,
        SmsWalletService $wallets,
        SmsAccessService $access,
        SmsBatchService $batches,
        SmsAuditService $audit,
    ) {
        $request->validate(['confirmation_token' => ['required', 'uuid']]);
        $key = 'sms.compose.confirmations.'.$request->input('confirmation_token');
        $confirmation = $request->session()->pull($key);
        if (!$confirmation || (int) ($confirmation['user_id'] ?? 0) !== $request->user()->id || (int) ($confirmation['created_at'] ?? 0) < now()->subMinutes(30)->timestamp) {
            throw ValidationException::withMessages(['confirmation_token' => 'This SMS confirmation has expired. Estimate the batch again.']);
        }

        $data = (array) $confirmation['selection'];
        if (filled($data['scheduled_at'] ?? null) && Carbon::parse($data['scheduled_at'])->isPast()) {
            throw ValidationException::withMessages(['scheduled_at' => 'The scheduled send time has passed. Estimate the batch again.']);
        }
        $senderApproved = SmsSenderId::where('sender_id', $data['sender_id'])->where('status', 'approved')->exists();
        if (!$senderApproved) throw ValidationException::withMessages(['sender_id' => 'The selected sender ID is no longer approved.']);
        $audience = $recipients->resolve($request->user(), $data);
        if ($audience['valid'] === 0) throw ValidationException::withMessages(['recipient_source' => 'No valid recipients remain in the selected audience.']);
        $wallet = $this->wallet($request, $data['wallet_type'], $wallets, $access);
        if ($wallet->id !== (int) $confirmation['wallet_id']) throw ValidationException::withMessages(['wallet_type' => 'The confirmed wallet is no longer available.']);

        $audit->record('sms.batch.confirmed', null, [
            'recipient_source' => $data['recipient_source'], 'valid_recipients' => $audience['valid'],
            'wallet_reference' => $wallet->wallet_reference, 'estimated_total' => data_get($confirmation, 'estimate.estimated_total'),
        ]);
        $result = $batches->create([
            'route' => $data['route'], 'sender_id' => $data['sender_id'], 'message' => $data['message'],
            'recipients' => $audience['recipients'], 'source_context' => 'compose:'.$data['recipient_source'],
            'recipient_stats' => collect($audience)->only(['matched', 'valid', 'invalid', 'duplicates'])->all(),
            'target_scope_metadata' => $audience['scope_summary'],
            'metadata' => ['recipient_source' => $data['recipient_source'], 'filters' => $data['filters'] ?? [], 'preview_stats' => collect($audience)->only(['matched', 'valid', 'invalid', 'duplicates'])->all()],
            'scheduled_at' => filled($data['scheduled_at'] ?? null) ? Carbon::parse($data['scheduled_at'])->utc()->toIso8601String() : null,
        ], $wallet, $request->user());

        if (!($result['ok'] ?? false)) {
            return redirect()->route($request->user()->access_level.'.sms.compose')->with(['message' => $result['message'], 'alert-type' => 'error']);
        }
        /** @var SmsBatch $batch */
        $batch = $result['data'];
        return redirect()->route($request->user()->access_level.'.sms.batches.show', $batch)->with(['message' => $result['message'], 'alert-type' => 'success']);
    }

    private function validated(Request $request): array
    {
        $max = (int) config('portal_sms.compose.message_max_characters', 10000);
        return $request->validate([
            'sender_id' => ['required', 'string', 'max:20'],
            'route' => ['required', Rule::in(['regular', 'priority'])],
            'message' => ['required', 'string', 'max:'.$max, function ($attribute, $value, $fail) { if (trim((string) $value) === '') $fail('The message cannot be empty.'); }],
            'recipient_source' => ['required', Rule::in(['members', 'agents', 'admins', 'manual'])],
            'manual_recipients' => ['nullable', 'required_if:recipient_source,manual', 'string'],
            'wallet_type' => ['required', Rule::in(['user_wallet', 'organization_wallet'])],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'filters.state_id' => ['nullable', 'integer'], 'filters.lga_id' => ['nullable', 'integer'],
            'filters.ward_id' => ['nullable', 'integer'], 'filters.polling_unit_id' => ['nullable', 'integer'],
            'filters.access_level' => ['nullable', 'string', 'max:40'],
            'filters.member_status' => ['nullable', Rule::in(['active', 'inactive', 'all'])],
            'filters.agent_status' => ['nullable', Rule::in(['approved', 'pending', 'rejected', 'suspended', 'revoked'])],
            'filters.phone_availability' => ['nullable', Rule::in(['available', 'missing', 'all'])],
            'filters.onboarding_status' => ['nullable', Rule::in(['complete', 'incomplete', 'all'])],
        ]);
    }

    private function wallet(Request $request, string $type, SmsWalletService $wallets, SmsAccessService $access)
    {
        if ($type === 'organization_wallet') {
            abort_unless($access->allows($request->user(), 'sms.organization_wallet.use'), 403, 'You cannot charge the organization SMS wallet.');
            return $wallets->organizationWallet($request->user()->id);
        }
        return $wallets->userWallet($request->user());
    }

    private function viewData(Request $request, SmsRecipientService $recipients, SmsWalletService $wallets, SmsAccessService $access): array
    {
        $user = $request->user();
        return [
            'profileData' => $user, 'pageTitle' => 'Compose SMS',
            'senderIds' => SmsSenderId::where('status', 'approved')->orderBy('sender_id')->get(),
            'sourceLabels' => $recipients->sourceLabels(), 'accessLevels' => $recipients->allowedAccessLevels($user),
            'userWallet' => $wallets->userWallet($user),
            'organizationWallet' => $access->allows($user, 'sms.organization_wallet.use') ? $wallets->organizationWallet($user->id) : null,
            'settings' => SystemSetting::first(), 'largeBatchWarning' => (int) config('portal_sms.compose.large_batch_warning', 5000),
        ];
    }
}
