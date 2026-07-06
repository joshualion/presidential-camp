@php
    $communityRealtime = app(\App\Services\CommunityRealtimeService::class);
    $communityAvailable = $communityRealtime->moduleAvailable();
@endphp

<div class="form-group row">
    <label for="frontend_community" class="col-sm-3 col-form-label">Community Forum</label>
    <div class="col-sm-9">
        <div class="custom-control custom-switch">
            <!-- Hidden input to ensure '0' is sent when unchecked -->
            <input type="hidden" name="frontend_community" value="0">

            <!-- The actual toggle switch -->
            <input type="checkbox" class="custom-control-input"
                   id="frontend_community"
                   name="frontend_community"
                   value="1"
                   {{ $communityAvailable ? '' : 'disabled' }}
                   {{ $communityAvailable && $SystemSetting->frontend_community == 1 ? 'checked' : '' }}>

            <label class="custom-control-label" for="frontend_community" id="frontend_community_label">
                {{ $communityAvailable ? ($SystemSetting->frontend_community == 1 ? 'Enabled' : 'Disabled') : 'Unavailable' }}
            </label>
        </div>
        @unless($communityAvailable)
            <small class="text-muted">Community Forum is not enabled for this license, so this toggle is locked off.</small>
        @endunless
    </div>
    @error('frontend_community')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group row">
    <label for="frontend_registration" class="col-sm-3 col-form-label">Public Registration</label>
    <div class="col-sm-9">
        <div class="custom-control custom-switch">
            <!-- Hidden input to ensure '0' is sent when unchecked -->
            <input type="hidden" name="frontend_registration" value="0">

            <!-- The actual toggle switch -->
            <input type="checkbox" class="custom-control-input"
                   id="frontend_registration"
                   name="frontend_registration"
                   value="1"
                   {{ $SystemSetting->frontend_registration == 1 ? 'checked' : '' }}>

            <label class="custom-control-label" for="frontend_registration" id="frontend_registration_label">
                {{ $SystemSetting->frontend_registration == 1 ? 'Enabled' : 'Disabled' }}
            </label>
        </div>
    </div>
    @error('frontend_registration')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>


<!-- Optional Custom CSS for better visual styling -->
<style>
    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: #28a745; /* Bootstrap success green color */
    }
    .custom-control-label::before {
        width: 3rem;
        height: 1.5rem;
        background-color: #ccc; /* Default gray when disabled */
        border-radius: 1rem;
        transition: background-color 0.3s ease-in-out;
    }
    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: #28a745; /* Success green when enabled */
    }
    .custom-control-input:checked ~ .custom-control-label::after {
        transform: translateX(1.5rem); /* Move the switch handle */
    }
    .custom-control-label::after {
        width: 1.2rem;
        height: 1.2rem;
        background-color: #fff;
        border-radius: 50%;
        position: absolute;
        top: 0.15rem;
        left: 0.15rem;
        transition: transform 0.3s ease-in-out;
    }
</style>

<!-- JavaScript to update label dynamically -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var frontendCommunityInput = document.getElementById('frontend_registration');
        var frontendCommunityLabel = document.getElementById('frontend_registration_label');

        // Update label based on the initial state
        frontendCommunityLabel.textContent = frontendCommunityInput.checked ? 'Enabled' : 'Disabled';

        // Add event listener to update the label when toggled
        frontendCommunityInput.addEventListener('change', function() {
            frontendCommunityLabel.textContent = frontendCommunityInput.checked ? 'Enabled' : 'Disabled';
        });
    });
</script>


<!-- JavaScript to update label dynamically -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var frontendCommunityInput = document.getElementById('frontend_community');
        var frontendCommunityLabel = document.getElementById('frontend_community_label');

        // Update label based on the initial state
        frontendCommunityLabel.textContent = frontendCommunityInput.disabled ? 'Unavailable' : (frontendCommunityInput.checked ? 'Enabled' : 'Disabled');

        // Add event listener to update the label when toggled
        frontendCommunityInput.addEventListener('change', function() {
            frontendCommunityLabel.textContent = frontendCommunityInput.disabled ? 'Unavailable' : (frontendCommunityInput.checked ? 'Enabled' : 'Disabled');
        });
    });
</script>
