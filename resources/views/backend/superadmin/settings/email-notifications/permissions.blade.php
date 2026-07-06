@extends('backend.template.backend-master')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Email Notification Permissions</h3></div>
            <form method="POST" action="{{ route('superadmin.settings.email-notifications.update') }}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                    <p class="text-muted">Choose which access levels can send email notifications.</p>
                    <table class="table table-bordered">
                        <thead><tr><th>Access level</th><th style="width: 160px">Can send email</th></tr></thead>
                        <tbody>
                            @foreach($settings as $setting)
                                <tr>
                                    <td>{{ $setting['label'] }}</td>
                                    <td>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="permission-{{ $setting['access_level'] }}" name="enabled[]" value="{{ $setting['access_level'] }}" @checked($setting['enabled']) @disabled($setting['locked'])>
                                            <label class="custom-control-label" for="permission-{{ $setting['access_level'] }}">{{ $setting['locked'] ? 'Always enabled' : 'Enabled' }}</label>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Save permissions</button></div>
            </form>
        </div>
    </div>
</section>
@endsection
