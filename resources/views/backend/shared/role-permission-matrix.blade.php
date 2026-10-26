<input type="hidden" name="permissions" value="">
<div class="form-group"><label><input type="checkbox" data-permission-all> Select All Permissions</label></div>
@foreach(collect($catalog)->groupBy(fn ($name) => substr($name, 0, strrpos($name, '.') ?: strlen($name))) as $module => $permissions)
<fieldset class="border rounded p-3 mb-3"><legend class="h5 px-2 w-auto">{{ ucwords(str_replace(['.', '-'], ' ', $module)) }}</legend><div class="row">
@foreach($permissions as $permission)<div class="col-sm-6 col-lg-3 mb-2"><label class="font-weight-normal"><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, is_array($selectedPermissions) ? $selectedPermissions : [], true))> {{ ucwords(str_replace(['.', '-'], ' ', \Illuminate\Support\Str::afterLast($permission, '.'))) }}</label></div>@endforeach
</div></fieldset>@endforeach
<script>
document.querySelectorAll('[data-permission-all]').forEach(toggle => {
 const form=toggle.closest('form'); const boxes=Array.from(form.querySelectorAll('input[name="permissions[]"]'));
 const sync=()=>{toggle.checked=boxes.length>0 && boxes.every(box=>box.checked);toggle.indeterminate=boxes.some(box=>box.checked) && !toggle.checked;};
 toggle.addEventListener('change',()=>{boxes.forEach(box=>box.checked=toggle.checked);sync();}); boxes.forEach(box=>box.addEventListener('change',sync));sync();
});
</script>
