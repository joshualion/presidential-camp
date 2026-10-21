<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Campaign Manager · Official Updates</title><style>
*{box-sizing:border-box}body{margin:0;background:#eff5f2;color:#203d32;font:16px/1.6 system-ui,sans-serif}main{max-width:760px;margin:6vh auto;padding:32px;background:white;border:1px solid #d8e6de;border-radius:16px}h1{line-height:1.2;color:#08613e}a{color:#08613e}button,.btn{display:inline-block;border:1px solid #08613e;border-radius:8px;padding:12px 20px;font:inherit;cursor:pointer;text-decoration:none;background:white;color:#08613e}.btn-primary{background:#08613e;color:white}label{display:block;margin:16px 0}input{margin-right:8px}.alert{padding:16px;border-radius:8px;background:#edf6f1;margin:16px 0}.alert-warning{background:#fff2de;color:#6c4100}.small{font-size:14px}form{margin:20px 0}summary{cursor:pointer}button:focus-visible,a:focus-visible,input:focus-visible{outline:3px solid #a3c33e;outline-offset:3px}@media(max-width:760px){main{margin:16px;padding:24px}}</style></head><body><main>
<a href="{{ url('/superadmin/dashboard') }}">Back to Campaign Manager</a>
@if($showRegistration)
    @include('campaignBootstrap::registration')
@else
    @includeFirst(['campaignBootstrap::customer-flow', 'backend.shared.update-customer-card'])
@endif
</main></body></html>
