@extends('backend.template.backend-master')
@section('content')

    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"> Edit Political Party</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.election.politicalparty') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i> All Political Parties</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">

                    <form action="{{ route($profileData->access_level.'.election.politicalparty.update', $politicalparty->uuid) }}" class="form-horizontal" id="politicalparty" method="POST"  enctype="multipart/form-data">
                        @csrf
                        @method('POST')

                        <div class="form-group row">
                            <label for="acronym" class="col-sm-4 col-form-label">Political Party Short name or Acronym</label>
                            <div class="col-sm-8">
                            <input type="text" class="form-control @error('acronym') is-invalid @enderror" name="acronym" id="acronym" placeholder="Political Party Acronym" value="{{ $politicalparty->acronym }}">
                            </div>
                            @error('acronym')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group row">
                            <label for="name" class="col-sm-4 col-form-label">Political Party Name</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" placeholder="Political Party Name" value="{{ $politicalparty->name }}">
                             </div>
                           @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>



                        <div class="form-group row">
                            <label for="slogan" class="col-sm-4 col-form-label">Political Party Slogan</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('slogan') is-invalid @enderror" name="slogan" id="slogan" value="{{ $politicalparty->slogan }}" placeholder="Political Party slogan" />
                            </div>
                            @error('slogan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        <div class="form-group row">
                            <label for="logo" class="col-sm-4 col-form-label">Political Party Logo</label>
                            <div class="col-sm-8">
                                <!-- Display existing logo if available -->
                                @if(!empty($politicalparty->logo))
                                    <img id="existing-logo"
                                         class="img-square img-responsive mb-2"
                                         src="{{ asset('uploads/system_images/politicalparty/'.$politicalparty->logo) }}"
                                         alt="logo"
                                         style="width: 50%; height: auto; border: 1px solid #ddd; padding: 5px;">
                                    <!-- Remove existing logo option -->
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="remove-logo" name="remove_logo" value="1" onchange="toggleLogoRemoval(this)">
                                        <label class="form-check-label" for="remove-logo">Remove existing logo</label>
                                    </div>
                                @endif

                                <!-- Image preview for new file -->
                                <img id="new-logo-preview"
                                     class="img-square img-responsive mb-2 d-none"
                                     src="#"
                                     alt="New logo preview"
                                     style="width: 50%; height: auto; border: 1px solid #ddd; padding: 5px;">

                                <div class="custom-file">
                                    <input type="file"
                                           class="custom-file-input @error('logo') is-invalid @enderror"
                                           id="logo"
                                           name="logo"
                                           onchange="previewFile(this)">
                                    <label class="custom-file-label" for="logo">
                                        {{ !empty($politicalparty->logo) ? $politicalparty->logo : 'Choose Logo File' }}
                                    </label>
                                </div>
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                        <div class="form-group row">
                            <div class="offset-sm-4 col-sm-4">
                            <input type="submit" class="btn btn-primary btn-block " value="Update Political Party">
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
        // Update file name dynamically in the label and show a preview
        function previewFile(input) {
            const label = input.nextElementSibling;
            const preview = document.getElementById('new-logo-preview');
            const existingLogo = document.getElementById('existing-logo');
            const removeLogoCheckbox = document.getElementById('remove-logo');

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

                // Hide the existing logo if a new file is selected
                if (existingLogo) {
                    existingLogo.classList.add('d-none');
                }

                // Uncheck "Remove Logo" if a new file is selected
                if (removeLogoCheckbox) {
                    removeLogoCheckbox.checked = false;
                }
            } else {
                // Reset the preview if no file is selected
                preview.src = '#';
                preview.classList.add('d-none');

                // Show the existing logo again if no file is selected
                if (existingLogo) {
                    existingLogo.classList.remove('d-none');
                }
            }
        }

        // Handle removal of existing logo
        function toggleLogoRemoval(checkbox) {
            const existingLogo = document.getElementById('existing-logo');
            const newLogoInput = document.getElementById('logo');
            const newLogoPreview = document.getElementById('new-logo-preview');

            if (checkbox.checked) {
                // Hide existing logo and disable new file upload
                if (existingLogo) {
                    existingLogo.classList.add('d-none');
                }
                newLogoInput.value = ''; // Clear file input
                newLogoPreview.src = '#'; // Reset preview
                newLogoPreview.classList.add('d-none'); // Hide preview
            } else {
                // Show existing logo again
                if (existingLogo) {
                    existingLogo.classList.remove('d-none');
                }
            }
        }
    </script>

@endsection




