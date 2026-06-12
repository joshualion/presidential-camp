@extends('backend.template.backend-master')
@section('content')



    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Members By Senatorial District</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.member.add') }}"><button class="btn btn-default"> <i class="fa fa-plus-square"></i> Add New Member</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table id="mySenatorialDistrictMembers" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>State</th>
                                        <th>Senatorial District</th>
                                        <th>Members</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($districts as $district)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ optional($district->state)->name }}</td>
                                                <td><strong>{{ $district->name }}</strong></td>
                                                <td>
                                                    <span class="badge bg-secondary text-white">
                                                        {{ number_format($district->members_count ?? 0) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route($profileData->access_level.'.members', $district->uuid) }}"><button class="btn btn-secondary"><i class="fas fa-eye"></i> View Members</button></a>
                                                    <a href="{{ route($profileData->access_level.'.location.senatorial-district.dashboard', $district->uuid) }}"><button class="btn btn-success"><i class="fas fa-chart-line"></i> Senatorial District Dashboard</button></a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>


                                    <tfoot>
                                    <tr>
                                        <th>S/N</th>
                                        <th>State</th>
                                        <th>Senatorial District</th>
                                        <th>Members</th>
                                        <th>Action</th>
                                    </tr>
                                    </tfoot>
                    </table>

                </div>
                <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
        </section>
    <!-- /.content -->


    <!-- jQuery -->
    <script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>
    <script>
    $(document).ready(function() {
        var dataTable = $("#mySenatorialDistrictMembers").DataTable({
            "responsive": true,
            "autoWidth": true,
            "paging": true,
            "searching": true,
            "ordering": false,
            "info": true,
        });

        dataTable.buttons().container().appendTo('#mySenatorialDistrictMembers_wrapper .col-md-6:eq(0)').addClass('datable-print-buttons');
    });
    </script>




@endsection
