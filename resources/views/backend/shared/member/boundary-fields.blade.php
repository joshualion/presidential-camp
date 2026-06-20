@php
    $selectedStateId = old('state_id', $member->state_id ?? null);
    $selectedSenatorialDistrictId = old('senatorial_district_id', $member->senatorial_district_id ?? null);
    $selectedFederalConstituencyId = old('federal_constituency_id', $member->federal_constituency_id ?? null);
@endphp

@include('backend.shared.location-form.context')

<div class="form-group row">
    <label for="senatorial-district-dropdown" class="col-sm-2 col-form-label">Senatorial District</label>
    <div class="col-sm-10">
        <select class="form-control @error('senatorial_district_id') is-invalid @enderror" name="senatorial_district_id" id="senatorial-district-dropdown">
            <option value="">Select Senatorial District</option>
            @foreach(($senatorialDistricts ?? []) as $district)
                @if(!$selectedStateId || !$district->state_id || (string) $district->state_id === (string) $selectedStateId)
                    <option value="{{ $district->id }}" {{ (string) $selectedSenatorialDistrictId === (string) $district->id ? 'selected' : '' }}>{{ $district->name }}</option>
                @endif
            @endforeach
        </select>
        @error('senatorial_district_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-group row">
    <label for="federal-constituency-dropdown" class="col-sm-2 col-form-label">Federal Constituency</label>
    <div class="col-sm-10">
        <select class="form-control @error('federal_constituency_id') is-invalid @enderror" name="federal_constituency_id" id="federal-constituency-dropdown">
            <option value="">Select Federal Constituency</option>
            @foreach(($federalConstituencies ?? []) as $constituency)
                @if((!$selectedStateId || !$constituency->state_id || (string) $constituency->state_id === (string) $selectedStateId)
                    && (!$selectedSenatorialDistrictId || !$constituency->senatorial_district_id || (string) $constituency->senatorial_district_id === (string) $selectedSenatorialDistrictId))
                    <option value="{{ $constituency->id }}" {{ (string) $selectedFederalConstituencyId === (string) $constituency->id ? 'selected' : '' }}>{{ $constituency->name }}</option>
                @endif
            @endforeach
        </select>
        @error('federal_constituency_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
