<div class="form-group row">
    <div class="col-sm-12">
        <label for="copyright" class="form-label fw-bold">Copyright Notice</label>
        <textarea
            class="form-control @error('copyright') is_invalid @enderror"
            name="copyright"
            id="copyright"
            rows="4"
            placeholder="e.g. © 2025 Your Organization. All rights reserved."
        >{{ old('copyright', $SystemSetting->copyright ?? '') }}</textarea>
    </div>
    @error('copyright')
        <div class="alert alert-danger mt-2">{{ $message }}</div>
    @enderror
</div>
