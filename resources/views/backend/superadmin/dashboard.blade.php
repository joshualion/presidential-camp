@extends('backend.template.backend-master')
@section('content')
  <!-- Dashboard Statistics -->
    <div class="card card-primary">
                <div class="card-header">
                        <h3 class="card-title">{{ $dashboardMetrics['scope']['title'] ?? $packageUi->statisticsTitle() }}</h3>
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
                        'groups' => ['coverage', 'people', 'membership_activity'],
                    ])

            </div> <!-- /.card-body -->
    </div>

    @include('backend.shared.dashboard-election-stat-section', ['dashboardMetrics' => $dashboardMetrics])

    <!-- Demographics -->
    <div class="row">
            <div class="col-md-6">
            <!-- PIE CHART -->
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ $packageUi->chartTitle('gender') }}</h3>

                    <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove">
                        <i class="fas fa-times"></i>
                    </button>
                    </div>
                </div>
                <div class="card-body" style="display: block;"><div class="chartjs-size-monitor"><div class="chartjs-size-monitor-expand"><div class=""></div></div><div class="chartjs-size-monitor-shrink"><div class=""></div></div></div>
                    <canvas id="pieChart" style="min-height: 250px; height: 241px; max-height: 250px; max-width: 100%; display: block; width: 470px;" width="470" height="241" class="chartjs-render-monitor"></canvas>
                </div>
                <!-- /.card-body -->
                </div> <!-- /.card -->

            </div>  <!-- /.col (LEFT) -->

        <div class="col-md-6">

        <!-- BAR CHART -->
        <div class="card card-primary ">
                <div class="card-header">
                    <h3 class="card-title">{{ $packageUi->chartTitle('age') }}</h3>

                    <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove">
                        <i class="fas fa-times"></i>
                    </button>
                    </div>
                </div>
            <div class="card-body" style="display: block;">
                    <div class="chart"><div class="chartjs-size-monitor"><div class="chartjs-size-monitor-expand"><div class=""></div></div><div class="chartjs-size-monitor-shrink"><div class=""></div></div></div>
                    <canvas id="barChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%; display: block; width: 487px;" width="487" height="250" class="chartjs-render-monitor"></canvas>
                    </div>
                </div> <!-- /.card-body -->
            </div>  <!-- /.card -->

            </div><!-- /.col Right -->
        </div>
    <!-- End Member MEMBER Distribution by Gender and Age Brcaket -->

    <!-- Member MEMBER Distribution by Region -->
    @if($packageUi->supportsChart('region') || $packageUi->supportsChart('geography'))
    <div class="row">
        <div class="col-md-12">
        <!-- PIE CHART -->
            <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ $packageUi->geographyDistributionTitle() }}</h3>

                <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove">
                    <i class="fas fa-times"></i>
                </button>
                </div>
            </div>
            <div class="card-body" style="display: block;">
                <div class="chartjs-size-monitor"><div class="chartjs-size-monitor-expand"><div class=""></div></div><div class="chartjs-size-monitor-shrink"><div class=""></div></div></div>
                <canvas id="donutChart" style="min-height: 250px; height: 241px; max-height: 250px; max-width: 100%; display: block; width: 470px;" width="470" height="241" class="chartjs-render-monitor"></canvas>
            </div>
            <!-- /.card-body -->
            </div> <!-- /.card -->

        </div>  <!-- /.col (LEFT) -->

    <div class="col-md-6">

    </div><!-- /.col Right -->
    </div>
    @else
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ $packageUi->geographyDistributionTitle() }}</h3>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted">{{ $packageUi->emptyStateMessage('geography data') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif
    <!-- Member MEMBER distribution by Religion and Voter Eligibility -->
    <div class="row">
            <div class="col-md-6">
                    <!-- PIE CHART -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">{{ $packageUi->chartTitle('voter') }}</h3>

                        <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove">
                            <i class="fas fa-times"></i>
                        </button>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;"><div class="chartjs-size-monitor"><div class="chartjs-size-monitor-expand"><div class=""></div></div><div class="chartjs-size-monitor-shrink"><div class=""></div></div></div>
                    <canvas id="validVoterPieChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%; display: block; width: 487px;"></canvas>
                    </div>
                <!-- /.card-body -->
                </div> <!-- /.card -->
            </div>  <!-- /.col (LEFT) -->

        <div class="col-md-6">

        <!-- BAR CHART -->
        <div class="card card-primary">
            <div class="card-header">
            <h3 class="card-title">{{ $packageUi->chartTitle('religion') }}</h3>

            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove">
                <i class="fas fa-times"></i>
                </button>
            </div>
            </div>
            <div class="card-body" style="display: block;">
            <div class="chart"><div class="chartjs-size-monitor"><div class="chartjs-size-monitor-expand"><div class=""></div></div><div class="chartjs-size-monitor-shrink"><div class=""></div></div></div>
                <canvas id="religionBarChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%; display: block; width: 487px;" width="487" height="250" class="chartjs-render-monitor"></canvas>
            </div>
            </div> <!-- /.card-body -->
    </div>
            <!-- /.card -->

        </div><!-- /.col Right -->
    </div>
    <!-- End Member MEMBER Distribution by Support and Eligibility -->

    <!-- Member MEMBER Distribution by  States -->
    @if($packageUi->supportsChart('state'))
    <div class="row">

        <div class="col-md-12">
        <!-- DONUT CHART -->
        <div class="card card-primary ">
            <div class="card-header">
            <h3 class="card-title">{{ $packageUi->chartTitle('state') }}</h3>

            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove">
                <i class="fas fa-times"></i>
                </button>
            </div>
            </div>
            <div class="card-body" style="display: block;">
                <div class="chart"><div class="chartjs-size-monitor"><div class="chartjs-size-monitor-expand"><div class=""></div></div><div class="chartjs-size-monitor-shrink"><div class=""></div></div></div>
                <canvas id="stateDonutChart" style="min-height: 250px; height: 241px; max-height: 250px; max-width: 100%; display: block; width: 470px;" width="470" height="241" class="chartjs-render-monitor"></canvas>
                </div>
            </div> <!-- /.card-body -->
        </div>  <!-- /.card -->
        </div><!-- /.col Right -->
    </div>
    @endif





<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>

<!-- AdminLTE ChartJS -->
<script src="{{asset('assets/plugins/chart.js/Chart.min.js')}}"></script>

<script>
    function campaignChartIsEmpty(data) {
        if (!data || data.empty === true) {
            return true;
        }

        var dataset = data.datasets && data.datasets[0] ? data.datasets[0].data || [] : [];
        return dataset.length === 0 || dataset.reduce(function(total, value) {
            return total + Number(value || 0);
        }, 0) === 0;
    }

    function campaignRenderChartEmptyState(canvas, data) {
        var wrapper = canvas.parentElement;
        var existing = wrapper.querySelector('.campaign-chart-empty-state');

        if (existing) {
            existing.remove();
        }

        if (!campaignChartIsEmpty(data)) {
            canvas.style.display = 'block';
            return false;
        }

        canvas.style.display = 'none';
        var empty = document.createElement('div');
        empty.className = 'campaign-chart-empty-state text-muted text-center py-5';
        empty.textContent = (data && data.message) ? data.message : 'No data available yet.';
        wrapper.appendChild(empty);
        return true;
    }

    function campaignDatasetTotal(data) {
        var dataset = data.datasets && data.datasets[0] ? data.datasets[0].data || [] : [];
        return dataset.reduce(function(total, value) {
            return total + Number(value || 0);
        }, 0);
    }
</script>

<!-- MEMBER Distribution By Gender -->
<script>
    // Get the canvas element and create a pie chart
    var pieChartElement = document.getElementById('pieChart');
    var pieChartCanvas = pieChartElement.getContext('2d');
    var pieChart;

    function updatePieChart() {
        $.ajax({
            url: "{{ route('superadmin.member.distribution.gender') }}",
            method: 'GET',
            success: function(data) {
                if (pieChart) {
                    pieChart.destroy();
                }
                if (campaignRenderChartEmptyState(pieChartElement, data)) {
                    return;
                }
                pieChart = new Chart(pieChartCanvas, {
                    type: 'pie',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        title: {
                            display: false,
                            text: 'Gender Distribution'
                        },
                        legend: {
                            display: true,
                            position: 'right',
                            labels: {
                                generateLabels: function(chart) {
                                    var data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map(function(label, index) {
                                            var value = data.datasets[0].data[index];
                                            var total = campaignDatasetTotal(data);
                                            var percentage = total > 0 ? ((value / total) * 100).toFixed(2) : '0.00';
                                            return {
                                                text: label + ' (' + percentage + '%)',
                                                fillStyle: data.datasets[0].backgroundColor[index],
                                                hidden: isNaN(data.datasets[0].data[index]) || chart.getDatasetMeta(0).data[index].hidden,
                                                lineCap: 'butt',
                                                lineDash: [],
                                                lineDashOffset: 0,
                                                lineJoin: 'miter',
                                                lineWidth: 10,
                                                strokeStyle: data.datasets[0].backgroundColor[index],
                                                pointStyle: 'circle',
                                                rotation: 0
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltips: {
                            mode: 'nearest',
                            callbacks: {
                                label: function(tooltipItem, data) {
                                    var label = data.labels[tooltipItem.index];
                                    var value = data.datasets[0].data[tooltipItem.index];
                                    var total = campaignDatasetTotal(data);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(2) : '0.00';
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        },
                        // You can add more options as needed
                    }
                });
            }
        });
    }

    // Call the function to initialize the pie chart
    updatePieChart();
</script>

<!-- MEMBER Distribution By Age Grade -->
<script>
    var barChartElement = document.getElementById('barChart');
    var barChartCanvas = barChartElement.getContext('2d');
    var barChart;

    function updateBarChart() {
        $.ajax({
            url: "{{ route('superadmin.member.distribution.age') }}",
            method: 'GET',
            success: function(data) {
                if (barChart) {
                    barChart.destroy();
                }
                if (campaignRenderChartEmptyState(barChartElement, data)) {
                    return;
                }
                barChart = new Chart(barChartCanvas, {
                    type: 'bar',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Member'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Age Grade'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value + ' yrs';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        var label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += context.parsed.y + ' Member';
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        },
                        indexAxis: 'y', // This sets the vertical bars

                        // You can add more options as needed
                    }
                });
            }
        });
    }

    // Call the function to initialize the bar chart
    updateBarChart();
</script>

<!-- Regional Distribution of member -->

@if($packageUi->supportsChart('region') || $packageUi->supportsChart('geography'))
<script>
    var donutChartElement = document.getElementById('donutChart');
    var donutChartCanvas = donutChartElement.getContext('2d');
    var donutChart;

    function updateDonutChart() {
        $.ajax({
            url: "{{ $packageUi->supportsChart('region') ? route('superadmin.member.distribution.region') : route('superadmin.member.distribution.state') }}",
            method: 'GET',
            success: function(data) {
                if (donutChart) {
                    donutChart.destroy();
                }
                if (campaignRenderChartEmptyState(donutChartElement, data)) {
                    return;
                }
                donutChart = new Chart(donutChartCanvas, {
                    type: 'doughnut', // Use 'doughnut' for a donut chart
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            position: 'right' // Position the legend to the right
                        }
                                            // Add more options as needed
                    }
                });
            }
        });
    }

    // Call the function to initialize the donut chart
    updateDonutChart();
</script>
@endif

<!-- Distribution of Members by Religion -->
<script>
    var religionBarChartElement = document.getElementById('religionBarChart');
    var religionBarChartCanvas = religionBarChartElement.getContext('2d');
    var religionBarChart;

    function updateReligionBarChart() {
        $.ajax({
            url: "{{ route('superadmin.member.distribution.religion') }}",
            method: 'GET',
            success: function(data) {
                if (religionBarChart) {
                    religionBarChart.destroy();
                }
                if (campaignRenderChartEmptyState(religionBarChartElement, data)) {
                    return;
                }
                religionBarChart = new Chart(religionBarChartCanvas, {
                    type: 'bar', // Change the chart type to 'bar'
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Members'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Religion'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                        // You can add more options as needed
                    }
                });
            }
        });
    }

    // Call the function to initialize the bar chart for religion distribution
    updateReligionBarChart();
</script>
<!-- Distribution of Members by Religion -->

<!-- Voter Eligibility -->
<script>
    var validVoterPieChartElement = document.getElementById('validVoterPieChart');
    var validVoterPieChartCanvas = validVoterPieChartElement.getContext('2d');
    var validVoterPieChart;

    function updateValidVoterPieChart() {
        $.ajax({
            url: "{{ route('superadmin.member.distribution.voter') }}",
            method: 'GET',
            success: function(data) {
                if (validVoterPieChart) {
                    validVoterPieChart.destroy();
                }
                if (campaignRenderChartEmptyState(validVoterPieChartElement, data)) {
                    return;
                }
                validVoterPieChart = new Chart(validVoterPieChartCanvas, {
                    type: 'pie', // Use 'pie' for a pie chart
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            position: 'bottom' // Position the legend to the right
                        }
                        // Add more options as needed
                    }
                });
            }
        });
    }

    // Call the function to initialize the pie chart
    updateValidVoterPieChart();
</script>


<!-- State Distribution -->
@if($packageUi->supportsChart('state'))
<script>
    var stateDonutChartElement = document.getElementById('stateDonutChart');
    var stateDonutChartCanvas = stateDonutChartElement.getContext('2d');
    var stateDonutChart;

    function updateStateDonutChart() {
        $.ajax({
            url: "{{ route('superadmin.member.distribution.state') }}",
            method: 'GET',
            success: function(data) {
                if (stateDonutChart) {
                    stateDonutChart.destroy();
                }
                if (campaignRenderChartEmptyState(stateDonutChartElement, data)) {
                    return;
                }
                // Calculate total count to calculate percentages
                var totalCount = campaignDatasetTotal(data);
                // Calculate and update the labels with percentages
                data.labels = data.labels.map((label, index) => `${label} (${totalCount > 0 ? ((data.datasets[0].data[index] / totalCount) * 100).toFixed(2) : '0.00'}%)`);
                stateDonutChart = new Chart(stateDonutChartCanvas, {
                    type: 'doughnut', // Use 'doughnut' for a donut chart
                    data: data,
                    options: {
                        responsive: true,
                        cutoutPercentage: 20, // Make the chart doughnut hole 20% of the radius
                        maintainAspectRatio: false,
                        legend: {
                            position: 'right' // Position the legend to the right
                        }
                        // Add more options as needed
                    }
                });
            }
        });
    }

    // Call the function to initialize the donut chart
    updateStateDonutChart();
</script>
@endif



@include('backend.shared.dashboard-stat-polling', [
    'statsEndpoint' => route('superadmin.dashboard.stats'),
    'pollInterval' => 30000,
])

@endsection
