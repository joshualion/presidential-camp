@extends('backend.template.backend-master')
@section('content')

    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Edit Ward</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.location.wards') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i> All Wards</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">


                        <form action="{{ route($profileData->access_level.'.location.ward.update', $ward->uuid) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('POST')
                            <div class="form-group row">
                                <label for="name" class="col-sm-3 col-form-label">Name</label>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name', $ward->name) }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="lga_id" class="col-sm-3 col-form-label">Local Government Area</label>
                                <div class="col-sm-5">
                                    <select class="form-control @error('lga_id') is-invalid @enderror" name="lga_id" id="lga_id">
                                        <option value="" disabled selected>Select Local Government Area</option>
                                        @foreach ($lgas->groupBy('state.name') as $stateName => $lgasByState)
                                            <optgroup label="{{ $stateName }}">
                                                @foreach ($lgasByState as $lga)
                                                <option value="{{ $lga->id }}" {{ old('lga_id', $ward->lga_id) == $lga->id ? 'selected' : '' }}>
                                                    {{ $lga->name }}
                                                </option>                                                
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


@endsection




