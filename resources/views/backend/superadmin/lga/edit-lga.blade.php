@extends('backend.template.backend-master')
@section('content')

    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Edit Local Government Area</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.location.lgas') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i> All local Government Area</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">


                        <form action="{{ route($profileData->access_level.'.location.lga.update', $lga->uuid) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('POST')
                            <div class="form-group row">
                                <label for="name" class="col-sm-3 col-form-label">Local Government Area Name</label>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name', $lga->name) }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="state" class="col-sm-3 col-form-label">State</label>
                                <div class="col-sm-5">
                                    <select class="form-control @error('state') is-invalid @enderror" name="state_id" id="state">
                                        <option value="" disabled selected>Select State</option>
                                        @foreach ($states as $state)
                                            <option value="{{ $state->id }}"{{ old('state_id', $lga->state_id ?? '') == $state->id ? 'selected' : '' }}>
                                                {{ $state->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('state_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="senatorial_district_id" class="col-sm-3 col-form-label">Senatorial District</label>
                                <div class="col-sm-5">
                                    <select class="form-control @error('senatorial_district_id') is-invalid @enderror" name="senatorial_district_id" id="senatorial_district_id" data-boundary="state">
                                        <option value="">Select Senatorial District</option>
                                        @foreach ($senatorialDistricts as $district)
                                            <option value="{{ $district->id }}" data-state-id="{{ $district->state_id }}" {{ old('senatorial_district_id', $lga->senatorial_district_id) == $district->id ? 'selected' : '' }}>{{ $district->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('senatorial_district_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="federal_constituency_id" class="col-sm-3 col-form-label">Federal Constituency</label>
                                <div class="col-sm-5">
                                    <select class="form-control @error('federal_constituency_id') is-invalid @enderror" name="federal_constituency_id" id="federal_constituency_id" data-boundary="state">
                                        <option value="">Select Federal Constituency</option>
                                        @foreach ($federalConstituencies as $constituency)
                                            <option value="{{ $constituency->id }}" data-state-id="{{ $constituency->state_id }}" {{ old('federal_constituency_id', $lga->federal_constituency_id) == $constituency->id ? 'selected' : '' }}>{{ $constituency->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('federal_constituency_id')
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
                        <script>
                            (function () {
                                const state = document.getElementById('state');
                                const selects = document.querySelectorAll('select[data-boundary="state"]');
                                function filter() {
                                    selects.forEach(function (select) {
                                        Array.from(select.options).forEach(function (option) {
                                            if (!option.value) return;
                                            option.hidden = state.value && option.dataset.stateId !== state.value;
                                        });
                                        if (select.selectedOptions[0] && select.selectedOptions[0].hidden) select.value = '';
                                    });
                                }
                                state && state.addEventListener('change', filter);
                                filter();
                            })();
                        </script>





                   
        
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


@endsection



