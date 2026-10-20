<form method="GET" action="{{ route($profileData->access_level.'.member.add') }}" class="mb-3 p-3 border rounded">
    <input type="hidden" name="offline_form" value="1">
    <label for="offline-enrollment-level">Offline registration form for</label>
    <select id="offline-enrollment-level" name="enrollment_level" class="form-control mb-2">
        @foreach($assignableAccessLevels as $level => $label)
        <option value="{{ $level }}" @selected($level === 'user')>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-outline-primary"><i class="fas fa-print" aria-hidden="true"></i> Print Offline Registration Form (PDF)</button>
    <small class="d-block text-muted mt-2">Download and print a blank form. Enter completed forms through Add Member, then complete each member's profile.</small>
</form>
