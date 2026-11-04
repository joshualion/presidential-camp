@extends('backend.template.backend-master')
@section('content')
<section class="content cm-ui"><div class="container-fluid">
    <h1 class="h3 text-primary">AI report settings</h1>
    <p>Use your own OpenAI account. Provider charges are paid directly to OpenAI and are separate from your add-on licence. Set provider account spending limits as an additional safeguard.</p>
    @if(session('status'))<p class="alert alert-success" role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<p class="alert alert-danger" role="alert">{{ $errors->first() }}</p>@endif
    <form class="card card-body" method="POST" action="{{ route('enterprise.reports.ai.settings.update') }}">@csrf @method('PUT')
        <input type="hidden" name="enabled" value="0">
        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="enabled" value="1" id="ai-enabled" @checked(old('enabled', $settings?->enabled ?? false))><label class="form-check-label" for="ai-enabled">Enable AI analysis for authorized users</label></div>
        <div class="form-group"><label for="ai-key">OpenAI API key</label><input id="ai-key" type="password" autocomplete="new-password" name="api_key" class="form-control"><small class="form-text text-muted">Leave blank to retain the stored key. The key is encrypted and is never displayed.</small></div>
        <div class="form-group"><label for="ai-model">OpenAI model ID</label><input id="ai-model" name="model" class="form-control" value="{{ old('model', $settings?->model ?? 'gpt-4.1-mini') }}" required maxlength="100"></div>
        <div class="row">
            @foreach(['daily_requests' => ['Requests per user per day', 5, 100], 'daily_tokens' => ['Token allowance per user per day', 20000, 1000000], 'installation_daily_requests' => ['Requests for entire installation per day', 25, 1000], 'installation_daily_tokens' => ['Token allowance for entire installation per day', 100000, 10000000], 'max_output_tokens' => ['Maximum output tokens per request', 1000, 4000]] as $name => [$label, $default, $max])
                <div class="col-md-6 form-group"><label for="ai-{{ $name }}">{{ $label }}</label><input id="ai-{{ $name }}" name="{{ $name }}" type="number" min="1" max="{{ $max }}" class="form-control" value="{{ old($name, $settings?->$name ?? $default) }}" required></div>
            @endforeach
        </div>
        <p>Daily limits reset at midnight UTC. Each attempt reserves a conservative input-token estimate plus the maximum output allowance. Failed or timed-out attempts keep their reservation because the provider may have processed them. Tokens do not represent a fixed currency amount; model prices vary.</p>
        <button class="btn btn-primary align-self-start">Save AI settings</button>
    </form>
    <a href="{{ route('enterprise.reports.workforce') }}">Return to workforce reports</a>
    <h2 class="h5 mt-4 text-primary">Recent AI usage and settings audit</h2>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Time</th><th>User ID</th><th>Model</th><th>Status</th><th>Reserved tokens</th><th>Actual tokens</th></tr></thead><tbody>
        @forelse($audits as $audit)<tr><td>{{ $audit->created_at }}</td><td>{{ $audit->user_id }}</td><td>{{ $audit->model }}</td><td>{{ str_replace('_', ' ', $audit->status) }}</td><td>{{ number_format($audit->reserved_tokens) }}</td><td>{{ $audit->actual_tokens === null ? '—' : number_format($audit->actual_tokens) }}</td></tr>
        @empty<tr><td colspan="6">No AI activity has been recorded.</td></tr>@endforelse
    </tbody></table></div>
</div></section>
@endsection
