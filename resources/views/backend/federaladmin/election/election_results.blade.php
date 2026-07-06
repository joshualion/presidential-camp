@extends('backend.template.backend-master')
@section('content')

<section class="content">
    <div class="container-fluid">
        @include('backend.shared.election.governance-summary-cards')

        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            @if($profileData->access_level == 'superadmin' || $profileData->access_level == 'nationaladmin')
                                Campaignwide Votes Distribution By Political Parties in {{$profileData->country->name}}
                            @elseif($profileData->access_level == 'regionaladmin')
                                Regional Votes Distribution By Political Parties in {{ $profileData->region->name ?? 'Your Region' }}
                            @elseif($profileData->access_level == 'stateadmin')
                                State Votes Distribution By Political Parties in {{ $profileData->state->name ?? 'Your State' }}
                            @elseif($profileData->access_level == 'senatorialadmin')
                                Senatorial District Votes Distribution By Political Parties in {{ $profileData->senatorialDistrict->name ?? 'Your Senatorial District' }}
                            @elseif($profileData->access_level == 'federaladmin')
                                Federal Constituency Votes Distribution By Political Parties in {{ $profileData->federalConstituency->name ?? 'Your Federal Constituency' }}
                            @elseif($profileData->access_level == 'lgaadmin')
                                Local Government Area (LGA) Votes Distribution By Political Parties in {{ $profileData->lga->name ?? 'Your LGA' }}
                            @elseif($profileData->access_level == 'wardadmin')
                                Ward Votes Distribution By Political Parties in {{ $profileData->ward->name ?? 'Your Ward' }}
                            @elseif($profileData->access_level == 'puadmin' || $profileData->access_level == 'pollingunitadmin')
                                Polling Unit Votes Distribution By Political Parties in {{ $profileData->pollingUnit->name ?? 'Your Polling Unit' }}
                            @endif
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <div class="card-body" style="height: 340px;">
                        <canvas id="ResultBarChart" style="min-height: 280px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($summaryStats['total_polling_units']) }}</h3>
                        <p>Total Polling Units</p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-pie"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format($summaryStats['total_valid_votes']) }}</h3>
                        <p>Total Valid Votes</p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-bar"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ number_format($summaryStats['total_parties_participated']) }}</h3>
                        <p>Total Parties</p>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-purple" style="background:#6f42c1 !important; color:#fff;">
                    <div class="inner">
                        <h3>{{ number_format($summaryStats['results_received_percent'], 2) }}<sup style="font-size: 16px">%</sup></h3>
                        <p>Results Received</p>
                    </div>
                    <div class="icon"><i class="fas fa-dot-circle"></i></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">Vote Share by Party</h3>
                    </div>
                    <div class="card-body" style="height: 320px;">
                        <canvas id="ResultDoughnutChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">Top Parties by Votes</h3>
                    </div>
                    <div class="card-body" style="height: 320px;">
                        <canvas id="ResultTopPartiesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Results Summary Ranking</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Rank</th>
                                    <th>Party</th>
                                    <th style="width: 180px;">Votes</th>
                                    <th style="width: 220px;">Vote Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summary as $item)
                                    @php
                                        $share = $summaryStats['total_valid_votes'] > 0
                                            ? round(($item['total_votes'] / $summaryStats['total_valid_votes']) * 100, 1)
                                            : 0;
                                    @endphp
                                    <tr>
                                        <td><span class="badge badge-dark">{{ $item['rank'] }}</span></td>
                                        <td>{{ $item['party_name'] }}</td>
                                        <td>{{ number_format($item['total_votes']) }}</td>
                                        <td>
                                            <div class="progress progress-xs">
                                                <div class="progress-bar bg-primary" style="width: {{ $share }}%"></div>
                                            </div>
                                            <small>{{ $share }}%</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="{{ asset('assets/plugins/chart.js/Chart.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartUrl = "{{ route('election.chart.data', $election->uuid) }}";
        const barCtx = document.getElementById('ResultBarChart').getContext('2d');
        const doughnutCtx = document.getElementById('ResultDoughnutChart').getContext('2d');
        const topPartiesCtx = document.getElementById('ResultTopPartiesChart').getContext('2d');

        let resultBarChart;
        let resultDoughnutChart;
        let resultTopPartiesChart;

        fetch(chartUrl)
            .then(response => response.json())
            .then(data => {
                const labels = data.labels || [];
                const votes = (data.datasets && data.datasets[0] && data.datasets[0].data) ? data.datasets[0].data : [];
                const colors = (data.datasets && data.datasets[0] && data.datasets[0].backgroundColor) ? data.datasets[0].backgroundColor : [];

                if (resultBarChart) resultBarChart.destroy();
                resultBarChart = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Election Results',
                            data: votes,
                            backgroundColor: colors,
                            borderColor: '#2c3e50',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true } },
                        plugins: { legend: { display: false } }
                    }
                });

                if (resultDoughnutChart) resultDoughnutChart.destroy();
                resultDoughnutChart = new Chart(doughnutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{ data: votes, backgroundColor: colors }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });

                const partyRows = labels.map((label, idx) => ({ label, value: votes[idx] || 0, color: colors[idx] || '#007bff' }))
                    .sort((a, b) => b.value - a.value)
                    .slice(0, 8);

                if (resultTopPartiesChart) resultTopPartiesChart.destroy();
                resultTopPartiesChart = new Chart(topPartiesCtx, {
                    type: 'bar',
                    data: {
                        labels: partyRows.map(x => x.label),
                        datasets: [{
                            label: 'Votes',
                            data: partyRows.map(x => x.value),
                            backgroundColor: partyRows.map(x => x.color),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true } }
                    }
                });
            })
            .catch(error => console.error('Error fetching chart data:', error));
    });
</script>
@endsection
