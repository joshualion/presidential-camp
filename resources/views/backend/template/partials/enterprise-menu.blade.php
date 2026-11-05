@php
    $enterpriseClaims = app(\App\Services\Enterprise\EntitlementService::class)->cached();
    $enterpriseCapabilities = app(\App\Services\CampaignCapabilityService::class);
    $enterpriseLinks = [
        'advanced_reports' => ['reports.view', 'enterprise.reports.workforce', 'Advanced Reports', 'fa-chart-bar'],
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
@php($financeGrant = $enterpriseClaims['features']['campaign_finance'] ?? null)
@if($financeGrant && $financeGrant['active'] === true && $financeGrant['starts_at'] <= time()
    && (!isset($financeGrant['expires_at']) || $financeGrant['expires_at'] > time())
    && \Illuminate\Support\Facades\Route::has('finance.section') && $enterpriseCapabilities->allows($profileData, 'finance.view'))
    @php($financeSections = app(\App\Services\Enterprise\CampaignFinancePolicy::class)->visible($profileData))
    @if($financeSections)
        <li class="nav-item {{ request()->routeIs('finance.*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-money-bill"></i><p>Finance <i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
                @foreach($financeSections as $key => $label)
                    @php($active = ($key === 'settings' && request()->routeIs('finance.settings')) || ($key === 'dashboard' && request()->routeIs('finance.index')) || request()->route('section') === $key)
                    <li class="nav-item">
                        <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ $key === 'settings' ? route('finance.settings') : route('finance.section', ['section' => $key]) }}"><i class="far fa-circle nav-icon"></i><p>{{ $label }}</p></a>
                    </li>
                @endforeach
            </ul>
        </li>
    @endif
@endif
</ul></nav>
