<div class="form-group row">
    <label for="facebook" class="col-sm-2 col-form-label">Facebook URL</label>
    <div class="col-sm-10">
        <input type="url" class="form-control @error('facebook') is_invalid @enderror" name="facebook" id="facebook" value="{{ $SystemSetting->facebook }}">
    </div>
    @error('facebook')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group row">
    <label for="twitter" class="col-sm-2 col-form-label">Twitter URL</label>
    <div class="col-sm-10">
        <input type="url" class="form-control @error('twitter') is_invalid @enderror" name="twitter" id="twitter" value="{{ $SystemSetting->twitter }}">
    </div>
    @error('twitter')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group row">
    <label for="instagram" class="col-sm-2 col-form-label">Instagram URL</label>
    <div class="col-sm-10">
        <input type="url" class="form-control @error('instagram') is_invalid @enderror" name="instagram" id="instagram" value="{{ $SystemSetting->instagram }}">
    </div>
    @error('instagram')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group row">
    <label for="linkedin" class="col-sm-2 col-form-label">LinkedIn URL</label>
    <div class="col-sm-10">
        <input type="url" class="form-control @error('linkedin') is_invalid @enderror" name="linkedin" id="linkedin" value="{{ $SystemSetting->linkedin }}">
    </div>
    @error('linkedin')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group row">
    <label for="youtube" class="col-sm-2 col-form-label">YouTube Channel URL</label>
    <div class="col-sm-10">
        <input type="url" class="form-control @error('youtube') is_invalid @enderror" name="youtube" id="youtube" value="{{ $SystemSetting->youtube }}">
    </div>
    @error('youtube')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div> 