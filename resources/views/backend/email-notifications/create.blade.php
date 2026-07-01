@extends('backend.template.backend-master')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Compose Email Notification</h3>
            </div>
            <form id="email-notification-form" method="POST" action="{{ route($profileData->access_level.'.email-notifications.preview') }}">
                @csrf
                <input type="hidden" name="submission_token" value="{{ old('submission_token', $submissionToken) }}">
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <div><strong>Your sending scope:</strong> {{ $sendingScope }}</div>
                    </div>
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="subject">Email subject</label>
                        <input id="subject" name="subject" class="form-control" maxlength="255" required value="{{ old('subject') }}">
                    </div>
                    <div class="form-group">
                        <label for="body">Message</label>
                        <textarea id="body" name="body" class="form-control" rows="10" maxlength="50000" required>{{ old('body') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="cta_label">CTA label (optional)</label>
                            <input id="cta_label" name="cta_label" class="form-control" maxlength="100" value="{{ old('cta_label') }}" placeholder="View details">
                        </div>
                        <div class="col-md-8 form-group">
                            <label for="cta_url">CTA URL (optional)</label>
                            <input id="cta_url" name="cta_url" type="url" class="form-control" maxlength="2048" value="{{ old('cta_url') }}" placeholder="https://example.com/details">
                        </div>
                    </div>

                    <hr>
                    <div class="form-group">
                        <label for="recipient_group">Recipient group</label>
                        <select id="recipient_group" name="recipient_group" class="form-control" required>
                            <option value="all" @selected(old('recipient_group') === 'all')>All eligible users</option>
                            <option value="admins" @selected(old('recipient_group') === 'admins')>Admins only</option>
                            <option value="members" @selected(old('recipient_group') === 'members')>Members only</option>
                            <option value="agents" @selected(old('recipient_group') === 'agents')>Agents only</option>
                            <option value="access_levels" @selected(old('recipient_group') === 'access_levels')>Selected access levels</option>
                            <option value="roles" @selected(old('recipient_group') === 'roles')>Selected roles</option>
                        </select>
                        <small class="form-text text-muted">Only eligible users inside your assigned jurisdiction and licensed campaign scope can receive this email.</small>
                    </div>

                    <div id="access-level-options" class="form-group">
                        <label>Access levels</label>
                        <div class="row">
                            @foreach($accessLevels as $level => $label)
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input class="custom-control-input" type="checkbox" id="level-{{ $level }}" name="access_levels[]" value="{{ $level }}" @checked(in_array($level, old('access_levels', [])))>
                                        <label class="custom-control-label" for="level-{{ $level }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div id="role-options" class="form-group">
                        <label>Roles</label>
                        <div class="row">
                            @forelse($roles as $role)
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input class="custom-control-input" type="checkbox" id="role-{{ $role->id }}" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', [])))>
                                        <label class="custom-control-label" for="role-{{ $role->id }}">{{ $role->name }}</label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-muted">No eligible roles are configured for this package.</div>
                            @endforelse
                        </div>
                    </div>

                    <hr>
                    <h5>Location filters <small class="text-muted">(optional)</small></h5>
                    <div class="alert alert-light border py-2">
                        Lower-level filters are optional. Leave them blank to send to <strong>all matching recipients within your jurisdiction</strong>. Selecting a location only narrows your sending scope; it never expands it.
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="state_id">State</label>
                            <select id="state_id" name="state_id" class="form-control">
                                <option value="">All states in my jurisdiction</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" @selected((string) old('state_id') === (string) $state->id)>{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="lga_id">LGA</label>
                            <select id="lga_id" name="lga_id" class="form-control" disabled>
                                <option value="">All LGAs</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="ward_id">Ward</label>
                            <select id="ward_id" name="ward_id" class="form-control" disabled>
                                <option value="">All wards</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="polling_unit_id">Polling unit</label>
                            <select id="polling_unit_id" name="polling_unit_id" class="form-control" disabled>
                                <option value="">All polling units</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <input type="hidden" name="active_only" value="0">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" type="checkbox" id="active_only" name="active_only" value="1" @checked(old('active_only', '1') === '1')>
                            <label class="custom-control-label" for="active_only">Active users only</label>
                        </div>
                        <input type="hidden" name="verified_only" value="0">
                        <div class="custom-control custom-checkbox mt-2">
                            <input class="custom-control-input" type="checkbox" id="verified_only" name="verified_only" value="1" @checked(old('verified_only') === '1')>
                            <label class="custom-control-label" for="verified_only">Verified email addresses only</label>
                        </div>
                    </div>

                    <div class="card bg-light mt-4 mb-0">
                        <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-1">Audience preview</h5>
                                <div id="recipient-count-message" class="text-muted">Select your audience, then preview the eligible recipient count.</div>
                            </div>
                            <button id="preview-recipient-count" type="button" class="btn btn-outline-primary mt-2 mt-md-0">
                                <i class="fas fa-users"></i> Preview Recipient Count
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button id="submit-notification" class="btn btn-primary" type="submit"><i class="fas fa-eye"></i> Preview Email</button>
                    <a href="{{ route($profileData->access_level.'.email-notifications.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const group = document.getElementById('recipient_group');
    const accessLevels = document.getElementById('access-level-options');
    const roles = document.getElementById('role-options');
    const state = document.getElementById('state_id');
    const lga = document.getElementById('lga_id');
    const ward = document.getElementById('ward_id');
    const pollingUnit = document.getElementById('polling_unit_id');
    const locationOptionsUrl = @json(route($profileData->access_level.'.email-notifications.location-options'));
    const audiencePreviewUrl = @json(route($profileData->access_level.'.email-notifications.audience-preview'));
    const countMessage = document.getElementById('recipient-count-message');
    const countButton = document.getElementById('preview-recipient-count');
    const oldLocations = {
        lga: @json((string) old('lga_id', '')),
        ward: @json((string) old('ward_id', '')),
        pollingUnit: @json((string) old('polling_unit_id', '')),
    };

    function updateAudienceFields() {
        accessLevels.style.display = group.value === 'access_levels' ? '' : 'none';
        roles.style.display = group.value === 'roles' ? '' : 'none';
    }

    function resetSelect(select, label) {
        select.innerHTML = '';
        select.add(new Option(label, ''));
        select.disabled = true;
    }

    async function loadOptions(type, params, select, label, selectedValue = '') {
        resetSelect(select, label);
        const query = new URLSearchParams({type, ...params});
        const response = await fetch(`${locationOptionsUrl}?${query.toString()}`, {
            headers: {'Accept': 'application/json'},
            credentials: 'same-origin',
        });
        if (!response.ok) {
            throw new Error('Unable to load location options.');
        }

        const payload = await response.json();
        payload.data.forEach(item => select.add(new Option(item.name, item.id)));
        select.disabled = false;
        if (selectedValue && [...select.options].some(option => option.value === selectedValue)) {
            select.value = selectedValue;
        }
    }

    async function loadLgas(selectedValue = '') {
        resetSelect(lga, 'All LGAs');
        resetSelect(ward, 'All wards');
        resetSelect(pollingUnit, 'All polling units');
        if (state.value) await loadOptions('lgas', {state_id: state.value}, lga, 'All LGAs', selectedValue);
        scheduleCountPreview();
    }

    async function loadWards(selectedValue = '') {
        resetSelect(ward, 'All wards');
        resetSelect(pollingUnit, 'All polling units');
        if (lga.value) await loadOptions('wards', {lga_id: lga.value}, ward, 'All wards', selectedValue);
        scheduleCountPreview();
    }

    async function loadPollingUnits(selectedValue = '') {
        resetSelect(pollingUnit, 'All polling units');
        if (ward.value) await loadOptions('polling_units', {ward_id: ward.value}, pollingUnit, 'All polling units', selectedValue);
        scheduleCountPreview();
    }

    let countTimer;
    function scheduleCountPreview() {
        clearTimeout(countTimer);
        countTimer = setTimeout(previewRecipientCount, 450);
    }

    async function previewRecipientCount() {
        countButton.disabled = true;
        countMessage.className = 'text-muted';
        countMessage.textContent = 'Calculating eligible recipients...';

        try {
            const response = await fetch(audiencePreviewUrl, {
                method: 'POST',
                body: new FormData(document.getElementById('email-notification-form')),
                headers: {'Accept': 'application/json'},
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!response.ok) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to preview recipients.');
            }

            countMessage.className = payload.count > 0 ? 'text-success font-weight-bold' : 'text-warning font-weight-bold';
            countMessage.textContent = payload.message;
        } catch (error) {
            countMessage.className = 'text-warning';
            countMessage.textContent = error.message || 'Unable to preview recipients.';
        } finally {
            countButton.disabled = false;
        }
    }

    group.addEventListener('change', updateAudienceFields);
    state.addEventListener('change', () => loadLgas().catch(() => resetSelect(lga, 'Unable to load LGAs')));
    lga.addEventListener('change', () => loadWards().catch(() => resetSelect(ward, 'Unable to load wards')));
    ward.addEventListener('change', () => loadPollingUnits().catch(() => resetSelect(pollingUnit, 'Unable to load polling units')));
    countButton.addEventListener('click', previewRecipientCount);
    document.querySelectorAll('#recipient_group, input[name="access_levels[]"], input[name="role_ids[]"], #active_only, #verified_only, #state_id, #lga_id, #ward_id, #polling_unit_id')
        .forEach(element => element.addEventListener('change', scheduleCountPreview));
    document.getElementById('email-notification-form').addEventListener('submit', function () {
        const button = document.getElementById('submit-notification');
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing preview...';
    });
    updateAudienceFields();

    (async function restoreLocations() {
        if (!state.value && state.options.length === 2) {
            state.selectedIndex = 1;
        }
        if (!state.value) {
            scheduleCountPreview();
            return;
        }
        await loadLgas(oldLocations.lga);
        if (!lga.value) {
            scheduleCountPreview();
            return;
        }
        await loadWards(oldLocations.ward);
        if (!ward.value) {
            scheduleCountPreview();
            return;
        }
        await loadPollingUnits(oldLocations.pollingUnit);
        scheduleCountPreview();
    })().catch(() => {});
});
</script>
@endsection
