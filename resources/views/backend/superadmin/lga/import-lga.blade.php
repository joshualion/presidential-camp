@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-9">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Import LGA</h3>
                <div class="card-tools">
                    <a href="{{ route($profileData->access_level.'.location.lgas') }}"><button class="btn btn-default"> <i class="fas fa-eye"></i> All lgas</button></a>
                    <a href="{{route($profileData->access_level.'.location.lga.exportLgas')}}"><button class="btn btn-dark"> <i class="fa fa-file-excel"></i> Download Excel File</button></a>

                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form action="{{ route($profileData->access_level.'.location.lga.importLgas') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="file">Upload LGA's Excel File:</label>
                    <input type="file" class="form-control" name="file" id="file" required>
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>
            
            </div>



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



@endsection
