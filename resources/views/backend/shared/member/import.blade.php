@extends('backend.template.backend-master')
@section('content')

@php
    $fixedLocations = $locationForm['fixed'] ?? [];
    $defaultRegionId = old('region_id', $profileData->region_id ?: ($fixedLocations['region_id'] ?? ''));
    $defaultStateId = old('state_id', $profileData->state_id ?: ($fixedLocations['state_id'] ?? ''));
    $defaultAccessLevel = old('access_level', array_key_exists('user', $assignableAccessLevels) ? 'user' : array_key_first($assignableAccessLevels));
@endphp

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Import Members</h3>
                    </div>
                    <form action="{{ route($profileData->access_level.'.member.import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            @if(isset($errors) && $errors->any())
                                <div class="alert alert-danger">
                                    <strong>Please correct the import settings:</strong>
                                    <ul class="mb-0 pl-3">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <p class="text-muted">
                                Choose one onboarding context for this batch, then upload a simple CSV or Excel file. The selected access level, role, password, and location defaults apply to every valid row.
                            </p>

                            @if(!empty($locationForm['message']))
                                <div class="alert alert-info">
                                    {{ $locationForm['message'] }}
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="region_id">Target region</label>
                                        <select name="region_id" id="region_id" class="form-control">
                                            <option value="">No region selected</option>
                                            @foreach($regions as $region)
                                                <option value="{{ $region->id }}" @selected((string) $defaultRegionId === (string) $region->id)>{{ $region->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="state_id">Target state</label>
                                        <select name="state_id" id="state_id" class="form-control">
                                            <option value="">No state selected</option>
                                            @foreach($states as $state)
                                                <option value="{{ $state->id }}" data-region-id="{{ $state->region_id }}" @selected((string) $defaultStateId === (string) $state->id)>{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted">Selecting a state automatically assigns its region.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="access_level">Access level <span class="text-danger">*</span></label>
                                        <select name="access_level" id="access_level" class="form-control" required>
                                            @foreach($assignableAccessLevels as $value => $label)
                                                @continue($value === 'superadmin')
                                                <option value="{{ $value }}" @selected($defaultAccessLevel === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role_id">Role <span class="text-danger">*</span></label>
                                        <select name="role_id" id="role_id" class="form-control" required>
                                            <option value="">Select role</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" data-access-level="{{ $role->group_name }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="default_password">Default password</label>
                                        <input type="password" name="default_password" id="default_password" class="form-control" minlength="8" maxlength="128" autocomplete="new-password" placeholder="password">
                                        <small class="form-text text-muted">Leave empty to use <code>password</code>. Imported users must change it after login.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 mt-2">
                                <a href="{{ route($profileData->access_level.'.member.import.template') }}" class="btn btn-success">
                                    <i class="fa fa-download"></i> Download CSV Template
                                </a>
                            </div>

                            <div class="form-group">
                                <label for="import_file">CSV or Excel file</label>
                                <input type="file" name="import_file" id="import_file" class="form-control" accept=".csv,.xlsx,.xls,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                                <small class="form-text text-muted">
                                    CSV, XLSX, or XLS. Maximum 5,000 rows per upload. Duplicates are skipped and reported.
                                </small>
                            </div>

                            <div class="alert alert-warning">
                                Existing users matched by email or phone are skipped. Their role, access level, and profile are never overwritten.
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <a href="{{ route($profileData->access_level.'.members') }}" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i> Back to Members
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-upload"></i> Upload and Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Template Notes</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">Required CSV/Excel columns:</p>
                        <ul class="pl-3">
                            <li>firstname</li>
                            <li>lastname</li>
                            <li>phone or email</li>
                        </ul>
                        <p class="text-muted mb-2">Optional columns: username, gender, region, state, senatorial_district, federal_constituency, lga, ward, polling_unit, valid_voter, vin, address, occupation, qualification. If username is omitted, it is generated from firstname and lastname.</p>
                        <p class="text-muted mb-2">Missing lower-level geography is allowed. Valid optional location data is preserved within the selected batch scope.</p>
                        <p class="text-muted mb-0">No email or SMS is sent during import.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const access = document.getElementById('access_level');
    const role = document.getElementById('role_id');
    const region = document.getElementById('region_id');
    const state = document.getElementById('state_id');

    function filterRoles() {
        let selectedIsValid = false;
        Array.from(role.options).forEach(function (option) {
            if (!option.value) return;
            const allowed = option.dataset.accessLevel === access.value;
            option.hidden = !allowed;
            option.disabled = !allowed;
            if (allowed && option.selected) selectedIsValid = true;
        });
        if (!selectedIsValid) {
            role.value = '';
            const first = Array.from(role.options).find(option => option.value && !option.disabled);
            if (first) first.selected = true;
        }
    }

    function filterStates(clearInvalid) {
        let selectedIsValid = !state.value;
        Array.from(state.options).forEach(function (option) {
            if (!option.value) return;
            const allowed = !region.value || option.dataset.regionId === region.value;
            option.hidden = !allowed;
            option.disabled = !allowed;
            if (allowed && option.selected) selectedIsValid = true;
        });
        if (clearInvalid && !selectedIsValid) state.value = '';
    }

    access.addEventListener('change', filterRoles);
    region.addEventListener('change', function () { filterStates(true); });
    state.addEventListener('change', function () {
        const selected = state.options[state.selectedIndex];
        if (selected && selected.dataset.regionId) {
            region.value = selected.dataset.regionId;
            filterStates(false);
        }
    });

    filterRoles();
    if (state.value) state.dispatchEvent(new Event('change'));
    else filterStates(false);
});
</script>

@endsection
