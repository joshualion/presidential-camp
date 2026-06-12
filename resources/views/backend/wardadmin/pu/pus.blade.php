@extends('backend.template.backend-master')
@section('content')

    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">All Polling Units - <span class="badge badge-light">{{ $puCount }} </span></h3>
                    @if(app(\App\Services\StructuralLocationAccessService::class)->canCreateOrDelete($profileData))
                        <div class="card-tools">
                            <a href="{{ route($profileData->access_level.'.location.pu.add') }}"><button class="btn btn-default"> <i class="fa fa-plus-square"></i> Add Polling Unit</button></a>
                            <a href="{{route($profileData->access_level.'.location.pu.import')}}"><button class="btn btn-dark"> <i class="fa fa-file-excel"></i> Import Polling Units</button></a>
                        </div>
                    @endif
                </div>
                <!-- /.card-header -->
                <div class="card-body">

            <table id="mypus" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Name</th>
                        <th>Ward</th>
                        <th>LGA</th>
                        <th>State</th>
                        <th>Region</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                    <tfoot>
                    <tr>
                        <th>S/N</th>
                        <th>Name</th>
                        <th>Ward</th>
                        <th>LGA</th>
                        <th>State</th>
                        <th>Region</th>
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


<!-- Page delete confirmation script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
    // Use event delegation on a parent container like 'body' to capture dynamically added elements
    document.body.addEventListener('click', function (event) {
        if (event.target.classList.contains('delete-btn')) {
            event.preventDefault();

            const button = event.target;
            const form = button.closest('form');
            const hasAssociatedUsers = form.getAttribute('data-associated-users') === 'true';

            if (hasAssociatedUsers) {
                Swal.fire({
                    title: 'Cannot Delete Polling Unit',
                    text: 'This Polling Unit cannot be deleted because it has associated users.',
                    icon: 'warning',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({
                    title: 'Confirm Delete',
                    text: 'Are you sure you want to delete this Polling Unit?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit(); // Submit the form after confirmation
                    }
                });
            }
        }
    });
});
</script>
<!-- Page specific data table script -->
<!-- jQuery -->
<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>


<script>

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

 $(document).ready(function() {
    $('#mypus').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{{ route('pus.data') }}", // Define a route to get the paginated data
        "pageLength": 25, // Default number of rows per page
        "searching": true,
        "lengthMenu": [ [10, 25, 50, 100], [10, 25, 50, 100] ],
        "columns": [
            {
                "data": null, // No specific data for this column
                "render": function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1; // Create the serial number dynamically
                },
                "orderable": false
            },
            { "data": "name", "searchable": true },
            { "data": "ward", "searchable": true },
            { "data": "lga", "searchable": true },
            { "data": "state", "searchable": true },
            { "data": "region", "searchable": true }, // Fix the column name here, it should match the one returned in JSON
            { "data": "action", "orderable": false, "searchable": false }
        ],
        "responsive": true,
        "autoWidth": false,
    });
});
</script>






@endsection



