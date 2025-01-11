@extends('backend.template.backend-master')
@section('content')



<div class="row">
          
          <div class="col-md-9">
            <div class="card card-primary">
              <div class="card-header p-2">
                <h5 class="card-title">INCOMPLETE SYSTEM SETUP</h5>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">

                  
                    <div class="alert alert-warning">
                        <p>{{ $notification }}</p>
                    </div>

                </div>
                <!-- /.tab-content -->
              </div><!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
</div>
<!-- /.row -->







@endsection
