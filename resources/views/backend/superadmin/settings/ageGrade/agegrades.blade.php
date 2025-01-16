@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">All Age Grades</h3>
                <div class="card-tools">
              <a href="{{ route($profileData->access_level.'.agegrade.add') }}"><button class="btn btn-default"> <i class="fa fa-plus-square"></i> Add New agegrade</button></a>
            </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="myagegrades" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S/N</th>
                    <th>AgeGrade</th>
                    <th>Description</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                  @php
                        $serialNumber = 1; // Initialize serial number
                        @endphp
                  @foreach($agegrades as $agegrade)
                  <tr>

                    <td>{{ $serialNumber++ }}</td>
                    <td>{{$agegrade->name}}</td>
                    <td>{{$agegrade->description}}</td>
                    <td>
                      <a href="{{ route($profileData->access_level.'.agegrade.edit', $agegrade->uuid)}}" ><button class="btn btn-success btn-sm"><i class="fas fa-pencil-alt"></i> Edit</button></a>
                      <form action="{{ route($profileData->access_level.'.agegrade.delete', $agegrade->uuid) }}" method="POST" style="display: inline-block;" data-associated-users="{{ optional($agegrade->users)->isNotEmpty() ? 'true' : 'false' }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm delete-btn"><i class="fas fa-trash"></i> Delete</button>
                      </form>
                    </td>

                  </tr>
                    @endforeach
                  </tbody>
                  <tfoot>
                  <tr>
                    <th>S/N</th>
                    <th>AgeGrade</th>
                    <th>Description</th>
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
                        title: 'Cannot Delete agegrade',
                        text: 'This agegrade cannot be deleted because it has associated users.',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Confirm Delete',
                        text: 'Are you sure you want to delete this agegrade?',
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
        var dataTable = $("#myagegrades").DataTable({
            "responsive": true,
            "autoWidth": true,
           // "buttons": ["excel", "pdf", "print"],
            "paging": true,
            "searching": true,
            "ordering": false,
            "info": true,
        });

        dataTable.buttons().container().appendTo('#myagegrades_wrapper .col-md-6:eq(0)').addClass('datable-print-buttons'); // Add this line to add the 'text-center' class
    });
</script>





@endsection




