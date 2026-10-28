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
    @php($grant = $entitlement['features'][$key] ?? null)
    <div class="col-12 col-lg-4 mb-3"><article class="card card-outline card-primary h-100"><div class="card-body">
      <h2 class="h5 text-primary">{{ $label }}</h2>
      <p>{{ $entitlement['catalogue'][$key]['description'] ?? config('enterprise.descriptions.'.$key) }}</p>
      <p><strong>{{ $grant && (!isset($grant['expires_at']) || $grant['expires_at'] > time()) ? 'Licence recorded' : 'Locked' }}</strong></p>
      @if(isset($grant['expires_at']))<p>Licence expiry: {{ gmdate('Y-m-d H:i', $grant['expires_at']) }} UTC</p>@endif
      @if(!empty($entitlement['catalogue'][$key]['prices']))
        <ul>@foreach($entitlement['catalogue'][$key]['prices'] as $price)<li>{{ $price['scope'] }}: {{ $price['currency'] }} {{ $price['amount'] }} — {{ $price['billing_label'] }}</li>@endforeach</ul>
      @else<p class="text-muted">Pricing is not yet available.</p>@endif
      <p class="small">This add-on is not yet available for purchase. Your existing campaign tools remain available.</p>
    </div></article></div>
  @endforeach
  </div>
</div></section>
@endsection
