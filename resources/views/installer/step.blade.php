<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Campaign Manager Self-Hosted Installer</title>
    <style>
        :root{--green:#008751;--green-dark:#006b40;--ink:#102033;--muted:#526174;--line:#dbe5ee;--panel:#fff;--soft:#f5f8fb;--gold:#d9a441;--danger:#b42318}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:linear-gradient(135deg,#072718 0%,#008751 42%,#f4f8fb 42.2%,#eef4f8 100%);color:var(--ink);font-family:Inter,Segoe UI,Arial,sans-serif}
        body:before{content:"";position:fixed;inset:0;background-image:linear-gradient(120deg,rgba(255,255,255,.06) 0 1px,transparent 1px 90px);opacity:.5;pointer-events:none;animation:drift 18s linear infinite}@keyframes drift{from{background-position:0 0}to{background-position:180px 90px}}
        .wrap{max-width:1220px;margin:0 auto;padding:34px 18px 44px}.top{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:24px;color:#fff}.brand{display:flex;gap:14px;align-items:center}.mark{width:58px;height:58px;border-radius:8px;background:rgba(255,255,255,.96);display:grid;place-items:center;box-shadow:0 18px 40px rgba(0,0,0,.18);overflow:hidden}.mark img{max-width:48px;max-height:48px}.mark span{font-weight:900;color:var(--green);font-size:22px}.brand h1{font-size:28px;line-height:1.15;margin:0}.brand p{margin:6px 0 0;color:rgba(255,255,255,.78)}.meta{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;max-width:520px}.pill{border:1px solid rgba(255,255,255,.24);background:rgba(255,255,255,.10);color:#fff;border-radius:999px;padding:7px 11px;font-size:12px;backdrop-filter:blur(10px)}
        .shell{display:grid;grid-template-columns:315px minmax(0,1fr);gap:22px}.shell.welcome-only{display:block;max-width:980px;margin:0 auto}.shell.welcome-only .steps{display:none}.steps,.panel{background:rgba(255,255,255,.98);border:1px solid rgba(219,229,238,.9);border-radius:8px;box-shadow:0 22px 55px rgba(9,31,48,.18)}.welcome-only .panel{min-height:0;padding:42px}.welcome-hero{display:grid;grid-template-columns:96px minmax(0,1fr);gap:24px;align-items:center}.welcome-logo{width:96px;height:96px;border-radius:8px;background:#f8fafc;border:1px solid #dbe5ee;display:grid;place-items:center;overflow:hidden}.welcome-logo img{max-width:78px;max-height:78px}.welcome-logo span{font-size:34px;font-weight:900;color:var(--green)}.welcome-title{font-size:42px;line-height:1.08;margin:0;color:#0f2b1f}.steps{padding:16px;align-self:start}.step{display:flex;gap:11px;align-items:center;width:100%;padding:10px;border-radius:7px;color:var(--muted);font-size:14px;text-decoration:none;border:1px solid transparent;background:transparent;text-align:left}.step+.step{margin-top:5px}.step.current{background:#e7f8ef;color:var(--green-dark);font-weight:800;border-color:#bfead2}.step.done{color:#24415e}.step.pending{opacity:.55}.step:hover:not(.pending){background:#f1f6f4}.num{width:26px;height:26px;border-radius:999px;background:#edf2f7;color:#54657a;display:grid;place-items:center;font-size:12px;font-weight:800;flex:0 0 auto}.done .num{background:#dff5e8;color:var(--green-dark)}.current .num{background:var(--green);color:#fff}.panel{padding:30px;min-height:580px}.panel h2{margin:0 0 8px;font-size:30px;letter-spacing:0;color:#102033}.lead{color:var(--muted);margin:0 0 20px;line-height:1.55;max-width:780px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.field{margin-bottom:15px}label{display:block;font-weight:800;margin-bottom:7px;font-size:13px;color:#263b53}input,select{width:100%;border:1px solid #cdd8e4;border-radius:7px;padding:12px 13px;font-size:15px;background:#fff;color:var(--ink)}input:focus,select:focus{outline:3px solid rgba(0,135,81,.14);border-color:var(--green)}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}.btn{border:0;border-radius:7px;background:var(--green);color:#fff;padding:12px 17px;font-weight:800;text-decoration:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;min-height:44px}.btn:hover{background:#006b40}.btn.secondary{background:#e8eef4;color:#203149}.btn.warning{background:#fff4dc;color:#704b00;border:1px solid #f2d28d}.btn:disabled{background:#a8b5c2;color:#fff;cursor:not-allowed}.alert{padding:13px 15px;border-radius:7px;margin:0 0 16px;border:1px solid transparent;line-height:1.45}.error{background:#fff1f1;color:#8f1d16;border-color:#ffd0cc}.ok{background:#edf9f1;color:#126a37;border-color:#c8ebd3}.warning-note{background:#fff8e7;color:#74500f;border-color:#f0d894}.info{background:#edf5ff;color:#244f82;border-color:#cfe2fa}table{width:100%;border-collapse:collapse;margin-top:14px;background:#fff;border:1px solid #e7edf3;border-radius:8px;overflow:hidden}td,th{border-bottom:1px solid #edf1f6;padding:11px;text-align:left;font-size:14px;color:#203149}tr:last-child td{border-bottom:0}.badge{display:inline-block;border-radius:999px;padding:4px 9px;font-size:12px;font-weight:800;text-transform:uppercase}.pass{background:#dcfce7;color:#166534}.fail{background:#fee2e2;color:#991b1b}.warn{background:#fef3c7;color:#92400e}.summary,.notice-grid,.progress-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:15px}.summary div{margin:7px 0}.notice-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:16px 0}.notice-grid div{padding:10px;border-radius:7px;background:#fff;border:1px solid #e7edf3}.notice-grid strong{display:block;font-size:12px;color:var(--muted);margin-bottom:4px}.progress-track{height:14px;background:#dce7ef;border-radius:999px;overflow:hidden;margin:12px 0}.progress-fill{height:100%;width:0;background:var(--green);transition:width .25s ease}.progress-copy{color:#263b53;font-weight:800}.section-title{margin:22px 0 8px;font-size:12px;text-transform:uppercase;color:#53657a;letter-spacing:.08em;font-weight:900}.choice-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.choice{display:block;border:1px solid #d7e2ec;border-radius:8px;padding:14px;background:#fff;cursor:pointer}.choice input{width:auto;margin-right:8px}.choice strong{display:block;margin-bottom:5px}.choice span{color:var(--muted);font-size:13px;line-height:1.45}.conditional{display:none}.conditional.active{display:block}
        @media(max-width:900px){.top{display:block}.meta{justify-content:flex-start;margin-top:14px}.shell{grid-template-columns:1fr}.steps{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.step+.step{margin-top:0}.grid,.notice-grid{grid-template-columns:1fr}.panel{padding:22px}.brand h1{font-size:24px}}
    </style>
</head>
<body>
@php
    $logoPath = public_path('logo.png');
    $logoUrl = file_exists($logoPath) ? asset('logo.png') : null;
    $missingStep = session('missing_step');
    $quickstartOrderUrl = $quickstartOrderUrl ?? rtrim((string) config('campaign.portal_web_base', 'https://campaignmanager.ng'), '/').'/order/campaign-manager?service=quickstart_geography';
    $provisioningLevels = [
        'regions' => 'Regions',
        'geopolitical_regions' => 'Geopolitical Regions',
        'states' => 'States',
        'senatorial_districts' => 'Senatorial Districts',
        'federal_constituencies' => 'Federal Constituencies',
        'local_government_areas' => 'LGAs',
        'wards' => 'Wards',
        'polling_units' => 'Polling Units',
    ];
@endphp
<div class="wrap">
    <header class="top">
        <div class="brand">
            <div class="mark">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Campaign Manager logo">
                @else
                    <span>CM</span>
                @endif
            </div>
            <div>
                <h1>Campaign Manager Self-Hosted Installer</h1>
                <p>Secure setup for your Community self-hosted campaign infrastructure.</p>
            </div>
        </div>
        <div class="meta">
            <span class="pill" style="color: #008751; background: #bfead2;">APP_URL: {{ $welcomeMeta['app_url'] }}</span>
            <span class="pill" style="color: #008751; background: #bfead2;">Domain: {{ $welcomeMeta['detected_domain'] }}</span>
            <span class="pill" style="color: #008751; background: #bfead2;">Mode: {{ $welcomeMeta['deployment_mode'] }}</span>
        </div>
    </header>

    <div class="shell {{ $step === 'welcome' ? 'welcome-only' : '' }}">
        <aside class="steps" aria-label="Installer progress">
            @php $stepNumber = 0; @endphp
            @foreach(($stepGroups ?? ['Setup' => array_keys($steps)]) as $group => $groupSteps)
                <div class="section-title">{{ $group }}</div>
                @foreach($groupSteps as $key)
                    @php
                        $stepNumber++;
                        $label = $steps[$key];
                        $status = $step === $key ? 'current' : (in_array($key, $completedSteps, true) ? 'done' : 'pending');
                        $clickable = in_array($key, $clickableSteps, true);
                    @endphp
                    @if($clickable)
                        <a class="step {{ $status }}" href="{{ route('install.'.$key) }}">
                            <span class="num">{{ $stepNumber }}</span><span>{{ $label }}</span>
                        </a>
                    @else
                        <div class="step {{ $status }}">
                            <span class="num">{{ $stepNumber }}</span><span>{{ $label }}</span>
                        </div>
                    @endif
                @endforeach
            @endforeach
        </aside>

        <main class="panel">
            @if($errors->any())
                <div class="alert error">
                    {{ $errors->first() }}
                    @if($missingStep && isset($steps[$missingStep]))
                        <div class="actions"><a class="btn warning" href="{{ route('install.'.$missingStep) }}">Back to {{ $steps[$missingStep] }}</a></div>
                    @endif
                </div>
            @endif
            @if(session('status'))
                <div class="alert ok">{{ session('status') }}</div>
            @endif

            @if($step === 'welcome')
                <div class="welcome-hero">
                    <div class="welcome-logo">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Campaign Manager logo">
                        @else
                            <span>CM</span>
                        @endif
                    </div>
                    <div>
                        <h2 class="welcome-title">Campaign Manager</h2>
                        <p class="lead">Configure this campaign installation, select its operating scope, prepare base geography, and create the first super admin.</p>
                    </div>
                </div>
                <div class="alert info">Community setup is free and self-hosted. QuickStart Geography Provisioning remains optional for teams that want Govware to populate detailed electoral geography automatically.</div>
                <div class="alert warning-note">For best security, point your domain document root to the Laravel public folder or use the public-webroot bridge. Do not expose the full Laravel source directory.</div>
                <div class="notice-grid">
                    <div><strong>Current APP_URL</strong>{{ $welcomeMeta['app_url'] }}</div>
                    <div><strong>Detected domain</strong>{{ $welcomeMeta['detected_domain'] }}</div>
                    <div><strong>Deployment mode</strong>{{ $welcomeMeta['deployment_mode'] }}</div>
                </div>
                <div class="actions"><a class="btn" href="{{ route('install.requirements') }}">Start Setup</a></div>
            @elseif($step === 'requirements')
                <h2 style="color: #008751;"> Requirement Checks</h2>
                <p class="lead">Critical failures must be resolved before setup can continue.</p>
                @if(!empty($sessionWarning))
                    <div class="alert warning-note">{{ $sessionWarning }}</div>
                @endif
                <table><thead><tr><th>Check</th><th>Status</th><th>Message</th></tr></thead><tbody>
                    @foreach($checks as $check)
                        <tr><td>{{ $check['label'] }}</td><td><span class="badge {{ $check['status'] === 'pass' ? 'pass' : ($check['status'] === 'fail' ? 'fail' : 'warn') }}">{{ $check['status'] }}</span></td><td>{{ $check['message'] }}</td></tr>
                    @endforeach
                </tbody></table>
                <form method="post">@csrf<div class="actions"><a class="btn secondary" href="{{ route('install.welcome') }}">Previous</a><button class="btn">Continue</button></div></form>
            @elseif($step === 'environment')
                <h2 style="color: #008751;">Environment Setup</h2>
                <p class="lead">These values are written to .env. First-run-safe session, cache, and queue drivers are also applied so setup does not depend on database-backed infrastructure before migrations.</p>
                <form method="post">@csrf
                    <div class="grid">
                        @foreach(['APP_NAME'=>'Campaign Manager','APP_URL'=>url('/'),'APP_ENV'=>'production','APP_TIMEZONE'=>'Africa/Lagos'] as $name => $default)
                            <div class="field"><label>{{ $name }}</label><input name="{{ $name }}" value="{{ old($name, data_get($state, 'environment.'.$name, $default)) }}" required></div>
                        @endforeach
                        <div class="field"><label>APP_DEBUG</label><select name="APP_DEBUG"><option value="0" @selected(!old('APP_DEBUG', data_get($state, 'environment.APP_DEBUG', false)))>false</option><option value="1" @selected(old('APP_DEBUG', data_get($state, 'environment.APP_DEBUG', false)))>true</option></select></div>
                        <div class="field"><label>DEPLOYMENT_MODE</label><input name="DEPLOYMENT_MODE" value="self_hosted" readonly></div>
                    </div>
                    <div class="summary"><div><strong>Installer-safe defaults:</strong> SESSION_DRIVER=file, CACHE_STORE=file, QUEUE_CONNECTION=sync</div></div>
                    <div class="actions"><a class="btn secondary" href="{{ route('install.requirements') }}">Previous</a><button class="btn">Save and continue</button></div>
                </form>
            @elseif($step === 'database')
                <h2 style="color: #008751;">Database Setup</h2>
                <p class="lead">The connection is tested before it is written to .env. If your browser session changes later, final installation can reuse the saved database settings.</p>
                <form method="post">@csrf
                    <div class="grid">
                        <div class="field"><label>DB_CONNECTION</label><input name="DB_CONNECTION" value="mysql" readonly></div>
                        @foreach(['DB_HOST'=>'127.0.0.1','DB_PORT'=>'3306','DB_DATABASE'=>'','DB_USERNAME'=>''] as $name => $default)
                            <div class="field"><label>{{ $name }}</label><input name="{{ $name }}" value="{{ old($name, data_get($state, 'database.'.$name, $default)) }}" required></div>
                        @endforeach
                        <div class="field"><label>DB_PASSWORD</label><input type="password" name="DB_PASSWORD" value=""></div>
                    </div>
                    <div class="actions"><a class="btn secondary" href="{{ route('install.environment') }}">Previous</a><button class="btn">Test and continue</button></div>
                </form>
            @elseif($step === 'mail')
                <h2 style="color: #008751;">Mail Setup</h2>
                <p class="lead">Mail is optional and can be adjusted after installation.</p>
                <form method="post">@csrf
                    <div class="grid">
                        @foreach(['MAIL_MAILER'=>'smtp','MAIL_HOST'=>'','MAIL_PORT'=>'587','MAIL_USERNAME'=>'','MAIL_ENCRYPTION'=>'tls','MAIL_FROM_ADDRESS'=>'','MAIL_FROM_NAME'=>'Campaign Manager'] as $name => $default)
                            <div class="field"><label>{{ $name }}</label><input name="{{ $name }}" value="{{ old($name, data_get($state, 'mail.'.$name, $default)) }}"></div>
                        @endforeach
                        <div class="field"><label>MAIL_PASSWORD</label><input type="password" name="MAIL_PASSWORD"></div>
                    </div>
                    <div class="actions"><a class="btn secondary" href="{{ route('install.database') }}">Previous</a><button class="btn">Continue</button></div>
                </form>
            @elseif($step === 'storage')
                <h2 style="color: #008751;">Storage Setup</h2>
                <p class="lead">The installer checks public storage availability and attempts a safe storage link where supported.</p>
                <div class="alert {{ $storageResult['status'] === 'pass' ? 'ok' : 'warning-note' }}">{{ $storageResult['message'] }}</div>
                <form method="post">@csrf<div class="actions"><a class="btn secondary" href="{{ route('install.mail') }}">Previous</a><button class="btn">Continue</button></div></form>
            @elseif($step === 'campaign_scope')
                <h2 style="color: #008751;">Campaign Scope</h2>
                <p class="lead">Choose the election scope this installation will manage. This is campaign configuration, not a software license.</p>
                <form method="post">@csrf
                    <div class="choice-grid">
                        @foreach($scopeOptions as $value => $option)
                            <label class="choice"><input type="radio" name="package_type" value="{{ $value }}" @checked(old('package_type', data_get($state, 'campaign_scope.package_type')) === $value) required><strong>{{ $option['label'] }}</strong><span>{{ ucfirst(str_replace('_', ' ', $option['scope_type'])) }} campaign boundary</span></label>
                        @endforeach
                    </div>
                    <div class="actions"><a class="btn secondary" href="{{ route('install.storage') }}">Previous</a><button class="btn">Save and continue</button></div>
                </form>
            @elseif($step === 'campaign_geography')
                <h2 style="color: #008751;">Relevant Geography</h2>
                <p class="lead">Select only the geography required for {{ $scopeOption['label'] }}. The selected base geography will be created locally during final installation.</p>
                @if(!empty($directoryError))
                    <div class="alert warning-note">{{ $directoryError }} @if($states->isNotEmpty())Showing available local fallback records. @else Retry when the directory is reachable, or add local geography records before continuing. @endif</div>
                @endif
                <form method="post">@csrf
                    @if($scopeOption['scope_type'] === 'national')
                        <div class="summary"><div><strong>Country:</strong> Nigeria</div></div>
                    @elseif($states->isEmpty())
                        <div class="alert warning-note">No geography directory records are available right now. Manual geography remains free, but a valid base campaign boundary is required before installation can continue.</div>
                        <div class="field"><label>Campaign geography name</label><input name="scope_name" value="{{ old('scope_name', data_get($state, 'campaign_geography.scope_name')) }}" required></div>
                    @else
                        <div class="grid">
                            <div class="field"><label>State</label><select name="state_id" id="campaign-state" required><option value="">Choose state</option>@foreach($states as $item)<option value="{{ $item->id }}" @selected((string) old('state_id', data_get($state, 'campaign_geography.state_id')) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select></div>
                            @if($scopeOption['scope_type'] === 'senatorial_district')
                                <div class="field"><label>Senatorial District</label><select name="senatorial_district_id" data-boundary="state" required><option value="">Choose senatorial district</option>@foreach($senatorialDistricts as $item)<option value="{{ $item->id }}" data-state-id="{{ $item->state_id }}" @selected((string) old('senatorial_district_id', data_get($state, 'campaign_geography.senatorial_district_id')) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select></div>
                            @elseif($scopeOption['scope_type'] === 'federal_constituency')
                                <div class="field"><label>Federal Constituency</label><select name="federal_constituency_id" data-boundary="state" required><option value="">Choose federal constituency</option>@foreach($federalConstituencies as $item)<option value="{{ $item->id }}" data-state-id="{{ $item->state_id }}" @selected((string) old('federal_constituency_id', data_get($state, 'campaign_geography.federal_constituency_id')) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select></div>
                            @elseif($scopeOption['scope_type'] === 'lga')
                                <div class="field"><label>LGA</label><select name="lga_id" data-boundary="state" required><option value="">Choose LGA</option>@foreach($lgas as $item)<option value="{{ $item->id }}" data-state-id="{{ $item->state_id }}" @selected((string) old('lga_id', data_get($state, 'campaign_geography.lga_id')) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select></div>
                            @endif
                        </div>
                        <script>(function(){const state=document.getElementById('campaign-state');const selects=document.querySelectorAll('select[data-boundary="state"]');function filter(){selects.forEach(function(select){Array.from(select.options).forEach(function(option){if(!option.value)return;option.hidden=state.value&&option.dataset.stateId!==state.value;});if(select.selectedOptions[0]&&select.selectedOptions[0].hidden)select.value='';});}state&&state.addEventListener('change',filter);filter();})();</script>
                    @endif
                    <div class="actions"><a class="btn secondary" href="{{ route('install.campaign_scope') }}">Previous</a><button class="btn">Save and continue</button></div>
                </form>
            @elseif($step === 'campaign_identity')
                <h2 style="color: #008751;">Campaign Identity</h2>
                <p class="lead">Set the public campaign name and optional slogan used by the application.</p>
                <form method="post">@csrf
                    <div class="field"><label>Campaign Name</label><input name="system_name" value="{{ old('system_name', data_get($state, 'campaign_identity.system_name', data_get($state, 'environment.APP_NAME', 'Campaign Manager'))) }}" required></div>
                    <div class="field"><label>Campaign Slogan</label><input name="campaign_slogan" value="{{ old('campaign_slogan', data_get($state, 'campaign_identity.campaign_slogan')) }}"></div>
                    <div class="actions"><a class="btn secondary" href="{{ route('install.campaign_geography') }}">Previous</a><button class="btn">Save and continue</button></div>
                </form>
            @elseif($step === 'geography_setup' || $step === 'license')
                <h2 style="color: #008751;">Geography Setup</h2>
                <p class="lead">Review the geography footprint for your selected campaign scope, then choose how to configure it.</p>
                @if(!empty($geographySummary))
                    @if($geographySummary['ok'] ?? false)
                        <div class="summary">
                            <div><strong>Selected campaign geography:</strong> {{ $geographySummary['scope_name'] ?? 'Selected campaign geography' }}</div>
                            @foreach(($geographySummary['counts'] ?? []) as $level => $count)
                                <div><strong>{{ $provisioningLevels[$level] ?? \Illuminate\Support\Str::headline($level) }}:</strong> {{ number_format((int) $count) }}</div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert warning-note">
                            {{ $geographySummary['message'] }}
                            @if(!empty($geographySummary['detail']))
                                <div><small>{{ $geographySummary['detail'] }}</small></div>
                            @endif
                        </div>
                    @endif
                @endif
                @if($display)<div class="summary">@foreach($display as $label => $value)<div><strong>{{ str_replace('_', ' ', ucfirst($label)) }}:</strong> {{ is_array($value) ? implode(', ', $value) : ($value ?: 'Not provided') }}</div>@endforeach</div>@endif
                <form method="post">@csrf
                    <div class="choice-grid">
                        <label class="choice"><input type="radio" name="method" value="manual" @checked(old('method', data_get($state, 'geography_setup.method', 'manual')) === 'manual')><strong>Configure Geography Manually</strong><span>Free. No key, portal connection, activation, or entitlement check required. The base campaign geography is created locally and you configure the remaining footprint yourself.</span></label>
                        <label class="choice"><input type="radio" name="method" value="quickstart" @checked(old('method', data_get($state, 'geography_setup.method')) === 'quickstart')><strong>QuickStart Geography Provisioning</strong><span>Optional paid convenience service for automatic supported geography import. Portal connectivity and a QuickStart Provisioning Key are required.</span></label>
                    </div>
                    <div class="field conditional" id="quickstart-key">
                        <label>QuickStart Provisioning Key</label>
                        <input type="password" name="license_key" value="{{ old('license_key') }}">
                        <p class="lead" style="margin-top:10px">Don't have a provisioning key? Get one from Campaign Manager Portal, then return here and paste the key to continue.</p>
                        <a class="btn secondary" href="{{ $quickstartOrderUrl }}" target="_blank" rel="noopener">Get QuickStart Provisioning Key</a>
                    </div>
                    <div class="actions"><a class="btn secondary" href="{{ route('install.campaign_identity') }}">Previous</a><button class="btn">Continue</button></div>
                </form>
                <script>(function(){const radios=document.querySelectorAll('input[name="method"]');const field=document.getElementById('quickstart-key');function toggle(){field.classList.toggle('active',document.querySelector('input[name="method"]:checked')?.value==='quickstart');}radios.forEach(function(radio){radio.addEventListener('change',toggle);});toggle();})();</script>
            @elseif($step === 'admin')
                <h2 style="color: #008751;">First Super Admin</h2>
                <p class="lead">The admin password is kept in the browser session only and is never written to installer state.</p>
                <form method="post">@csrf
                    <div class="grid">
                        <div class="field"><label>Name</label><input name="name" value="{{ old('name', data_get($state, 'admin.name')) }}" required></div>
                        <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email', data_get($state, 'admin.email')) }}" required></div>
                        <div class="field"><label>Phone</label><input name="phone" value="{{ old('phone', data_get($state, 'admin.phone')) }}"></div>
                        <div class="field"><label>Password</label><input type="password" name="password" required></div>
                        <div class="field"><label>Confirm password</label><input type="password" name="password_confirmation" required></div>
                    </div>
                    <div class="actions"><a class="btn secondary" href="{{ route('install.geography_setup') }}">Previous</a><button class="btn">Continue</button></div>
                </form>
            @elseif($step === 'run')
                <h2 style="color: #008751;">Run Installation</h2>
                <p class="lead">This step prepares your application, runs migrations, writes the campaign scope and identity, and creates the first super admin. Manual Community installs skip remote activation and portal calls.</p>
                @if($display)<div class="summary"><div><strong>Package:</strong> {{ $display['package_type'] ?? 'Not provided' }}</div><div><strong>Scope:</strong> {{ $display['scope_type'] ?? '' }} {{ $display['scope_name'] ?? '' }}</div></div>@endif
                @if(!empty($display))<div class="alert info">QuickStart will provision supported location data from the Campaign Manager portal. Full CSV data is not bundled in this release.</div>@else<div class="alert info">Community setup does not contact the Campaign Manager portal. You can configure geography manually after installation.</div>@endif
                <form method="post">@csrf<div class="actions"><a class="btn secondary" href="{{ route('install.admin') }}">Previous</a><button class="btn">Run Installation</button></div></form>
            @elseif($step === 'provision')
                <h2 style="color: #008751;">Provision Location Data</h2>
                <p class="lead">Optional location data is imported server-side from the Campaign Manager portal in resumable JSON batches when a legacy provisioning entitlement is used.</p>
                @if($display)<div class="summary"><div><strong>Package:</strong> {{ $display['package_type'] ?? 'Not provided' }}</div><div><strong>Scope:</strong> {{ $display['scope_type'] ?? '' }} {{ $display['scope_name'] ?? '' }}</div></div>@endif
                <div class="alert info">{{ $provisioningCopy ?? 'The installer will now prepare your campaign geography.' }}</div>
                @if(!empty($provisioningDeferred))
                    <div class="alert warning-note">The Campaign Manager portal could not be reached during installation. Minimal bootstrap setup can continue now. Full location provisioning can be completed later with <strong>php artisan campaign:locations:provision</strong>.</div>
                    <form method="post">@csrf<div class="actions"><button class="btn">Continue with minimal setup</button></div></form>
                @else
                @if(!empty($provisioningStarted))
                    <div class="progress-card" id="provisioning-progress" data-step-url="{{ route('install.provision.step') }}" data-finish-url="{{ route('install.provision.finish') }}">
                        <div class="progress-copy" id="progress-message">Preparing your campaign structure...</div>
                        <div class="lead" id="progress-stage">This may take a few minutes for national installations. Please keep this page open.</div>
                        <div class="progress-track"><div class="progress-fill" id="progress-fill"></div></div>
                        <div class="lead" id="progress-detail">Progress saved. If interrupted, you can continue from this page.</div>
                    </div>
                    <script>
                        (function () {
                            const card = document.getElementById('provisioning-progress');
                            const fill = document.getElementById('progress-fill');
                            const message = document.getElementById('progress-message');
                            const stage = document.getElementById('progress-stage');
                            const detail = document.getElementById('progress-detail');
                            const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

                            async function post(url) {
                                const response = await fetch(url, {method: 'POST', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}});
                                const data = await response.json();
                                if (!response.ok || data.ok === false) {
                                    throw new Error(data.message || 'Setup could not continue.');
                                }
                                return data;
                            }

                            async function step() {
                                try {
                                    const data = await post(card.dataset.stepUrl);
                                    message.textContent = data.message || 'Preparing your campaign structure...';
                                    stage.textContent = data.stage_label || 'Setting up campaign locations...';
                                    fill.style.width = (data.percent || 0) + '%';
                                    detail.textContent = data.percent ? data.percent + '% complete. Progress saved automatically.' : 'Progress saved automatically.';

                                    if (data.status === 'completed') {
                                        message.textContent = 'Finalizing installation...';
                                        const finished = await post(card.dataset.finishUrl);
                                        window.location.href = finished.redirect;
                                        return;
                                    }

                                    window.setTimeout(step, 700);
                                } catch (error) {
                                    message.textContent = error.message;
                                    detail.textContent = 'You can reload this page to continue, or contact support if the issue repeats.';
                                }
                            }

                            step();
                        })();
                    </script>
                @else
                    <div class="alert warning-note">Location provisioning has not started. Return to Run Installation and try again.</div>
                    <div class="actions"><a class="btn secondary" href="{{ route('install.run') }}">Back to Run Installation</a></div>
                @endif
                @endif
            @elseif($step === 'complete')
                <h2 style="color: #008751;">Installation Complete</h2>
                <p class="lead">The installation lock has been written. Use the super admin account to sign in.</p>
                @if(!empty($provisioningSummary))
                    <div class="alert ok">Location data provisioned successfully for {{ ucfirst(str_replace('_', ' ', $provisioningSummary['package_type'] ?? 'campaign package')) }} / {{ $provisioningSummary['scope_name'] ?? 'campaign scope' }}.</div>
                    <div class="summary">
                        <div><strong>Package type:</strong> {{ $provisioningSummary['package_type'] ?? 'Not recorded' }}</div>
                        <div><strong>Deployment mode:</strong> {{ $provisioningSummary['deployment_mode'] ?? 'Not recorded' }}</div>
                        <div><strong>Campaign scope:</strong> {{ $provisioningSummary['scope_type'] ?? '' }} {{ $provisioningSummary['scope_name'] ?? '' }}</div>
                    </div>
                    <table><thead><tr><th>Location level</th><th>Imported</th><th>Batches</th><th>Total provisioned</th></tr></thead><tbody>
                        @foreach($provisioningLevels as $key => $label)
                            @php
                                $counts = data_get($provisioningSummary, 'summary.'.$key, []);
                                $imported = (int) ($counts['imported'] ?? $counts['created'] ?? 0);
                                $batches = (int) ($counts['batches'] ?? 0);
                            @endphp
                            <tr><td>{{ $label }}</td><td>{{ $imported }}</td><td>{{ $batches }}</td><td>{{ $imported }}</td></tr>
                        @endforeach
                    </tbody></table>
                    @if(!empty($provisioningSummary['warnings']))
                        <div class="alert warning-note">
                            <strong>Provisioning warnings:</strong>
                            @foreach($provisioningSummary['warnings'] as $warning)
                                <div>{{ $warning }}</div>
                            @endforeach
                        </div>
                    @endif
                @endif
                <div class="actions"><a class="btn" href="{{ route('login') }}">Finish and log in</a></div>
            @endif
        </main>
    </div>
</div>
</body>
</html>

