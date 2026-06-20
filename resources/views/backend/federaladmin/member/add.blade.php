@extends('backend.template.backend-master')
@section('content')


<style>
    .text-primary, .card-outline{
        color: {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#008751' }} !important;
    }
</style>


    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Add Member</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.member.add') }}"><button class="btn btn-default"> <i class="fa fa-plus-square"></i> Add New Member</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">

                    <form action="{{ route($profileData->access_level.'.member.store') }}" method="POST">
                        @csrf

                        @include('backend.'.$profileData->access_level.'.member.add-member-form')
                    </form>

                </div>
                <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
        </section>
    <!-- /.content -->


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
                  $('#state-dropdown').html('<option value="">Select Voting State</option>');
                        $.each(result.states, function (key, value) {
                            $("#state-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        });

                    $('#lga-dropdown').html('<option value="">Select Voting LGA</option>');
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
                  $('#lga-dropdown').html('<option value="">Select Voting LGA</option>');
                        $.each(result.lgas, function (key, value) {
                            $("#lga-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        });
                    $('#ward-dropdown').html('<option value="">Select Voting Ward</option>');
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
                  $('#ward-dropdown').html('<option value="">Select Voting Ward</option>');
                        $.each(result.wards, function (key, value) {
                            $("#ward-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        });
                    $('#pu-dropdown').html('<option value="">Select Voting Polling Units</option>');
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
                    $('#pu-dropdown').html('<option value="">Select Voting Polling Units</option>');
                        $.each(result.pollingUnits, function (key, value) {
                            $("#pu-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        });
                }
            });
        });
    });
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
