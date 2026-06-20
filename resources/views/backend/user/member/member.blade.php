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
                      @if ($member->role === 'superadmin' || $member->role === 'nationaladmin'|| $member->role === 'regionaladmin' || $member->role === 'stateadmin' || $member->role === 'lgaadmin'||$member->role === 'wardadmin'||$member->role === 'puadmin')
                      <img class="profile-user-img img-fluid img-circle" src="{{(!empty($member->photo)) ? url('uploads/member_images/'.$member->photo) : url('uploads/no_image.jpg')}}" alt="profile">
                      @else
                      <img class="profile-user-img img-fluid img-circle" src="{{(!empty($member->photo)) ? url('uploads/member_images/'.$member->photo) : url('uploads/no_image.jpg')}}" alt="profile">
                      @endif
                  <span class="h4 ms-3 "></span>
                      </div>

                      <h3 class="profile-username text-center">{{($member->firstname)}} {{($member->lastname)}}</h3>

                      <p class="text-muted text-center">VIN: {{(!empty($member->vin)) ? ($member->vin) : ('Nil') }}</p>

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
                      {{(!empty($member->qualification)) ? ($member->qualification) : ('Nil') }}
                      </p>

                      <hr>

                      <strong><i class="fas  fa-pencil-alt mr-1"></i> Occupation</strong>

                      <p class="text-muted">
                      {{(!empty($member->occupation)) ? ($member->occupation) : ('Nil') }}

                      </p>

                      <hr>

                      <strong><i class="fas fa-map-marker-alt mr-1"></i> Address</strong>

                      <p class="text-muted">
                      {{(!empty($member->address)) ? ($member->address) : ('Nil') }}
                      </p>

                      <hr>

                      <strong><i class="fas fa-phone mr-1"></i> Phone</strong>

                      <p class="text-muted">
                      {{(!empty($member->phone)) ? ($member->phone) : ('Nil') }}
                      </p>
                      <hr>
                      <strong><i class="fas fa-user mr-1"></i> Member Since</strong>
                    <p class="text-muted">{{ Carbon::parse($member->created_at)->format('F jS Y') }}</p>

                    </div>

                    <!-- /.card-body -->
                  </div>
                  <!-- /.card -->
          </div><!-- /.col -->


          <div class="col-md-9">
            <div class="card card-primary">
              <div class="card-header p-2">

                 <h3 class="nav-item"> Edit {{($member->firstname)}} {{($member->lastname)}}'s Infomation</h3>

              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">

                  <!-- /.tab-pane -->
                  <div class="tab-pane active" id="editprofile">


        <form class="form-horizontal" id="member" method="POST" action="{{route($profileData->access_level.'.member.update', $member->id)}}" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')

                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

        <input type="hidden" name="id" value="{{$member->id}}">

            <!-- First Name -->
        <div class="form-group row">
            <label for="firstname" class="col-sm-2 col-form-label">First Name</label>
            <div class="col-sm-10">
                <input type="text" class="form-control @error('firstname') is-invalid @enderror" name="firstname" id="firstname" value="{{ old('firstname', $member->firstname) }}">
            </div>
            @error('firstname')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        </div>

        <!-- Last Name -->
        <div class="form-group row">
            <label for="lastname" class="col-sm-2 col-form-label">Last Name</label>
            <div class="col-sm-10">
                <input type="text" class="form-control @error('lastname') is-invalid @enderror" name="lastname" id="lastname" value="{{ old('lastname', $member->lastname) }}">
            </div>
            @error('lastname')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        </div>

        <!-- Username (Read-only) -->
        <div class="form-group row">
            <label for="username" class="col-sm-2 col-form-label">Username</label>
            <div class="col-sm-10">
                <input type="text" class="form-control @error('username') is-invalid @enderror" name="username" id="username" value="{{ old('username', $member->username) }}" readonly>
            </div>
            @error('username')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        </div>

            <!-- Email -->
            <div class="form-group row">
                <label for="email" class="col-sm-2 col-form-label">Email</label>
                <div class="col-sm-10">
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" value="{{ old('email', $member->email) }}">
                </div>
                @error('email')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror
            </div>

            <!-- Phone -->
            <div class="form-group row">
                <label for="phone" class="col-sm-2 col-form-label">Phone</label>
                <div class="col-sm-10">
                    <input class="form-control @error('phone') is-invalid @enderror" name="phone" id="phone" type="tel" pattern="\+\d{1,3}\d{5,15}" title="Please enter a valid phone number with country code without any space (e.g., +2348061906478)"  value="{{ old('phone', $member->phone) }}">
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
                        <input class="form-check-input" type="radio" name="validvoter" id="validvoter_yes" value="yes" {{ old('validvoter', $member->validVoter) == "yes" ? 'checked' : '' }}>
                        <label class="form-check-label" for="validvoter_yes">Yes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="validvoter" id="validvoter_no" value="no" {{ old('validvoter', $member->validVoter) == "no" ? 'checked' : '' }}>
                        <label class="form-check-label" for="validvoter_no">No</label>
                    </div>
                </div>
            </div>

            <!-- VIN -->
            <div class="form-group row" id="vinGroup">
                <label for="vinInputBox" class="col-sm-2 col-form-label">VIN</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="vin" id="vinInputBox" value="{{ old('vin', $member->vin) }}" @if(!empty($member->vin)) readonly @endif>
                </div>
            </div>

            <!-- Age Grade -->
            <div class="form-group row">
                <label for="age_grade" class="col-sm-2 col-form-label">Age Grade</label>
                <div class="col-sm-10">
                    <select id="age_grade" name="age_grade_id" class="form-control">
                        <option value="" disabled selected>Choose Your Age Grade</option>
                        @foreach($ageGrades as $ageGrade)
                            <option value="{{ $ageGrade->id }}" {{ old('age_grade_id', $member->age_grade_id) == $ageGrade->id ? 'selected' : '' }}>{{ $ageGrade->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Gender -->
            <div class="form-group row">
                <label for="gender" class="col-sm-2 col-form-label">Gender</label>
                <div class="col-sm-10">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="male" value="male" {{ old('gender', $member->gender) == 'male' ? 'checked' : '' }}>
                        <label class="form-check-label" for="male">Male</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="female" value="female" {{ old('gender', $member->gender) == 'female' ? 'checked' : '' }}>
                        <label class="form-check-label" for="female">Female</label>
                    </div>
                </div>
            </div>
                <!-- Voting Region -->
                <div class="form-group row">
                    <label for="region_id" class="col-sm-2 col-form-label">Voting Region</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="region_id" id="region-dropdown">
                            <option value="" disabled selected>Select Region</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}" {{ $member->region_id == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Voting State -->
                <div class="form-group row">
                    <label for="state-dropdown" class="col-sm-2 col-form-label">Voting State</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="state_id" id="state-dropdown">
                            <option value="" disabled selected>Select State</option>
                            @foreach($states as $state)
                                @if($state->region_id == $member->region_id)
                                    <option value="{{ $state->id }}" {{ ($member->state_id == $state->id) ? 'selected' : '' }}>{{ $state->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                @include('backend.shared.member.boundary-fields')

                <!-- Voting LGA -->
                <div class="form-group row">
                    <label for="lga-dropdown" class="col-sm-2 col-form-label">Voting LGA</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="lga_id" id="lga-dropdown">
                            <option value="" disabled selected>Select LGA</option>
                            @foreach($lgas as $lga)
                                @if($lga->state_id == $member->state_id)
                                    <option value="{{ $lga->id }}" {{ ($member->lga_id == $lga->id) ? 'selected' : '' }}>{{ $lga->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                    <!-- Voting Ward -->
                    <div class="form-group row">
                        <label for="ward-dropdown" class="col-sm-2 col-form-label">Voting Ward</label>
                        <div class="col-sm-10">
                            <select class="form-control" name="ward_id" id="ward-dropdown">
                                <option value="" disabled selected>Select Ward</option>
                                @foreach($wards as $ward)
                                    @if($ward->lga_id == $member->lga_id)
                                        <option value="{{ $ward->id }}" {{ ($member->ward_id == $ward->id) ? 'selected' : '' }}>{{ $ward->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Polling Unit -->
                    <div class="form-group row">
                        <label for="pu-dropdown" class="col-sm-2 col-form-label">Voting Polling Unit</label>
                        <div class="col-sm-10">
                            <select class="form-control" name="polling_unit_id" id="pu-dropdown">
                                <option value="" disabled selected>Select Polling Unit</option>
                                @foreach($pollingUnits as $pu)
                                    @if($pu->ward_id == $member->ward_id)
                                        <option value="{{ $pu->id }}" {{ ($member->polling_unit_id == $pu->id) ? 'selected' : '' }}>{{ $pu->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>


<!-- Access Level -->
<div class="form-group row">
    <label for="access_level" class="col-sm-2 col-form-label">Access Level</label>
    <div class="col-sm-10">
        <select class="form-control @error('access_level') is-invalid @enderror" name="access_level" id="access_level" required>
            <option value="" disabled {{ old('access_level', $selectedAccessLevel ?? null) ? '' : 'selected' }}>Select Access Level</option>
            @include('backend.shared.member.access-level-options', ['selectedAccessLevel' => $selectedAccessLevel ?? ($member->access_level ?? null)])
        </select>
        @error('access_level')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

@if(!empty($assignableAccessLevels ?? []))


@if(!empty($invalidPackageRoleWarning))
    <div class="alert alert-warning">{{ $invalidPackageRoleWarning }}</div>
@endif

<!-- Role -->
<div class="form-group row">
    <label for="roles" class="col-sm-2 col-form-label">Role</label>
    <div class="col-sm-10">
        <select class="form-control @error('roles') is-invalid @enderror" name="roles" id="roles" >
            <option value="" disabled selected>Select Role</option>
            @foreach($roles as $role)
            <option value="{{ $role->id }}" {{ $member->roles->contains('id', $role->id) ? 'selected' : '' }}>
                {{ $role->name }}
            </option>
        @endforeach

            <!-- Options will be populated dynamically -->
        </select>
        @error('roles')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
@endif

<!-- Religion -->
<div class="form-group row">
    <label for="religion" class="col-sm-2 col-form-label">Religion</label>
    <div class="col-sm-10">
        <select id="religion" name="religion_id" class="form-control">
            <option value="" disabled selected>Choose Your Religion</option>
            @foreach($religions as $religion)
                <option value="{{ $religion->id }}" {{ ($member->religion_id == $religion->id) ? 'selected' : '' }}>{{ $religion->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<!-- Residential Address -->
<div class="form-group row">
    <label for="address" class="col-sm-3 col-form-label">Residential Address</label>
    <div class="col-sm-9">
        <textarea class="form-control" id="address" name="address">{{ old('address', $member->address) }}</textarea>
    </div>
</div>

<!-- Occupation/Profession -->
<div class="form-group row">
    <label for="occupation" class="col-sm-3 col-form-label">Occupation/Profession</label>
    <div class="col-sm-9">
        <textarea class="form-control" id="occupation" name="occupation">{{ old('occupation', $member->occupation) }}</textarea>
    </div>
</div>

<!-- Educational Qualification -->
<div class="form-group row">
    <label for="qualification" class="col-sm-5 col-form-label">Highest Educational Qualification Obtained</label>
    <div class="col-sm-7">
        <textarea class="form-control" id="qualification" name="qualification">{{ old('qualification', $member->qualification) }}</textarea>
    </div>
</div>

<!-- Profile Photo Upload -->
                                        <div class="form-group row">
                                            <span class="b ms-6">
                                                    <!-- Custom file input button -->
                                                <label for="image" class="custom-file-upload" id="custom-label">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class=" off-set-2 col-sm-6 image">
                                                <img id="showimage" class="img-circle" src="{{(!empty($member->photo)) ? url('uploads/member_images/'.$member->photo) : url('uploads/no_image.jpg')}}" alt="profile" width="45%" height="45%">
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




                                        <!-- Submit button -->
                                        <div class="form-group row">
                                            <div class="col-sm-12">
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
@include('backend.shared.member.boundary-dropdown-scripts')
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
            const input = document.querySelector("#phone");
            const button = document.querySelector("#btn");
            const errorMsg = document.querySelector("#error-msg");
            const validMsg = document.querySelector("#valid-msg");

            // here, the index maps to the error code returned from getValidationError - see readme
            const errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];

            window.intlTelInput(input, {
                initialCountry: "ng",
            geoIpLookup: callback => {
                fetch("https://ipapi.co/json")
                .then(res => res.json())
                .then(data => callback(data.country_code))
                .catch(() => callback("us"));

            },
                separateDialCode: false,
                hiddenInput: "full_phone",
                utilsScript: "{{asset('assets/js/intl-tel-input/utils.js')}}",
            });

                const reset = () => {
                input.classList.remove("error");
                errorMsg.innerHTML = "";
                errorMsg.classList.add("hide");
                validMsg.classList.add("hide");
                };

                // on click button: validate
                button.addEventListener('click', () => {
                reset();
                if (input.value.trim()) {
                    if (iti.isPossibleNumber()) {
                    validMsg.classList.remove("hide");
                    } else {
                    input.classList.add("error");
                    const errorCode = iti.getValidationError();
                    errorMsg.innerHTML = errorMap[errorCode];
                    errorMsg.classList.remove("hide");
                    }
                }
                });

                // on keyup / change flag: reset
                input.addEventListener('change', reset);
                input.addEventListener('keyup', reset);
</script>

{{-- Role Dependent Select Dropdown --}}
<script>
    document.getElementById('access_level').addEventListener('change', function() {
        let accessLevel = this.value;

        // Make an AJAX request to get roles based on access_level
        fetch("{{ url('get-roles-by-access-level') }}/" + encodeURIComponent(accessLevel))
            .then(response => response.json())
            .then(data => {
                let rolesDropdown = document.getElementById('roles');
                rolesDropdown.innerHTML = ''; // Clear previous options

                // Add the default "Select Role" option
                let defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.text = 'Select Role';
                defaultOption.disabled = true;
                defaultOption.selected = true;
                rolesDropdown.appendChild(defaultOption);

                // Add the roles dynamically
                data.forEach(role => {
                    let option = document.createElement('option');
                    option.value = role.id;
                    option.text = role.name;
                    rolesDropdown.appendChild(option);
                });
            });
    });
</script>


@endsection


