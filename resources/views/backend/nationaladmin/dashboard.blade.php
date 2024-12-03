@extends('backend.template.backend-master')
@section('content')

<div class="card card-primary">
              <div class="card-header">
                    <h3 class="card-title">Nationwide Statistics</h3>
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

                   <!-- Small boxes (Stat box) -->
                  <div class="row">
                            <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box bg-primary">
                                <div class="inner">
                                  <h3>{{$statesWithUsersCount}}</h3>

                                  <p>Out of <strong>{{$stateCount }}</strong> States in {{$SystemSetting->system_country}}</p>
                                  <p style="float: right;">

                                    <b>
                                        @php
                                            if ($stateCount > 0) {
                                                echo number_format(($statesWithUsersCount / $stateCount) * 100, 2);
                                            } else {
                                                echo '0.00'; // Handle the case where $wardCount is zero
                                            }
                                        @endphp %
                                    </b>
                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-nigeria"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.members.byState', $uuid)}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                            <!-- ./col -->
                            <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box bg-success">
                                <div class="inner">
                                  <h3>{{$lgaWithUsersCount}}</h3>
                                  <p>Out of <strong>{{$lgaCount}}</strong> LGAs in {{$SystemSetting->system_country}}</p>
                                  <p style="float: right;">
                                    <b>
                                        @php
                                            if ($lgaCount > 0) {
                                                echo number_format(($lgaWithUsersCount / $lgaCount) * 100, 2);
                                            } else {
                                                echo '0.00'; // Handle the case where $wardCount is zero
                                            }
                                        @endphp %
                                    </b>
                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-lga"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.members.byLga', $uuid)}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                            <!-- ./col -->
                            <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box bg-info">
                                <div class="inner">
                                <h3>{{$wardWithUsersCount}}</h3>
                                  <p>Out of <strong>{{$wardCount}}</strong> Wards in {{$SystemSetting->system_country}}</p>
                                  <p style="float: right;">
                                    <b>
                                        @php
                                            if ($wardCount > 0) {
                                                echo number_format(($wardWithUsersCount / $wardCount) * 100, 2);
                                            } else {
                                                echo '0.00'; // Handle the case where $wardCount is zero
                                            }
                                        @endphp %
                                    </b>
                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-ward"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.members.byWard', $uuid)}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                            <!-- ./col -->
                            <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box bg-danger">
                                <div class="inner">
                                <h3>{{$puWithUsersCount}}</h3>
                                  <p>Out of <strong>{{$puCount}}</strong> PUs in {{$SystemSetting->system_country}}</p>
                                  <p style="float: right;">
                                    <b>
                                        @php
                                            if ($puCount > 0) {
                                                echo number_format(($puWithUsersCount / $puCount) * 100, 2);
                                            } else {
                                                echo '0.00'; // Handle the case where $wardCount is zero
                                            }
                                        @endphp %
                                    </b>
                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-pu"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.members.byPu', $uuid)}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                            <!-- ./col -->
                  </div><!-- /.row -->

                  <!-- Small boxes (Stat box) -->
                  <div class="row">
                            <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box bg-danger">
                                <div class="inner">
                                <h3>{{$regionsWithUsersCount}}</h3>
                                  <p>Out of <strong>{{$regionsCount}}</strong> Regions in {{$SystemSetting->system_country}}</p>
                                  <p style="float: right;">
                                        <b>@php
                                        echo number_format(($regionsWithUsersCount / $regionsCount) * 100, 2);
                                          @endphp %</b>
                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-map"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.members.region')}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                            <!-- ./col -->
                            <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box bg-secondary">
                                <div class="inner">
                                  <h3>{{$excoCount}}</h3>

                                  <p>Executive Members</p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-person"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.leaders', $uuid)}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                            <!-- ./col -->
                            <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box bg-primary">
                                <div class="inner">
                                  <h3>{{$memberCount}}</h3>
                                  <p>Regular Members</p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-person-add"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.regulars', $uuid)}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                            <!-- ./col -->
                            <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box bg-success">
                                <div class="inner">
                                  <h3>{{$userCount}}</h3>

                                  <p>All Members</p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-person"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.members', $uuid)}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div> <!-- ./col -->
                  </div><!-- /.row -->
          </div> <!-- /.card-body -->
</div>

<!-- Demographics -->
<div class="row">
        <div class="col-md-6">
        <!-- PIE CHART -->
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">MEMBER DISTRIBUTION BY GENDER</h3>

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
                <h3 class="card-title">MEMBER DISTRIBUTION BY AGE BRACKET</h3>

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
<div class="row">
    <div class="col-md-12">
    <!-- PIE CHART -->
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title"> MEMBER DISTRIBUTION BY GEOPOLITICAL REGIONS</h3>

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



<!-- Member MEMBER distribution by Religion and Voter Eligibility -->
 <div class="row">
        <div class="col-md-6">
                <!-- PIE CHART -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">MEMBER DISTRIBUTION BY VOTE ELIGIBILITY</h3>

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
          <h3 class="card-title">MEMBER DISTRIBUTION BY RELIGION</h3>

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
<div class="row">

    <div class="col-md-12">
    <!-- DONUT CHART -->
      <div class="card card-primary ">
          <div class="card-header">
          <h3 class="card-title">MEMBER DISTRIBUTION BY STATES</h3>

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



<!-- Add this within the <head> section of your HTML -->

<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>

<!-- AdminLTE ChartJS -->
<script src="{{asset('assets/plugins/chart.js/Chart.min.js')}}"></script>

<!-- MEMBER Distribution By Gender -->
<script>
    // Get the canvas element and create a pie chart
    var pieChartCanvas = document.getElementById('pieChart').getContext('2d');
    var pieChart;

    function updatePieChart() {
        $.ajax({
            url: '/national/member/distribution/gender', // Update with your route URL
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

<!-- MEMBER Distribution By Age Grade -->
<script>
    var barChartCanvas = document.getElementById('barChart').getContext('2d');
    var barChart;

    function updateBarChart() {
        $.ajax({
            url: '/national/member/distribution/age', // Update with your route URL
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

<!-- Regional Distribution of member -->

<script>
    var donutChartCanvas = document.getElementById('donutChart').getContext('2d');
    var donutChart;

    function updateDonutChart() {
        $.ajax({
            url: '/national/member/distribution/region', // Update with your route URL
            method: 'GET',
            success: function(data) {
                if (donutChart) {
                    donutChart.destroy();
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

<!-- Religion Distribution of Member -->

<script>
    var religionBarChartCanvas = document.getElementById('religionBarChart').getContext('2d');
    var religionBarChart;

    function updateReligionBarChart() {
        $.ajax({
            url: '/national/member/distribution/religion', // Update with your route URL
            method: 'GET',
            success: function(data) {
                if (religionBarChart) {
                    religionBarChart.destroy();
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

<!-- Voter Eligibility -->
<script>
    var validVoterPieChartCanvas = document.getElementById('validVoterPieChart').getContext('2d');
    var validVoterPieChart;

    function updateValidVoterPieChart() {
        $.ajax({
            url: '/national/member/distribution/voter', // Update with your route URL
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


<!-- State Distribution -->
<script>
    var stateDonutChartCanvas = document.getElementById('stateDonutChart').getContext('2d');
    var stateDonutChart;

    function updateStateDonutChart() {
        $.ajax({
            url: '/national/member/distribution/state', // Update with your route URL
            method: 'GET',
            success: function(data) {
                if (stateDonutChart) {
                    stateDonutChart.destroy();
                }
                // Calculate total count to calculate percentages
                var totalCount = data.datasets[0].data.reduce((a, b) => a + b, 0);
                // Calculate and update the labels with percentages
                data.labels = data.labels.map((label, index) => `${label} (${((data.datasets[0].data[index] / totalCount) * 100).toFixed(2)}%)`);
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

<!-- Volunteer Group Distribution -->
<script>
    var volunteerGroupDonutChartCanvas = document.getElementById('volunteerGroupDonutChart').getContext('2d');
    var volunteerGroupDonutChart;

    function updateVolunteerGroupDonutChart() {
        $.ajax({
            url: '/admin/member/distribution/volunteergroup', // Update with your route URL
            method: 'GET',
            success: function(data) {

                if (volunteerGroupDonutChart) {
                    volunteerGroupDonutChart.destroy();
                }
                // Calculate total count to calculate percentages
                var totalCount = data.datasets[0].data.reduce((a, b) => a + b, 0);
                // Calculate and update the labels with percentages
                data.labels = data.labels.map((label, index) => `${label} (${((data.datasets[0].data[index] / totalCount) * 100).toFixed(2)}%)`);
                volunteerGroupDonutChart = new Chart(volunteerGroupDonutChartCanvas, {
                    type: 'pie', // Use 'doughnut' for a donut chart
                    data: data,
                    options: {
                        responsive: true,
                        //cutoutPercentage: 20, // Make the chart doughnut hole 20% of the radius
                        maintainAspectRatio: false,
                        legend: {
                            position: 'right' // Position the legend to the right
                        }
                        // Add more options as needed
                    }
                });
            },
            error: function(error) { // Add error handling
                console.error('Error:', error);
            }
        });
    }

    // Call the function to initialize the donut chart
    updateVolunteerGroupDonutChart();
</script>

@endsection
