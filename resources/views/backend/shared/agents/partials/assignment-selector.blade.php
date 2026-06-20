<div class="alert alert-info" id="registered-pu-summary">
    Select a user to see the user's registered polling unit. The appointment will default to that polling unit where available.
</div>

@include('backend.shared.location-form.context')

<input type="hidden" name="polling_unit_id" id="assigned-polling-unit-id" value="{{ old('polling_unit_id') }}">

<div class="form-group">
    <label>Assignment Option</label>
    <div class="form-check">
        <input class="form-check-input" type="radio" name="assignment_mode" id="assignment-registered" value="registered" checked>
        <label class="form-check-label" for="assignment-registered">Use user's registered polling unit</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="radio" name="assignment_mode" id="assignment-override" value="override" @checked(old('assignment_mode') === 'override')>
        <label class="form-check-label" for="assignment-override">Assign to another polling unit using campaign deployment override</label>
    </div>
</div>

<div id="override-panel" class="d-none">
    <div class="alert alert-warning">
        You are assigning this user to a polling unit different from their registered voting polling unit. This should only be done for deliberate campaign deployment. Please provide a reason.
    </div>
    <input type="hidden" name="use_override" id="use-override" value="{{ old('assignment_mode') === 'override' ? '1' : '0' }}">

    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label>State</label>
                <select id="agent-state-select" class="form-control">
                    <option value="">Select State</option>
                    @foreach($states as $state)
                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>LGA</label>
                <select id="agent-lga-select" class="form-control" disabled>
                    <option value="">Select State First</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Ward</label>
                <select id="agent-ward-select" class="form-control" disabled>
                    <option value="">Select LGA First</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Polling Unit</label>
                <select id="agent-polling-unit-select" class="form-control" disabled>
                    <option value="">Select Ward First</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label>Override Reason</label>
        <textarea name="override_reason" id="override-reason" rows="3" class="form-control">{{ old('override_reason') }}</textarea>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const routes = {
        user: "{{ route($profileData->access_level.'.agents.locations.user', ['user' => '__USER__']) }}",
        lgas: "{{ route($profileData->access_level.'.agents.locations.lgas') }}",
        wards: "{{ route($profileData->access_level.'.agents.locations.wards') }}",
        pollingUnits: "{{ route($profileData->access_level.'.agents.locations.polling-units') }}"
    };

    const userSelect = document.getElementById('agent-user-select');
    const summary = document.getElementById('registered-pu-summary');
    const assignedPollingUnit = document.getElementById('assigned-polling-unit-id');
    const registeredRadio = document.getElementById('assignment-registered');
    const overrideRadio = document.getElementById('assignment-override');
    const overridePanel = document.getElementById('override-panel');
    const overrideFlag = document.getElementById('use-override');
    const stateSelect = document.getElementById('agent-state-select');
    const lgaSelect = document.getElementById('agent-lga-select');
    const wardSelect = document.getElementById('agent-ward-select');
    const pollingUnitSelect = document.getElementById('agent-polling-unit-select');
    const fixedLocationFields = window.campaignFixedLocationFields || {};
    let registeredLocation = null;

    function option(label, value = '') {
        return `<option value="${value}">${label}</option>`;
    }

    function resetSelect(select, label) {
        select.innerHTML = option(label);
        select.disabled = true;
    }

    async function fetchJson(url) {
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) {
            throw new Error('Unable to load options');
        }
        return await response.json();
    }

    function setMode() {
        const isOverride = overrideRadio.checked;
        overridePanel.classList.toggle('d-none', !isOverride);
        overrideFlag.value = isOverride ? '1' : '0';
        assignedPollingUnit.value = isOverride ? (pollingUnitSelect.value || '') : (registeredLocation?.polling_unit_id || '');
    }

    async function loadLgas(stateId, selectedLgaId = null) {
        resetSelect(lgaSelect, 'Loading LGAs...');
        resetSelect(wardSelect, 'Select LGA First');
        resetSelect(pollingUnitSelect, 'Select Ward First');
        const data = await fetchJson(`${routes.lgas}?state_id=${stateId}`);
        lgaSelect.disabled = false;
        lgaSelect.innerHTML = option(data.lgas.length ? 'Select LGA' : 'No LGAs found');
        data.lgas.forEach(item => lgaSelect.insertAdjacentHTML('beforeend', option(item.name, item.id)));
        if (selectedLgaId) {
            lgaSelect.value = selectedLgaId;
            await loadWards(selectedLgaId, registeredLocation?.ward_id);
        }
    }

    async function loadWards(lgaId, selectedWardId = null) {
        resetSelect(wardSelect, 'Loading Wards...');
        resetSelect(pollingUnitSelect, 'Select Ward First');
        const data = await fetchJson(`${routes.wards}?lga_id=${lgaId}`);
        wardSelect.disabled = false;
        wardSelect.innerHTML = option(data.wards.length ? 'Select Ward' : 'No wards found');
        data.wards.forEach(item => wardSelect.insertAdjacentHTML('beforeend', option(item.name, item.id)));
        if (selectedWardId) {
            wardSelect.value = selectedWardId;
            await loadPollingUnits(selectedWardId, registeredLocation?.polling_unit_id);
        }
    }

    async function loadPollingUnits(wardId, selectedPollingUnitId = null) {
        resetSelect(pollingUnitSelect, 'Loading Polling Units...');
        const data = await fetchJson(`${routes.pollingUnits}?ward_id=${wardId}`);
        pollingUnitSelect.disabled = false;
        pollingUnitSelect.innerHTML = option(data.pollingUnits.length ? 'Select Polling Unit' : 'No polling units found');
        data.pollingUnits.forEach(item => pollingUnitSelect.insertAdjacentHTML('beforeend', option(item.name, item.id)));
        if (selectedPollingUnitId) {
            pollingUnitSelect.value = selectedPollingUnitId;
        }
        if (overrideRadio.checked) {
            assignedPollingUnit.value = pollingUnitSelect.value || '';
        }
    }

    async function loadUserLocation() {
        const userId = userSelect.value;
        registeredLocation = null;
        assignedPollingUnit.value = '';
        resetSelect(lgaSelect, 'Select State First');
        resetSelect(wardSelect, 'Select LGA First');
        resetSelect(pollingUnitSelect, 'Select Ward First');

        if (!userId) {
            summary.textContent = 'Select a user to see the user registered polling unit.';
            return;
        }

        summary.textContent = 'Loading registered location...';
        const data = await fetchJson(routes.user.replace('__USER__', userId));
        registeredLocation = data.registered;

        if (registeredLocation.polling_unit_id) {
            summary.textContent = 'Registered polling unit: ' + registeredLocation.label;
            assignedPollingUnit.value = registeredLocation.polling_unit_id;
            stateSelect.value = registeredLocation.state_id || '';
            if (stateSelect.value) {
                await loadLgas(stateSelect.value, registeredLocation.lga_id);
            }
        } else {
            summary.textContent = 'This user does not have a registered polling unit. Use campaign deployment override with a clear reason.';
            overrideRadio.checked = true;
        }

        setMode();
    }

    userSelect.addEventListener('change', loadUserLocation);
    registeredRadio.addEventListener('change', setMode);
    overrideRadio.addEventListener('change', setMode);
    stateSelect.addEventListener('change', () => stateSelect.value && loadLgas(stateSelect.value));
    lgaSelect.addEventListener('change', () => lgaSelect.value && loadWards(lgaSelect.value));
    wardSelect.addEventListener('change', () => wardSelect.value && loadPollingUnits(wardSelect.value));
    pollingUnitSelect.addEventListener('change', function () {
        if (overrideRadio.checked) {
            assignedPollingUnit.value = pollingUnitSelect.value;
        }
    });

    if (userSelect.value) {
        loadUserLocation();
    } else if (fixedLocationFields.lga_id) {
        loadWards(fixedLocationFields.lga_id);
    } else if (fixedLocationFields.state_id) {
        stateSelect.value = fixedLocationFields.state_id;
        loadLgas(fixedLocationFields.state_id);
    }
    setMode();
});
</script>
