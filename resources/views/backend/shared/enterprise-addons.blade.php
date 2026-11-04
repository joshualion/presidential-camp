@extends('backend.template.backend-master')
@section('content')
<section class="content cm-ui"><div class="container-fluid">
  <p>Extend this installation with independently licensed campaign services. Community features and update coverage remain available under their existing terms.</p>
  @if(session('addon_status'))<div class="alert alert-success" role="status">{{ session('addon_status') }}</div>@endif
  @if(session('addon_error'))<div class="alert alert-warning" role="alert">{{ session('addon_error') }}</div>@endif
  <form method="POST" action="{{ route('superadmin.addons.refresh') }}" class="mb-3">@csrf<button class="btn btn-primary">Refresh licence status</button></form>
  @unless($entitlement)<p class="alert alert-info">No verified add-on status is cached. Register this installation and refresh to retrieve available pricing and licences.</p>@endunless
  <div class="row">
  @foreach($features as $key => $label)
    @php
      $grant = $entitlement['features'][$key] ?? null;
      $catalogue = $entitlement['catalogue'][$key] ?? [];
      $scope = app(\App\Services\PackageScopeService::class)->packageType();
      $scope = $scope === 'chairmanship' ? 'lga' : $scope;
      $prices = collect($catalogue['prices'] ?? [])->where('scope', $scope);
      $canPurchase = !empty($catalogue['purchasable']) && $prices->isNotEmpty()
        && !empty($entitlement['installation_id']);
      $portalBase = rtrim((string) config('campaign.portal_web_base'), '/');
      $origin = parse_url($portalBase, PHP_URL_SCHEME).'://'.parse_url($portalBase, PHP_URL_HOST);
      $trustedOrigin = parse_url($portalBase, PHP_URL_SCHEME) === 'https'
        && in_array($origin, config('campaign.portal_checkout_origins', []), true);
      $checkoutUrl = $canPurchase && $trustedOrigin
        ? $portalBase.'/clientarea/campaign-manager/addons/checkout?'.http_build_query([
          'installation' => $entitlement['installation_id'], 'feature' => $key, 'scope' => $scope,
        ], '', '&', PHP_QUERY_RFC3986)
        : null;
    @endphp
    <div class="col-12 col-lg-4 mb-3"><article class="card card-outline card-primary h-100"><div class="card-body">
      <h2 class="h5 text-primary">{{ $label }}</h2>
      <p>{{ $entitlement['catalogue'][$key]['description'] ?? config('enterprise.descriptions.'.$key) }}</p>
      <p><strong>{{ $grant && (!isset($grant['expires_at']) || $grant['expires_at'] > time()) ? 'Licence recorded' : 'Locked' }}</strong></p>
      @if(isset($grant['expires_at']))<p>Licence expiry: {{ gmdate('Y-m-d H:i', $grant['expires_at']) }} UTC</p>@endif
      @if($prices->isNotEmpty())
        <ul>@foreach($prices as $price)<li>{{ $price['currency'] }} {{ number_format((float) $price['amount'], 2) }} — {{ $price['billing_label'] }} ({{ str($price['scope'])->replace('_', ' ')->title() }})</li>@endforeach</ul>
      @elseif(!empty($catalogue['prices']))
        <p class="text-muted">No price is configured for this installation's campaign scope.</p>
      @else
        <p class="text-muted">Pricing is not yet available for this add-on.</p>
      @endif
      @if($checkoutUrl && !($grant && !empty($grant['active'])))
        <a href="{{ $checkoutUrl }}" class="btn btn-primary" rel="noopener noreferrer">Review price and purchase</a>
      @elseif(!empty($catalogue['purchasable']) && !$trustedOrigin)
        <p class="small text-warning">The official Portal checkout origin must be configured before purchasing.</p>
      @elseif(!empty($grant['active']))
        <p class="small">This installation has a recorded licence. Refresh status to confirm access.</p>
      @else
        <p class="small">This add-on is not yet available for purchase. Your existing campaign tools remain available.</p>
      @endif
    </div></article></div>
  @endforeach
  </div>
</div></section>
@endsection
