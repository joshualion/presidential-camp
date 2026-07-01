@extends('backend.template.backend-master')
@section('content')

@php
    use Carbon\Carbon;
@endphp


<div class="row">
          <div class="col-md-3">

                  <!-- Profile Image -->
                  <div class="card card-primary">
                    <div class="card-body box-profile">
                      <div class="text-center">
                      <img class="profile-user-img img-fluid img-circle" src="{{(!empty($profileData->photo)) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" alt="profile">
                      <span class="h4 ms-3 "></span>
                      </div>

                      <h3 class="profile-username text-center">{{($profileData->firstname)}} {{($profileData->lastname)}}</h3>

                      <p class="text-muted text-center">VIN: {{(!empty($profileData->vin)) ? ($profileData->vin) : ('Nil') }}</p>

                    </div>
                    <!-- /.card-body -->
                  </div>
                  <!-- /.card -->

                  <!-- About Me Box -->
                  <div class="card card-primary">
                    <div class="card-header">
                      <h3 class="card-title">About</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                      <strong><i class="fas fa-book mr-1"></i> Education</strong>

                      <p class="text-muted">
                      {{(!empty($profileData->qualification)) ? ($profileData->qualification) : ('Nil') }}
                      </p>

                      <hr>

                      <strong><i class="fas  fa-pencil-alt mr-1"></i> Occupation</strong>

                      <p class="text-muted">
                      {{(!empty($profileData->occupation)) ? ($profileData->occupation) : ('Nil') }}

                      </p>

                      <hr>

                      <strong><i class="fas fa-map-marker-alt mr-1"></i> Address</strong>

                      <p class="text-muted">
                      {{(!empty($profileData->address)) ? ($profileData->address) : ('Nil') }}
                      </p>

                      <hr>

                      <strong><i class="fas fa-phone mr-1"></i> Phone</strong>

                      <p class="text-muted">
                      {{(!empty($profileData->phone)) ? ($profileData->phone) : ('Nil') }}
                      </p>
                      <hr>
                      <strong><i class="fas fa-user mr-1"></i> Member Since</strong>
                    <p class="text-muted">{{ Carbon::parse($profileData->created_at)->format('F jS Y') }}</p>

                    </div>

                    <!-- /.card-body -->
                  </div>
                  <!-- /.card -->
          </div><!-- /.col -->


          <div class="col-md-9">
            <div class="card card-primary">
              <div class="card-header p-2">
                <h5 class="card-title">CHANGE PASSWORD</h5>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">

                  <!-- /.tab-pane -->
                  <div class="tab-pane active" id="editprofile">
                  <form class="form-horizontal" method="POST" action="{{route('nationaladmin.update.password')}}" enctype="multipart/form-data">
                            @csrf
                  <div class="form-group row">
                        <label for="old_password" class="col-sm-3 col-form-label">Old Password</label>
                        <div class="col-sm-9">
                          <input type="password" class="form-control @error('old_password') is-invalid @enderror" name="old_password" id="old_password" placeholder="Old password" autocomplete="current-password" required>
                        </div>
                        @error('old_password')
                          <span class="text-danger">{{$message}}</span>
                        @enderror
                  </div>

                  <div class="form-group row">
                        <label for="new_password" class="col-sm-3 col-form-label">New Password</label>
                        <div class="col-sm-9">
                          <input type="password" class="form-control @error('new_password') is-invalid @enderror" name="new_password" placeholder="New Password" id="new_password" autocomplete="new-password" minlength="8" required>
                        </div>
                        @error('new_password')
                        <span class="text-danger">{{$message}}</span>
                        @enderror
                  </div>

                      <div class="form-group row">
                        <label for="new_password_confirmation" class="col-sm-3 col-form-label">Confirm Password</label>
                        <div class="col-sm-9">
                          <input type="password" class="form-control" name="new_password_confirmation" placeholder="Retype New password" id="new_password_confirmation" autocomplete="new-password" minlength="8" required>
                        </div>
                      </div>

                      <div class="form-group row">
                      <div class=" col-sm-12"><hr/><div>
                        <div class="off-set-2 col-sm-6">
                          <button type="submit" class="btn btn-primary btn-block">Change Password</button>
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
