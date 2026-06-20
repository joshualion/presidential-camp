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
                        <div class="alert alert-warning">No active local license was found. Please contact support or rerun the installer/reset procedure.</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Deployment mode</div>
                                <div class="font-weight-bold">{{ $currentScope->deployment_mode ?: 'Not resolved' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Fallback package</div>
                                <div class="font-weight-bold">{{ $currentScope->package_type ?: 'Not resolved' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Dashboard label</div>
                                <div class="font-weight-bold">{{ $scopeService->dashboardLabel() }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Scope helper</div>
                                <div class="font-weight-bold">{{ $currentScope->fallback_used ? 'Fallback mode' : 'Local license mode' }}</div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-success">This installation was activated during setup. The installer/local license record is the activation source for this deployment.</div>
                        <div class="row">
                            @foreach([
                                'License key' => $maskedKey,
                                'Status' => $license->status,
                                'Activation source' => 'Installer / local license',
                                'Deployment mode' => $currentScope->deployment_mode,
                                'Package type' => $currentScope->package_type,
                                'Scope type' => $currentScope->scope_type,
                                'Scope name' => $currentScope->scope_name,
                                'Dashboard label' => $scopeService->dashboardLabel(),
                                'Scope helper display' => $scopeService->display(),
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
                        <h5>Enabled modules</h5>
                        @if(empty($currentScope->modules))
                            <p class="text-muted mb-0">No module records were returned by the portal.</p>
                        @else
                            <div class="d-flex flex-wrap">
                                @foreach($currentScope->modules as $module)
                                    <span class="badge badge-success mr-2 mb-2">{{ $module }}</span>
                                @endforeach
                            </div>
                        @endif
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
