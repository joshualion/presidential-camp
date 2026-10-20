<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Campaign Manager · Official Updates</title><style>
*{box-sizing:border-box}body{margin:0;background:#eff5f2;color:#203d32;font:16px/1.6 system-ui,sans-serif}main{max-width:760px;margin:6vh auto;padding:32px;background:white;border:1px solid #d8e6de;border-radius:16px;box-shadow:0 16px 48px #173b2d12}h1{line-height:1.2;color:#08613e}h2{font-size:20px}a{color:#08613e}button,.button{display:inline-block;background:#08613e;color:white;border:0;border-radius:8px;padding:12px 20px;font:inherit;cursor:pointer;text-decoration:none}label{display:block;margin:16px 0}input{margin-right:8px}.notice{padding:16px;border-radius:8px;background:#edf6f1;margin:16px 0}.error{background:#fff2de;color:#6c4100}.muted{color:#53675e;font-size:14px}form{margin:20px 0}summary{cursor:pointer}button:focus-visible,a:focus-visible,input:focus-visible{outline:3px solid #a3c33e;outline-offset:3px}@media(max-width:760px){main{margin:16px;padding:24px}}</style></head><body><main><a href="{{ url('/superadmin/dashboard') }}">← Back to Campaign Manager</a><h1>Official Updates</h1><p>Installed version <strong>{{ $installedVersion }}</strong></p>
@if(session('update_notice'))<div class="notice" role="status">{{ session('update_notice') }}</div>@endif
@if(session('update_error'))<div class="notice error" role="alert">{{ session('update_error') }}</div>@endif
@if($job)<div class="notice" id="job-message" role="status" aria-live="polite">{{ $job['message'] }}</div>@endif
@if(empty($registration['registered']))
<h2>Connect this installation once</h2><p>Use your Super Admin email to confirm ownership with the official Portal. This does not create a subscription.</p><form method="post">@csrf<input type="hidden" name="action" value="register"><label><input type="checkbox" name="registration_consent" value="1" required>I agree to register this installation and its contact email under the <a href="https://campaignmanager.ng/community/privacy" target="_blank" rel="noopener">privacy notice</a>.</label><button>Connect installation</button></form>
@elseif(!empty($status['available']) && !empty($status['verified']))
<h2>Automatic update to {{ $status['latest_version'] }}</h2>
@if($issue)<div class="notice error">{{ $issue['message'] }}</div>
@if($issue['reason'] === 'registration')<p><a href="{{ url('/superadmin/settings/installation-registration') }}">Confirm this installation contact</a></p>@endif
@elseif(!empty($entitlement['entitled']))
<div class="notice">Your automatic updates are covered. No additional payment is needed.</div>
<form method="post">@csrf<input type="hidden" name="action" value="prepare">
<input type="hidden" name="version" value="{{ $status['latest_version'] }}">
<input type="hidden" name="sha256" value="{{ $status['manifest']['checksum_sha256'] }}">
<label><input type="checkbox" name="update_consent" value="1" required>I am ready for the backup and a short maintenance period.</label>
<button>Back up and update to {{ $status['latest_version'] }}</button></form>
@elseif($entitlement)
<p>Automatic updating costs <strong>{{ $entitlement['currency'] }} {{ number_format($entitlement['price_minor']/100,2) }}</strong> for {{ $entitlement['scope_label'] }}, {{ $entitlement['coverage_months'] ?? 12 }} months of updates for this installation. No automatic renewal.</p>
<a class="button" href="{{ $entitlement['checkout_url'] }}" target="_blank" rel="noopener">Continue to secure payment</a>
<p class="muted">Return here after payment and check coverage. The package is downloaded only after coverage is confirmed and you start updating.</p>
@endif
<p><a href="{{ url('/campaign-update-bootstrap.php') }}">Check readiness and coverage again</a></p>
@if($stage)<details><summary>Previously downloaded package</summary><p>The prepared version {{ $stage['version'] }} is not installed.</p><form method="post">@csrf<input type="hidden" name="action" value="discard"><input type="hidden" name="stage_id" value="{{ $stage['id'] }}"><button>Remove prepared package</button></form></details>@endif
@elseif($issue)<div class="notice error">{{ $issue['message'] }}</div>
@elseif(!empty($status['checked_at']))<p>No newer verified automatic update is ready. Manual releases remain available on GitHub.</p>@endif
<form method="post" id="check-form">@csrf<input type="hidden" name="action" value="check"><button>Check for updates</button></form><p class="muted"><a href="https://github.com/joshualion/Campaign-Manager/releases" target="_blank" rel="noopener">Free manual downloads and release notes</a></p><p class="muted">The one-time bootstrap can be removed after a successful update.</p>
@if($job && !in_array($job['state'],['completed','failed','recovered','recovery_required']))<script>(function poll(){fetch(@json(url('/campaign-update-status.php').'?job='.$job['id']),{credentials:'same-origin',cache:'no-store'}).then(r=>{if(!r.ok)throw new Error();return r.json()}).then(s=>{document.getElementById('job-message').textContent=s.message;if(['completed','recovered','failed'].includes(s.state)){location.href=@json(url('/superadmin/updates'));return;}if(s.state!=='recovery_required')setTimeout(poll,3000)}).catch(()=>setTimeout(poll,5000))})()</script>
@elseif(empty($status['checked_at']) && !empty($registration['registered']))<script>document.getElementById('check-form').submit()</script>@endif
</main></body></html>
