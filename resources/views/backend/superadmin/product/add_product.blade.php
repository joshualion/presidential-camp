@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-9">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Add Product</h3>
                <div class="card-tools">
                    <a href="{{ route($profileData->access_level.'.products') }}"><button class="btn btn-light"> <i class="fa fa-caret-square-left"></i> Back To Products</button></a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form class="form" action="{{route($profileData->access_level.'.product.store')}}" method="POST"  enctype="multipart/form-data">
                @csrf
                         <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter product Name">
                        </div>
                        <div class="form-group">
                            <label for="description">product Description</label>
                            <textarea class="form-control" id="description" name="description" placeholder="Enter product Description"></textarea>
                        </div>
                       
                        <div class="form-group">
                            <label for="price">Product Price</label>
                            <input type="number" class="form-control" id="price" name="price" placeholder="@foreach ($systemSettings as $setting){{ $setting->system_currency }} 0.00 @endforeach">
                        </div>

                       

                        <div class="form-group">
                            <label for="category">Product Category</label>
                            <select class="form-control" id="category" name="category">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                <option value="{{$category->id}}">{{$category->name}}</option>
                                @endforeach
                            </select>
                        </div>


                        

                        <div class="form-group">
                            <label for="name">Product Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" placeholder="e.g product-name (optional)">
                        </div>

                        <div class="form-group row">                    
                      <span class="b ms-6">
                                <!-- Custom file input button -->
                          <label for="image" class="custom-file-upload" id="custom-label">      
                          <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class=" off-set-2 col-sm-6 image">
                            <img id="showimage" class="img-circle" src="{{(!empty($product->image)) ? url('uploads/product_images/'.$product->image) : 	url('uploads/product_images/no_image.jpg')}}" alt="product Image" width="75%" height="75%">
                            <br/><br/>
                           <span class="btn btn-secondary"> Upload Image </span>
                                </label>
                              <!-- Display the chosen file name -->
                            <span class="file-name" id="file-name"></span>
                            <input type="file" class="form-control" id="image" name="image">
                            </span>
                          </div>   
                          </div>               
                    </div>

                        <div class="col-3">
                        <button type="submit" class="btn btn-primary btn-block btn-group-lg">Add Product</button>
                        </div>
              </form>
           

                
               
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
 
  <!-- TinyMCE Editor Integration -->
  <script src="https://cdn.tiny.cloud/1/cgksa1lzpz0juy9k9hf7tg6zdsh2j5a1s2thsbc7uz5kx2by/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

  <script>
  tinymce.init({
    selector: 'textarea',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
  });
</script>

@endsection