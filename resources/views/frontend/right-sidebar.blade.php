@php
    $postWatch = app(\App\Services\CommunityPostWatchService::class)->summary();
    $scopeSummary = app(\App\Services\PackageScopeService::class)->display();
    $guidelines = trim(strip_tags((string) ($SystemSetting->community_guidelines ?? '')));
@endphp

<div class="card card-primary mb-3 community-post-watch">
    <div class="card-header">
        <h6 class="card-title mb-0">{{ $postWatch['title'] ?? 'Election Watch' }}</h6>
    </div>
    <div class="card-body">
        @if(($postWatch['state'] ?? 'empty') === 'active')
            <p class="small text-muted mb-2">{{ $postWatch['message'] }}</p>
            <div class="fw-semibold mb-2">
                Leading: {{ $postWatch['leading']['party'] ?? 'Not available' }}
                <span class="text-muted">({{ number_format($postWatch['leading']['votes'] ?? 0) }})</span>
            </div>
            <ol class="ps-3 mb-0 small">
                @foreach($postWatch['top'] as $result)
                    <li>{{ $result['party'] }} - {{ number_format($result['votes']) }}</li>
                @endforeach
            </ol>
        @elseif(($postWatch['state'] ?? 'empty') === 'upcoming')
            <p class="mb-1">{{ $postWatch['message'] }}</p>
            <p class="mb-0 small text-muted">{{ $postWatch['countdown_label'] }}@if(!empty($postWatch['date'])) - {{ $postWatch['date'] }}@endif</p>
        @else
            <p class="mb-0 text-muted">{{ $postWatch['message'] ?? 'No active election watch at the moment.' }}</p>
        @endif
    </div>
</div>

<div class="card card-primary mb-3 community-scope-summary">
    <div class="card-header">
        <h6 class="card-title mb-0">My Campaign Scope</h6>
    </div>
    <div class="card-body">
        <p class="mb-0 small text-muted">{{ $scopeSummary }}</p>
    </div>
</div>

<div class="card card-primary mb-3 community-guidelines">
    <div class="card-header">
        <h6 class="card-title mb-0">Community Guidelines</h6>
    </div>
    <div class="card-body">
        <p class="mb-0 small text-muted">
            {{ $guidelines !== '' ? \Illuminate\Support\Str::limit($guidelines, 220) : 'Keep posts relevant, respectful, and within your campaign community.' }}
        </p>
    </div>
</div>
