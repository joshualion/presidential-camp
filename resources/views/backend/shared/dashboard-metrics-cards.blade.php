@php
    $metrics = $dashboardMetrics ?? [];
    $geography = $metrics['geography'] ?? [];
    $people = $metrics['people'] ?? [];
    $election = $metrics['election'] ?? [];
    $scope = $metrics['scope'] ?? ['label' => 'Dashboard'];
    $expandedMetricsCollapsed = $expandedMetricsCollapsed ?? true;
    $expandedMetricsTitle = $expandedMetricsTitle ?? 'Expanded Location Metrics';
    $activeElection = $metrics['active_election'] ?? null;
    $packageVisibility = app(\App\Services\PackageVisibilityService::class);
    $resultReportRoute = !empty($profileData ?? null) ? $profileData->access_level.'.election.votesByPu' : null;
    $resultSummaryRoute = !empty($profileData ?? null) ? $profileData->access_level.'.election.results' : null;
    $operationsRoute = !empty($profileData ?? null) ? $profileData->access_level.'.election.operations' : null;
    $electionsRoute = !empty($profileData ?? null) ? $profileData->access_level.'.elections' : null;
    $electionsUrl = ($electionsRoute && \Illuminate\Support\Facades\Route::has($electionsRoute))
        ? route($electionsRoute)
        : null;
    $resultReportUrl = (!empty($activeElection['uuid'] ?? null) && $resultReportRoute && \Illuminate\Support\Facades\Route::has($resultReportRoute))
        ? route($resultReportRoute, $activeElection['uuid'])
        : null;
    $resultSummaryUrl = (!empty($activeElection['uuid'] ?? null) && $resultSummaryRoute && \Illuminate\Support\Facades\Route::has($resultSummaryRoute))
        ? route($resultSummaryRoute, $activeElection['uuid'])
        : $resultReportUrl;
    $operationsUrl = ($operationsRoute && \Illuminate\Support\Facades\Route::has($operationsRoute))
        ? route($operationsRoute)
        : null;
    $operationsUrlFor = fn (array $query) => $operationsUrl ? $operationsUrl.'?'.http_build_query($query) : null;

    $cards = [
        ['module' => 'state', 'label' => 'States', 'value' => $geography['states'] ?? 0, 'color' => 'bg-primary', 'icon' => 'fas fa-map'],
        ['module' => 'senatorial', 'label' => 'Senatorial Districts', 'value' => $geography['senatorial_districts'] ?? 0, 'color' => 'bg-indigo', 'icon' => 'fas fa-landmark'],
        ['module' => 'federal', 'label' => 'Federal Constituencies', 'value' => $geography['federal_constituencies'] ?? 0, 'color' => 'bg-purple', 'icon' => 'fas fa-university'],
        ['module' => 'lga', 'label' => 'LGAs', 'value' => $geography['lgas'] ?? 0, 'color' => 'bg-success', 'icon' => 'fas fa-layer-group'],
        ['module' => 'ward', 'label' => 'Wards', 'value' => $geography['wards'] ?? 0, 'color' => 'bg-info', 'icon' => 'fas fa-map-marker-alt'],
        ['module' => 'polling_unit', 'label' => 'Polling Units', 'value' => $geography['polling_units'] ?? 0, 'color' => 'bg-danger', 'icon' => 'fas fa-vote-yea'],
        ['label' => 'Users', 'value' => $people['users'] ?? 0, 'color' => 'bg-secondary', 'icon' => 'fas fa-users'],
        ['label' => 'Members', 'value' => $people['members'] ?? 0, 'color' => 'bg-primary', 'icon' => 'fas fa-user'],
        ['label' => 'Agents', 'value' => $people['agents'] ?? 0, 'color' => 'bg-warning', 'icon' => 'fas fa-user-shield'],
        ['label' => 'Executives', 'value' => $people['executives'] ?? 0, 'color' => 'bg-dark', 'icon' => 'fas fa-id-badge'],
        [
            'label' => 'Elections',
            'value' => $election['elections'] ?? 0,
            'color' => 'bg-primary',
            'icon' => 'fas fa-award',
            'footerUrl' => $electionsUrl,
            'footerText' => 'Election metric',
        ],
        [
            'label' => 'Total Votes',
            'value' => $election['votes'] ?? 0,
            'color' => 'bg-success',
            'icon' => 'fas fa-poll',
            'footerUrl' => $resultSummaryUrl,
            'footerText' => 'View vote intelligence',
        ],
        [
            'label' => 'Results Submitted',
            'value' => $election['polling_unit_results'] ?? 0,
            'color' => 'bg-info',
            'icon' => 'fas fa-file-alt',
            'footerUrl' => $resultReportUrl ? $resultReportUrl.'?verification_status=submitted' : null,
            'footerText' => 'View submitted results',
        ],
        [
            'label' => 'Verified Results',
            'value' => $election['verified_results'] ?? 0,
            'color' => 'bg-success',
            'icon' => 'fas fa-check-circle',
            'footerUrl' => $resultReportUrl ? $resultReportUrl.'?verification_status=verified' : null,
            'footerText' => 'View verified results',
        ],
        [
            'label' => 'Disputed Results',
            'value' => $election['disputed_results'] ?? 0,
            'color' => 'bg-danger',
            'icon' => 'fas fa-flag',
            'footerUrl' => $resultReportUrl ? $resultReportUrl.'?dispute_status=disputed' : null,
            'footerText' => 'View disputed results',
        ],
        [
            'label' => 'Incidents Reported',
            'value' => $election['election_incidents'] ?? 0,
            'color' => 'bg-danger',
            'icon' => 'fas fa-exclamation-triangle',
            'footerUrl' => $operationsUrlFor(['focus' => 'incidents']),
            'footerText' => 'Open incident reports',
        ],
    ];

    $cards = array_values(array_filter($cards, fn ($card) => empty($card['module']) || $packageVisibility->canSeeModule($card['module'])));
@endphp

<div class="card card-secondary {{ $expandedMetricsCollapsed ? 'collapsed-card' : '' }}">
    <div class="card-header">
        <h3 class="card-title">{{ $expandedMetricsTitle }}</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas {{ $expandedMetricsCollapsed ? 'fa-plus' : 'fa-minus' }}"></i>
            </button>
        </div>
    </div>
    <div class="card-body" @if($expandedMetricsCollapsed) style="display: none;" @endif>
        <p class="text-muted mb-3">{{ $scope['label'] ?? 'Dashboard' }} metrics for electoral boundary reporting.</p>
        <div class="row">
            @foreach($cards as $card)
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="small-box {{ $card['color'] }}">
                        <div class="inner">
                            <h3>{{ number_format((int) $card['value']) }}</h3>
                            <p>{{ $card['label'] }}</p>
                        </div>
                        <div class="icon">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                        @if(!empty($card['footerUrl']))
                            <a href="{{ $card['footerUrl'] }}" class="small-box-footer">{{ $card['footerText'] ?? 'View Details' }} <i class="fas fa-arrow-circle-right"></i></a>
                        @else
                            <span class="small-box-footer">Dashboard metric</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
