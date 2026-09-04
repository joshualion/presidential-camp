@extends('backend.template.backend-master')

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-warning">{{ $errors->first() }}</div>
                    @endif
                    @if(!$license)
                        <div class="alert alert-success">Community installation is active. No commercial license is required for normal Campaign Manager operation.</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Package type</div>
                                <div class="font-weight-bold">{{ $currentScope->package_type ?: 'Not configured' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Campaign scope</div>
                                <div class="font-weight-bold">{{ trim(($currentScope->scope_type ?? '').' '.($currentScope->scope_name ?? '')) ?: 'Not configured' }}</div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-success">This installation was activated during setup.</div>
                        <div class="row">
                            @foreach([
                                'License key' => $maskedKey,
                                'Status' => $license->status,
                                'Package type' => $currentScope->package_type,
                                'Scope type' => $currentScope->scope_type,
                                'Scope name' => $currentScope->scope_name,
                                'Maintenance expiry' => optional($license->maintenance_expires_at)->toDayDateTimeString(),
                                'Updates until' => optional($license->updates_until)->toDayDateTimeString(),
                                'Activated at' => optional($license->activated_at)->toDayDateTimeString(),
                                'Last checked at' => optional($license->last_checked_at)->toDayDateTimeString(),
                                'App URL' => $license->app_url,
                                'Domain' => $license->domain,
                            ] as $label => $value)
                                <div class="col-md-6 mb-3">
                                    <div class="text-muted small">{{ $label }}</div>
                                    <div class="font-weight-bold">{{ $value ?: 'Not available' }}</div>
                                </div>
                            @endforeach
                        </div>
                        <hr>
                        <h5>Resolved licensed boundaries</h5>
                        <div class="row">
                            @foreach([
                                'State' => trim(($currentScope->state_id ? '#'.$currentScope->state_id : '').' '.($currentScope->state_name ?? '')),
                                'Senatorial district' => trim(($currentScope->senatorial_district_id ? '#'.$currentScope->senatorial_district_id : '').' '.($currentScope->senatorial_district_name ?? '')),
                                'Federal constituency' => trim(($currentScope->federal_constituency_id ? '#'.$currentScope->federal_constituency_id : '').' '.($currentScope->federal_constituency_name ?? '')),
                                'LGA' => trim(($currentScope->lga_id ? '#'.$currentScope->lga_id : '').' '.($currentScope->lga_name ?? '')),
                            ] as $label => $value)
                                <div class="col-md-6 mb-3">
                                    <div class="text-muted small">{{ $label }}</div>
                                    <div class="font-weight-bold">{{ $value ?: 'Not set' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

