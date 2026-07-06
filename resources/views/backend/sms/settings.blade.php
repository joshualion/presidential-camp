@extends('backend.template.backend-master')
@section('content')
<section class="content"><div class="container-fluid">
    <div class="row"><div class="col-lg-8"><div class="card card-primary"><div class="card-header"><h3 class="card-title">Portal connection</h3></div><div class="card-body">
        <form method="POST" action="{{ route('superadmin.sms.settings.update') }}">@csrf @method('PUT')
            @foreach($errors->all() as $error)<div class="alert alert-danger">{{ $error }}</div>@endforeach
            <div class="custom-control custom-switch mb-3"><input type="checkbox" class="custom-control-input" id="enabled" name="portal_sms_enabled" value="1" @checked(old('portal_sms_enabled', $settings->portal_sms_enabled))><label class="custom-control-label" for="enabled">Enable portal SMS</label></div>
            <div class="form-group"><label>Portal SMS API base URL</label><input class="form-control" type="url" name="portal_sms_base_url" value="{{ old('portal_sms_base_url', $settings->portal_sms_base_url ?: config('portal_sms.base_url')) }}" required><small class="text-muted">Must end with /api/core/v1.</small></div>
            <div class="form-group"><label>Client key</label><input class="form-control" name="portal_sms_client_key" value="{{ old('portal_sms_client_key', $settings->portal_sms_client_key) }}" autocomplete="off" required></div>
            <div class="form-group"><label>Client secret</label><input class="form-control" type="password" name="portal_sms_secret" value="" autocomplete="new-password" placeholder="{{ $settings->getRawOriginal('portal_sms_secret') ? 'Secret saved — leave blank to keep it' : 'Enter portal client secret' }}"><small class="text-muted">The stored secret is encrypted and is never shown again.</small></div>
            <div class="form-group"><label>Default route</label><select class="form-control" name="portal_sms_default_route"><option value="regular" @selected($settings->portal_sms_default_route === 'regular')>Regular</option><option value="priority" @selected($settings->portal_sms_default_route === 'priority')>Priority</option></select></div>
            @foreach(['portal_sms_system_route_enabled'=>'Allow system route','portal_sms_sender_id_request_enabled'=>'Allow sender ID requests','portal_sms_sending_enabled'=>'Allow SMS sending','portal_sms_topup_enabled'=>'Allow top-ups','portal_sms_credit_request_enabled'=>'Allow credit requests'] as $field=>$label)
                <div class="custom-control custom-switch mb-2"><input class="custom-control-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $settings->{$field}))><label class="custom-control-label" for="{{ $field }}">{{ $label }}</label></div>
            @endforeach
            <button class="btn btn-primary mt-3" type="submit">Save settings</button>
        </form>
    </div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-header">Portal status</div><div class="card-body">
        <p><strong>Connection:</strong> {{ $settings->portal_sms_last_connection_status ?: 'Not tested' }}</p><p><strong>Last successful portal check:</strong> {{ $settings->portal_sms_last_synced_at?->format('d M Y H:i') ?: 'Never' }}</p>
        @if($wallet && ($wallet['ok'] ?? false))<p><strong>Master wallet balance:</strong> {{ data_get($wallet, 'data.currency', 'NGN') }} {{ number_format((float) data_get($wallet, 'data.wallet_balance', 0), 2) }}<br><small>Available {{ number_format((float) data_get($wallet, 'data.available_balance', 0), 2) }}; reserved {{ number_format((float) data_get($wallet, 'data.reserved_balance', 0), 2) }}</small></p>
            @foreach((array) data_get($wallet, 'data.active_route_prices', []) as $price)<div>{{ str(data_get($price, 'route'))->title() }}: {{ data_get($price, 'currency', 'NGN') }} {{ data_get($price, 'retail_price_per_unit') }} / unit</div>@endforeach
        @elseif($wallet)<div class="alert alert-warning">{{ $wallet['message'] }}</div>@endif
        <form method="POST" action="{{ route('superadmin.sms.settings.test') }}">@csrf<button class="btn btn-outline-primary" type="submit">Test connection</button></form>
    </div></div></div></div>
</div></section>
@endsection
