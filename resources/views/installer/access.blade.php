<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Confirm server access · Campaign Manager</title>
@include('installer.styles')
<style>.access-wrap{max-width:760px}.access-panel{min-height:0}.access-steps{padding-left:1.4rem;line-height:1.7}.access-steps li{margin-bottom:.75rem}code{overflow-wrap:anywhere;white-space:normal;background:#edf3ef;padding:.15rem .3rem;border-radius:3px}.access-note{font-size:.875rem}.access-panel input{margin-top:.5rem}@media(max-width:600px){.access-wrap{padding:24px 14px}.access-panel{padding:22px}.brand h1{font-size:23px}}</style>
</head>
<body><div class="wrap access-wrap">
<header class="top"><div class="brand"><div class="mark">@if(is_file(public_path('logo.png')))<img src="{{ asset('logo.png') }}" alt="Campaign Manager">@else<span>CM</span>@endif</div><div><h1>Campaign Manager</h1><p>Secure installation</p></div></div></header>
<main class="panel access-panel">
<span class="badge pass">Before you begin</span><h2 style="margin-top:16px">Confirm server access</h2>
<p class="lead">Confirm that you own or administer this hosting account before setting up Campaign Manager.</p>
<ol class="access-steps"><li>Open your hosting <strong>File Manager</strong> or SSH session.</li><li>Inside this application's folder, open <code>storage/app/installer/access-token</code>.</li><li>Copy the server setup code and paste it below. Keep this code private.</li></ol>
@error('setup_code')<div class="alert error" role="alert" id="setup-code-error">{{ $message }}</div>@enderror
<form method="post" action="{{ route('install.access.verify') }}">@csrf
<div class="field"><label for="setup-code">Server setup code</label><input id="setup-code" type="password" name="setup_code" autocomplete="off" required maxlength="256" aria-describedby="setup-code-help{{ $errors->has('setup_code') ? ' setup-code-error' : '' }}" aria-invalid="{{ $errors->has('setup_code') ? 'true' : 'false' }}"><p class="access-note" id="setup-code-help">Use the code from the file on this server.</p></div>
<div class="actions"><button class="btn" type="submit">Continue to installation</button></div>
</form>
<div class="alert info access-note" style="margin-top:24px;margin-bottom:0">This local security check is free. It is not software activation, a licence key or a geography purchase. No remote service is contacted.</div>
</main></div></body></html>
