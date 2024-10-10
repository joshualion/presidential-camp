
@if($profileData->access_level == 'superadmin')
    <div class="form-group row">
        <label for="system_country" class="col-sm-3 col-form-label">System Country</label>
            <div class="col-sm-9">
                <select  class="form-control @error('system_country') is_invalid @enderror" name="system_country" id="system_country">
                        <option value="" disabled selected>Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->name }}" {{ $SystemSetting->system_country == $country->name ? 'selected' : '' }}>
                                {{ ucfirst($country->name) }}
                            </option>

                        @endforeach
                </select>


            </div>
            @error('system_country')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
    </div>
@else
    <input type="hidden" name="system_country" value="{{ $SystemSetting->system_country }}" />
@endif

@if($profileData->access_level == 'superadmin')
    <div class="form-group row">
        <label for="package" class="col-sm-3 col-form-label">Package</label>
            <div class="col-sm-9">
                <select  class="form-control @error('package') is_invalid @enderror" name="package" id="package">
                        <option value="" disabled selected>Select Package</option>
                        @foreach($packages as $package)
                            <option value="{{ $package }}" {{ $SystemSetting->package == $package ? 'selected' : '' }}>
                                {{ ucfirst($package) }} <!-- Capitalizes the first letter of the package name -->
                            </option>
                        @endforeach
                </select>


            </div>
            @error('package')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
    </div>
@else
    <input type="hidden" name="package" value="{{ $SystemSetting->package }}" />
@endif

        <div class="form-group row">
            <label for="system_name" class="col-sm-3 col-form-label">System Name</label>
            <div class="col-sm-9">
                <input type="text" class="form-control @error('system_name') is_invalid @enderror" name="system_name" id="system_name" value="{{ $SystemSetting->system_name }}" placeholder="System Name">
            </div>
            @error('system_name')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group row">
            <label for="campaign_slogan" class="col-sm-3 col-form-label"> Slogan(Tagline) </label>
            <div class="col-sm-9">
                <input type="text" class="form-control @error('campaign_slogan') is_invalid @enderror" name="campaign_slogan" id="campaign_slogan" value="{{ $SystemSetting->campaign_slogan }}" placeholder="System Name">
            </div>
            @error('campaign_slogan')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group row">
            <label for="system_email" class="col-sm-3 col-form-label">System Email</label>
            <div class="col-sm-9">
                <input type="text" class="form-control @error('system_email') is_invalid @enderror" name="system_email" id="system_email" value="{{ $SystemSetting->system_email }}">
            </div>
            @error('system_email')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group row">
            <label for="system_currency" class="col-sm-3 col-form-label">System Currency</label>
            <div class="col-sm-9">
                <input type="text" class="form-control @error('system_currency') is_invalid @enderror" name="system_currency" id="system_currency" value="{{ $SystemSetting->system_currency }}" placeholder="587">
            </div>
            @error('system_currency')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group row">
            <label for="company_address" class="col-sm-3 col-form-label">Office Address</label>
            <div class="col-sm-9">
                <input type="text" class="form-control @error('company_address') is_invalid @enderror" name="company_address" id="company_address" value="{{ $SystemSetting->company_address }}" placeholder="youremail@gmail.com">
            </div>
            @error('company_address')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group row">
            <label for="company_phone" class="col-sm-3 col-form-label">Office Phone</label>
            <div class="col-sm-9">
                <input type="text" class="form-control @error('company_phone') is_invalid @enderror" name="company_phone" id="company_phone" value="{{ $SystemSetting->company_phone }}">
            </div>
            @error('company_phone')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        </div>
