<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Campaign Manager Self-Hosted Installer</title>
    @include('installer.styles')
@include('shared.password-visibility')
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
                <div class="alert info">Community setup is free and self-hosted. Populate Campaign Locations Automatically remains optional for teams that want Govware to populate detailed electoral geography automatically.</div>
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
                        <label class="choice"><input type="radio" name="method" value="manual" @checked(old('method', data_get($state, 'geography_setup.method', 'manual')) === 'manual')><strong>Configure Geography Manually</strong><span>Free. No key, portal connection, activation, or entitlement check required. Choose this if you have the campaign locations and want to enter or import them yourself. The base campaign geography is created locally.</span></label>
                        <label class="choice"><input type="radio" name="method" value="quickstart" @checked(old('method', data_get($state, 'geography_setup.method')) === 'quickstart')><strong>Populate Campaign Locations Automatically</strong><span>Ask the system to populate supported states, constituencies, local governments, wards and polling units for your selected campaign scope. This optional paid service requires a Portal purchase and a provisioning code. Review coverage before paying.</span></label>
                    </div>
                    <div class="field conditional" id="quickstart-key">
                        <label>Geography Provisioning Code</label>
                        <input type="password" name="license_key" value="{{ old('license_key') }}">
                        <p class="lead" style="margin-top:10px">Don't have a provisioning key? Get one from Campaign Manager Portal, then return here and paste the key to continue.</p>
                        <a class="btn secondary" href="{{ $quickstartOrderUrl }}" target="_blank" rel="noopener">Buy a Geography Provisioning Code</a>
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
                    @include('installer.registration-terms')
                    <div class="actions"><a class="btn secondary" href="{{ route('install.geography_setup') }}">Previous</a><button class="btn">Continue</button></div>
                </form>
            @elseif($step === 'run')
                <h2 style="color: #008751;">Run Installation</h2>
                <p class="lead">This step prepares your application, runs migrations, writes the campaign scope and identity, and creates the first super admin. Manual Community installs require a free installation registration receipt; geography provisioning is optional.</p>
                @if($display)<div class="summary"><div><strong>Package:</strong> {{ $display['package_type'] ?? 'Not provided' }}</div><div><strong>Scope:</strong> {{ $display['scope_type'] ?? '' }} {{ $display['scope_name'] ?? '' }}</div></div>@endif
                @if(!empty($display))<div class="alert info">Automatic setup will provision supported location data from the Campaign Manager portal. Full CSV data is not bundled in this release.</div>@else<div class="alert info">The official portal must acknowledge installation registration before setup completes. Temporary failure preserves your configuration and database for retry.</div>@endif
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
                @php
                    $installationRegistration = app(\App\Services\DeploymentRegistrationService::class)->summary();
                @endphp
                @if(!empty($installationRegistration['registered']) && empty($installationRegistration['contact_verified']))
                    <div class="alert info">Your installation is registered. Open the verification email sent to {{ $installationRegistration['contact_email'] }} to confirm ownership for future purchases and account changes. This is a one-time step; you can sign in and use Campaign Manager now.</div>
                @endif
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



