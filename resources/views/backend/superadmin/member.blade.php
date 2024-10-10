@extends('backend.template.backend-master')
@section('content')

@php
    use Carbon\Carbon;
@endphp

  
<div class="row">
          <div class="col-md-3">

                  <!-- Profile Image -->
                  <div class="card card-success card-outline">
                    <div class="card-body box-profile">
                      <div class="text-center">
                      @if ($userData->role === 'superadmin' || $userData->role === 'nationaladmin'|| $userData->role === 'regionaladmin' || $userData->role === 'stateadmin' || $userData->role === 'lgaadmin'||$userData->role === 'wardadmin'||$userData->role === 'puadmin')
                      <img class="profile-user-img img-fluid img-circle" src="{{(!empty($userData->photo)) ? url('uploads/admin_images/'.$userData->photo) : url('uploads/no_image.jpg')}}" alt="profile">
                      @else
                      <img class="profile-user-img img-fluid img-circle" src="{{(!empty($userData->photo)) ? url('uploads/member_images/'.$userData->photo) : url('uploads/no_image.jpg')}}" alt="profile">
                      @endif
                  <span class="h4 ms-3 "></span>
                      </div>

                      <h3 class="profile-username text-center">{{($userData->firstname)}} {{($userData->lastname)}}</h3>

                      <p class="text-muted text-center">VIN: {{(!empty($userData->vin)) ? ($userData->vin) : ('VIN: Nil') }}</p>

                    </div>
                    <!-- /.card-body -->
                  </div>
                  <!-- /.card -->

                  <!-- About Me Box -->
                  <div class="card card-success">
                    <div class="card-header">
                      <h3 class="card-title">About</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                      <strong><i class="fas fa-book mr-1"></i> Education</strong>

                      <p class="text-muted">
                      {{(!empty($userData->qualification)) ? ($userData->qualification) : ('Nil') }}
                      </p>

                      <hr>

                      <strong><i class="fas  fa-pencil-alt mr-1"></i> Occupation</strong>

                      <p class="text-muted">
                      {{(!empty($userData->occupation)) ? ($userData->occupation) : ('Nil') }}
                    
                      </p>

                      <hr>

                      <strong><i class="fas fa-map-marker-alt mr-1"></i> Address</strong>

                      <p class="text-muted">
                      {{(!empty($userData->address)) ? ($userData->address) : ('Nil') }}
                      </p>

                      <hr>

                      <strong><i class="fas fa-phone mr-1"></i> Phone</strong>

                      <p class="text-muted">
                      {{(!empty($userData->phone)) ? ($userData->phone) : ('Nil') }}  
                      </p>
                      <hr>
                      <strong><i class="fas fa-user mr-1"></i> Member Since</strong>
                    <p class="text-muted">{{ Carbon::parse($userData->created_at)->format('F jS Y') }}</p>
       
                    </div>
                    
                    <!-- /.card-body -->
                  </div>
                  <!-- /.card -->
          </div><!-- /.col -->


          <div class="col-md-9">
            <div class="card card-success card-outline">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                 <li class="nav-item"><a class="active" href="#editprofile" data-toggle="tab"><h3>Review {{($userData->firstname)}} {{($userData->lastname)}}'s Infomation</h3></a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  
                  <!-- /.tab-pane -->
                  <div class="tab-pane active" id="editprofile">
                  <form class="form-horizontal" id="userData" method="POST" action="{{route('admin.update.member', $userData->id)}}" enctype="multipart/form-data">
                      @csrf
                    @method('PUT')

                  <div class="form-group row">
                        <label for="firstname" class="col-sm-2 col-form-label">First Name</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control @error('firstname') is-invalid @enderror" name="firstname" id="firstname" value="{{$userData->firstname}}">
                        </div>
                        @error('firstname')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                      </div>

                      <div class="form-group row">
                        <label for="lastname" class="col-sm-2 col-form-label">Last Name</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control @error('lastname') is_invalid @enderror" name="lastname" id="lastname" value="{{($userData->lastname)}}">
                        </div>
                        @error('lastname')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                      </div>

                      <div class="form-group row">
                        <label for="username" class="col-sm-2 col-form-label">Username</label>
                        <div class="col-sm-10">
                          <input type="text" readonly  class="form-control @error('username') is_invalid @enderror" name="username" id="username" value="{{($userData->username)}}">
                        </div>
                        @error('username')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                      </div>
                      <div class="form-group row">
                        <label for="email" class="col-sm-2 col-form-label">Email</label>
                        <div class="col-sm-10">
                          <input type="email" class="form-control @error('email') is_invalid @enderror" id="email" value="{{($userData->email)}}">
                        </div>
                        @error('email')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                      </div>

                      <div class="form-group row">
                        <label for="phone" class="col-sm-2 col-form-label">Phone</label>
                        <div class="col-sm-10">
                          <input  class="form-control @error('phone') is_invalid @enderror" pattern="\+\d{1,3}\d{5,15}" name="phone" id="phone" type="tel" value="{{($userData->phone)}}" title="Please enter a valid phone number with country code without any space (e.g., +2348061906478)" >
                        </div>
                        @error('phone')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                      </div>

                            
                      <div class="form-group row">
                            <label for="validvoter" class="col-sm-2 col-form-label">Voter Status</label>
                            <div class="col-sm-10">
                                <p class="col-sm-6 col-form-label">Are You A Registered & Eligible Voter?</p>
                                <div class="radio col-sm-4">
                                    <input class="form-check-input" type="radio" name="validvoter" id="validvoter_yes" value="yes" {{ (!empty($userData->validvoter) && $userData->validvoter == "yes") ? 'checked' : '' }}>
                                    <label class="form-check-label" for="validvoter_yes">Yes</label>
                                </div>
                                <div class="radio col-sm-4">
                                    <input class="form-check-input" type="radio" name="validvoter" id="validvoter_no" value="no" {{ (!empty($userData->validvoter) && $userData->validvoter == "no") ? 'checked' : '' }}>
                                    <label class="form-check-label" for="validvoter_no">No</label>
                                </div>
                            </div>
                        </div>

                      <div class="form-group row">
                        <label for="vin" class="col-sm-2 col-form-label">VIN <small class="text-success " title="Voter Identification Number"><em><i class="fa fa-info-circle" aria-hidden="true"></i></em></small></label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control" name="vin" id="vin" value="{{($userData->vin)}}" readonly>
                        </div>
                      </div>

                      <div class="form-group row">
                        <label for="age_garde" class="col-sm-2 col-form-label">Age Grade</label>
                        <div class="col-sm-10">

                        <select id="age_grade" name="age_grade" required class="form-control">
                            <option value="">Choose Your Age Grade</option> 
                            <option value="18-24" {{ ($userData->age_grade == '18-24') ? 'selected' : '' }}>18-24</option>                            
                            <option value="25-30" {{ ($userData->age_grade == '25-30') ? 'selected' : '' }}>25-30</option>
                            <option value="31-35" {{ ($userData->age_grade == '31-35') ? 'selected' : '' }}>31-35</option>
                            <option value="36-40" {{ ($userData->age_grade == '36-40') ? 'selected' : '' }}>36-40</option>
                            <option value="41-45" {{ ($userData->age_grade == '41-45') ? 'selected' : '' }}>41-45</option>
                            <option value="46-50" {{ ($userData->age_grade == '46-50') ? 'selected' : '' }}>46-50</option>
                            <option value="51-55" {{ ($userData->age_grade == '51-55') ? 'selected' : '' }}>51-55</option>
                            <option value="56-60" {{ ($userData->age_grade == '56-60') ? 'selected' : '' }}>56-60</option>
                            <option value="61-65" {{ ($userData->age_grade == '61-65') ? 'selected' : '' }}>61-65</option>
                            <option value="66-70" {{ ($userData->age_grade == '66-70') ? 'selected' : '' }}>66-70</option>
                            <option value="70+" {{ ($userData->age_grade == '70+') ? 'selected' : '' }}>70+</option>
                        </select>

                          
                        </div>
                      </div>

                      <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Gender</label>
                        <div class="col-sm-10">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="male" value="Male" {{ ($userData->gender == 'Male') ? 'checked' : '' }}>
                                <label class="form-check-label" for="male">Male</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="female" value="Female" {{ ($userData->gender == 'Female') ? 'checked' : '' }}>
                                <label class="form-check-label" for="female">Female</label>
                            </div>
                           
                        </div>
                    </div>

                    <div class="form-group row">
                    <label for="country_id" class="col-sm-2 col-form-label">Country</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="country_id" id="country_id" required readonly>
                            <option value="{{($countries->id)}}">{{($countries->name)}}</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                            <label for="region" class="col-sm-2 col-form-label">Voting Region</label>
                            <div class="col-sm-10">
                                <select class="form-control" name="region_id" id="region-dropdown">
                                    <option value="">Select Region</option>
                                    @if(!empty($userData->region_id))
                                        @foreach($regions as $region)
                                            @if($region->country_id == $countries->id)
                                                <option value="{{ $region->id }}" {{ ($userData->region_id == $region->id) ? 'selected' : '' }}>
                                                    {{ $region->name }}
                                                </option>
                                            @endif
                                        @endforeach
                                    @else
                                        @foreach($regions as $region)
                                            @if($region->country_id == $countries->id)
                                                <option value="{{ $region->id }}">
                                                    {{ $region->name }}
                                                </option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                  </div>

                  <div class="form-group row">
                    <label for="state-dropdown" class="col-sm-2 col-form-label">Voting State</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="state_id" id="state-dropdown">
                            <option value="">Select State</option>
                            @foreach($states as $state)
                                @if($state->region_id == $userData->region_id)
                                    <option value="{{ $state->id }}" {{ ($userData->state_id == $state->id) ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>




                <div class="form-group row">
                    <label for="lga-dropdown" class="col-sm-2 col-form-label">Voting LGA</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="lga_id" id="lga-dropdown">
                            <option value="">Select LGA</option>
                            @foreach($lgas as $lga)
                                @if($lga->state_id == $userData->state_id)
                                    <option value="{{ $lga->id }}" {{ ($userData->lga_id == $lga->id) ? 'selected' : '' }}>
                                        {{ $lga->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>


                <div class="form-group row">
                    <label for="ward-dropdown" class="col-sm-2 col-form-label">Voting Ward</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="ward_id" id="ward-dropdown">
                            <option value="">Select Ward</option>
                            @foreach($wards as $ward)
                                @if($ward->lga_id == $userData->lga_id)
                                    <option value="{{ $ward->id }}" {{ ($userData->ward_id == $ward->id) ? 'selected' : '' }}>
                                        {{ $ward->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>


                <div class="form-group row">
                  <label for="pu-dropdown" class="col-sm-3 col-form-label">Voting Polling Unit</label>
                  <div class="col-sm-9">
                      <select class="form-control" name="pu_id" id="pu-dropdown">
                          <option value="">Select Polling Unit</option>
                          @foreach($pus as $pu)
                              @if($pu->ward_id == $userData->ward_id)
                                  <option value="{{ $pu->id }}" {{ ($userData->pu_id == $pu->id) ? 'selected' : '' }}>
                                      {{ $pu->name }}
                                  </option>
                              @endif
                          @endforeach
                      </select>
                  </div>
              </div>




                     
                    <div class="form-group row">
                        <label for="support_group_id" class="col-sm-2 col-form-label">Volunteer Group</label>
                        <div class="col-sm-10">
                            <select class="form-control" name="support_group_id" id="support_group_id">
                                <option value="">Choose Support Group</option>
                                @foreach($supportGroups as $supportGroup)
                                    <option value="{{ $supportGroup->id }}" {{ ($userData->support_group_id == $supportGroup->id) ? 'selected' : '' }}>
                                        {{ $supportGroup->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>


                    
                    <div class="form-group row">
                            <label for="religion" class="col-sm-2 col-form-label">Religion</label>
                            <div class="col-sm-10">
                                <select id="religion" name="religion" required class="form-control">
                                    <option value="">Choose Your Religion</option> 
                                    <option value="Christian" {{ ($userData->religion == 'Christian') ? 'selected' : '' }}>Christian</option>                            
                                    <option value="Muslim" {{ ($userData->religion == 'Muslim') ? 'selected' : '' }}>Muslim</option>
                                    <option value="Indigenous" {{ ($userData->religion == 'Indigenous') ? 'selected' : '' }}>Indigenous</option>
                                    <option value="Secular" {{ ($userData->religion == 'Secular') ? 'selected' : '' }}>Secular</option>
                                    <option value="No Religion" {{ ($userData->religion == 'No Religion') ? 'selected' : '' }}>No Religion</option>
                                    <option value="Others" {{ ($userData->religion == 'Others') ? 'selected' : '' }}>Others</option>
                                </select>
                            </div>
                        </div>  


                    

                      <div class="form-group row">
                        <label for="address" class="col-sm-3 col-form-label">Residential Address</label>
                        <div class="col-sm-9">
                          <textarea class="form-control" id="address" name="address" Value="{{$userData->address}}">{{$userData->address}}</textarea>
                        </div>
                      </div>

                      <div class="form-group row">
                        <label for="occupation" class="col-sm-3 col-form-label">Occupation/Profession</label>
                        <div class="col-sm-9">
                          <textarea class="form-control" id="occupation" name="occupation" Value="{{$userData->occupation}}">{{$userData->occupation}}</textarea>
                        </div>
                      </div>

                      <div class="form-group row">
                        <label for="qualification" class="col-sm-5 col-form-label">Highest Educational Qualification Obtained</label>
                        <div class="col-sm-7">
                          <textarea class="form-control" id="qualification" name="qualification" Value="{{$userData->qualification}}">{{$userData->qualification}}</textarea>
                        </div>
                      </div>    

                      <div class="form-group row">                    
                      <span class="b ms-6">
                                <!-- Custom file input button -->
                          <label for="image" class="custom-file-upload" id="custom-label">      
                          <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class=" off-set-2 col-sm-6 image">
                            <img id="showimage" class="img-circle" src="{{(!empty($userData->photo)) ? url('uploads/member_images/'.$userData->photo) : url('uploads/no_image.jpg')}}" alt="profile" width="45%" height="45%">
                            <br/><br/>
                           <span class="btn btn-secondary " >Change Profile Photo</span>
                                </label>
                              <!-- Display the chosen file name -->
                            <span class="file-name" id="file-name"></span>
                            <input type="file" class="form-control" id="image" name="photo">
                            </span>
                          </div>   
                          </div>               
                      </div>
                      
                      


                
                      <div class="form-group row">
                      <div class=" col-sm-12"><hr/><div>
                        <div class="off-set-2 col-sm-6">
                          <button type="submit" class="btn btn-success btn-lg btn-block">Update</button>
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