@php
    $enterpriseClaims = app(\App\Services\Enterprise\EntitlementService::class)->cached();
    $enterpriseCapabilities = app(\App\Services\CampaignCapabilityService::class);
    $enterpriseLinks = [
        'advanced_reports' => ['reports.view', 'enterprise.reports.workforce', 'Advanced Reports', 'fa-chart-bar'],
        'campaign_finance' => ['finance.view', 'finance.index', 'Campaign Finance', 'fa-money-bill'],
        'campaign_merchandise' => ['merchandise.view', 'enterprise.inventory.index', 'Merchandise & Inventory', 'fa-boxes'],
    ];
@endphp
<nav aria-label="Enterprise modules"><ul class="nav nav-pills nav-sidebar flex-column sidebar-nav">
@foreach($enterpriseLinks as $enterpriseFeature => [$enterprisePermission, $enterpriseRoute, $enterpriseLabel, $enterpriseIcon])
    @php($enterpriseGrant = $enterpriseClaims['features'][$enterpriseFeature] ?? null)
    @if($enterpriseGrant && $enterpriseGrant['active'] === true && $enterpriseGrant['starts_at'] <= time()
        && (!isset($enterpriseGrant['expires_at']) || $enterpriseGrant['expires_at'] > time())
        && \Illuminate\Support\Facades\Route::has($enterpriseRoute) && $enterpriseCapabilities->allows($profileData, $enterprisePermission))
        <li class="nav-item"><a href="{{ route($enterpriseRoute) }}" class="nav-link {{ request()->routeIs($enterpriseRoute.'*') ? 'active' : '' }}"><i class="nav-icon fas {{ $enterpriseIcon }}"></i><p>{{ $enterpriseLabel }}</p></a></li>
    @endif
@endforeach
</ul></nav>
