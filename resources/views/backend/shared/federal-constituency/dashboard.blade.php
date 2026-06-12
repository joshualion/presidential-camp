@extends('backend.template.backend-master')
@section('content')

@php
    $eligibleVotersCount = $eligibleVotersCount ?? \App\Models\User::where('federal_constituency_id', $constituency->id)
        ->where('access_level', '!=', 'superadmin')
        ->whereIn('validVoter', ['yes', 'Yes', 'YES'])
        ->count();
    $inEligibleVotersCount = $inEligibleVotersCount ?? max(0, $userCount - $eligibleVotersCount);
@endphp

<div class="card card-primary">
              <div class="card-header">
                    <h3 class="card-title">{{$constituency->name}} Federal Constituency Statistics</h3>
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

                  <div class="row dashboard-stats-grid align-items-stretch">
                            <div class="col-xl-3 col-md-6 col-12 d-flex mb-3">
                              <div class="small-box bg-success h-100 w-100 d-flex flex-column">
                                <div class="inner">
                                  <h3>{{$lgaWithUsersCount}}</h3>
                                  <p>Out of <strong>{{$lgaCount}}</strong> LGAs in {{$constituency->name}}</p>
                                  <p style="float: right;">
                                    <b>
                                        @php
                                            echo $lgaCount > 0 ? number_format(($lgaWithUsersCount / $lgaCount) * 100, 2) : '0.00';
                                        @endphp %
                                    </b>
                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-lga"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.members.byLga', $constituency->uuid)}}" class="small-box-footer mt-auto">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>

                            <div class="col-xl-3 col-md-6 col-12 d-flex mb-3">
                              <div class="small-box bg-info h-100 w-100 d-flex flex-column">
                                <div class="inner">
                                <h3>{{$wardWithUsersCount}}</h3>
                                  <p>Out of <strong>{{$wardCount}}</strong> Wards in {{$constituency->name}}</p>
                                  <p style="float: right;">
                                    <b>
                                        @php
                                            echo $wardCount > 0 ? number_format(($wardWithUsersCount / $wardCount) * 100, 2) : '0.00';
                                        @endphp %
                                    </b>
                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-ward"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.members.byWard', $constituency->uuid)}}" class="small-box-footer mt-auto">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>

                            <div class="col-xl-3 col-md-6 col-12 d-flex mb-3">
                              <div class="small-box bg-danger h-100 w-100 d-flex flex-column">
                                <div class="inner">
                                <h3>{{$puWithUsersCount}}</h3>
                                  <p>Out of <strong>{{$puCount}}</strong> PUs in {{$constituency->name}}</p>
                                  <p style="float: right;">
                                    <b>
                                        @php
                                            echo $puCount > 0 ? number_format(($puWithUsersCount / $puCount) * 100, 2) : '0.00';
                                        @endphp %
                                    </b>
                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-pu"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.members.byPu', $constituency->uuid)}}" class="small-box-footer mt-auto">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                            <div class="col-xl-3 col-md-6 col-12 d-flex mb-3">
                              <div class="small-box bg-secondary h-100 w-100 d-flex flex-column">
                                <div class="inner">
                                  <h3>{{$excoCount}}</h3>
                                  <p>Coordinators / Admins</p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-person"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.leaders', $constituency->uuid)}}" class="small-box-footer mt-auto">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>

                            <div class="col-xl-3 col-md-6 col-12 d-flex mb-3">
                              <div class="small-box bg-primary h-100 w-100 d-flex flex-column">
                                <div class="inner">
                                  <h3>{{$memberCount}}</h3>
                                  <p>Regular Members</p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-person-add"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.regulars', $constituency->uuid)}}" class="small-box-footer mt-auto">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>

                            <div class="col-xl-3 col-md-6 col-12 d-flex mb-3">
                              <div class="small-box bg-success h-100 w-100 d-flex flex-column">
                                <div class="inner">
                                  <h3>{{$userCount}}</h3>
                                  <p>All Members</p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-person"></i>
                                </div>
                                <a href="{{route($profileData->access_level.'.members', $constituency->uuid)}}" class="small-box-footer mt-auto">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                            <div class="col-xl-3 col-md-6 col-12 d-flex mb-3">
                              <div class="small-box bg-success h-100 w-100 d-flex flex-column">
                                <div class="inner">
                                  <h3>{{ number_format($eligibleVotersCount) }} <small>of {{ number_format($userCount) }}</small></h3>
                                  <p>Eligible Voters</p>
                                  <p style="float: right;">
                                    <b>{{ $userCount > 0 ? number_format(($eligibleVotersCount / $userCount) * 100, 2) : '0.00' }}%</b>
                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="fas fa-id-card"></i>
                                </div>
                                <a href="{{ route($profileData->access_level.'.peopleMetric', ['metric' => 'eligible-voters', 'uuid' => $constituency->uuid]) }}" class="small-box-footer mt-auto">Voter readiness <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>

                            <div class="col-xl-3 col-md-6 col-12 d-flex mb-3">
                              <div class="small-box bg-danger h-100 w-100 d-flex flex-column">
                                <div class="inner">
                                  <h3>{{ number_format($inEligibleVotersCount) }} <small>of {{ number_format($userCount) }}</small></h3>
                                  <p>Without Voter Card</p>
                                  <p style="float: right;">
                                    <b>{{ $userCount > 0 ? number_format(($inEligibleVotersCount / $userCount) * 100, 2) : '0.00' }}%</b>
                                  </p>
                                </div>
                                <div class="icon">
                                  <i class="fas fa-user-times"></i>
                                </div>
                                <a href="{{ route($profileData->access_level.'.peopleMetric', ['metric' => 'without-voter-card', 'uuid' => $constituency->uuid]) }}" class="small-box-footer mt-auto">Follow-up required <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                            <div class="col-xl-3 col-md-6 col-12 d-flex mb-3">
                              <div class="small-box bg-danger h-100 w-100 d-flex flex-column">
                                <div class="inner">
                                <h3>{{ $newMembers }}</h3>
                                  <p>New Member(s) Today</p>
                                  <p style="float: right;"></p>
                                </div>
                                <div class="icon">
                                  <i class="ion ion-map"></i>
                                </div>
                                <a href="{{ route($profileData->access_level.'.peopleMetric', ['metric' => 'new-today', 'uuid' => $constituency->uuid]) }}" class="small-box-footer mt-auto">More info <i class="fas fa-arrow-circle-right"></i></a>
                              </div>
                            </div>
                  </div>
          </div>
</div>

@if(!empty($dashboardMetrics))
@include('backend.shared.dashboard-election-stat-section', ['dashboardMetrics' => $dashboardMetrics])
@endif

<!-- Demographics -->
<div class="row">
    <div class="col-md-6">
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
          <div class="card-body" style="display: block;">
            <canvas id="pieChart" style="min-height: 250px; height: 241px; max-height: 250px; max-width: 100%; display: block; width: 470px;" width="470" height="241" class="chartjs-render-monitor"></canvas>
          </div>
        </div>
    </div>

  <div class="col-md-6">
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
            <div class="chart">
              <canvas id="barChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%; display: block; width: 487px;" width="487" height="250" class="chartjs-render-monitor"></canvas>
            </div>
          </div>
      </div>

       </div>
  </div>

<div class="row">
    <div class="col-md-6">
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
            <div class="card-body" style="display: block;">
            <canvas id="validVoterPieChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%; display: block; width: 487px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-6">
            <div class="card card-primary ">
                <div class="card-header">
                <h3 class="card-title">DISTRIBUTION BY LGA</h3>

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
                    <div class="chart">
                    <canvas id="lgaDonutChart" style="min-height: 250px; height: 241px; max-height: 250px; max-width: 100%; display: block; width: 470px;" width="470" height="241" class="chartjs-render-monitor"></canvas>
                    </div>
                </div>
            </div>
            </div>
    </div>


<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>
<script src="{{asset('assets/plugins/chart.js/Chart.min.js')}}"></script>

<script>
    var pieChartCanvas = document.getElementById('pieChart').getContext('2d');
    var pieChart;

    function updatePieChart() {
        $.ajax({
            url:"{{route($profileData->access_level.'.federal-constituency.member.distribution.gender',$constituency->uuid)}}",
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
                                    var total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(2) : '0.00';
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        },
                    }
                });
            }
        });
    }

    updatePieChart();
</script>

<script>
    var barChartCanvas = document.getElementById('barChart').getContext('2d');
    var barChart;

    function updateBarChart() {
        $.ajax({
            url: "{{route($profileData->access_level.'.federal-constituency.member.distribution.age', $constituency->uuid)}}",
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
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        },
                        indexAxis: 'y',
                    }
                });
            }
        });
    }

    updateBarChart();
</script>

<script>
    var validVoterPieChartCanvas = document.getElementById('validVoterPieChart').getContext('2d');
    var validVoterPieChart;

    function updateValidVoterPieChart() {
        $.ajax({
            url: "{{route($profileData->access_level.'.federal-constituency.member.distribution.voter', $constituency->uuid)}}",
            method: 'GET',
            success: function(data) {
                if (validVoterPieChart) {
                    validVoterPieChart.destroy();
                }
                validVoterPieChart = new Chart(validVoterPieChartCanvas, {
                    type: 'pie',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            position: 'bottom'
                        }
                    }
                });
            }
        });
    }

    updateValidVoterPieChart();
</script>

<script>
    var lgaDonutChartCanvas = document.getElementById('lgaDonutChart').getContext('2d');
    var lgaDonutChart;

    function updatelgaDonutChart() {
        $.ajax({
            url: "{{route($profileData->access_level.'.federal-constituency.member.distribution.lga', $constituency->uuid)}}",
            method: 'GET',
            success: function(data) {
                if (lgaDonutChart) {
                    lgaDonutChart.destroy();
                }
                var totalCount = data.datasets[0].data.reduce((a, b) => a + b, 0);
                data.labels = data.labels.map((label, index) => `${label} (${totalCount > 0 ? ((data.datasets[0].data[index] / totalCount) * 100).toFixed(2) : '0.00'}%)`);
                lgaDonutChart = new Chart(lgaDonutChartCanvas, {
                    type: 'doughnut',
                    data: data,
                    options: {
                        responsive: true,
                        cutoutPercentage: 20,
                        maintainAspectRatio: false,
                        legend: {
                            position: 'right'
                        }
                    }
                });
            }
        });
    }

    updatelgaDonutChart();
</script>

@endsection
