@php
    $dashboardMetrics = $dashboardMetrics ?? [];
    $packageUi = $packageUi ?? app(\App\Services\CampaignPackageUiService::class);
    $scopeType = $dashboardMetrics['scope']['type'] ?? null;
    $election = $dashboardMetrics['election'] ?? [];
    $electionCards = $dashboardMetrics['card_groups']['election_operations'] ?? [];
    $title = $electionStatsTitle
        ?? ($election['title'] ?? $packageUi->electionStatisticsTitleForScope($scopeType));

    if (empty($electionCards)) {
        $electionCards = [
            ['key' => 'elections', 'label' => 'Elections', 'value' => $election['elections'] ?? 0, 'color' => 'bg-primary', 'icon' => 'fas fa-award', 'footerText' => 'Election metric', 'footerUrl' => null],
            ['key' => 'total_votes', 'label' => 'Total Votes', 'value' => $election['votes'] ?? 0, 'color' => 'bg-success', 'icon' => 'fas fa-poll', 'footerText' => 'Vote intelligence', 'footerUrl' => null],
            ['key' => 'results_submitted', 'label' => 'Results Submitted', 'value' => $election['results_submitted'] ?? ($election['polling_unit_results'] ?? 0), 'color' => 'bg-info', 'icon' => 'fas fa-file-alt', 'footerText' => 'Submitted results', 'footerUrl' => null],
            ['key' => 'verified_results', 'label' => 'Verified Results', 'value' => $election['verified_results'] ?? 0, 'color' => 'bg-success', 'icon' => 'fas fa-check-circle', 'footerText' => 'Verified results', 'footerUrl' => null],
            ['key' => 'disputed_results', 'label' => 'Disputed Results', 'value' => $election['disputed_results'] ?? 0, 'color' => 'bg-danger', 'icon' => 'fas fa-flag', 'footerText' => 'Disputed results', 'footerUrl' => null],
            ['key' => 'results_pending', 'label' => 'Results Pending', 'value' => $election['results_pending'] ?? 0, 'color' => 'bg-warning', 'icon' => 'fas fa-hourglass-half', 'footerText' => 'Pending results', 'footerUrl' => null],
            ['key' => 'incidents_reported', 'label' => 'Incidents Reported', 'value' => $election['incidents_reported'] ?? ($election['election_incidents'] ?? 0), 'color' => 'bg-danger', 'icon' => 'fas fa-exclamation-triangle', 'footerText' => 'Incident reports', 'footerUrl' => null],
            ['key' => 'pictures_uploaded', 'label' => 'Pictures Uploaded', 'value' => $election['pictures_uploaded'] ?? 0, 'color' => 'bg-secondary', 'icon' => 'fas fa-image', 'footerText' => 'Evidence reports', 'footerUrl' => null],
            ['key' => 'videos_uploaded', 'label' => 'Videos Uploaded', 'value' => $election['videos_uploaded'] ?? 0, 'color' => 'bg-dark', 'icon' => 'fas fa-video', 'footerText' => 'Evidence reports', 'footerUrl' => null],
        ];

        data_set($dashboardMetrics, 'card_groups.election_operations', $electionCards);
    }

    $totalElectionValues = collect($electionCards)->sum(fn ($card) => (int) ($card['value'] ?? 0));
@endphp

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        @if($totalElectionValues === 0)
            <p class="text-muted mb-3">No election data is available for this campaign scope yet.</p>
        @endif

        @include('backend.shared.dashboard-stat-grid', [
            'dashboardMetrics' => $dashboardMetrics,
            'groups' => ['election_operations'],
        ])
    </div>
</div>
