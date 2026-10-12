<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Confirm Server Setup Access</title></head><body>
<main><h1>Confirm server setup access</h1>
<p>Before configuring the server, confirm that you own or administer this hosting account.</p>
<p>Open your hosting File Manager or SSH session and read <code>storage/app/installer/access-token</code> inside this application's folder. Paste the server setup code below. Keep the code private.</p>
<p>This local security check is free. It is not software activation, a licence key or a geography purchase. No remote service is contacted.</p>
@error('setup_code')<p>{{ $message }}</p>@enderror
<form method="post" action="{{ route('install.access.verify') }}">@csrf<label>Server setup code<input type="password" name="setup_code" autocomplete="off" required maxlength="256"></label><button>Continue to installation</button></form>
</main></body></html>
