@extends('backend.template.backend-master')
@section('content')


    <!-- Main content -->
<section class="content">
    <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            @if($profileData->access_level == 'superadmin' || $profileData->access_level == 'nationaladmin')
                            Nationwide Results Summary
                        @elseif($profileData->access_level == 'regionaladmin')
                            Regional Results Summary for {{ $profileData->region->name ?? 'Your Region' }}
                        @elseif($profileData->access_level == 'stateadmin')
                            State Results Summary for {{ $profileData->state->name ?? 'Your State' }}
                        @elseif($profileData->access_level == 'lgaadmin')
                            Local Government Area (LGA) Results Summary for {{ $profileData->lga->name ?? 'Your LGA' }}
                        @elseif($profileData->access_level == 'wardadmin')
                            Ward Results Summary for {{ $profileData->ward->name ?? 'Your Ward' }}
                        @elseif($profileData->access_level == 'pollingunitadmin')
                            Polling Unit Results Summary for {{ $profileData->pollingUnit->name ?? 'Your Polling Unit' }}
                        @endif

                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-tool" data-card-widget="remove">
                                <i class="fas fa-times"></i>
                            </button>

                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">

                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>Party Name</th>
                                    <th>Votes Obtained</th>
                                    <th>Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summary as $item)
                                <tr>
                                    <td>{{ $item['sn'] }}</td>
                                    <td>{{ $item['party_name'] }}</td>
                                    <td>{{ number_format($item['total_votes']) }}</td>
                                    <td>{{ $item['comment'] }}</td>
                                </tr>
                                @endforeach

                            </tbody>
                        </table>


                    </div>
                    <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
            </div>
            <!-- /.row -->

            <div class="row">
                <!-- BAR CHART -->
                <div class="col-12">
                    <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    @if($profileData->access_level == 'superadmin' || $profileData->access_level == 'nationaladmin')
                                    Nationwide Votes Distribution
                                @elseif($profileData->access_level == 'regionaladmin')
                                    Regional Votes Distribution for {{ $profileData->region->name ?? 'Your Region' }}
                                @elseif($profileData->access_level == 'stateadmin')
                                    State Votes Distribution for {{ $profileData->state->name ?? 'Your State' }}
                                @elseif($profileData->access_level == 'lgaadmin')
                                    Local Government Area (LGA) Votes Distribution for {{ $profileData->lga->name ?? 'Your LGA' }}
                                @elseif($profileData->access_level == 'wardadmin')
                                    Ward Votes Distribution for {{ $profileData->ward->name ?? 'Your Ward' }}
                                @elseif($profileData->access_level == 'pollingunitadmin')
                                    Polling Unit Votes Distribution for {{ $profileData->pollingUnit->name ?? 'Your Polling Unit' }}
                                @endif
                                </h3>
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
                                            <canvas id="ResultBarChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%; display: block; width: 487px;" width="487" height="250" class="chartjs-render-monitor"></canvas>
                                        </div>
                            </div>
                    </div>
                </div><!-- /.col  -->
           </div>
           <!-- /.row -->

    </div>
        <!-- /.container-fluid -->
</section>
    <!-- /.content -->


<!-- AdminLTE ChartJS -->
<script src="{{ asset('assets/plugins/chart.js/Chart.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartUrl = "{{ route('election.chart.data', $election->uuid) }}";

        // Fetch chart data via AJAX
        fetch(chartUrl)
            .then(response => response.json())
            .then(data => {
                // Extract labels and dataset from the JSON response
                const labels = data.labels;
                const dataset = data.datasets[0]; // Access the first dataset
                
                // Render the bar chart
                const ctx = document.getElementById('ResultBarChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: dataset.label,
                            data: dataset.data,
                            backgroundColor: dataset.backgroundColor,
                            borderColor: dataset.borderColor,
                            borderWidth: dataset.borderWidth,
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching chart data:', error);
            });
    });
</script>



@endsection




