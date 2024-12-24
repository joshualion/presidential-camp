@extends('backend.template.backend-master')
@section('content')
<style>
    .blinking-text {
        animation: blink-animation 1s steps(5, start) infinite;
        color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
    }

    @keyframes blink-animation {
        5% {
            visibility: hidden;
        }
    }
</style>


<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-9">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Send Bulk SMS</h3>
                    </div>
                    <div class="card-body">
                        <form id="send-sms-form" class="form" action="{{ route($profileData->access_level.'.bulksms.send.sms') }}" method="POST">
                            @csrf
                            
                            <div class="form-group">
                                <label for="recipient-location">Filter Recipient(s) By Location</label>
                                <div class="row">
                                    <!-- Country Field -->
                                    <div class="col-md-12">
                                        <label>Country</label>
                                        <select class="form-control" disabled>
                                            <option value="{{ $profileData->country_id }}" selected>
                                                {{ $profileData->country->name }}
                                            </option>
                                        </select>
                                        <input type="hidden" name="country_id" value="{{ $profileData->country_id }}">
                                        <small class="form-text text-muted text-danger">
                                            No Change Required.
                                        </small>
                                    </div>

                                </div>

                                <div class="row">

                                    <!-- Region Field -->
                                    @if($profileData->access_level == "superadmin" || $profileData->access_level == "nationaladmin")
                                        <div class="col-md-12">
                                            <label>Region</label>
                                            <select class="form-control" name="region_id" id="region-dropdown">
                                                <option value="" disabled selected>Select Region</option>
                                                @foreach ($regions as $region)
                                                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted text-primary">
                                                If no region is selected, the SMS will be sent to everyone in the country.
                                            </small>
                                        </div>
                                    @elseif($profileData->access_level == "regionaladmin")
                                        <input type="hidden" name="region_id" value="{{ $profileData->region_id }}">
                                        <input type="hidden" name="country_id" value="{{ $profileData->country_id }}">
                                        <div class="col-md-12">
                                            <label>Region</label>
                                            <select class="form-control" disabled>
                                                <option value="{{ $profileData->region_id }}" selected>
                                                    {{ $profileData->region->name }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted text-primary">
                                                If no state is selected, the SMS will be sent to everyone in your region.
                                            </small>
                                        </div>
                                    @endif
                                </div>

                                <!-- State Field -->
                                @if($profileData->access_level == "superadmin" || $profileData->access_level == "nationaladmin")
                                    <div class="row mt-3">
                                        <div class="col-md-12" id="state-dropdown-col">
                                            <label>State</label>
                                            <select class="form-control" name="state_id" id="state-dropdown" style="display:none;">
                                                <option value="" disabled selected>Select State</option>
                                            </select>
                                            <small class="form-text text-muted text-danger">
                                                No Change Required.
                                            </small>
                                        </div>
                                    </div>
                                @elseif($profileData->access_level == "regionaladmin")
                                    <div class="row mt-3">
                                        <div class="col-md-12" id="state-dropdown-col">
                                            <label>State</label>
                                            <select class="form-control" name="state_id" id="state-dropdown">
                                                <option value="" disabled selected>Select State</option>
                                            </select>
                                            <small class="form-text text-muted text-danger">
                                                If no state is selected, the SMS will be sent to everyone in your region.
                                            </small>
                                        </div>
                                    </div>
                                    <script>
                                        $(document).ready(function() {
                                            const regionId = "{{ $profileData->region_id }}";
                                            loadStates(regionId); // Load states based on region
                                        });
                                    </script>
                                @elseif($profileData->access_level == "stateadmin")
                                    <input type="hidden" name="state_id" value="{{ $profileData->state_id }}">
                                    <div class="row mt-3">
                                        <div class="col-md-12" id="lga-dropdown-col">
                                            <label>LGA</label>
                                            <select class="form-control" name="lga_id" id="lga-dropdown">
                                                <option value="" disabled selected>Select LGA</option>
                                            </select>
                                            <small class="form-text text-muted text-danger">
                                                If no LGA is selected, the SMS will be sent to everyone in the selected state.
                                            </small>
                                        </div>
                                    </div>
                                    <script>
                                        $(document).ready(function() {
                                            const stateId = "{{ $profileData->state_id }}";
                                            loadLgas(stateId); // Load LGAs based on state
                                        });
                                    </script>
                                @endif

                                <!-- LGA Field -->
                                @if($profileData->access_level == "superadmin" || $profileData->access_level == "nationaladmin" || $profileData->access_level == "regionaladmin")
                                    <div class="row mt-3">
                                        <div class="col-md-12" id="lga-dropdown-col">
                                            <label>LGA</label>
                                            <select class="form-control" name="lga_id" id="lga-dropdown" style="display:none;">
                                                <option value="" disabled selected>Select LGA</option>
                                            </select>
                                            <small class="form-text text-muted text-danger">
                                                If no LGA is selected, the SMS will be sent to everyone in the selected state.
                                            </small>
                                        </div>
                                    </div>
                                @endif

                                <!-- Ward Field -->
                                @if($profileData->access_level == "superadmin" || $profileData->access_level == "nationaladmin" || $profileData->access_level == "regionaladmin" || $profileData->access_level == "stateadmin")
                                    <div class="row mt-3">
                                        <div class="col-md-12" id="ward-dropdown-col">
                                            <label>Ward</label>
                                            <select class="form-control" name="ward_id" id="ward-dropdown" style="display:none;">
                                                <option value="" disabled selected>Select Ward</option>
                                            </select>
                                            <small class="form-text text-muted text-danger">
                                                If no ward is selected, the SMS will be sent to everyone in the selected LGA.
                                            </small>
                                        </div>
                                    </div>
                                @endif

                                <!-- Polling Unit Field -->
                                @if($profileData->access_level == "superadmin" || $profileData->access_level == "nationaladmin" || $profileData->access_level == "regionaladmin" || $profileData->access_level == "stateadmin")
                                    <div class="row mt-3">
                                        <div class="col-md-12" id="pu-dropdown-col">
                                            <label>Polling Unit</label>
                                            <select class="form-control" name="pu_id" id="pu-dropdown" style="display:none;">
                                                <option value="" disabled selected>Select Polling Unit</option>
                                            </select>
                                            <small class="form-text text-muted text-danger">
                                                If no polling unit is selected, the SMS will be sent to everyone in the selected ward.
                                            </small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            

                            <div class="col-lg-12 col-12">
                                <div class="form-group">
                                    <label for="support_group_id">Recipient Group</label>
                                    
                                    <select class="form-control" name="support_group_id" id="support_group_id">
                                        <option value="">Choose Recipient Group</option>
                                        @foreach($supportGroups as $supportGroup)
                                            <option value="{{ $supportGroup->id }}">
                                                {{ $supportGroup->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted text-danger">
                                        
                                        <code>No change is required if sending to all Groups</code>
                                    </small>
                                   
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <label for="message">Message</label>
                                <div style="float:right;" class="blinking-text  text-upper">
                                    <small>Estimated Number of Recipients:</small>
                                    <span id="total-recipients-count">0</span>
                                </div>
                                <textarea name="message" class="form-control" rows="5" id="message"></textarea>
                                <small class="form-text text-muted text-danger">
                                    <span id="count">0</span>/160 characters | Pages: <span id="pageCount">1</span>
                                </small>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" id="send-sms-button" >
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.getElementById('message');
    const charCount = document.getElementById('count');
    const pageCount = document.getElementById('pageCount');

    messageInput.addEventListener('input', function() {
        const messageLength = this.value.length;
        charCount.textContent = messageLength;
        pageCount.textContent = Math.ceil(messageLength / 160);
    });
});
</script>
<!-- jQuery -->
<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>


<script>
    $(document).ready(function () {
        const userAccessLevel = "{{ $profileData->access_level }}"; // Example: 'superadmin', 'regionaladmin', etc.
        const userRegionId = "{{ $profileData->region_id ?? '' }}";
        const userStateId = "{{ $profileData->state_id ?? '' }}";
        const userLgaId = "{{ $profileData->lga_id ?? '' }}";
        const userWardId = "{{ $profileData->ward_id ?? '' }}";
        const userPuId = "{{ $profileData->pu_id ?? '' }}";

        // Hide all dropdowns initially
        $(' #lga-dropdown-col, #ward-dropdown-col, #pu-dropdown-col').hide();
        $(' #lga-dropdown, #ward-dropdown, #pu-dropdown').hide();

        // Load state options based on the region selection
        
            var region_id = userRegionId;
            loadStates(region_id); // Load states based on regionn after region is selected
            fetchRecipientCount(); // Update recipient count

        // Load LGA options based on state selection
        $('#state-dropdown').on('change', function () {
            var state_id = this.value;
            loadLgas(state_id); // Load LGAs based on state
            $('#lga-dropdown-col').show(); // Show LGA dropdown-col after state is selected
            $('#lga-dropdown').show(); // Show LGA dropdown after state is selected
            fetchRecipientCount(); // Update recipient count
        });

        // Load ward options based on LGA selection
        $('#lga-dropdown').on('change', function () {
            var lga_id = this.value;
            loadWards(lga_id); // Load wards based on LGA
            $('#ward-dropdown-col').show(); // Show ward dropdown-col after LGA is selected
            $('#ward-dropdown').show(); // Show ward dropdown after LGA is selected
            fetchRecipientCount(); // Update recipient count
        });

        // Load polling unit options based on ward selection
        $('#ward-dropdown').on('change', function () {
            var ward_id = this.value;
            loadPollingUnits(ward_id); // Load polling units based on ward
            $('#pu-dropdown-col').show(); // Show polling unit dropdown-col after ward is selected
            $('#pu-dropdown').show(); // Show polling unit dropdown after ward is selected
            fetchRecipientCount(); // Update recipient count
        });

        $('#pu-dropdown').on('change', function () {
            var pu_id = this.value;
                fetchRecipientCount(); // Trigger the recipient count update
        });

        // Functions to Load Data (AJAX)
        function loadStates(region_id) {
            $.ajax({
                url: "{{ route('getStates') }}",
                type: "POST",
                data: { region_id: region_id, _token: '{{ csrf_token() }}' },
                success: function (result) {
                    $('#state-dropdown').html('<option value="" disabled selected>Select State</option>');
                    $.each(result.states, function (key, value) {
                        $('#state-dropdown').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                    $('#lga-dropdown, #ward-dropdown, #pu-dropdown').html('<option value="" disabled selected>Select</option>'); // Reset lower levels
                }
            });
        }

        function loadLgas(state_id) {
            $.ajax({
                url: "{{ route('getLgas') }}",
                type: "POST",
                data: { state_id: state_id, _token: '{{ csrf_token() }}' },
                success: function (result) {
                    $('#lga-dropdown').html('<option value="" disabled selected>Select LGA</option>');
                    $.each(result.lgas, function (key, value) {
                        $('#lga-dropdown').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                    $('#ward-dropdown, #pu-dropdown').html('<option value="" disabled selected>Select</option>'); // Reset lower levels
                }
            });
        }

        function loadWards(lga_id) {
            $.ajax({
                url: "{{ route('getWards') }}",
                type: "POST",
                data: { lga_id: lga_id, _token: '{{ csrf_token() }}' },
                success: function (result) {
                    $('#ward-dropdown').html('<option value="" disabled selected>Select Ward</option>');
                    $.each(result.wards, function (key, value) {
                        $('#ward-dropdown').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                    $('#pu-dropdown').html('<option value="" disabled selected>Select</option>'); // Reset lower levels
                }
            });
        }

        function loadPollingUnits(ward_id) {
            $.ajax({
                url: "{{ route('getPollingUnits') }}",
                type: "POST",
                data: { ward_id: ward_id, _token: '{{ csrf_token() }}' },
                success: function (result) {
                    $('#pu-dropdown').html('<option value="" disabled selected>Select Polling Unit</option>');
                    $.each(result.pollingUnits, function (key, value) {
                        $('#pu-dropdown').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                }
            });
        }

        // Function to fetch recipient count
        function fetchRecipientCount() {
             const groupId = $('#support_group_id').val();
            const regionIdFilter = region_id || null;
            const stateIdFilter = $('#state-dropdown').val() || null;
            const lgaIdFilter = $('#lga-dropdown').val() || null;
            const wardIdFilter = $('#ward-dropdown').val() || null;
            const puIdFilter = $('#pu-dropdown').val() || null;

            $.ajax({
                url: "/get-group-recipients",
                type: "POST",
                data: {
                    group_id: groupId,
                    country_id: 1, // Static for all users
                    region_id: regionIdFilter,
                    state_id: stateIdFilter,
                    lga_id: lgaIdFilter,
                    ward_id: wardIdFilter,
                    pu_id: puIdFilter,
                    _token: $("meta[name='csrf-token']").attr("content"),
                },
                success: function (response) {
                    const recipientCount = response.total || 0;
                    // Format recipient count with commas
                    const formattedCount = recipientCount.toLocaleString();
                    $('#total-recipients-count').text(formattedCount);
                },
                error: function (xhr) {
                    console.error('AJAX Error:', xhr.responseText);
                    $('#total-recipients-count').text(0);
                },
            });
        }

        // Trigger initial recipient count fetch
        fetchRecipientCount();
    });
</script>

   {{-- SMS Sending Confirmation --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sendSmsForm = document.getElementById('send-sms-form'); // Assuming form has this ID
        const sendSmsButton = document.getElementById('send-sms-button'); // Button to trigger SMS

        sendSmsButton.addEventListener('click', function (event) {
            event.preventDefault();

            const formData = new FormData(sendSmsForm);
            formData.append('preview', true); // Indicate preview mode

            //customize button colors
           var yesColor = "{{ $SystemSetting->dark_theme_color ?? ' #008751'}}" ;
           var noColor = "#333333" ;

            // Fetch preview data from the server
            fetch(sendSmsForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Confirm SMS Sending',
                        html: `<p><strong>Message:</strong> ${data.message}</p>
                               <p><strong>Recipients:</strong> ${data.recipient_count}</p>`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: yesColor,
                        cancelButtonColor: noColor,
                        confirmButtonText: 'Send',
                        cancelButtonText: 'Cancel',
                    }).then(result => {
                        if (result.isConfirmed) {
                            // Remove preview flag and resubmit the form
                            formData.delete('preview');
                            fetch(sendSmsForm.action, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: formData,
                            })
                            .then(response => response.json())
                            .then(finalData => {
                                Swal.fire({
                                    title: finalData.status === 'success' ? 'Success' : 'Error',                                    
                                    confirmButtonColor: yesColor,
                                    text: finalData.message,
                                    icon: finalData.status === 'success' ? 'success' : 'error',
                                }).then(() => {
                                        // Reload the page after the Swal alert is closed
                                        location.reload();
                                    });
                            });
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: "#d33",
                        icon: 'error',
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while processing your request.',
                    confirmButtonColor: "#d33",
                    icon: 'error',
                });
            });
        });
    });
</script>



@endsection
