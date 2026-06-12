@extends('backend.template.backend-master')
@section('content')


@php
// Map status to corresponding badge classes
$badgeClasses = [
    'pending' => 'badge badge-info',
    'ongoing' => 'badge badge-success',
    'inconclusive' => 'badge badge-warning',
    'concluded' => 'badge badge-primary',
    'cancelled' => 'badge badge-danger',
];
@endphp
    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">All Elections</h3>
                    <div class="card-tools">
                        @if($profileData->access_level == 'superadmin')
                        <a href="{{ route($profileData->access_level.'.election.add') }}"><button class="btn btn-default"> <i class="fa fa-plus-square"></i> Create Election</button></a>
                        @endif
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table id="myElections" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Name</th>
                        <th>Party</th>
                        <th>Votes Received</th>
                        <th>Total Votes</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php
                            $serialNumber = 1; // Initialize serial number
                            @endphp
                    @foreach( $elections as  $election)
                    <tr>
                        <td>{{ $serialNumber++ }}</td>
                        <td>{{ \Carbon\Carbon::parse($election->year)->year }} - {{ $election->name }}</td>
                        <td title="{{ $election->party->name }}">{{ $election->party->acronym }}</td>
                        <td>{{ number_format((int) ($election->scoped_votes_received ?? 0)) }}</td>
                        <td>{{ number_format((int) ($election->scoped_total_votes ?? 0)) }}</td>
                        <td> <span class="{{ $badgeClasses[$election->dynamic_status] ?? 'badge badge-secondary' }}">
                            {{ ucfirst($election->dynamic_status) }}
                        </span>
                        </td>

                        <td>
                            <a href="{{ route($profileData->access_level.'.election.results',  ($election->uuid))}}" ><button class="btn btn-success btn-sm"><i class="fas fa-chart-line"></i>  Results</button></a>
                        <a href="{{ route($profileData->access_level.'.election.votesByPu',  ($election->uuid))}}" ><button class="btn btn-dark btn-sm"><i class="fas fa-eye"></i>  Reports</button></a>
                    @if($profileData->access_level == 'superadmin')
                        <a href="{{ route($profileData->access_level.'.election.edit',  ($election->uuid))}}" ><button class="btn btn-primary btn-sm"><i class="fas fa-pencil-alt"></i> Edit</button></a>
                        <form action="{{ route($profileData->access_level.'.election.delete',  ($election->uuid)) }}" method="POST" style="display: inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm delete-btn"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    @endif
                        </td>

                    </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>S/N</th>
                        <th>Name</th>
                        <th>Party</th>
                        <th>Votes Received</th>
                        <th>Total Votes</th>
                        <th>Status</th>
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
        const deleteButtons = document.querySelectorAll('.delete-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                const form = button.closest('form');
                const hasAssociatedUsers = form.getAttribute('data-associated-users') === 'true';

                if (hasAssociatedUsers) {
                    Swal.fire({
                        title: 'Cannot Delete Group',
                        text: 'This Election cannot be deleted because it has associated Votes.',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Confirm Delete',
                        text: 'Are you sure you want to delete this Election Data?',
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

<!-- Page specific data table script -->


<!-- jQuery -->
<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>
<script>
$(document).ready(function() {
    var dataTable = $("#myElections").DataTable({
        "responsive": true,
        "autoWidth": true,
        "paging": true,
        "searching": true,
        "ordering": false,
        "info": true,
    });

    dataTable.buttons().container().appendTo('#myElections_wrapper .col-md-6:eq(0)').addClass('datable-print-buttons'); // Add this line to add the 'text-center' class
});
</script>





@endsection




