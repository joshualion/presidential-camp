@extends('backend.template.backend-master')
@section('content')

@php
 $state = \App\Models\State::find(Auth::user()->state_id);
@endphp

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">  {{optional($state)->name }} State  Members By LGA</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.member.add') }}"><button class="btn btn-default"> <i class="fa fa-plus-square"></i> Add New Member</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table id="mylgaMembers" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>LGA</th>
                                        <th>Members</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lgas as $lga)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td><strong>{{ $lga->name }}</strong></td>
                                                <td>
                                                    <span class="badge bg-secondary text-white">
                                                        {{ number_format($lga->members_count ?? 0) }}
                                                    </span>

                                                </td>
                                                <td>
                                                    <a href="{{ route($profileData->access_level.'.member.lga.view', $lga->uuid) }}"><button class="btn btn-secondary"><i class="fas fa-eye"></i> View Members</button></a>

                                                    <a href="{{ route($profileData->access_level.'.location.lga.dashboard', $lga->uuid) }}" > <button class="btn btn-success "><i class="fas fa-chart-line"></i> Goto LGA Dashboard</button></a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>


                                    <tfoot>
                                    <tr>
                                        <th>S/N</th>
                                        <th>LGA</th>
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
            var dataTable = $("#mylgaMembers").DataTable({
                "responsive": true,
                "autoWidth": true,
                "paging": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "pageLength": 25, // Default number of rows per page
                "lengthMenu": [ [10, 25, 50, 100], [10, 25, 50, 100] ],
            });

            dataTable.buttons().container().appendTo('#mylgaMembers_wrapper .col-md-6:eq(0)').addClass('datable-print-buttons'); // Add this line to add the 'text-center' class
        });
    </script>






@endsection
