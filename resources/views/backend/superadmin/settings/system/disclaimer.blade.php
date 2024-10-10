
<div class="form-group row">

    <div class="col-sm-12">
        <textarea class="form-control @error('disclaimer') is_invalid @enderror" name="disclaimer" id="disclaimer">{{ $SystemSetting->disclaimer }}</textarea>
    </div>
    @error('disclaimer')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>



