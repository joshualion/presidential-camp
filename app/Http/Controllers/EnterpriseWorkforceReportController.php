<?php

namespace App\Http\Controllers;

use App\Services\CampaignCapabilityService;
use App\Services\Enterprise\WorkforceReportService;
use App\Services\Enterprise\WorkforceAiService;
use App\Services\Enterprise\WorkforceAiUnavailable;
use App\Models\EnterpriseAiSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnterpriseWorkforceReportController extends Controller
{
    public function index(Request $request, WorkforceReportService $reports)
    {
        return $this->report($request, $reports);
    }

    public function analyze(Request $request, WorkforceReportService $reports, WorkforceAiService $ai)
    {
        $filters = $this->filters($request);
        $request->validate(['share_aggregates' => ['accepted']]);
        try {
            $analysis = $ai->generate($request->user(), $filters);
        } catch (WorkforceAiUnavailable $exception) {
            return $this->report($request, $reports, null, $exception->getMessage());
        }

        return $this->report($request, $reports, $analysis);
    }

    private function report(Request $request, WorkforceReportService $reports, ?string $analysis = null, ?string $aiError = null)
    {
        $filters = $this->filters($request);

        return response()->view('backend.enterprise.workforce-reports', [
            'profileData' => $request->user(), 'pageTitle' => 'Workforce reports',
            'filters' => $filters,
            'summary' => $reports->summary($request->user(), $filters),
            'rows' => $reports->distribution($request->user(), $filters)->paginate(50)->withQueryString(),
            'canExport' => app(CampaignCapabilityService::class)->allows($request->user(), 'reports.export'),
            'canAnalyze' => app(CampaignCapabilityService::class)->allows($request->user(), 'reports.ai.generate'),
            'canConfigureAi' => $request->user()->access_level === 'superadmin',
            'aiConfigured' => EnterpriseAiSetting::whereKey(1)->where('enabled', true)->exists(),
            'analysis' => $analysis, 'aiError' => $aiError,
        ])->header('Cache-Control', 'no-store');
    }

    public function export(Request $request, WorkforceReportService $reports)
    {
        $filters = $this->filters($request);
        $query = $reports->distribution($request->user(), $filters);

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Location', 'Members', 'Active members', 'Registered voters']);
            foreach ($query->cursor() as $row) {
                fputcsv($output, [
                    WorkforceReportService::csvCell($row->location_name ?? 'Unassigned'),
                    (int) $row->members, (int) $row->active_members, (int) $row->registered_voters,
                ]);
            }
            fclose($output);
        }, 'campaign-workforce-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function pdf(Request $request, WorkforceReportService $reports, \App\Services\Enterprise\ReportPdfDocument $pdf)
    {
        $filters = $this->filters($request);
        // Bound server memory on shared hosting; CSV remains available for larger exports.
        $rows = $reports->distribution($request->user(), $filters)->limit(5001)->get();
        abort_if($rows->count() > 5000, 422, 'PDF exports support up to 5,000 locations. Narrow your filters or export CSV.');
        $summary = $reports->summary($request->user(), $filters);
        $document = $pdf->render('Campaign workforce report', [
            'Members: '.number_format($summary['members']).' | Active accounts: '.number_format($summary['active']).' | Registered voters: '.number_format($summary['registered_voters']),
            'Polling-unit membership coverage: '.$summary['covered_polling_units'].' of '.$summary['polling_units'].' ('.$summary['coverage_percent'].'%)',
            'Registration period: '.($filters['from'] ?? 'All dates').' through '.($filters['to'] ?? 'present').' | Group: '.($filters['group'] ?? 'state'),
            'Authorized jurisdiction only. Coverage indicates membership presence, not verified readiness.',
        ], ['Location', 'Members', 'Active', 'Voters'], $rows->map(fn ($row) => [
            $row->location_name ?? 'Unassigned', number_format($row->members),
            number_format($row->active_members), number_format($row->registered_voters),
        ]));

        return response($document, 200, [
            'Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="campaign-workforce.pdf"',
            'Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'group' => ['sometimes', Rule::in(array_keys(WorkforceReportService::GROUPS))],
            'state_id' => ['nullable', 'integer', 'min:1'],
            'lga_id' => ['nullable', 'integer', 'min:1'],
            'ward_id' => ['nullable', 'integer', 'min:1'],
            'polling_unit_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('from') ? 'after_or_equal:from' : null]),
        ]);
    }
}

