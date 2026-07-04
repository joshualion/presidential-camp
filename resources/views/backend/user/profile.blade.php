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

                      @if(!empty($profileData->bank) && !empty($profileData->bank_account_number))
                      <!-- Bank Name -->
                    <strong><i class="fas fa-university mr-1"></i> Bank</strong>
                    <p class="text-muted">
                        {{ (!empty($profileData->bank)) ? ($profileData->bank) : ('Nil') }}
                    </p>
                    <hr>

                    <!-- Bank Account Number -->
                    <strong><i class="fas fa-credit-card mr-1"></i> Account Number</strong>
                    <p class="text-muted">
                        {{ (!empty($profileData->bank_account_number)) ? ($profileData->bank_account_number) : ('Nil') }}
                    </p>
                    <hr>
                    @endif

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

                    <strong><i class="fas fa-users mr-1"></i> Volunteer Groups</strong>
                    <p class="text-muted">
                        @if(!empty($userSupportGroups))
                            @foreach($userSupportGroups as $group)
                                <span class="badge badge-secondary">{{ $group }}</span>
                            @endforeach
                        @else
                            <span class="text-danger">Not a member of any group</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>


          <div class="col-md-9">
            <div class="card card-primary">
              <div class="card-header p-2">
                <div class="card-header">
                 <h5 class="card-title">PROFILE</h5>
                </div>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">

                  <!-- /.tab-pane -->
                  <div class="tab-pane active" id="editprofile">
                  <form class="form-horizontal" id="profileData" method="POST" action="{{route('user.profile.store')}}" enctype="multipart/form-data">
                            @csrf

                            @method('POST')

                            @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <input type="hidden" name="id" value="{{$profileData->id}}">

                                                <!-- First Name -->
                        <div class="form-group row">
                        <label for="firstname" class="col-sm-2 col-form-label">First Name</label>
                        <div class="col-sm-10">
                        <input type="text" class="form-control @error('firstname') is-invalid @enderror" name="firstname" id="firstname" value="{{ old('firstname', $profileData->firstname) }}">
                        </div>
                        @error('firstname')
                        <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        </div>

                        <!-- Last Name -->
                        <div class="form-group row">
                        <label for="lastname" class="col-sm-2 col-form-label">Last Name</label>
                        <div class="col-sm-10">
                        <input type="text" class="form-control @error('lastname') is-invalid @enderror" name="lastname" id="lastname" value="{{ old('lastname', $profileData->lastname) }}">
                        </div>
                        @error('lastname')
                        <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        </div>

                        <!-- Username (Read-only) -->
                        <div class="form-group row">
                        <label for="username" class="col-sm-2 col-form-label">Username</label>
                        <div class="col-sm-10">
                        <input type="text" class="form-control @error('username') is-invalid @enderror" name="username" id="username" value="{{ old('username', $profileData->username) }}" readonly>
                        </div>
                        @error('username')
                        <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        </div>

                        <!-- Email -->
                        <div class="form-group row">
                        <label for="email" class="col-sm-2 col-form-label">Email</label>
                        <div class="col-sm-10">
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" value="{{ old('email', $profileData->email) }}">
                        </div>
                        @error('email')
                        <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        </div>

                        <!-- Phone -->
                        <div class="form-group row">
                        <label for="phone" class="col-sm-2 col-form-label">Phone</label>
                        <div class="col-sm-10">
                        <input class="form-control @error('phone') is-invalid @enderror" name="phone" id="phone" type="tel" pattern="\+\d{1,3}\d{5,15}" title="Please enter a valid phone number with country code without any space (e.g., +2348061906478)"  value="{{ old('phone', $profileData->phone) }}">
                        <small id="phoneHelp" class="form-text text-muted">We'll never share your phone number with anyone else.</small>
                        </div>
                        @error('phone')
                        <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        </div>

                        <!-- Voter Status -->
                        <div class="form-group row">
                        <label for="validvoter" class="col-sm-2 col-form-label">Voter Status</label>
                        <div class="col-sm-10">
                        <p>Are You A Registered & Eligible Voter?</p>
                        <div class="form-check">
                        <input class="form-check-input" type="radio" name="validvoter" id="validvoter_yes" value="yes" {{ old('validvoter', $profileData->validVoter) == "yes" ? 'checked' : '' }}>
                        <label class="form-check-label" for="validvoter_yes">Yes</label>
                        </div>
                        <div class="form-check">
                        <input class="form-check-input" type="radio" name="validvoter" id="validvoter_no" value="no" {{ old('validvoter', $profileData->validVoter) == "no" ? 'checked' : '' }}>
                        <label class="form-check-label" for="validvoter_no">No</label>
                        </div>
                        </div>
                        </div>

                        <!-- VIN -->
                        <div class="form-group row" id="vinGroup">
                        <label for="vinInputBox" class="col-sm-2 col-form-label">VIN</label>
                        <div class="col-sm-10">
                        <input type="text" class="form-control" name="vin" id="vinInputBox" value="{{ old('vin', $profileData->vin) }}" @if(!empty($profileData->vin)) readonly @endif>
                        </div>
                        </div>

                        <!-- Age Grade -->
                        <div class="form-group row">
                        <label for="age_grade" class="col-sm-2 col-form-label">Age Grade</label>
                        <div class="col-sm-10">
                        <select id="age_grade" name="age_grade_id" class="form-control">
                        <option value="" disabled selected>Choose Your Age Grade</option>
                        @foreach($ageGrades as $ageGrade)
                            <option value="{{ $ageGrade->id }}" {{ old('age_grade_id', $profileData->age_grade_id) == $ageGrade->id ? 'selected' : '' }}>{{ $ageGrade->name }}</option>
                        @endforeach
                        </select>
                        </div>
                        </div>

                        <!-- Gender -->
                        <div class="form-group row">
                        <label for="gender" class="col-sm-2 col-form-label">Gender</label>
                        <div class="col-sm-10">
                        <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="male" value="male" {{ old('gender', $profileData->gender) == 'male' ? 'checked' : '' }}>
                        <label class="form-check-label" for="male">Male</label>
                        </div>
                        <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="female" value="female" {{ old('gender', $profileData->gender) == 'female' ? 'checked' : '' }}>
                        <label class="form-check-label" for="female">Female</label>
                        </div>
                        </div>
                        </div>

                        @include('backend.shared.location-form.context')


                        <!-- Voting Region -->
<div class="form-group row">
    <label for="region_id" class="col-sm-2 col-form-label">Voting Region</label>
    <div class="col-sm-10">
        <select class="form-control" name="region_id" id="region-dropdown" {{ $profileData->region_id ? 'disabled' : '' }}>
            <option value="" disabled selected>Select Region</option>
            @foreach($regions as $region)
                <option value="{{ $region->id }}" {{ $profileData->region_id == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
            @endforeach
        </select>
        @if($profileData->region_id)
            <input type="hidden" name="region_id" value="{{ $profileData->region_id }}">
        @endif
    </div>
</div>

<!-- Voting State -->
<div class="form-group row">
    <label for="state-dropdown" class="col-sm-2 col-form-label">Voting State</label>
    <div class="col-sm-10">
        <select class="form-control" name="state_id" id="state-dropdown" {{ $profileData->state_id ? 'disabled' : '' }}>
            <option value="" disabled selected>Select State</option>
            @foreach($states as $state)
                @if($state->region_id == $profileData->region_id)
                    <option value="{{ $state->id }}" {{ ($profileData->state_id == $state->id) ? 'selected' : '' }}>{{ $state->name }}</option>
                @endif
            @endforeach
        </select>
        @if($profileData->state_id)
            <input type="hidden" name="state_id" value="{{ $profileData->state_id }}">
        @endif
    </div>
</div>

<!-- Voting LGA -->
<div class="form-group row">
    <label for="lga-dropdown" class="col-sm-2 col-form-label">Voting LGA</label>
    <div class="col-sm-10">
        <select class="form-control" name="lga_id" id="lga-dropdown" {{ $profileData->lga_id ? 'disabled' : '' }}>
            <option value="" disabled selected>Select LGA</option>
            @foreach($lgas as $lga)
                @if($lga->state_id == $profileData->state_id)
                    <option value="{{ $lga->id }}" {{ ($profileData->lga_id == $lga->id) ? 'selected' : '' }}>{{ $lga->name }}</option>
                @endif
            @endforeach
        </select>
        @if($profileData->lga_id)
            <input type="hidden" name="lga_id" value="{{ $profileData->lga_id }}">
        @endif
    </div>
</div>

<!-- Voting Ward -->
<div class="form-group row">
    <label for="ward-dropdown" class="col-sm-2 col-form-label">Voting Ward</label>
    <div class="col-sm-10">
        <select class="form-control" name="ward_id" id="ward-dropdown" {{ $profileData->ward_id ? 'disabled' : '' }}>
            <option value="" disabled selected>Select Ward</option>
            @foreach($wards as $ward)
                @if($ward->lga_id == $profileData->lga_id)
                    <option value="{{ $ward->id }}" {{ ($profileData->ward_id == $ward->id) ? 'selected' : '' }}>{{ $ward->name }}</option>
                @endif
            @endforeach
        </select>
        @if($profileData->ward_id)
            <input type="hidden" name="ward_id" value="{{ $profileData->ward_id }}">
        @endif
    </div>
</div>

<!-- Polling Unit -->
<div class="form-group row">
    <label for="pu-dropdown" class="col-sm-2 col-form-label">Voting Polling Unit</label>
    <div class="col-sm-10">
        <select class="form-control" name="polling_unit_id" id="pu-dropdown" {{ $profileData->polling_unit_id ? 'disabled' : '' }}>
            <option value="" disabled selected>Select Polling Unit</option>
            @foreach($pollingUnits as $pu)
                @if($pu->ward_id == $profileData->ward_id)
                    <option value="{{ $pu->id }}" {{ ($profileData->polling_unit_id == $pu->id) ? 'selected' : '' }}>{{ $pu->name }}</option>
                @endif
            @endforeach
        </select>
        @if($profileData->polling_unit_id)
            <input type="hidden" name="polling_unit_id" value="{{ $profileData->polling_unit_id }}">
        @endif
    </div>
</div>




                        <!-- Religion -->
                        <div class="form-group row">
                        <label for="religion" class="col-sm-2 col-form-label">Religion</label>
                        <div class="col-sm-10">
                        <select id="religion" name="religion_id" class="form-control">
                        <option value="" disabled selected>Choose Your Religion</option>
                        @foreach($religions as $religion)
                            <option value="{{ $religion->id }}" {{ ($profileData->religion_id == $religion->id) ? 'selected' : '' }}>{{ $religion->name }}</option>
                        @endforeach
                        </select>
                        </div>
                        </div>

                        <!-- Bank Name -->
                        {{-- <div class="form-group row">
                            <label for="bank" class="col-sm-2 col-form-label">Bank Name</label>
                            <div class="col-sm-10">
                                <input type="text"
                                    class="form-control @error('bank') is-invalid @enderror"
                                    name="bank"
                                    id="bank"
                                    value="{{ old('bank', $profileData->bank) }}"
                                    placeholder="Enter your bank name">
                            </div>
                            @error('bank')
                            <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div> --}}

                        <!-- Bank Account Number -->
                        {{-- <div class="form-group row">
                            <label for="bank_account_number" class="col-sm-2 col-form-label">Account Number</label>
                            <div class="col-sm-10">
                                <input type="text"
                                    class="form-control @error('bank_account_number') is-invalid @enderror"
                                    name="bank_account_number"
                                    id="bank_account_number"
                                    value="{{ old('bank_account_number', $profileData->bank_account_number) }}"
                                    placeholder="Enter your account number"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                            @error('bank_account_number')
                            <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div> --}}


                        <!-- Residential Address -->
                        <div class="form-group row">
                        <label for="address" class="col-sm-3 col-form-label">Residential Address</label>
                        <div class="col-sm-9">
                        <textarea class="form-control" id="address" name="address">{{ old('address', $profileData->address) }}</textarea>
                        </div>
                        </div>

                        <!-- Occupation/Profession -->
                        <div class="form-group row">
                        <label for="occupation" class="col-sm-3 col-form-label">Occupation/Profession</label>
                        <div class="col-sm-9">
                        <textarea class="form-control" id="occupation" name="occupation">{{ old('occupation', $profileData->occupation) }}</textarea>
                        </div>
                        </div>

                        <!-- Educational Qualification -->
                        <div class="form-group row">
                        <label for="qualification" class="col-sm-5 col-form-label">Highest Educational Qualification Obtained</label>
                        <div class="col-sm-7">
                        <textarea class="form-control" id="qualification" name="qualification">{{ old('qualification', $profileData->qualification) }}</textarea>
                        </div>
                        </div>


                        <div class="form-group row">
                            <span class="b ms-6">
                                      <!-- Custom file input button -->
                                <label for="image" class="custom-file-upload" id="custom-label">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                  <div class=" off-set-2 col-sm-6 image">
                                  <img id="showimage" class="img-circle" src="{{(!empty($profileData->photo)) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" alt="profile" width="45%" height="45%">
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
                          <button type="submit" class="btn btn-primary btn-lg btn-block">Update Profile</button>
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



 <!-- Handle Voter eligibility and toggles display of VIN input  -->
 <script>
    // Function to toggle visibility of the VIN input box and its label
    function toggleVinInput() {
        var validVoterYes = document.getElementById('validvoter_yes');
        var validVoterNo = document.getElementById('validvoter_no');
        var vinGroup = document.getElementById('vinGroup'); // The whole VIN group (input and label)

        // Initially hide the VIN input box if "No" is selected
        if (validVoterNo.checked) {
            vinGroup.style.display = 'none';
        } else if (validVoterYes.checked) {
            vinGroup.style.display = 'block';
        }

        // Add event listeners to toggle visibility based on selection
        validVoterYes.addEventListener('click', function() {
            vinGroup.style.display = 'block';
        });

        validVoterNo.addEventListener('click', function() {
            vinGroup.style.display = 'none';
        });
    }

    // Call the function on page load to set the initial visibility state
    window.onload = toggleVinInput;
 </script>

<!-- jQuery -->
<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>
<!-- Handle Ajax request to states, lgas, wards and PU dropdown select -->
<script>
    $(document).ready(function() {
        $('#region-dropdown').on('change', function() {
            var region_id = this.value;
            console.log(region_id);
            $.ajax({
                url: "{{ route('getStates') }}",
                type: "POST",
                data: {
                    region_id: region_id,
                    _token: '{{csrf_token()}}'
                },
                cache: false,
                dataType: 'json',
                success: function(result){
                  $('#state-dropdown').html('<option value="" disabled selected>Select Voting State</option>');
                        $.each(result.states, function (key, value) {
                            $("#state-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        });

                    $('#lga-dropdown').html('<option value="" disabled selected>Select Voting LGA</option>');
                }
            });
        });

        $('#state-dropdown').on('change', function() {
            var state_id = this.value;
            console.log(state_id);
            $.ajax({
                url: "{{ route('getLgas') }}",
                type: "POST",
                data: {
                    state_id: state_id,
                    _token: '{{csrf_token()}}'
                },
                cache: false,
                dataType: 'json',
                success: function(result){
                  $('#lga-dropdown').html('<option value="" disabled selected>Select Voting LGA</option>');
                        $.each(result.lgas, function (key, value) {
                            $("#lga-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        });
                    $('#ward-dropdown').html('<option value="" disabled selected>Select Voting Ward</option>');
                }
            });
        });

        $('#lga-dropdown').on('change', function() {
            var lga_id = this.value;
            console.log(lga_id);
            $.ajax({
                url: "{{ route('getWards') }}",
                type: "POST",
                data: {
                    lga_id: lga_id,
                    _token: '{{csrf_token()}}'
                },
                cache: false,
                dataType: 'json',
                success: function(result){
                  $('#ward-dropdown').html('<option value="" disabled selected>Select Voting Ward</option>');
                        $.each(result.wards, function (key, value) {
                            $("#ward-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        });
                    $('#pu-dropdown').html('<option value="" disabled selected>Select Voting Polling Units</option>');
                }
            });
        });

        $('#ward-dropdown').on('change', function() {
            var ward_id = this.value;
            console.log(ward_id);
            $.ajax({
                url: "{{ route('getPollingUnits') }}",
                type: "POST",
                data: {
                    ward_id: ward_id,
                    _token: '{{csrf_token()}}'
                },
                cache: false,
                dataType: 'json',
                success: function(result){
                    $('#pu-dropdown').html('<option value="" disabled selected>Select Voting Polling Units</option>');
                        $.each(result.pollingUnits, function (key, value) {
                            $("#pu-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        });
                }
            });
        });
    });
</script>


<script src="{{asset('assets/js/intl-tel-input/intl-tel-input.js')}}"></script>
<script>
    const phoneInput = document.querySelector('#phone');
    if (phoneInput && window.intlTelInput) {
        window.intlTelInput(phoneInput, {
            initialCountry: 'ng',
            separateDialCode: false,
            hiddenInput: 'full_phone',
            utilsScript: "{{ asset('assets/js/intl-tel-input/utils.js') }}",
        });
    }
</script>

@endsection
