<?php

namespace App\Services\Updates;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;

class UpdateProgress
{
    public static function latest(): ?array
    {
        $latest = null;
        foreach (glob(storage_path('app/updates/jobs/*/journal.json')) as $path) {
            $job = json_decode(file_get_contents($path), true);
            if (is_array($job) && (! $latest || ($job['created_at'] ?? '') > ($latest['created_at'] ?? ''))) {
                $latest = $job;
            }
        }

        return $latest;
    }

    /** Return progress in the initiating response, before maintenance can block a redirect. */
    public static function response(array $job, Request $request)
    {
        $name = 'cm_update_'.$job['id'];
        EncryptCookies::except($name);
        $status = json_encode(url('/campaign-update-status.php').'?job='.$job['id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $return = json_encode(url('/superadmin/updates'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $html = <<<HTML
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Campaign Manager · Updating</title><style>body{margin:0;background:#eff5f2;color:#203d32;font:16px/1.7 system-ui,sans-serif}main{max-width:640px;margin:10vh auto;padding:32px;background:#fff;border:1px solid #d8e6de;border-radius:16px}h1{color:#08613e;line-height:1.2}a{color:#08613e}#message{padding:18px;background:#edf6f1;border-radius:8px}small{color:#53675e}@media(max-width:700px){main{margin:20px;padding:24px}}</style></head><body><main><h1>Updating Campaign Manager</h1><p id="message" role="status" aria-live="polite">Starting the update and checking your installation…</p><p id="help">Keep this page open to follow progress. The updater continues if your connection drops.</p><a id="return" hidden>Return to Official Updates</a><p><small>Database backups and configuration stay in private server storage.</small></p><noscript>JavaScript is required for live progress. Ask your hosting operator to check the private update journal before retrying.</noscript></main><script>
const endpoint={$status}, destination={$return};let failures=0;
async function poll(){try{const r=await fetch(endpoint,{credentials:'same-origin',cache:'no-store'});if(!r.ok)throw new Error();const s=await r.json();failures=0;document.getElementById('message').textContent=s.message;if(['completed','failed','recovered','recovery_required'].includes(s.state)){const link=document.getElementById('return');link.href=destination;link.hidden=s.state==='recovery_required';document.getElementById('help').textContent=s.state==='recovery_required'?'Ask your hosting operator to use the private recovery kit. Keep maintenance enabled until recovery is complete.':'You can return to Official Updates.';return;}}catch(e){failures++;if(failures>10)document.getElementById('help').textContent='Progress is temporarily unavailable. The update may still be running. Ask your hosting operator to inspect the private update journal before retrying.';}setTimeout(poll,3000);}poll();
</script></body></html>
HTML;

        return response($html)->header('Cache-Control', 'no-store')->header('X-Content-Type-Options', 'nosniff')->withCookie(cookie($name, $job['status_token'], 600, parse_url(url('/campaign-update-status.php'), PHP_URL_PATH), null, $request->isSecure(), true, false, 'strict'));
    }
}
