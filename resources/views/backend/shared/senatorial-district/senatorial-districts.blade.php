<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">All Senatorial Districts - <span class="badge badge-light">{{ number_format($districts->count(), 0) }}</span></h3>
                        @if(app(\App\Services\StructuralLocationAccessService::class)->canCreateOrDelete($profileData))
                            <div class="card-tools">
                                <a href="{{ route($profileData->access_level.'.location.senatorial-district.add') }}"><button class="btn btn-default"> <i class="fa fa-plus-square"></i> Add Senatorial District</button></a>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <table id="mysenatorialdistricts" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Name</th>
                                <th>State</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                            <tr>
                                <th>S/N</th>
                                <th>Name</th>
                                <th>State</th>
                                <th>Action</th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (event) {
            if (event.target.classList.contains('delete-btn')) {
                event.preventDefault();
                const form = event.target.closest('form');

                Swal.fire({
                    title: 'Confirm Delete',
                    text: 'Are you sure you want to delete this Senatorial District?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            }
        });
    });
</script>

<script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#mysenatorialdistricts').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": "{{ route('senatorial-districts.data') }}",
            "pageLength": 10,
            "searching": true,
            "lengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
            "columns": [
                {
                    "data": null,
                    "render": function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                    "orderable": false
                },
                { "data": "name", "searchable": true },
                { "data": "state", "searchable": true },
                { "data": "action", "orderable": false, "searchable": false }
            ],
            "responsive": true,
            "autoWidth": false,
        });
    });
</script>
