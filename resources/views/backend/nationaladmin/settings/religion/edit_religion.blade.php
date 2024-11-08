@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Edit Religion</h3>
                <div class="card-tools">
              <a href="{{ route($profileData->access_level.'.religion') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i> All Religions</button></a>
            </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

                <form action="{{ route($profileData->access_level.'.religion.update', $religion->uuid) }}"  method="POST">
                    @csrf
                    @method('POST')


                    <div class="form-group">
                        <label for="name">Religion Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="Enter Religion Name" name="name" value="{{ old('name', $religion->name) }}" required>

                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Optional Description --}}
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" placeholder="Enter Description (Optional)" name="description" value="{{ old('description', $religion->description) }}"> {{$religion->description}} </textarea>

                        @error('description')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>



                    <button type="submit" class="btn btn-primary">Update</button>

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




