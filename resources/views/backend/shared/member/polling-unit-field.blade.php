@php
    $selectedPollingUnitId = old('pu_id', old('polling_unit_id', $member->polling_unit_id ?? null));
    $pollingUnitOptions = $pollingUnits ?? $pus ?? collect();
@endphp

<div class="form-group row">
    <label for="pu-dropdown" class="col-sm-2 col-form-label">Voting Polling Unit</label>
    <div class="col-sm-10">
        <select class="form-control @error('pu_id') is-invalid @enderror" name="pu_id" id="pu-dropdown">
            <option value="">Select Polling Unit</option>
            @foreach($pollingUnitOptions as $pollingUnit)
                <option value="{{ $pollingUnit->id }}" {{ (string) $selectedPollingUnitId === (string) $pollingUnit->id ? 'selected' : '' }}>
                    {{ $pollingUnit->name }}
                </option>
            @endforeach
        </select>
        @error('pu_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
