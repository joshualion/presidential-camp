<input type="hidden" name="submission_token" value="{{ $composition['submission_token'] }}">
<input type="hidden" name="subject" value="{{ $composition['subject'] }}">
<textarea name="body" hidden>{{ $composition['body'] }}</textarea>
<input type="hidden" name="cta_label" value="{{ $composition['cta_label'] ?? '' }}">
<input type="hidden" name="cta_url" value="{{ $composition['cta_url'] ?? '' }}">
<input type="hidden" name="recipient_group" value="{{ $composition['recipient_group'] }}">
@foreach($composition['access_levels'] ?? [] as $accessLevel)
    <input type="hidden" name="access_levels[]" value="{{ $accessLevel }}">
@endforeach
@foreach($composition['role_ids'] ?? [] as $roleId)
    <input type="hidden" name="role_ids[]" value="{{ $roleId }}">
@endforeach
<input type="hidden" name="active_only" value="{{ $composition['audience_filters']['active_only'] ? 1 : 0 }}">
<input type="hidden" name="verified_only" value="{{ $composition['audience_filters']['verified_only'] ? 1 : 0 }}">
@foreach(['state_id', 'lga_id', 'ward_id', 'polling_unit_id'] as $locationField)
    @if($composition['audience_filters'][$locationField])
        <input type="hidden" name="{{ $locationField }}" value="{{ $composition['audience_filters'][$locationField] }}">
    @endif
@endforeach
