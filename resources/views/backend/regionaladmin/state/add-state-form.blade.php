<div class="form group row">
    <label for="name" class="col-sm-2 col-form-label">State Name</label>
    <div class="col-sm-10">
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') }}" required>
    </div>  

    @error('name')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

</div>
<br>

<div class="form group row">
    <label for="region" class="col-sm-2 col-form-label">Region</label>
    <div class="col-sm-6">
        <select class="form-control @error('region') is-invalid @enderror" name="region_id" id="region" required>
            <option value="" disabled selected>Select Region</option>
            @foreach($regions as $region)
                <option value="{{ $region->id }}">{{ $region->name }}</option>
            @endforeach
        </select>
    </div>

    @error('region')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

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