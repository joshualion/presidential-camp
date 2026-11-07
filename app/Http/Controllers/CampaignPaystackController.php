<?php

namespace App\Http\Controllers;

use App\Services\Enterprise\CampaignFinanceService;
use App\Services\Enterprise\CampaignPaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CampaignPaystackController extends Controller
{
    public function settings(Request $request, CampaignPaystackService $paystack, CampaignFinanceService $finance)
    {
        $paystack->manage($request->user());
        return response()->view('backend.finance.paystack-settings', [
            'profileData' => $request->user(),
            'pageTitle' => 'Campaign Payment Settings',
            'settings' => $paystack->settings(),
            'beneficiaries' => \Illuminate\Support\Facades\DB::table('campaign_finance_request_recipients as beneficiaries')
                ->join('campaign_finance_requests as requests', 'requests.id', '=', 'beneficiaries.request_id')
                ->where('requests.status', 'approved')
                ->whereNotIn('beneficiaries.id', \Illuminate\Support\Facades\DB::table('campaign_finance_profile_beneficiaries')->select('beneficiary_id'))
                ->whereIn('requests.account_id', $finance->accounts($request->user())->select('campaign_finance_accounts.id'))
                ->latest('beneficiaries.id')->limit(100)
                ->get(['beneficiaries.id', 'beneficiaries.request_id', 'beneficiaries.recipient_name']),
            'accounts' => $finance->accounts($request->user())->orderBy('name')->get(['id', 'name']),
        ])->header('Cache-Control', 'no-store');
    }

    public function save(Request $request, CampaignPaystackService $paystack)
    {
        $paystack->manage($request->user());
        try {
            // Never flash the submitted credential to the session.
            $fields = array_fill_keys(['test_public', 'test_secret', 'live_public', 'live_secret', 'test_webhook', 'live_webhook'], 'nullable|string|max:120');
            $rules = $fields + [
                'mode' => 'required|in:test,live', 'collections' => 'sometimes|boolean',
                'transfers' => 'sometimes|boolean', 'confirm_live' => 'accepted_if:mode,live',
            ];
            $data = Validator::make($request->only(array_keys($rules)), $rules)->validate();
            $paystack->saveSettings($request->user(), array_intersect_key($data, $fields),
                $data['mode'], (bool) ($data['collections'] ?? false), (bool) ($data['transfers'] ?? false));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
        return back()->with('finance_status', 'Campaign payment settings saved.');
    }

    public function recipient(Request $request, CampaignPaystackService $paystack)
    {
        $data = $request->validate(['account_id' => 'required|integer|min:1', 'recipient' => 'required|string|max:80']);
        $paystack->saveRecipient($request->user(), (int) $data['account_id'], $data['recipient']);
        return back()->with('finance_status', 'Test recipient verified and linked to the selected ledger account.');
    }

    public function index(Request $request, CampaignPaystackService $paystack, CampaignFinanceService $finance)
    {
        $actor = $request->user();
        $operations = $paystack->operations($actor)
            ->leftJoin('campaign_paystack_beneficiary_links as links', 'links.operation_id', '=', 'campaign_paystack_operations.id')
            ->leftJoin('campaign_finance_request_recipients as beneficiary', 'beneficiary.id', '=', 'links.beneficiary_id')
            ->select(['account_id', 'destination_id', 'reference', 'kind', 'campaign_paystack_operations.amount_minor',
                'campaign_paystack_operations.status', 'campaign_paystack_operations.created_at', 'submitted_by'])
            ->selectRaw('COALESCE(campaign_paystack_operations.request_id, beneficiary.request_id) as request_id')
            ->selectSub(\Illuminate\Support\Facades\DB::table('campaign_paystack_refund_checks')
                ->whereColumn('operation_id', 'campaign_paystack_operations.id')->where('status', 'processed')
                ->selectRaw('COALESCE(SUM(amount_minor), 0)'), 'processed_refunds_minor')
            ->latest('campaign_paystack_operations.id')->paginate(25);
        $accounts = $finance->accounts($actor)->orderBy('name')->pluck('name', 'id');
        return response()->view('backend.finance.paystack-operations', [
            'profileData' => $actor, 'pageTitle' => 'Campaign Paystack test activity',
            'operations' => $operations, 'accounts' => $accounts, 'settings' => $paystack->settings(),
        ])->header('Cache-Control', 'no-store');
    }

    public function collect(Request $request, CampaignPaystackService $paystack)
    {
        $data = $request->validate([
            'account_id' => 'required|integer|min:1', 'email' => 'required|email:rfc|max:254',
            'amount' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/D'],
        ]);
        [$whole, $fraction] = array_pad(explode('.', $data['amount'], 2), 2, '');
        $minor = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        $checkout = $paystack->collect($request->user(), (int) $data['account_id'], $minor, $data['email'], route('finance.paystack.index'));
        return redirect()->away($checkout);
    }

    public function transfer(Request $request, int $id, CampaignPaystackService $paystack)
    {
        $paystack->transfer($request->user(), $id);
        return redirect()->route('finance.paystack.index')->with('finance_status', 'Test transfer submitted. Verify the reference for its current status.');
    }

    public function linkBeneficiary(Request $request, CampaignPaystackService $paystack)
    {
        $data = $request->validate(['beneficiary_id' => 'required|integer|min:1', 'recipient' => 'required|string|max:80']);
        $paystack->linkBeneficiary($request->user(), (int) $data['beneficiary_id'], $data['recipient']);
        return back()->with('finance_status', 'Test beneficiary verified and linked.');
    }

    public function linkProfileBeneficiary(Request $request, int $id, CampaignPaystackService $paystack)
    {
        $paystack->linkProfileBeneficiary($request->user(), $id);
        return back()->with('finance_status', 'Saved profile linked to the approved reimbursement for test payout.');
    }

    public function approveBatch(Request $request, int $id, CampaignPaystackService $paystack)
    {
        $paystack->approveBatch($request->user(), $id);
        return back()->with('finance_status', 'Test batch authorized for an independent officer.');
    }

    public function submitBatch(Request $request, int $id, CampaignPaystackService $paystack)
    {
        $paystack->submitBatch($request->user(), $id);
        return redirect()->route('finance.paystack.index')->with('finance_status', 'Test batch submitted. Verify each reference separately.');
    }

    public function webhook(Request $request, string $mode, CampaignPaystackService $paystack)
    {
        try {
            $paystack->receiveWebhook($mode, $request->getContent(),
                (string) $request->header('Paystack-Signature'), (string) $request->header('X-Paystack-Signature'));
        } catch (ValidationException) {
            return response()->json(['message' => 'Provider verification did not complete.'], 503);
        }
        return response()->noContent();
    }

    public function approveTransfer(Request $request, int $id, CampaignPaystackService $paystack)
    {
        $paystack->approveTransfer($request->user(), $id);
        return back()->with('finance_status', 'Test payout authorized for a separate officer to submit.');
    }

    public function matchSettlement(Request $request, int $id, CampaignPaystackService $paystack)
    {
        $data = $request->validate([
            'settlement_id' => 'required|integer|min:1', 'bank_credit_reference' => 'required|string|max:150',
        ]);
        $paystack->matchSettlement($request->user(), $id, (int) $data['settlement_id'], $data['bank_credit_reference']);
        return back()->with('finance_status', 'Verified settlement matched to statement evidence.');
    }

    public function refund(Request $request, string $reference, CampaignPaystackService $paystack)
    {
        $data = $request->validate(['refund_id' => 'required|integer|min:1']);
        $status = $paystack->reconcileRefund($request->user(), $reference, (int) $data['refund_id']);
        return back()->with('finance_status', 'Verified refund status: '.$status.'.');
    }

    public function verify(Request $request, string $reference, CampaignPaystackService $paystack)
    {
        $status = $paystack->verify($request->user(), $reference);
        return back()->with('finance_status', 'Paystack test status: '.$status.'.');
    }
}
