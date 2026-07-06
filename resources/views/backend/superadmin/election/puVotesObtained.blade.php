@extends('backend.template.backend-master')
@section('content')

@php
    $ajaxUrl = route($profileData->access_level.'.election.votesByPuData', $election->uuid);
@endphp


    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        @if($profileData->access_level == 'superadmin' || $profileData->access_level == 'nationaladmin')
                        Campaignwide Votes By PU
                    @elseif($profileData->access_level == 'regionaladmin')
                        Regional Votes By PU for {{ $profileData->region->name ?? 'Your Region' }}
                    @elseif($profileData->access_level == 'stateadmin')
                        State Votes By PU for {{ $profileData->state->name ?? 'Your State' }}
                    @elseif($profileData->access_level == 'lgaadmin')
                        Local Government Area (LGA) Votes By PU for {{ $profileData->lga->name ?? 'Your LGA' }}
                    @elseif($profileData->access_level == 'wardadmin')
                        Ward Votes By PU for {{ $profileData->ward->name ?? 'Your Ward' }}
                    @elseif($profileData->access_level == 'pollingunitadmin')
                        Votes  for {{ $profileData->pollingUnit->name ?? 'Your Polling Unit' }}
                    @endif

                    </h3>
                    <div class="card-tools">
                        @if(app(\App\Services\PollingUnitResultPermissionService::class)->userHasAnyApprovedAssignment($profileData))
                        <a href="{{ route($profileData->access_level.'.vote.add') }}"><button class="btn btn-default btn-sm"> <i class="fa fa-plus-square"></i> Add Vote Records </button></a>
                        @endif
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table id="puVotes" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>S/N</th>
                        <th>PU</th>
                        <th>{{ $election->party->acronym }}  Votes </th>
                        <th>Total Votes Cast</th>
                        <th>Result Sheet</th>
                        <th>Incident</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                    <tfoot>
                    <tr>
                        <th>S/N</th>
                        <th>PU</th>
                        <th>{{ $election->party->acronym }}  Votes </th>
                        <th>Total Votes Cast</th>
                        <th>Result Sheet</th>
                        <th>Incident</th>
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

<!-- Confirm Deletion script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.delete-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                const form = button.closest('form');
                const hasAssociatedUsers = form.getAttribute('data-associated-users') === 'true';

                if (hasAssociatedUsers) {
                    Swal.fire({
                        title: 'Cannot Delete Group',
                        text: 'This Vote cannot be deleted because it has associated Votes.',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Confirm Delete',
                        text: 'Are you sure you want to delete this Vote Data?',
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
    });
</script>

<!-- jQuery -->
<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>
<!-- Page specific data table script -->

<script>
    // Assign the base path for the uploads directory dynamically
    const basePath = "{{ asset('uploads/system_images/election_result_sheets/') }}/";
</script>

<script>
    $(document).ready(function() {
        $('#puVotes').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ajax:{
                url: "{{ $ajaxUrl }}",
                type: 'GET',
            },
            pageLength: 50,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            columns: [
                {
                    data: null,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                    orderable: false,
                    searchable: false
                },
                { data: "polling_unit_name", searchable: true },
                { data: "votes_obtained", searchable: false },
                { data: "total_votes_cast", searchable: false },
                { data: "result_sheet",   orderable: false,  searchable: false},
                { data: "incident_report", searchable: true }
            ],
            responsive: true,
            autoWidth: false
        });

          // Set placeholder for the search box
          $('#puVotes_filter input').attr('placeholder', 'Search By PU Name');
    });
</script>



@endsection


