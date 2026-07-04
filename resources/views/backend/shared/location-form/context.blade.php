@php
    $locationForm = $locationForm ?? null;
    $fixedLocationInputs = $locationForm['hidden_inputs'] ?? [];
    $fixedLocationLabels = $locationForm['fixed_labels'] ?? [];
    $fixedLocationMessage = $locationForm['message'] ?? null;
@endphp

@if(!empty($fixedLocationInputs))
    @if(($profileData->access_level ?? null) !== 'user')
    <div class="alert alert-info campaign-location-context">
        <strong>Licensed geography:</strong>
        {{ $fixedLocationMessage ?: 'Fixed licensed geography is applied automatically for this installation.' }}
        @if(!empty($fixedLocationLabels))
            <div class="mt-1 small">
                @foreach($fixedLocationLabels as $field => $label)
                    <span class="badge badge-light border mr-1">{{ ucwords(str_replace('_id', '', str_replace('_', ' ', $field))) }}: {{ $label }}</span>
                @endforeach
            </div>
        @endif
    </div>
    @endif

    @foreach($fixedLocationInputs as $field => $value)
        @if($value !== null && $value !== '')
            <input type="hidden" name="{{ $field }}" value="{{ $value }}" data-fixed-location-field="{{ $field }}">
            @if($field === 'polling_unit_id')
                <input type="hidden" name="pu_id" value="{{ $value }}" data-fixed-location-field="pu_id">
            @endif
        @endif
    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fixedLocationFields = @json($fixedLocationInputs);
            window.campaignFixedLocationFields = Object.assign({}, window.campaignFixedLocationFields || {}, fixedLocationFields);
            const selectors = {
                region_id: '#region-dropdown',
                state_id: '#state-dropdown, #agent-state-select',
                senatorial_district_id: '#senatorial-district-dropdown',
                federal_constituency_id: '#federal-constituency-dropdown',
                lga_id: '#lga-dropdown, #agent-lga-select',
                ward_id: '#ward-dropdown, #agent-ward-select',
                polling_unit_id: '#pu-dropdown, #agent-polling-unit-select',
                pu_id: '#pu-dropdown, #agent-polling-unit-select',
            };

            Object.entries(fixedLocationFields).forEach(function ([field, value]) {
                const selector = selectors[field];
                if (!selector || value === null || value === '') {
                    return;
                }

                document.querySelectorAll(selector).forEach(function (select) {
                    select.value = String(value);
                    select.disabled = true;
                    const formGroup = select.closest('.form-group');
                    if (formGroup) {
                        formGroup.classList.add('d-none');
                    }
                });
            });
        });
    </script>
@endif
