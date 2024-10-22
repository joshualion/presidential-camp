@extends('backend.template.backend-master')
@section('content')

    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Add Polling Unit</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.location.pus') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i> All Polling Units</button></a>
                        <a href="{{route($profileData->access_level.'.location.pu.import')}}"><button class="btn btn-dark"> <i class="fa fa-file-excel"></i> Import PU's</button></a>

                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">


                        <form action="{{ route($profileData->access_level.'.location.pu.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('POST')
                            <div class="form-group row">
                                <label for="name" class="col-sm-3 col-form-label">PU Name</label>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="lga-dropdown" class="col-sm-3 col-form-label">Local Government Area</label>
                                <div class="col-sm-5">
                                    <select class="form-control @error('lga_id') is-invalid @enderror" name="lga_id" id="lga-dropdown">
                                        <option value="" disabled selected>Select Local Government Area</option>
                                       
                                        @foreach ($lgas->groupBy('state.name') as $stateName => $lgasByState)
                                            <optgroup label="{{ $stateName }}">
                                                @foreach ($lgasByState as $lga)
                                                    <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('lga_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="ward-dropdown" class="col-sm-3 col-form-label">Ward</label>
                                <div class="col-sm-5">
                                    <select class="form-control @error('ward_id') is-invalid @enderror" name="ward_id" id="ward-dropdown">
                                        <option value="" disabled selected>Select Ward</option>
                                       
                                        @foreach ($wards->groupBy('lga_name') as $lgaName => $wardsByLga)
                                            <optgroup label="{{ $lgaName }}">
                                                @foreach ($wardsByLga as $ward)
                                                    <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach

                                    </select>
                                    @error('ward_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>


                            <div class="form-group row">
                                <div class="offset-sm-3">
                                    <div class="form-check">
                                        {{-- just placeholder --}}
                                    </div>
                                </div>
                                <div class="col-sm-8">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </div>
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

@endsection




