@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-9">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Import Polling Units</h3>
                <div class="card-tools">
                    <a href="{{ route($profileData->access_level.'.location.pus') }}"><button class="btn btn-default"> <i class="fas fa-eye"></i> All Polling Units</button></a>
                    <a href="{{route($profileData->access_level.'.location.pu.exportPollingUnits')}}"><button class="btn btn-dark"> <i class="fa fa-file-excel"></i> Download Excel File</button></a>

                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form action="{{ route($profileData->access_level.'.location.pu.importPollingUnits') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="file">Upload Polling Units Excel File:</label>
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
