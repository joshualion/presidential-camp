@php
    $electionCards = $dashboardMetrics['card_groups']['election_operations'] ?? [];
@endphp

@if(!empty($electionCards))
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Election Statistics</h3>
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
        @include('backend.shared.dashboard-stat-grid', [
            'dashboardMetrics' => $dashboardMetrics,
            'groups' => ['election_operations'],
        ])
    </div>
</div>
@endif
