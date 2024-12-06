@extends('backend.template.backend-master')
@section('content')

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"> Record Votes  </h3>
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
                            <form action="{{ route($profileData->access_level.'.vote.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class="form-group row">
                                    <label for="election" class="col-sm-4 col-form-label">Election</label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($election->year)->year }} - {{ $election->name }}" readonly>
                                        <input type="hidden" name="election_id" value="{{ $election->id }}">
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

                                <div class="form-group row">
                                    <label for="result_sheet" class="col-sm-4 col-form-label">PU Result Sheet</label>
                                    <div class="col-sm-6">
                                        @if ($pollingUnitResult && $pollingUnitResult->result_sheet)
                                            <img
                                                id="result_sheet-image-preview"
                                                src="{{ asset('uploads/system_images/election_result_sheets/' . $pollingUnitResult->result_sheet) }}"
                                                alt="Result Sheet"
                                                style="width: 50%; height: auto; border: 1px solid #ddd; padding: 5px;"
                                            >
                                        @endif

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
                                        <button type="submit" class="btn btn-primary btn-block">Record Votes</button>
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

<script>
    function previewresult_sheet(input) {
        const imagePreview = document.getElementById('result_sheet-image-preview');
        const pdfPreview = document.getElementById('result_sheet-pdf-preview');
        const label = input.nextElementSibling;
        const file = input.files[0];

        if (file) {
            // Update the label with the selected file name
            label.innerText = file.name;

            // Check the file type
            const fileType = file.type;

            // Reset the previews before showing the new one
            imagePreview.classList.add('d-none');
            pdfPreview.classList.add('d-none');

            // Handle image preview
            if (fileType.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none'); // Show the image preview
                };
                reader.readAsDataURL(file);
            }
            // Handle PDF preview
            else if (fileType === 'application/pdf') {
                const fileURL = URL.createObjectURL(file);
                pdfPreview.src = fileURL;
                pdfPreview.classList.remove('d-none'); // Show the PDF preview
            }
        } else {
            // Reset the preview if no file is selected
            imagePreview.src = '#';
            pdfPreview.src = '#';
            imagePreview.classList.add('d-none'); // Hide the image preview
            pdfPreview.classList.add('d-none'); // Hide the PDF preview
            label.innerText = 'Choose result_sheet File'; // Reset label text
        }
    }
</script>




@endsection
