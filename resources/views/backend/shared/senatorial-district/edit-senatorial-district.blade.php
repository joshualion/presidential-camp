<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Edit Senatorial District</h3>
                        <div class="card-tools">
                            <a href="{{ route($profileData->access_level.'.location.senatorial-districts') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i> All Senatorial Districts</button></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route($profileData->access_level.'.location.senatorial-district.update', $district->uuid) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('POST')
                            <div class="form-group row">
                                <label for="name" class="col-sm-3 col-form-label">Senatorial District Name</label>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name', $district->name) }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="state_id" class="col-sm-3 col-form-label">State</label>
                                <div class="col-sm-5">
                                    <select class="form-control @error('state_id') is-invalid @enderror" name="state_id" id="state_id">
                                        <option value="" disabled>Select State</option>
                                        @foreach ($states as $state)
                                            <option value="{{ $state->id }}" {{ old('state_id', $district->state_id) == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('state_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="offset-sm-3 col-sm-8">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
