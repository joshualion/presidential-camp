
            <!-- Username Unique -->
            <div class="form-group row">
                <label for="username" class="col-sm-3 col-form-label">Username</label>
                <div class="col-sm-9">
                    <div class="  input-group mb-3">
                        <input value="{{ old('username') }}" type="text" class="form-control @error('username') is-invalid @enderror " id="username" name="username" required placeholder="Username *" autofocus="false">
                        <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user text-primary"></span>
                        </div>
                        </div>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        <!-- / Username Unique -->


            <!-- Firstname-->
            <div class="form-group row">
                <label for="firstname" class="col-sm-3 col-form-label">First Name</label>
                <div class="col-sm-9">
                    <div class=" input-group mb-3">
                            <input value="{{ old('firstname') }}" type="text" class="form-control  @error('firstname') is-invalid @enderror" id="firstname" name="firstname" required placeholder="First Name *">
                            <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user text-primary"></span>
                            </div>
                            </div>
                            @error('firstname')
                                <div class="invalid-feedback text-warning">{{ $message }}</div>
                            @enderror
                    </div>
                </div>
            </div>
            <!-- / Firstname-->


            <!-- Lastname-->
            <div class="form-group row">
                <label for="lastname" class="col-sm-3 col-form-label">Last Name</label>
                <div class="col-sm-9">
                    <div class=" input-group mb-3">
                        <input value="{{ old('lastname') }}" type="text" class="form-control @error('lastname') is-invalid @enderror" id="lastname" name="lastname" required placeholder="Last Name *">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user text-primary"></span>
                            </div>
                        </div>
                        @error('lastname')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>


           <div class="form-group row">
            <label for="email" class="col-sm-3 col-form-label">Email</label>
            <div class="col-sm-9">
                <div class=" input-group">
                <input value="{{ old('email') }}" type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="Email">
                <div class="input-group-append">
                    <div class="input-group-text">
                    <span class="fas fa-envelope text-primary"></span>
                    </div>
                </div>
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                </div>
            </div>
        </div>




          <!-- Password -->
          <div class="form-group row">
            <label for="password" class="col-sm-3 col-form-label">Password</label>
            <div class="col-sm-9">
                <div class=" input-group mb-3">
                    <input value="{{ old('password') }}" type="password" class="form-control" id="password" name="password" required  placeholder="Password">
                    <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock text-primary"></span>
                    </div>
                    </div>
                </div>
            </div>
          </div>
          <!-- Confirm Password -->
          <div class="form-group row">
            <label for="password_confirmation" class="col-sm-3 col-form-label">Retype Password</label>
            <div class="col-sm-9">
                <div class=" input-group mb-3">
                    <input value="{{ old('password_confirmation') }}" type="password" class="form-control" id="password_confirmation" name="password_confirmation" required  placeholder="Confirm password">
                    <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock text-primary"></span>
                    </div>
                    </div>
                </div>
            </div>
          </div>

          <div class="form-group row">
            <label for="access_level" class="col-sm-3 col-form-label">Access Level</label>
            <div class="col-sm-9">
            <div class=" input-group mb-3">
                <select class="form-control  @error('access_level') is-invalid @enderror" name="access_level" id="access_level">
            <option value="" disabled {{ old('access_level', $selectedAccessLevel ?? null) ? '' : 'selected' }}>Select Access Level</option>
            @include('backend.shared.member.access-level-options', ['selectedAccessLevel' => $selectedAccessLevel ?? ($member->access_level ?? null)])
        </select>
                <div class="input-group-append">
                    <div class="input-group-text">
                      <span class="fas fa-key text-primary"></span>
                    </div>
                  </div>
                  @error('access_level')
                      <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
            </div>
            </div>
          </div>


          <div class="form-group row">
            <label for="roles" class="col-sm-3 col-form-label">Role</label>
            <div class="col-sm-9">
                    <div class=" input-group mb-3">
                        <select  class="form-control  @error('roles') is-invalid @enderror" name="roles" id="roles">
                            <option value="" disabled selected>Select Role</option>
                            {{-- populate dynamically --}}
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text">
                            <span class="fas fa-key text-primary"></span>
                            </div>
                        </div>
                        @error('roles')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
            </div>
          </div>

          @include('backend.shared.member.gender-field')

        <div class="form-group row">
            <input type="hidden" name="country_id" value="{{$profileData->country_id}}" >

        </div>

    <div class="form-group row">
                <label for="region" class="col-sm-2 col-form-label">Voting Region</label>
                <div class="col-sm-10">
                    <select class="form-control" name="region_id">
                        <option value="" disabled selected>Select Region</option>

                            @foreach($regions as $region)
                                    <option value="{{ $region->id }}">
                                        {{ $region->name }}
                                    </option>

                            @endforeach
                    </select>
                </div>
      </div>

      <div class="form-group row">
        <label for="state-dropdown" class="col-sm-2 col-form-label">Voting State</label>
        <div class="col-sm-10">
            <select class="form-control" name="state_id" >
                <option value="" disabled selected>Select State</option>
                @foreach($states as $state)
                        <option value="{{ $state->id }}">
                            {{ $state->name }}
                        </option>
                @endforeach
            </select>
        </div>
    </div>




    @include('backend.shared.member.boundary-fields')

    <div class="form-group row">
        <label for="lga-dropdown" class="col-sm-2 col-form-label">Voting LGA</label>
        <div class="col-sm-10">
            <select class="form-control" name="lga_id">
                <option value="" disabled selected>Select LGA</option>
                {{-- @foreach($lgas as $lga) --}}
                        <option value="{{ $profileData->lga_id }}" >
                            {{ $profileData->lga->name}}
                        </option>
                {{-- @endforeach --}}
            </select>
        </div>
    </div>



    <div class="form-group row">
        <label for="ward-dropdown" class="col-sm-2 col-form-label">Voting Ward</label>
        <div class="col-sm-10">
            <select class="form-control" name="ward_id" >
                <option value="" disabled selected>Select Ward</option>
               <option value="{{ $profileData->ward_id }}" >
                            {{ $profileData->ward->name }}
                </option>
                
            </select>
        </div>
    </div>

    <div class="form-group row">
        <label for="ward-dropdown" class="col-sm-2 col-form-label">Voting PU</label>
        <div class="col-sm-10">
            <select class="form-control" name="polling_unit_id" >
                <option value="" disabled selected>Select Polling Unit</option>
               <option value="{{ $profileData->polling_unit_id }}" >
                            {{ $profileData->pollingUnit->name }}
                </option>
                
            </select>
        </div>
    </div>














            <!-- /.col -->
            <div class="input-group mb-3">
              <button type="submit" class="btn btn-primary fa-pull-right text-center "><i class="fa fa-plus"></i> Add Member</button>

            </div>
            <!-- /.col -->

