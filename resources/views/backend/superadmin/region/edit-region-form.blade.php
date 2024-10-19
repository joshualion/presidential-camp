<div class="form-group row">
    <label for="name" class="col-sm-2 col-form-label">Region Name</label>
    <div class="col-sm-10">
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') ?? $region->name }}">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-group row">
    <label for="description" class="col-sm-2 col-form-label">Description</label>
    <div class="col-sm-10">
        <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="description">{{ old('description') ?? $region->description }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>


<div class="form-group row">
    <div class="offset-sm-2">
        <div class="form-check">
            {{-- just placeholder --}}
        </div>
    </div>
    <div class="col-sm-10">
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>
</div>
