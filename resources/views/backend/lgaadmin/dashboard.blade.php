@extends('backend.template.backend-master')
@section('content')


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
            <h3 class="card-title">DISTRIBUTION BY GENDER</h3>

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
            <h3 class="card-title">DISTRIBUTION BY AGE BRACKET</h3>

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
<!-- End Demographics -->


<!-- Member distribution by lga and Voter Eligibility -->
<div class="row">
    <div class="col-md-6">
        <!-- PIE CHART -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">DISTRIBUTION BY VOTE ELIGIBILITY</h3>

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
    </div>

    <div class="col-md-6">
            <!-- DONUT CHART -->
            <div class="card card-primary ">
                <div class="card-header">
                <h3 class="card-title">DISTRIBUTION BY Ward</h3>

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
                    <canvas id="wardDonutChart" style="min-height: 250px; height: 241px; max-height: 250px; max-width: 100%; display: block; width: 470px;" width="470" height="241" class="chartjs-render-monitor"></canvas>
                    </div>
                </div> <!-- /.card-body -->
            </div>  <!-- /.card -->
            </div><!-- /.col Right -->
    </div>


<!-- End Member Distribution by lga and Eligibility -->



<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>

<!-- AdminLTE ChartJS -->
<script src="{{asset('assets/plugins/chart.js/Chart.min.js')}}"></script>

<!-- Distribution By Gender -->
<script>
    // Get the canvas element and create a pie chart
    var pieChartCanvas = document.getElementById('pieChart').getContext('2d');
    var pieChart;

    function updatePieChart() {
        $.ajax({
            url:"{{route($profileData->access_level.'.lga.member.distribution.gender',$lga->uuid)}}", // Ensure URL is a string
            method: 'GET',
            success: function(data) {
                if (pieChart) {
                    pieChart.destroy();
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
                                            var total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                            var percentage = ((value / total) * 100).toFixed(2);
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
                                    var total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    var percentage = ((value / total) * 100).toFixed(2);
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

<!-- Distribution By Age Grade -->
<script>
    var barChartCanvas = document.getElementById('barChart').getContext('2d');
    var barChart;

    function updateBarChart() {
        $.ajax({
            url: "{{route($profileData->access_level.'.lga.member.distribution.age', $lga->uuid)}}", // Update with your route URL
            method: 'GET',
            success: function(data) {
                if (barChart) {
                    barChart.destroy();
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

<!-- Voter Eligibility -->
<script>
    var validVoterPieChartCanvas = document.getElementById('validVoterPieChart').getContext('2d');
    var validVoterPieChart;

    function updateValidVoterPieChart() {
        $.ajax({
            url: "{{route($profileData->access_level.'.lga.member.distribution.voter', $lga->uuid)}}", // Update with your route URL
            method: 'GET',
            success: function(data) {
                if (validVoterPieChart) {
                    validVoterPieChart.destroy();
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


<!-- lga Distribution -->
<script>
    var wardDonutChartCanvas = document.getElementById('wardDonutChart').getContext('2d');
    var wardDonutChart;

    function updatewardDonutChart() {
        $.ajax({
            url: "{{route($profileData->access_level.'.lga.member.distribution.ward', $lga->uuid)}}", // Update with your route URL
            method: 'GET',
            success: function(data) {
                if (wardDonutChart) {
                    wardDonutChart.destroy();
                }
                // Calculate total count to calculate percentages
                var totalCount = data.datasets[0].data.reduce((a, b) => a + b, 0);
                // Calculate and update the labels with percentages
                data.labels = data.labels.map((label, index) => `${label} (${((data.datasets[0].data[index] / totalCount) * 100).toFixed(2)}%)`);
                wardDonutChart = new Chart(wardDonutChartCanvas, {
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
    updatewardDonutChart();
</script>



@include('backend.shared.dashboard-stat-polling', [
    'statsEndpoint' => route('lgaadmin.dashboard.stats'),
    'pollInterval' => 30000,
])

@endsection
