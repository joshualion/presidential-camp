@extends('backend.template.backend-master')
@section('content')

    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"> Add Political Party</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.election.politicalparty') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i> All Political Parties</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">

                    <form action="{{ route($profileData->access_level.'.election.politicalparty.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf


                        <div class="form-group row">
                            <label for="acronym" class="col-sm-4 col-form-label">Political Party Short name or Acronym</label>
                            <div class="col-sm-8">
                            <input type="text" class="form-control @error('acronym') is-invalid @enderror" name="acronym" id="acronym" placeholder="Political Party Acronym">
                            </div>
                            @error('acronym')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group row">
                            <label for="name" class="col-sm-4 col-form-label">Political Party Name</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" placeholder="Political Party Name">
                             </div>
                        @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>



                        <div class="form-group row">
                            <label for="slogan" class="col-sm-4 col-form-label">Political Party Slogan</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('slogan') is-invalid @enderror" name="slogan" id="slogan" placeholder="Political Party slogan" />
                            </div>
                            @error('slogan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        <div class="form-group row">
                            <label for="logo" class="col-sm-4 col-form-label">Political Party Logo</label>
                            <div class="col-sm-8">
                                <!-- Image Preview -->
                                <img id="logo-preview"
                                     class="img-square img-responsive mb-2 d-none"
                                     src="#"
                                     alt="Logo preview"
                                     style="width: 50%; height: auto; border: 1px solid #ddd; padding: 5px;">

                                <!-- File Input -->
                                <div class="custom-file">
                                    <input type="file"
                                           class="custom-file-input @error('logo') is-invalid @enderror"
                                           id="logo"
                                           name="logo"
                                           onchange="previewLogo(this)">
                                    <label class="custom-file-label" for="logo">Choose Logo File</label>
                                </div>
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>



                        <div class="form-group row">
                            <div class="offset-sm-4 col-sm-4">
                            <input type="submit" class="btn btn-primary btn-block " value="Add Political Party">
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




