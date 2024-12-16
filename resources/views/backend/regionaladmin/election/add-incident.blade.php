@extends('backend.template.backend-master')
@section('content')

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"> Record Incident   For {{optional($profileData->pollingUnit)->name;}} </h3>
                    </div>
                    <div class="card-body">

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form action="{{ route($profileData->access_level.'.incident.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <input type="hidden" name="region_id" value="{{ $regionId }}">
                                <input type="hidden" name="state_id" value="{{ $stateId }}">
                                <input type="hidden" name="lga_id" value="{{ $lgaId }}">
                                <input type="hidden" name="ward_id" value="{{ $wardId }}">
                                <input type="hidden" name="polling_unit_id" value="{{ $pollingUnitId }}">

                                <div class="form-group row">
                                    <label for="election" class="col-sm-4 col-form-label">Election</label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($election->year)->year }} - {{ $election->name }}" readonly>
                                        <input type="hidden" name="election_id" value="{{ $election->id }}">
                                    </div>
                                </div>

                               
                                <div class="form-group row">
                                    <label for="remarks" class="col-sm-4 col-form-label">Remarks</label>
                                    <div class="col-sm-6">
                                        <textarea name="remarks" class="form-control" rows="3" placeholder="Enter any additional details (optional)"></textarea>
                                    </div>
                                </div>

                                @if ($errors->has('pictures'))
                                    <div class="alert alert-danger">
                                        {{ $errors->first('pictures') }}
                                    </div>
                                @endif

                                <div class="form-group row">
                                    <label for="pictures" class="col-sm-4 col-form-label">Picture Evidences</label>
                                    <div class="col-sm-6">
                                        <div id="picture-evidence-container">
                                            <input type="file" name="pictures[]" accept="image/*" class="form-control mb-2 picture-input">
                                        </div>
                                        <button type="button" class="btn btn-sm btn-secondary" id="add-picture-input">Add Another Picture</button>
                                        <small class="form-text text-muted">Maximum 3 images allowed.</small>
                                        <div id="preview-container" class="mt-3"></div>
                                    </div>
                                </div>
                                
                                
                               

                                <div class="form-group row">
                                    <div class="offset-sm-4 col-sm-4">
                                        <button type="submit" class="btn btn-primary btn-block">Add Record</button>
                                    </div>
                                </div>
                            </form>


                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const maxUploads = 3;
        const pictureContainer = document.getElementById('picture-evidence-container');
        const previewContainer = document.getElementById('preview-container');
        const addPictureButton = document.getElementById('add-picture-input');

        // Add new file input dynamically
        addPictureButton.addEventListener('click', () => {
            const inputs = document.querySelectorAll('.picture-input');
            if (inputs.length >= maxUploads) {
                alert(`You can upload a maximum of ${maxUploads} images.`);
                return;
            }

            const newInput = document.createElement('input');
            newInput.type = 'file';
            newInput.name = 'pictures[]';
            newInput.accept = 'image/*';
            newInput.className = 'form-control mb-2 picture-input';

            // Add change event listener for preview
            newInput.addEventListener('change', handleFilePreview);

            pictureContainer.appendChild(newInput);
        });

        // Handle file preview
        const handleFilePreview = (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Preview';
                    img.className = 'img-thumbnail';
                    img.style.width = '150px';
                    img.style.margin = '5px';

                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        };

        // Add initial change event listener to the first input
        document.querySelector('.picture-input').addEventListener('change', handleFilePreview);
    });
</script>



@endsection
