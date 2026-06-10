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
                        All Regular Members
                        @if(isset($region) || isset($state) || isset($lga) || isset($ward) || isset($pu))
                            in
                            @if(isset($region))
                                {{ $region->name }} Region
                            @elseif(isset($state))
                                {{ $state->name }} State
                            @elseif(isset($lga))
                                {{ $lga->name }} LGA
                            @elseif(isset($ward))
                                {{ $ward->name }} Ward
                            @elseif(isset($pu))
                                {{ $pu->name }} Polling Unit
                            @endif
                        @endif

                    </h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.member.add') }}"><button class="btn btn-default"> <i class="fa fa-plus-square"></i> Add New Member</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table id="myMembers" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Name</th>
                                        <th>Access</th>
                                        <th>Role</th>
                                        <th>Eligible Voter</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>


                                    <tfoot>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Name</th>
                                        <th>Access</th>
                                        <th>Role</th>
                                        <th>Eligible Voter</th>
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
                    title: 'Cannot Delete Member',
                    text: 'This member cannot be deleted because it has associated users.',
                    icon: 'warning',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({
                    title: 'Confirm Delete',
                    text: 'Are you sure you want to delete this member?',
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
 $(document).ready(function() {
    $('#myMembers').DataTable({
        "processing": true,
        "serverSide": true,
        "searching": true,
        "ajax": "{{ route($profileData->access_level.'.regulars.data', $PuLgaStateRegionCountryUUID) }}", // Define a route to get the paginated data
        "pageLength": 25, // Default number of rows per page
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
            { "data": "access_level" },
            { "data": "role" }, // Fix the column name here, it should match the one returned in JSON
            { "data": "Voter_status" },
            { "data": "action", "orderable": false, "searchable": false }
        ],
        "responsive": true,
        "autoWidth": false,
    });
     // Set placeholder for the search box
     $('#myMembers_filter input').attr('placeholder', 'firstname or lastname');
});
</script>







@endsection
