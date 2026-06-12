@extends('backend.template.backend-master')
@section('content')

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"> Record Votes - For {{optional($profileData->pollingUnit)->name;}}  </h3>
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
                            <form action="{{ route($profileData->access_level.'.vote.store') }}" method="POST" enctype="multipart/form-data"  id="vote-form">
                                @csrf

                                <input type="hidden" id="redirect_to" name="redirect_to" value="summary">
                                <div class="form-group row">
                                    <label for="election" class="col-sm-4 col-form-label">Election</label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($election->year)->year }} - {{ $election->name }}" readonly>
                                        <input type="hidden" name="election_id" value="{{ $election->id }}">
                                <input type="hidden" name="polling_unit_id" value="{{ $targetPollingUnitId ?? $profileData->polling_unit_id }}">
                                    </div>
                                </div>

                                @foreach ($parties as $party)
                                    <div class="form-group row">
                                        <label for="party_{{ $party->id }}" class="col-sm-4 col-form-label">
                                            {{ $party->name }} ({{ $party->acronym }})
                                        </label>
                                        <div class="col-sm-6">
                                            <input
                                                type="number"
                                                class="form-control party-vote"
                                                name="votes[{{ $party->id }}]"
                                                id="party_{{ $party->id }}"
                                                min="0"
                                                value="{{ $votes[$party->id] ?? 0 }}"
                                            >
                                        </div>
                                    </div>
                                @endforeach

                                <div class="form-group row">
                                    <label for="total_votes" class="col-sm-4 col-form-label">Total Valid Votes</label>
                                    <div class="col-sm-6">
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="total_votes"
                                            value="0"
                                            readonly
                                        >
                                    </div>
                                </div>

                              
                                {{-- <div class="form-group row">
                                    <label for="result_sheet" class="col-sm-4 col-form-label">PU Result Sheet</label>
                                    <div class="col-sm-6">
                                        <!-- Image Preview -->
                                        <img
                                            id="result_sheet-image-preview"
                                            src="#"
                                            alt="Result Sheet Preview"
                                            class="d-none"
                                            style="width: 50%; height: auto; border: 1px solid #ddd; padding: 5px;"
                                        >
                                        <!-- PDF Preview -->
                                        <iframe
                                            id="result_sheet-pdf-preview"
                                            src="#"
                                            class="d-none"
                                            style="width: 100%; height: 500px; border: 1px solid #ddd;"
                                        ></iframe>
                                
                                        <!-- File Input -->
                                        <div class="custom-file">
                                            <input
                                                type="file"
                                                class="custom-file-input @error('result_sheet') is-invalid @enderror"
                                                id="result_sheet"
                                                name="result_sheet"
                                                onchange="previewresult_sheet(this)"
                                            >
                                            <label class="custom-file-label" for="result_sheet">Attach Result Sheet</label>
                                        </div>
                                        @error('result_sheet')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div> --}}
                                <div class="form-group row">
                                    <label for="result_sheet" class="col-sm-4 col-form-label">PU Result Sheet</label>
                                    <div class="col-sm-6">
                                        <!-- Display existing file if it exists -->
                                        @if(isset($pollingUnitResult) && $pollingUnitResult->result_sheet)
                                            @php
                                                $filePath = asset('uploads/system_images/election_result_sheets/' . $pollingUnitResult->result_sheet);
                                                $isPdf = pathinfo($filePath, PATHINFO_EXTENSION) === 'pdf';
                                            @endphp
                                            <!-- Existing Image Preview -->
                                            @if(!$isPdf)
                                                <img
                                                    id="result_sheet-image-preview"
                                                    src="{{ $filePath }}"
                                                    alt="Existing Result Sheet"
                                                    style="width: 50%; height: auto; border: 1px solid #ddd; padding: 5px;"
                                                >
                                            @else
                                                <!-- Existing PDF Preview -->
                                                <iframe
                                                    id="result_sheet-pdf-preview"
                                                    src="{{ $filePath }}"
                                                    style="width: 100%; height: 500px; border: 1px solid #ddd;"
                                                ></iframe>
                                            @endif
                                        @else
                                            <!-- Hide preview if no file exists -->
                                            <img
                                                id="result_sheet-image-preview"
                                                src="#"
                                                alt="Result Sheet Preview"
                                                class="d-none"
                                                style="width: 50%; height: auto; border: 1px solid #ddd; padding: 5px;"
                                            >
                                            <iframe
                                                id="result_sheet-pdf-preview"
                                                src="#"
                                                class="d-none"
                                                style="width: 100%; height: 500px; border: 1px solid #ddd;"
                                            ></iframe>
                                        @endif
                                
                                        <!-- File Input -->
                                        <div class="custom-file">
                                            <input
                                                type="file"
                                                class="custom-file-input @error('result_sheet') is-invalid @enderror"
                                                id="result_sheet"
                                                name="result_sheet"
                                                onchange="previewresult_sheet(this)"
                                            >
                                            <label class="custom-file-label" for="result_sheet">Attach Result Sheet</label>
                                        </div>
                                        @error('result_sheet')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                

                                <div class="form-group row">
                                    <div class="offset-sm-4 col-sm-4">
                                        <button type="submit" class="btn btn-primary btn-block submit-btn">Record Votes</button>
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
    document.addEventListener('DOMContentLoaded', function () {
        // Select all party vote inputs
        const voteInputs = document.querySelectorAll('.party-vote');
        const totalVotesField = document.getElementById('total_votes');

        // Add event listeners to recalculate total votes and handle focus/blur
        voteInputs.forEach(input => {
            input.addEventListener('input', calculateTotalVotes);
            input.addEventListener('focus', clearInitialValue);
            input.addEventListener('blur', setInitialValue);
        });

        // Clear initial value when input is focused
        function clearInitialValue(event) {
            if (event.target.value === '0') {
                event.target.value = '';
            }
        }

        // Set initial value when input is blurred
        function setInitialValue(event) {
            if (event.target.value === '') {
                event.target.value = '0';
            }
        }

        // Recalculate total votes
        function calculateTotalVotes() {
            let total = 0;
            voteInputs.forEach(input => {
                const value = parseInt(input.value) || 0; // Default to 0 if empty
                total += value;
            });
            totalVotesField.value = total; // Update the readonly field
        }
    });
</script>


{{-- <script>
    
    function previewresult_sheet(input) {
        const imagePreview = document.getElementById('result_sheet-image-preview');
        const pdfPreview = document.getElementById('result_sheet-pdf-preview');
        const label = input.nextElementSibling;
        const file = input.files[0];

        if (file) {
            // Update the label with the selected file name
            label.textContent = file.name;

            // Check the file type
            const fileType = file.type;

            // Reset previews
            if (imagePreview) {
                imagePreview.classList.add('d-none');
                imagePreview.src = '#';
            }
            if (pdfPreview) {
                pdfPreview.classList.add('d-none');
                pdfPreview.src = '#';
            }

            // Handle image preview
            if (fileType.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (imagePreview) {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.remove('d-none');
                    }
                };
                reader.readAsDataURL(file);
            } 
            // Handle PDF preview
            else if (fileType === 'application/pdf') {
                const fileURL = URL.createObjectURL(file);
                if (pdfPreview) {
                    pdfPreview.src = fileURL;
                    pdfPreview.classList.remove('d-none');
                }
            }
        } else {
            // Reset the label and previews
            if (label) label.textContent = 'Choose Result Sheet File';
            if (imagePreview) imagePreview.classList.add('d-none');
            if (pdfPreview) pdfPreview.classList.add('d-none');
        }
    }
</script> --}}
<script>
function previewresult_sheet(input) {
    const imagePreview = document.getElementById('result_sheet-image-preview');
    const pdfPreview = document.getElementById('result_sheet-pdf-preview');
    const label = input.nextElementSibling;
    const file = input.files[0];

    if (file) {
        // Update the label with the selected file name
        label.textContent = file.name;

        // Check the file type
        const fileType = file.type;

        // Reset previews
        if (imagePreview) {
            imagePreview.classList.add('d-none');
            imagePreview.src = '#';
        }
        if (pdfPreview) {
            pdfPreview.classList.add('d-none');
            pdfPreview.src = '#';
        }

        // Handle image preview
        if (fileType.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                if (imagePreview) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                }
            };
            reader.readAsDataURL(file);
        } 
        // Handle PDF preview
        else if (fileType === 'application/pdf') {
            const fileURL = URL.createObjectURL(file);
            if (pdfPreview) {
                pdfPreview.src = fileURL;
                pdfPreview.classList.remove('d-none');
            }
        }
    } else {
        // Reset the label and previews
        if (label) label.textContent = 'Choose Result Sheet File';
        if (imagePreview) imagePreview.classList.add('d-none');
        if (pdfPreview) pdfPreview.classList.add('d-none');
    }
}
</script>


{{-- check if there's anay incident to report --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (event) {
            if (event.target.classList.contains('submit-btn')) {
                event.preventDefault();

                Swal.fire({
                    title: 'Do you have any incident to report?',
                    text: 'Please let us know if there is an incident related to this action.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, report incident',
                    cancelButtonText: 'No, proceed'
                }).then((result) => {
                    const form = document.getElementById('vote-form');
                    const redirectInput = document.getElementById('redirect_to');
                    if (result.isConfirmed) {
                        redirectInput.value = 'incident';
                    } else {
                        redirectInput.value = 'summary';
                    }
                    form.submit(); // Submit the form
                });
            }
        });
    });
</script>


@endsection
