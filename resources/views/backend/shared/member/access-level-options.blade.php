@php
    $selectedAccessLevel = old('access_level', $selectedAccessLevel ?? null);
    $accessLevelOptions = $assignableAccessLevels ?? [];
@endphp

@foreach($accessLevelOptions as $accessLevelValue => $accessLevelLabel)
    <option value="{{ $accessLevelValue }}" {{ $selectedAccessLevel === $accessLevelValue ? 'selected' : '' }}>
        {{ $accessLevelLabel }}
    </option>
@endforeach
