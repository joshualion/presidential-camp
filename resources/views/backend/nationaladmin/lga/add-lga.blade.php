@extends('backend.template.backend-master')
@section('content')

    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Add Local Government Area</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.location.lgas') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i> All local Government Area</button></a>
                        <a href="{{route($profileData->access_level.'.location.lga.import')}}"><button class="btn btn-dark"> <i class="fa fa-file-excel"></i> Import LGAs</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">


                        <form action="{{ route($profileData->access_level.'.location.lga.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('POST')
                            <div class="form-group row">
                                <label for="name" class="col-sm-3 col-form-label">Local Government Area Name</label>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="state" class="col-sm-3 col-form-label">State</label>
                                <div class="col-sm-5">
                                    <select class="form-control @error('state') is-invalid @enderror" name="state" id="state">
                                        <option value="" disabled selected>Select State</option>
                                        @foreach ($states as $state)
                                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('state')
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




