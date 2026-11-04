<?php

namespace App\Http\Controllers;

use App\Services\CampaignCapabilityService;
use App\Services\Enterprise\WorkforceReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnterpriseWorkforceReportController extends Controller
{
    public function index(Request $request, WorkforceReportService $reports)
    {
        $filters = $this->filters($request);

        return response()->view('backend.enterprise.workforce-reports', [
            'profileData' => $request->user(), 'pageTitle' => 'Workforce reports',
            'filters' => $filters,
            'summary' => $reports->summary($request->user(), $filters),
            'rows' => $reports->distribution($request->user(), $filters)->paginate(50)->withQueryString(),
            'canExport' => app(CampaignCapabilityService::class)->allows($request->user(), 'reports.export'),
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
