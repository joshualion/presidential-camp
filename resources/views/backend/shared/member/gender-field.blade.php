@php
    $selectedGender = old('gender', $member->gender ?? null);
@endphp

<div class="form-group row">
    <label for="gender" class="col-sm-3 col-form-label">Gender</label>
    <div class="col-sm-9">
        <div class="form-check">
            <input class="form-check-input @error('gender') is-invalid @enderror" type="radio" name="gender" id="gender-male" value="male" {{ $selectedGender === 'male' ? 'checked' : '' }} required>
            <label class="form-check-label" for="gender-male">Male</label>
        </div>
        <div class="form-check">
            <input class="form-check-input @error('gender') is-invalid @enderror" type="radio" name="gender" id="gender-female" value="female" {{ $selectedGender === 'female' ? 'checked' : '' }} required>
            <label class="form-check-label" for="gender-female">Female</label>
        </div>
        @error('gender')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>
