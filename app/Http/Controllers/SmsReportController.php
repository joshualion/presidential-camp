<?php

namespace App\Http\Controllers;

use App\Models\SmsBatch;
use App\Services\Sms\SmsAuditService;
use App\Services\Sms\SmsReportAccessService;
use App\Services\Sms\SmsSettlementSyncService;
use Illuminate\Http\Request;

class SmsReportController extends Controller
{
    public function index(Request $request, SmsReportAccessService $access)
    {
        $batches = $access->query($request->user())
            ->with(['wallet', 'createdBy'])
            ->withCount([
                'messages as delivered_count' => fn ($query) => $query->where('delivery_status', 'delivered'),
                'messages as failed_count' => fn ($query) => $query->whereIn('delivery_status', ['failed', 'rejected', 'undelivered', 'expired']),
                'messages as dnd_blocked_count' => fn ($query) => $query->where('delivery_status', 'dnd_blocked'),
                'messages as charged_count' => fn ($query) => $query->where('billing_status', 'charged'),
                'messages as refunded_count' => fn ($query) => $query->where('refunded', true),
            ])->latest()->paginate(30)->withQueryString();
        return view('backend.sms.batches', ['profileData' => $request->user(), 'batches' => $batches, 'pageTitle' => 'SMS Reports']);
    }

    public function show(Request $request, SmsBatch $batch, SmsReportAccessService $access, SmsAuditService $audit)
    {
        $access->authorize($request->user(), $batch);
        $audit->record('sms.report.viewed', $batch);
        $deliveryCounts = $batch->messages()->selectRaw('delivery_status, COUNT(*) as aggregate')->groupBy('delivery_status')->pluck('aggregate', 'delivery_status');
        $billingCounts = $batch->messages()->selectRaw("CASE WHEN billing_review_required = 1 THEN 'billing_review_required' WHEN refunded = 1 THEN 'refunded' ELSE COALESCE(billing_status, 'pending') END as billing_label, COUNT(*) as aggregate")->groupBy('billing_label')->pluck('aggregate', 'billing_label');
        return view('backend.sms.batch', [
            'profileData' => $request->user(), 'batch' => $batch->load(['wallet', 'createdBy']),
            'messages' => $batch->messages()->paginate(100), 'deliveryCounts' => $deliveryCounts,
            'billingCounts' => $billingCounts, 'pageTitle' => 'SMS Batch Report',
        ]);
    }

    public function sync(Request $request, SmsBatch $batch, SmsReportAccessService $access, SmsSettlementSyncService $sync)
    {
        $access->authorize($request->user(), $batch);
        $result = $sync->sync($batch);
        return back()->with(['message' => $result['message'], 'alert-type' => ($result['ok'] ?? false) ? 'success' : 'error']);
    }
}
