@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-9">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Edit Product Category</h3>
                <div class="card-tools">
                    <a href="{{ route($profileData->access_level.'.product.categories') }}"><button class="btn btn-light"> <i class="fa fa-caret-square-left"></i> Back To Categories </button></a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form class="form" action="{{route($profileData->access_level.'.product.category.update', $category->id)}}" method="POST" >
                @csrf

                    
                         <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{$category->name}}">
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" value="{{$category->description}}">{{$category->description}}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="slug">Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="{{$category->slug}}">
                        </div>

                    

                        <div class="col-3">
                        <button type="submit" class="btn btn-primary btn-block btn-group-lg">Update Category</button>
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