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
              <div class="alert alert-info">
                Imported LGAs are added to your campaign geography but are not automatically mapped to Senatorial Districts and Federal Constituencies. Manual Add LGA lets administrators map each LGA correctly while creating it. For complete electoral geography with all relationships automatically configured, use QuickStart Geography Provisioning.
                <a href="{{ $quickstartOrderUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-success ml-2">Configure with QuickStart</a>
              </div>

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
