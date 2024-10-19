
@if($profileData->access_level == 'superadmin')

<div class="form-group row">
   <div class="col-sm-12">
        <textarea class="form-control @error('copyright') is_invalid @enderror" name="copyright" id="copyright">{{ $SystemSetting->copyright }}</textarea>
    </div>
    @error('copyright')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>

@endif

