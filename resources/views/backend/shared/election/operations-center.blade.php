@extends('backend.template.backend-master')
@section('content')

@php
    $activeElection = $operationsCenter['active_election'] ?? null;
    $coverage = $operationsCenter['submission_coverage'] ?? [];
    $puActivity = $operationsCenter['polling_unit_activity'] ?? [];
    $incidents = $operationsCenter['incidents'] ?? [];
    $latestResults = collect($operationsCenter['latest_results'] ?? []);
    $latestIncidents = collect($operationsCenter['latest_incidents'] ?? []);
    $recentActivity = collect($operationsCenter['recent_activity'] ?? []);
    $resultsUrl = $activeElection ? route($profileData->access_level.'.election.votesByPu', $activeElection->uuid) : null;
    $summaryCards = [
        [
            'label' => 'Total Polling Units',
            'value' => $coverage['total_polling_units'] ?? 0,
            'color' => 'bg-primary',
            'icon' => 'fas fa-vote-yea',
            'footerText' => 'Submission coverage',
        ],
        [
            'label' => 'Submitted',
            'value' => $coverage['submitted_polling_units'] ?? 0,
            'color' => 'bg-success',
            'icon' => 'fas fa-file-alt',
            'footerText' => 'View submitted results',
            'footerUrl' => $resultsUrl ? $resultsUrl.'?verification_status=submitted' : null,
        ],
        [
            'label' => 'Pending',
            'value' => $coverage['pending_polling_units'] ?? 0,
            'color' => 'bg-warning',
            'icon' => 'fas fa-hourglass-half',
            'footerText' => 'Results pending',
        ],
        [
            'label' => 'Coverage %',
            'value' => $coverage['coverage_percent'] ?? 0,
            'suffix' => '%',
            'decimalPlaces' => 2,
            'color' => 'bg-info',
            'icon' => 'fas fa-percent',
            'footerText' => 'Submitted / total PUs',
        ],
        [
            'label' => 'Verified Results',
            'value' => $coverage['verified_polling_units'] ?? 0,
            'color' => 'bg-success',
            'icon' => 'fas fa-check-circle',
            'footerText' => 'View verified results',
            'footerUrl' => $resultsUrl ? $resultsUrl.'?verification_status=verified' : null,
        ],
        [
            'label' => 'Disputed Results',
            'value' => $coverage['disputed_polling_units'] ?? 0,
            'color' => 'bg-danger',
            'icon' => 'fas fa-flag',
            'footerText' => 'View disputed results',
            'footerUrl' => $resultsUrl ? $resultsUrl.'?dispute_status=disputed' : null,
        ],
        [
            'label' => 'Active Polling Units',
            'value' => $puActivity['active_polling_units'] ?? 0,
            'color' => 'bg-success',
            'icon' => 'fas fa-broadcast-tower',
            'footerText' => 'Results, votes, or incidents',
        ],
        [
            'label' => 'Silent Polling Units',
            'value' => $puActivity['silent_polling_units'] ?? 0,
            'color' => 'bg-danger',
            'icon' => 'fas fa-volume-mute',
            'footerText' => 'No activity yet',
        ],
        [
            'label' => 'Total Incidents',
            'value' => $incidents['total'] ?? 0,
            'color' => 'bg-danger',
            'icon' => 'fas fa-exclamation-triangle',
            'footerText' => 'Reported incidents',
        ],
        [
            'label' => 'With Evidence',
            'value' => $incidents['with_evidence'] ?? 0,
            'color' => 'bg-secondary',
            'icon' => 'fas fa-paperclip',
            'footerText' => 'Incident evidence',
        ],
        [
            'label' => 'Without Evidence',
            'value' => $incidents['without_evidence'] ?? 0,
            'color' => 'bg-dark',
            'icon' => 'fas fa-folder-open',
            'footerText' => 'Needs evidence',
        ],
    ];
@endphp

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Situation Room</h3>
        <div class="card-tools">
            @if($activeElection)
                <span class="badge badge-light">{{ \Carbon\Carbon::parse($activeElection->year)->year }} - {{ $activeElection->name }}</span>
            @else
                <span class="badge badge-warning">No active election</span>
            @endif
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row dashboard-stats-grid">
            @foreach($summaryCards as $card)
                @include('backend.shared.dashboard-stat-box', $card)
            @endforeach
        </div>
    </div>
</div>

@include('backend.shared.election.analytics-charts', [
    'analytics' => $analytics ?? [],
])

<div class="row">
    <div class="col-lg-6">
        @include('backend.shared.election.operations-feed-table', [
            'title' => 'Latest Result Submissions',
            'items' => $latestResults,
            'emptyText' => 'No result submissions found for this scope.',
        ])
    </div>
    <div class="col-lg-6">
        @include('backend.shared.election.operations-feed-table', [
            'title' => 'Latest Incidents',
            'items' => $latestIncidents,
            'emptyText' => 'No incidents found for this scope.',
        ])
    </div>
</div>

@include('backend.shared.election.operations-feed-table', [
    'title' => 'Recent Operational Activity',
    'items' => $recentActivity,
    'emptyText' => 'No operational activity found for this scope.',
])

@endsection
