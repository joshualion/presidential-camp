@extends('backend.template.backend-master')
@section('content')

@php
    $activeElection = $situationRoom['active_election'] ?? null;
    $status = $situationRoom['status'] ?? [];
    $recentActivity = collect($situationRoom['recent_activity'] ?? []);
    $statusCards = [
        [
            'label' => 'Result Submitted',
            'ok' => $status['result_submitted'] ?? false,
            'icon' => 'fas fa-file-alt',
            'footerText' => 'Polling unit result status',
        ],
        [
            'label' => 'Vote Uploaded',
            'ok' => $status['vote_uploaded'] ?? false,
            'icon' => 'fas fa-cloud-upload-alt',
            'footerText' => 'Vote upload status',
        ],
        [
            'label' => 'Incident Reported',
            'ok' => $status['incident_reported'] ?? false,
            'icon' => 'fas fa-exclamation-triangle',
            'footerText' => 'Incident status',
        ],
        [
            'label' => 'Evidence Uploaded',
            'ok' => $status['evidence_uploaded'] ?? false,
            'icon' => 'fas fa-paperclip',
            'footerText' => 'Evidence upload status',
        ],
    ];
@endphp

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Polling Unit Situation Room</h3>
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
            @foreach($statusCards as $card)
                @include('backend.shared.dashboard-stat-box', [
                    'label' => $card['label'],
                    'value' => $card['ok'] ? 'Yes' : 'No',
                    'color' => $card['ok'] ? 'bg-success' : 'bg-secondary',
                    'icon' => $card['icon'],
                    'footerText' => $card['footerText'],
                ])
            @endforeach
        </div>

        <div class="card card-primary mb-0">
            <div class="card-header">
                <h3 class="card-title">Last Activity</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if(!empty($status['last_activity_at']))
                    <strong>{{ $status['last_activity_at']->diffForHumans() }}</strong>
                    <span class="text-muted">({{ $status['last_activity_at']->format('Y-m-d H:i') }})</span>
                @else
                    <span class="text-muted">No activity found for the active election.</span>
                @endif
            </div>
        </div>
    </div>
</div>

@include('backend.shared.election.analytics-charts', [
    'analytics' => $analytics ?? [],
])

@include('backend.shared.election.operations-feed-table', [
    'title' => 'Recent Activity',
    'items' => $recentActivity,
    'emptyText' => 'No polling unit activity found for the active election.',
])

@endsection
