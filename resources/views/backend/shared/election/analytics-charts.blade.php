@php
    $analyticsData = $analytics ?? [];
    $chartUid = str_replace('.', '', uniqid('situation-room-', true));
    $coverageCharts = $analyticsData['coverage_by_subdivision'] ?? [];
@endphp

<div class="row">
    <div class="col-lg-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Submission Coverage</h3>
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
                <div style="height: 280px;">
                    <canvas id="{{ $chartUid }}-submission-coverage"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Incident Evidence</h3>
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
                <div style="height: 280px;">
                    <canvas id="{{ $chartUid }}-incident-evidence"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @forelse($coverageCharts as $index => $coverageChart)
        <div class="col-lg-{{ count($coverageCharts) === 1 ? 12 : 6 }}">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ $coverageChart['title'] ?? 'Reporting Progress' }}</h3>
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
                    <div style="height: 320px;">
                        <canvas id="{{ $chartUid }}-coverage-subdivision-{{ $index }}"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Reporting Progress</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body text-muted">
                    No polling units found for this scope.
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Operational Activity Timeline</h3>
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
        <div style="height: 320px;">
            <canvas id="{{ $chartUid }}-activity-timeline"></canvas>
        </div>
    </div>
</div>

<script>
    (function () {
        var analytics = @json($analyticsData);
        var chartUid = @json($chartUid);

        function sum(values) {
            return (values || []).reduce(function (total, value) {
                return total + Number(value || 0);
            }, 0);
        }

        function doughnutPayload(payload) {
            var labels = payload.labels || [];
            var data = payload.data || [];

            if (!labels.length || sum(data) === 0) {
                return {
                    labels: ['No data'],
                    data: [1],
                    colors: ['#d6d8db']
                };
            }

            return {
                labels: labels,
                data: data,
                colors: ['#28a745', '#ffc107', '#dc3545', '#17a2b8']
            };
        }

        function renderDoughnut(id, payload) {
            var canvas = document.getElementById(id);
            if (!canvas) {
                return;
            }

            var chartData = doughnutPayload(payload || {});
            new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        data: chartData.data,
                        backgroundColor: chartData.colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom'
                    }
                }
            });
        }

        function renderStackedBar(id, payload) {
            var canvas = document.getElementById(id);
            if (!canvas) {
                return;
            }

            var labels = payload.labels || [];
            var submitted = payload.submitted || [];
            var pending = payload.pending || [];

            if (!labels.length) {
                labels = ['No data'];
                submitted = [0];
                pending = [0];
            }

            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Submitted',
                            data: submitted,
                            backgroundColor: '#28a745'
                        },
                        {
                            label: 'Pending',
                            data: pending,
                            backgroundColor: '#ffc107'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom'
                    },
                    scales: {
                        xAxes: [{
                            stacked: true,
                            ticks: {
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 0
                            }
                        }],
                        yAxes: [{
                            stacked: true,
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }]
                    }
                }
            });
        }

        function renderTimeline(id, payload) {
            var canvas = document.getElementById(id);
            if (!canvas) {
                return;
            }

            new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: payload.labels || [],
                    datasets: [
                        {
                            label: 'Results',
                            data: payload.results || [],
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.12)',
                            fill: false,
                            lineTension: 0.2
                        },
                        {
                            label: 'Incidents',
                            data: payload.incidents || [],
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.12)',
                            fill: false,
                            lineTension: 0.2
                        },
                        {
                            label: 'Evidence',
                            data: payload.evidence || [],
                            borderColor: '#17a2b8',
                            backgroundColor: 'rgba(23, 162, 184, 0.12)',
                            fill: false,
                            lineTension: 0.2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom'
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }]
                    }
                }
            });
        }

        function bootSituationRoomCharts() {
            if (typeof Chart === 'undefined') {
                return;
            }

            renderDoughnut(chartUid + '-submission-coverage', analytics.submission_coverage || {});
            renderDoughnut(chartUid + '-incident-evidence', analytics.incident_evidence || {});
            (analytics.coverage_by_subdivision || []).forEach(function (coverageChart, index) {
                renderStackedBar(chartUid + '-coverage-subdivision-' + index, coverageChart);
            });
            renderTimeline(chartUid + '-activity-timeline', analytics.activity_timeline || {});
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootSituationRoomCharts);
        } else {
            bootSituationRoomCharts();
        }
    })();
</script>
