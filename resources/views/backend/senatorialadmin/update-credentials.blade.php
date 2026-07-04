@extends('backend.template.backend-master')
@section('content')

@php
    use Carbon\Carbon;
@endphp


<div class="row">
          
          <div class="col-md-9">
            <div class="card card-primary">
              <div class="card-header p-2">
                <h5 class="card-title">CHANGE DEFAULT CREDENTIALS</h5>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">

                  <!-- /.tab-pane -->
                  <div class="tab-pane active" id="editprofile">
                    <form class="form-horizontal" method="POST" action="{{ route($profileData->access_level.'.account.update-credentials.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <label for="email" class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" placeholder="{{$profileData->email}}" value="{{$profileData->email}}" required>
                            </div>
                            @error('email')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    
                        <div class="form-group row">
                            <label for="new_password" class="col-sm-3 col-form-label">New Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="New Password" id="password" required>
                            </div>
                            @error('password')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    
                        <div class="form-group row">
                            <label for="password_confirmation" class="col-sm-3 col-form-label">Confirm Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" name="password_confirmation" placeholder="Retype New password" id="password_confirmation">
                            </div>
                        </div>
                    
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <hr />
                            </div>
                            <div class="offset-2 col-sm-6">
                                <button type="submit" class="btn btn-primary btn-block">Change Credentials</button>
                            </div>
                        </div>
                    </form>
                    
                  </div>
                  <!-- /.tab-pane -->


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
