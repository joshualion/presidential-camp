@extends('backend.template.backend-master')
@section('content')

    <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"> Edit Election</h3>
                        <div class="card-tools">
                            <a href="{{ route($profileData->access_level.'.elections') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i> All Elections</button></a>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">

                        <form action="{{ route($profileData->access_level.'.election.update', $election->uuid) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="form-group row">                            
                                <label for="name" class="col-sm-4 col-form-label">Which State Election?</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control @error('name') @enderror" name="name" required value="{{ $election->name }}" />
    
                                </div>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            
                            </div>
                            

                            <div class="form-group row">
                                <label for="party_id" class="col-sm-4 col-form-label">Choose Your Political Party</label>
                                <div class="col-sm-8">
                                <select  class="form-control @error('party_id') is-invalid @enderror" name="party_id" id="party_id">
                                    <option disabled selected> Select Party</option>
                                    @foreach ($parties as $party)
                                        <option value="{{ $party->id }}"
                                            {{ old('party_id', $election->party_id) == $party->id ? 'selected' : '' }}>
                                            {{ $party->name }}
                                        </option>
                                    @endforeach
                                </select>
                                </div>
                                @error('party_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group row">
                                <label for="year" class="col-sm-4 col-form-label" title="Ensure that the election is year is correct. Day and month is not really important">When is the Election(year)</label>
                                <div class="col-sm-8">
                                    <input type="date" class="form-control @error('year') is-invalid @enderror" name="year" id="year" value="{{old('year', $election->year)}}">
                                </div>
                            @error('year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>



                            <div class="form-group row">
                                <label for="description" class="col-sm-4 col-form-label">Comment(Optional)</label>
                                <div class="col-sm-8">
                                    <textarea type="text" class="form-control @error('description') is-invalid @enderror" name="description" id="description" value="{{ old('description', $election->description) }}">
                                        {{ $election->description }}</textarea>
                                </div>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                            <div class="form-group row">
                                <label for="status" class="col-sm-4 col-form-label">Election Status</label>
                                <div class="col-sm-8">
                                    <select class="form-control @error('status') is-invalid @enderror" name="status" id="status">
                                        <option disabled selected> Select Status</option>
                                        @foreach ($statuses as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ old('status', $election->status) == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>




                            <div class="form-group row">
                                <div class="offset-sm-4 col-sm-4">
                                <input type="submit" class="btn btn-primary btn-block " value="Update Election">
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
    <script>
        function previewLogo(input) {
            const preview = document.getElementById('logo-preview');
            const label = input.nextElementSibling;

            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                // Update the label with the selected file name
                label.innerText = file.name;

                // Read the file and update the preview image
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none'); // Show the preview image
                };
                reader.readAsDataURL(file);
            } else {
                // Reset the preview if no file is selected
                preview.src = '#';
                preview.classList.add('d-none'); // Hide the preview image
                label.innerText = 'Choose Logo File'; // Reset label text
            }
        }
    </script>

@endsection




