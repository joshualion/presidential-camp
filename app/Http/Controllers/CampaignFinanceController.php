<?php

namespace App\Http\Controllers;

use App\Services\Enterprise\CampaignFinanceJournal;
use App\Services\Enterprise\CampaignFinancePolicy;
use App\Services\Enterprise\CampaignFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignFinanceController extends Controller
{
    public function index(Request $request, CampaignFinanceService $finance)
    {
        return $this->section($request, 'dashboard', $finance);
    }

    public function section(Request $request, string $section, CampaignFinanceService $finance)
    {
        $actor = $request->user();
        $finance->authorize($actor, 'finance.view');
        $sections = app(CampaignFinancePolicy::class)->visible($actor);
        abort_unless(isset($sections[$section]) && $section !== 'settings', 404);
        $filters = $request->validate([
            'account' => 'nullable|integer|min:1',
            'custodian_search' => 'nullable|string|max:100',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d'.($request->filled('from') ? '|after_or_equal:from' : ''),
            'status' => 'nullable|in:pending,approved,rejected,cancelled,recorded',
        ]);
        $search = $filters['custodian_search'] ?? '';
        $custodians = collect();
        if ($section === 'accounts' && app(\App\Services\CampaignCapabilityService::class)->allows($request->user(), 'finance.accounts.manage')) {
            $query = app(\App\Services\LocationScopeService::class)->applyScope(\App\Models\User::query()->where('status', 'active'), $request->user(), 'users');
            if ($search !== '') {
                $query->where(function ($query) use ($search) {
                    $query->where('firstname', 'like', '%'.$search.'%')->orWhere('lastname', 'like', '%'.$search.'%')->orWhere('username', 'like', '%'.$search.'%');
                });
            }
            $custodians = $query->orderBy('firstname')->limit(100)->get(['id', 'firstname', 'lastname', 'username']);
        }
        $accounts = $finance->accounts($actor)->orderBy('name')->get();
        $account = isset($filters['account']) ? $finance->account($actor, (int) $filters['account']) : $accounts->first();
        $id = $account?->id ?? 0;
        $selectedAccounts = $finance->accounts($actor)->select('campaign_finance_accounts.id');
        if (isset($filters['account'])) {
            $selectedAccounts->whereKey($id);
        }
        $ledger = DB::table('campaign_finance_entries')->whereIn('account_id', clone $selectedAccounts);
        $requests = DB::table('campaign_finance_requests')->where('account_id', $id);
        if (isset($filters['from'])) {
            $ledger->where('created_at', '>=', $filters['from'].' 00:00:00');
        }
        if (isset($filters['to'])) {
            $ledger->where('created_at', '<=', $filters['to'].' 23:59:59');
        }
        if (isset($filters['status'])) {
            $requests->where('status', $filters['status']);
        }
        if ($section === 'approvals') {
            $requests->where('status', 'pending');
        }
        if ($section === 'disbursements') {
            $requests->whereIn('status', ['approved', 'recorded']);
        }
        $totals = (clone $ledger)->selectRaw('kind, SUM(amount_minor) AS total')->groupBy('kind')->pluck('total', 'kind');
        $entries = (clone $ledger)->where('account_id', $id);
        if ($section === 'income' || $section === 'expenditure') {
            $entries->where('kind', $section);
        }
        $capabilities = app(\App\Services\CampaignCapabilityService::class);
        $reserved = $finance->reservations()->whereIn('account_id', clone $selectedAccounts)->sum('amount_minor');

        return response()->view('backend.finance.index', [
            'profileData' => $actor,
            'pageTitle' => $section === 'dashboard' ? 'Campaign Finance' : $sections[$section],
            'section' => $section, 'sections' => $sections, 'filters' => $filters, 'capabilities' => $capabilities,
            'custodians' => $custodians, 'custodianSearch' => $search, 'accounts' => $accounts, 'account' => $account,
            'documents' => $section === 'reports' && $capabilities->allows($actor, 'finance.documents.view')
                ? DB::table('campaign_finance_documents')->where('account_id', $id)->latest('id')->paginate(25, ['*'], 'documents_page')->withQueryString()
                : null,
            'budgets' => in_array($section, ['budgets', 'requests'], true) ? DB::table('campaign_finance_budgets')->where('account_id', $id)->orderBy('id')->get() : [],
            'requests' => in_array($section, ['requests', 'approvals', 'disbursements'], true)
                ? $requests->leftJoin('campaign_finance_workflows', 'campaign_finance_workflows.request_id', '=', 'campaign_finance_requests.id')
                    ->select('campaign_finance_requests.*', 'campaign_finance_workflows.next_stage', 'campaign_finance_workflows.stages')
                    ->latest('campaign_finance_requests.id')->paginate(25, ['*'], 'requests_page')->withQueryString() : null,
            'entries' => in_array($section, ['income', 'expenditure', 'reports'], true)
                ? $entries->leftJoin('campaign_finance_journal', 'campaign_finance_journal.entry_id', '=', 'campaign_finance_entries.id')
                    ->select('campaign_finance_entries.*', 'campaign_finance_journal.debit_code', 'campaign_finance_journal.credit_code')
                    ->latest('campaign_finance_entries.id')->paginate(25, ['*'], 'entries_page')->withQueryString() : null,
            'audits' => $section === 'reports' && $capabilities->allows($actor, 'finance.reconcile')
                ? DB::table('campaign_finance_audits')->where('account_id', $id)->latest('id')->paginate(25, ['*'], 'audits_page')->withQueryString() : null,
            'ledgerCheck' => $section === 'reports' ? DB::table('campaign_finance_ledger_checks')->where('account_id', $id)->first() : null,
            'chartOfAccounts' => CampaignFinanceJournal::ACCOUNTS,
            'treasury' => (clone $selectedAccounts)->sum('campaign_finance_accounts.balance_minor'),
            'reserved' => $reserved,
            'pending' => DB::table('campaign_finance_requests')->whereIn('account_id', $selectedAccounts)->where('status', 'pending')->count(),
            'totals' => $totals,
        ])->header('Cache-Control', 'no-store');
    }

    public function reconcile(Request $request, int $id, CampaignFinanceService $finance)
    {
        $finance->reconcileHistory($request->user(), $id);
        return back()->with('finance_status', 'The recorded transaction history reconciles to the account balance.');
    }

    public function cancel(Request $request, int $id, CampaignFinanceService $finance)
    {
        $finance->cancel($request->user(), $id);
        return back()->with('finance_status', 'Funding request cancelled.');
    }

    public function account(Request $request, CampaignFinanceService $finance)
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'owner_id' => 'required|integer|min:1']);
        $account = $finance->createAccount($request->user(), $data['name'], (int) $data['owner_id']);
        return redirect()->route('finance.section', ['section' => 'accounts', 'account' => $account->id])->with('finance_status', 'Financial account created.');
    }

    public function budget(Request $request, CampaignFinanceService $finance)
    {
        $data = $request->validate(['account_id' => 'required|integer|min:1', 'name' => 'required|string|max:120', 'amount' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/D']]);
        $finance->createBudget($request->user(), (int) $data['account_id'], $data['name'], $this->minor($data['amount']));
        return back()->with('finance_status', 'Budget allocation recorded.');
    }

    public function paymentRequest(Request $request, CampaignFinanceService $finance)
    {
        $data = $request->validate(['account_id' => 'required|integer|min:1', 'budget_id' => 'nullable|integer|min:1',
            'amount' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/D'], 'description' => 'required|string|max:500']);
        $finance->request($request->user(), (int) $data['account_id'], isset($data['budget_id']) ? (int) $data['budget_id'] : null, $this->minor($data['amount']), $data['description']);
        return back()->with('finance_status', 'Payment request submitted for independent approval.');
    }

    public function decide(Request $request, int $id, CampaignFinanceService $finance)
    {
        $data = $request->validate(['decision' => 'required|in:approve,reject']);
        $finance->decide($request->user(), $id, $data['decision'] === 'approve');
        return back()->with('finance_status', 'Payment request decision recorded.');
    }

    public function record(Request $request, CampaignFinanceService $finance)
    {
        $data = $request->validate(['account_id' => 'required|integer|min:1', 'kind' => 'required|in:income,expenditure',
            'request_id' => 'nullable|integer|min:1', 'amount' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/D'],
            'description' => 'required|string|max:500', 'idempotency_key' => 'required|uuid']);
        $finance->record($request->user(), (int) $data['account_id'], $data['kind'], $this->minor($data['amount']), $data['description'],
            $data['idempotency_key'], isset($data['request_id']) ? (int) $data['request_id'] : null);
        return back()->with('finance_status', 'Transaction recorded. No bank transfer was initiated.');
    }

    public function statement(Request $request, int $id, CampaignFinanceService $finance)
    {
        $finance->authorize($request->user(), 'finance.reports.export');
        $account = $finance->account($request->user(), $id);
        return response()->streamDownload(function () use ($account) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Transaction', 'Date', 'Kind', 'Currency', 'Amount', 'Balance', 'Description', 'Payment request', 'Debit code', 'Credit code'], ',', '"', '');
            foreach (DB::table('campaign_finance_entries')
                ->leftJoin('campaign_finance_journal', 'campaign_finance_journal.entry_id', '=', 'campaign_finance_entries.id')
                ->where('account_id', $account->id)
                ->select('campaign_finance_entries.*', 'campaign_finance_journal.debit_code', 'campaign_finance_journal.credit_code')
                ->orderBy('campaign_finance_entries.id')->cursor() as $entry) {
                $description = preg_match('/^[\s]*[=+\-@\t\r]/u', $entry->description) ? "'".$entry->description : $entry->description;
                fputcsv($out, [$entry->id, $entry->created_at, $entry->kind, $account->currency,
                    intdiv((int) $entry->amount_minor, 100).'.'.str_pad((string) ($entry->amount_minor % 100), 2, '0', STR_PAD_LEFT),
                    intdiv((int) $entry->balance_after_minor, 100).'.'.str_pad((string) ($entry->balance_after_minor % 100), 2, '0', STR_PAD_LEFT),
                    $description, $entry->request_id, $entry->debit_code, $entry->credit_code], ',', '"', '');
            }
            fclose($out);
        }, 'campaign-finance-'.$account->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function document(Request $request, CampaignFinanceService $finance)
    {
        $data = $request->validate(['account_id' => 'required|integer|min:1', 'request_id' => 'nullable|integer|min:1', 'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $finance->attachDocument($request->user(), (int) $data['account_id'], isset($data['request_id']) ? (int) $data['request_id'] : null, $request->file('document'));
        return back()->with('finance_status', 'Supporting document attached.');
    }

    public function downloadDocument(Request $request, int $id, CampaignFinanceService $finance)
    {
        $finance->authorize($request->user(), 'finance.documents.view');
        $document = DB::table('campaign_finance_documents')->where('id', $id)->first();
        abort_unless($document, 404);
        $finance->account($request->user(), (int) $document->account_id);
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($disk->exists($document->path) && hash_equals($document->sha256, hash_file('sha256', $disk->path($document->path))), 404);
        return $disk->download($document->path, $document->filename, ['Content-Type' => 'application/octet-stream', 'Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function minor(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }
}

