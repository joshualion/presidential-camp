@extends('backend.template.backend-master')
@section('content')


<div class="card card-primary">
              <div class="card-header">
                    <h3 class="card-title">{{$ward->name}} Ward Statistics</h3>
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
                     <!-- col -->
                    <div class="col-lg-4 col-4">
                        <!-- small box -->
                        <div class="small-box bg-success">
                          <div class="inner">
                          <h3>{{$puWithUsersCount}}</h3>
                            <p>Out of <strong>{{$puCount}}</strong> PUs in {{$ward->name}}</p>
                            <p style="float: right;">
                              <b>
                                  @php
                                      if ($puCount > 0) {
                                          echo number_format(($puWithUsersCount / $puCount) * 100, 2);
                                      } else {
                                          echo '0.00'; // Handle the case where $puCount is zero
                                      }
                                  @endphp %
                              </b>
                            </p>
                          </div>
                          <div class="icon">
                            <i class="ion ion-pu"></i>
                          </div>
                          <a href="{{route($profileData->access_level.'.members.byPu', $ward->uuid)}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->

                     <!-- /col -->
                    <div class="col-lg-4 col-4">
                              <!-- small box -->
                              <div class="small-box bg-primary">
                                <div class="inner">
                                <h3>{{$eligibleVotersCount}}</h3>
                                  <p>Eligible Voters in {{$ward->name}} Ward</p>
                                  <p style="float: right;">
                                    <b>
                                        @php
                                            if ($userCount> 0) {
                                                echo number_format(($eligibleVotersCount / $userCount) * 100, 2);
                                            } else {
                                                echo '0.00'; // Handle the case where $puCount is zero
                                            }
                                        @endphp %
                                    </b>
                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-pu"></i>
                                </div>
                                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                    </div>
                            <!-- ./col -->


                      <!-- /col -->
                    <div class="col-lg-4 col-4">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                          <div class="inner">
                          <h3>{{$inEligibleVotersCount}}</h3>
                            <p>Ineligible voters in {{$ward->name}} Ward</p>
                            <p style="float: right;">
                              <b>
                                  @php
                                      if ($userCount> 0) {
                                          echo number_format(($inEligibleVotersCount / $userCount) * 100, 2);
                                      } else {
                                          echo '0.00'; // Handle the case where $puCount is zero
                                      }
                                  @endphp %
                              </b>
                            </p>
                          </div>
                          <div class="icon">
                            <i class="ion ion-pu"></i>
                          </div>
                          <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                      </div>
                      <!-- ./col -->


                    </div>
                    <!-- ./col -->


                  <!-- Small boxes (Stat box) -->
                  <div class="row">
                            <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box bg-danger">
                                <div class="inner">
                                <h3>{{ $newMembers }}</h3>
                                  <p>New Member(s) Today</p>
                                  <p style="float: right;">

                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-map"></i>
                                </div>
                                <a href="{{ route($profileData->access_level.'.peopleMetric', ['metric' => 'new-today', 'uuid' => $ward->uuid]) }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                            <!-- ./col -->
                            <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box bg-secondary">
                                <div class="inner">
                                  <h3>{{$excoCount}}</h3>

                                  <p>Coordinators / Admins</p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-person"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.leaders', $ward->uuid)}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
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
                                <a href="{{route($profileData->access_level.'.regulars', $ward->uuid)}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
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
                                <a href="{{route($profileData->access_level.'.member.ward.view', $ward->uuid)}}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div> <!-- ./col -->
                  </div><!-- /.row -->
          </div> <!-- /.card-body -->
</div>


@if(!empty($dashboardMetrics))
@include('backend.shared.dashboard-election-stat-section', ['dashboardMetrics' => $dashboardMetrics])
@endif

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
</div>
<!-- End Demographics -->




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
            url:"{{route($profileData->access_level.'.ward.member.distribution.gender',$ward->uuid)}}", // Ensure URL is a string
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


<!-- Voter Eligibility -->
<script>
    var validVoterPieChartCanvas = document.getElementById('validVoterPieChart').getContext('2d');
    var validVoterPieChart;

    function updateValidVoterPieChart() {
        $.ajax({
            url: "{{route($profileData->access_level.'.ward.member.distribution.voter', $ward->uuid)}}", // Update with your route URL
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



@endsection
