
@php
    $currentPackage = app(\App\Services\PackageGovernanceService::class)->normalize($SystemSetting->package);
@endphp

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
                <input type="hidden" name="package" value="{{ $currentPackage }}" />

                <select disabled class="form-control @error('package') is_invalid @enderror" name="package" id="package">
                        <option value="" disabled selected>Select Package</option>
                        @foreach($packages as $package => $label)
                            <option value="{{ $package }}" {{ $currentPackage == $package ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                </select>


            </div>
            @error('package')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
    </div>
@else
    <input type="hidden" name="package" value="{{ $currentPackage }}" />
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
            <label for="phone" class="col-sm-3 col-form-label">Office Phone</label>
            <div class="col-sm-9">
                <input type="text" id="phone" class="form-control @error('company_phone') is_invalid @enderror" name="company_phone" type="tel" pattern="\+\d{1,3}\d{5,15}" value="{{ old('phone', $SystemSetting->company_phone) }}" title="Please enter a valid phone number with country code without any space (e.g., +2348061906478)" value="{{ $SystemSetting->company_phone }}">
            </div>
            @error('company_phone')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group row">
            <label for="require_bank_details" class="col-sm-3 col-form-label">Require Bank Details</label>
            <div class="col-sm-9 d-flex align-items-center">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="require_bank_details" name="require_bank_details" value="1" {{ $SystemSetting->require_bank_details ? 'checked' : '' }}>
                    <label class="form-check-label" for="require_bank_details">Force all profiles to include bank name & account number</label>
                </div>
            </div>
        </div>


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
