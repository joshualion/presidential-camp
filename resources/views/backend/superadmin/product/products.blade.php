@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Products</h3>
                <div class="card-tools">
                <a href="{{ route($profileData->access_level.'.product.add') }}"><button class="btn btn-light"> <i class="fa fa-plus-square"></i> Add New Product</button></a>
                <a href="{{ route($profileData->access_level.'.product.categories') }}"><button class="btn btn-light"> <i class="fas fa-pencil-alt"></i> Manage Product Categories</button></a>
               </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="myproducts" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                 

                  @foreach($products as $product)
                  <tr>

                   
                    <td>
                      @if (!empty($product->image))
                        <img class="img-circle img-fluid img-responsive" src="{{ url('uploads/product_images/' . $product->image) }}" alt="product image" width="30"0height="30">
                      @else
                        <img class="img-circle img-fluid img-responsive" src="{{ url('uploads/no_image.jpg') }}" alt="product image   " width="30" height="30">
                      @endif
                    </td>
                    <td>{{ $product->name }}</td>
                    <td>{!!$product->description !!}</td>
                    <td>
                    @foreach ($systemSettings as $setting)

                  {{ $setting->system_currency  . number_format($product->price) }}
       


                      @endforeach
                    </td>
                    <td>
                          {{-- @foreach($categories as $prem) --}}
                                  <span class="badge bg-secondary">{{ $product->Productcategory->name }}</span>
                          {{-- @endforeach --}}
                    </td>
                      

                    <td>
                      <a href="{{ route($profileData->access_level.'.product.edit',  $product->id)}}" ><button class="btn btn-success btn-sm"><i class="fas fa-pencil-alt"></i> Edit</button></a>
                      <form action="{{ route($profileData->access_level.'.product.delete',  $product->id) }}" method="POST" style="display: inline-block;">
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
                  
                    <th>Image</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Category</th>
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
                        text: 'This product cannot be deleted because it has associated users.',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Confirm Delete',
                        text: 'Are you sure you want to delete this product?',
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
    var dataTable = $("#myproducts").DataTable({
        "responsive": true,
        "autoWidth": true,
        "paging": true,
        "searching": true,
        "ordering": false,
        "info": true,
    });

    dataTable.buttons().container().appendTo('#myproducts_wrapper .col-md-6:eq(0)').addClass('datable-print-buttons'); // Add this line to add the 'text-center' class
});
</script>





@endsection




