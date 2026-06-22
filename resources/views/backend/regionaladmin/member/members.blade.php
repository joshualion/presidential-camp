@extends('backend.template.backend-master')
@section('content')



    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">All Members</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.member.add') }}"><button class="btn btn-default"> <i class="fa fa-plus-square"></i> Add New Member</button></a>
                        @include('backend.shared.member.import-button')
                        <button class="btn btn-success" data-toggle="modal" data-target="#exportModal">
                            <i class="fa fa-file-excel"></i> Export Members
                        </button>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table id="myMembers" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Name</th>
                                        <th>Access Level</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>


                                    <tfoot>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Name</th>
                                        <th>Access Level</th>
                                        <th>Role</th>
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
        "ajax": "{{ route($profileData->access_level.'.members.data', $PuLgaStateRegionCountryUUID) }}", // Define a route to get the paginated data
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
            { "data": "action", "orderable": false, "searchable": false }
        ],
        "responsive": true,
        "autoWidth": false,
    });
     // Set placeholder for the search box
     $('#myMembers_filter input').attr('placeholder', 'firstname or lastname');
});
</script>



<div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('members.export') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Select Columns to Export</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="row">
            @php
              $availableColumns = [
                'firstname',
                'lastname',
                'email',
                'phone',
                'gender',
                'vin',
                'role',        // 👈 custom (will be resolved via relation)
                'region',      // 👈 resolved via relation
                'state',      // 👈 resolved via relation
                'lga',        // 👈 resolved via relation
                'ward',      // 👈 resolved via relation
                'polling_unit',  // 👈 resolved via relation
                'occupation',
                'qualification',
                'bank',
                'bank_account_number',
                'status',
              ];
            @endphp
            @foreach($availableColumns as $col)
              <div class="col-md-4">
                <label>
                  <input type="checkbox" name="headers[]" value="{{ $col }}"> {{ ucfirst(str_replace('_', ' ', $col)) }}
                </label>
              </div>
            @endforeach
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Export</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>




@endsection
