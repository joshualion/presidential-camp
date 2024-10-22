@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-9">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Import Permission</h3>
                <div class="card-tools">
                    <a href="{{ route('nationaladmin.settings.permissions') }}"><button class="btn btn-default"> <i class="fas fa-eye"></i> All Permissions</button></a>
                    <a href="{{route('nationaladmin.settings.export.permission')}}"><button class="btn btn-dark"> <i class="fa fa-file-excel"></i> Download Excel File</button></a>

                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form class="form" action="{{route('nationaladmin.settings.import.permission.data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                  <div class="form-group">
                    <label for="import_file">Excel File Import</label>
                    <input type="file" class="form-control" id="import_file" name="import_file">
                  </div>



                <div class="col-3">
                  <button type="submit" class="btn btn-primary btn-block btn-group-lg">Upload</button>
                </div>
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
