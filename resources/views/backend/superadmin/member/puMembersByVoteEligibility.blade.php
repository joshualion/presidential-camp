@extends('backend.template.backend-master')
@section('content')


    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Eligible Members By Polling Unit</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.member.add') }}"><button class="btn btn-default"> <i class="fa fa-plus-square"></i> Add New Member</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table id="myPuMembers" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>PU</th>
                                        <th>Members</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>



                                    </tbody>


                                    <tfoot>
                                    <tr>
                                        <th>S/N</th>
                                        <th>PU</th>
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
       $('#myPuMembers').DataTable({
           "processing": true,
           "serverSide": true,
           "searching": true,
           "ajax": "{{ route($profileData->access_level.'.eligible.members.byPu.ajax', $WardLgaStateRegionCountryUUID ?? null) }}"

           "pageLength": 25, // Default number of rows per page
           "lengthMenu": [ [10, 25, 50, 100], [10, 25, 50, 100] ],
           "columns": [
               {
                   "data": null, // No specific data for this column
                   "render": function (data, type, row, meta) {
                       return meta.row + meta.settings._iDisplayStart + 1; // Create the serial number dynamically
                   },
                   "orderable": false,
                   "searchable": false // Set to false because it’s just an index
               },

               { "data": "pu", "searchable": true },
               { "data": "members", "searchable": false },
               { "data": "action", "orderable": false, "searchable": false }
           ],
           "responsive": true,
           "autoWidth": false,
       });
   });
</script>

@endsection
