<?php

namespace App\Http\Controllers;

use App\Services\Enterprise\CampaignFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignFinanceController extends Controller
{
    public function index(Request $request, CampaignFinanceService $finance)
    {
        $finance->authorize($request->user(), 'finance.view');
        $accounts = $finance->accounts($request->user())->orderBy('name')->get();
        $account = $request->filled('account') ? $finance->account($request->user(), (int) $request->input('account')) : $accounts->first();
        $id = $account?->id;
        return response()->view('backend.finance.index', [
            'profileData' => $request->user(), 'pageTitle' => 'Campaign Finance', 'accounts' => $accounts, 'account' => $account,
            'documents' => DB::table('campaign_finance_documents')->where('account_id', $id ?? 0)->latest('id')->paginate(25, ['*'], 'documents_page')->withQueryString(),
            'budgets' => DB::table('campaign_finance_budgets')->where('account_id', $id ?? 0)->orderBy('id')->get(),
            'requests' => DB::table('campaign_finance_requests')->where('account_id', $id ?? 0)->latest('id')->paginate(25, ['*'], 'requests_page')->withQueryString(),
            'entries' => DB::table('campaign_finance_entries')->where('account_id', $id ?? 0)->latest('id')->paginate(25, ['*'], 'entries_page')->withQueryString(),
            'audits' => DB::table('campaign_finance_audits')->where('account_id', $id ?? 0)->latest('id')->paginate(25, ['*'], 'audits_page')->withQueryString(),
            'monthlyTotals' => DB::table('campaign_finance_entries')->where('account_id', $id ?? 0)->where('created_at', '>=', now()->startOfMonth())->selectRaw('kind, SUM(amount_minor) AS total')->groupBy('kind')->pluck('total', 'kind'),
            'totals' => DB::table('campaign_finance_entries')->where('account_id', $id ?? 0)->selectRaw('kind, SUM(amount_minor) AS total')->groupBy('kind')->pluck('total', 'kind'),
        ])->header('Cache-Control', 'no-store');
    }

    public function account(Request $request, CampaignFinanceService $finance)
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'owner_id' => 'required|integer|min:1']);
        $account = $finance->createAccount($request->user(), $data['name'], (int) $data['owner_id']);
        return redirect()->route('finance.index', ['account' => $account->id])->with('finance_status', 'Financial account created.');
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
            fputcsv($out, ['Transaction', 'Date', 'Kind', 'Currency', 'Amount', 'Balance', 'Description', 'Payment request'], ',', '"', '');
            foreach (DB::table('campaign_finance_entries')->where('account_id', $account->id)->orderBy('id')->cursor() as $entry) {
                $description = preg_match('/^[\s]*[=+\-@\t\r]/u', $entry->description) ? "'".$entry->description : $entry->description;
                fputcsv($out, [$entry->id, $entry->created_at, $entry->kind, $account->currency,
                    intdiv((int) $entry->amount_minor, 100).'.'.str_pad((string) ($entry->amount_minor % 100), 2, '0', STR_PAD_LEFT),
                    intdiv((int) $entry->balance_after_minor, 100).'.'.str_pad((string) ($entry->balance_after_minor % 100), 2, '0', STR_PAD_LEFT),
                    $description, $entry->request_id], ',', '"', '');
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
        $finance->authorize($request->user(), 'finance.view');
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
