<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DeploymentRegistrationService
{
    private function path(): string
    {
        return storage_path('app/private/deployment-registration.json');
    }

    private function state(): array
    {
        if (!File::isFile($this->path())) {
            return ['id' => (string) Str::uuid(), 'token' => bin2hex(random_bytes(32)), 'registered' => false, 'messages' => []];
        }
        return json_decode(Crypt::decryptString(File::get($this->path())), true, 64, JSON_THROW_ON_ERROR);
    }

    private function save(array $state): void
    {
        File::ensureDirectoryExists(dirname($this->path()), 0700);
        File::replace($this->path(), Crypt::encryptString(json_encode($state, JSON_THROW_ON_ERROR)), 0600);
    }

    public function summary(): array
    {
        try {
            $state = $this->state();
        } catch (\Throwable $exception) {
            return ['registered' => false, 'messages' => [], 'error' => 'Registration credentials could not be read. Restore the original application key or contact support.'];
        }
        unset($state['token']);
        return $state;
    }

    public function domain(): string
    {
        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if (function_exists('idn_to_ascii')) {
            $host = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
        }
        if (!$host || !preg_match('/^(?=.{1,253}$)[a-z0-9]+(?:[.-][a-z0-9]+)*$/D', $host)) {
            throw new RuntimeException('Configure a valid application domain before registering.');
        }
        return $host;
    }

    private function base(): string
    {
        $base = rtrim((string) config('campaign.portal_api_base'), '/');
        if (parse_url($base, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('Deployment registration requires an HTTPS Portal connection.');
        }
        return $base.'/campaign-manager/deployments';
    }

    public function register(User $admin, bool $marketingOptIn): void
    {
        if ($admin->access_level !== 'superadmin') {
            throw new RuntimeException('Only the Super Admin can register this deployment.');
        }
        $state = $this->state();
        if (!empty($state['pending_revoke'])) {
            throw new RuntimeException('Finish deleting the previous Portal registration before registering again.');
        }
        $domain = $this->domain();
        $this->save($state);
        $response = Http::acceptJson()->connectTimeout(5)->timeout(20)->post($this->base().'/register', [
            'deployment_id' => $state['id'], 'token' => $state['token'], 'domain' => $domain,
            'contact_email' => $admin->email, 'installed_version' => app(OfficialUpdateService::class)->installedVersion(),
            'installation_status' => 'completed', 'registration_consent' => true, 'marketing_opt_in' => $marketingOptIn,
        ])->throw()->json();
        if (($response['registered'] ?? false) !== true) {
            throw new RuntimeException('The Portal did not confirm registration.');
        }
        $state = array_merge($state, [
            'registered' => true, 'domain' => $domain, 'contact_email' => $admin->email,
            'contact_verified' => (bool) ($response['contact_verified'] ?? false),
            'marketing_opt_in' => $marketingOptIn, 'consented_at' => now()->toIso8601String(),
        ]);
        $this->save($state);
        $this->preferences($marketingOptIn);
    }

    public function preferences(bool $marketingOptIn): void
    {
        $state = $this->state();
        if (empty($state['registered'])) {
            throw new RuntimeException('This deployment is not registered.');
        }
        if (!$marketingOptIn) {
            $state['marketing_opt_in'] = false;
            $state['pending_marketing_opt_in'] = false;
            $state['messages'] = array_values(array_filter($state['messages'] ?? [], fn ($message) => ($message['type'] ?? '') !== 'promotion'));
            $this->save($state);
        }
        Http::withToken($state['token'])->acceptJson()->connectTimeout(5)->timeout(20)
            ->put($this->base().'/'.$state['id'].'/preferences', ['marketing_opt_in' => $marketingOptIn])->throw();
        $state['marketing_opt_in'] = $marketingOptIn;
        unset($state['pending_marketing_opt_in']);
        $this->save($state);
    }

    public function sync(): void
    {
        $state = $this->state();
        if (!empty($state['pending_revoke'])) {
            $this->revoke();
            return;
        }
        if (empty($state['registered'])) {
            return;
        }
        if (array_key_exists('pending_marketing_opt_in', $state)) {
            $this->preferences((bool) $state['pending_marketing_opt_in']);
            $state = $this->state();
        }
        $version = app(OfficialUpdateService::class)->installedVersion();
        $checked = data_get(app(OfficialUpdateService::class)->status(), 'checked_at');
        if ($checked && ($state['reported_update_check'] ?? null) !== $checked) {
            Http::withToken($state['token'])->acceptJson()->connectTimeout(5)->timeout(20)
                ->post($this->base().'/'.$state['id'].'/heartbeat', ['event' => 'update_checked', 'installed_version' => $version])->throw();
            $state['reported_update_check'] = $checked;
            $this->save($state);
        }
        $response = Http::withToken($state['token'])->acceptJson()->connectTimeout(5)->timeout(20)
            ->get($this->base().'/'.$state['id'].'/messages')->throw();
        if (strlen($response->body()) > 131072) {
            throw new RuntimeException('Portal messages exceed the allowed size.');
        }
        $messages = $response->json('messages', []);
        $state['contact_verified'] = (bool) $response->json('contact_verified', false);
        $state['messages'] = collect(is_array($messages) ? $messages : [])->take(20)->map(fn ($message) => [
            'title' => mb_substr((string) ($message['title'] ?? ''), 0, 120),
            'body' => mb_substr((string) ($message['body'] ?? ''), 0, 2000),
            'type' => in_array($message['type'] ?? '', ['operational', 'support', 'update', 'promotion'], true) ? $message['type'] : 'operational',
        ])->filter(fn ($message) => $message['type'] !== 'promotion' || !empty($state['marketing_opt_in']))->values()->all();
        $state['messages_checked_at'] = now()->toIso8601String();
        $this->save($state);
    }

    public function revoke(): void
    {
        $state = $this->state();
        if (empty($state['registered']) && empty($state['pending_revoke'])) {
            return;
        }
        // Stop reporting immediately, including when the Portal is offline.
        $state['registered'] = false;
        $state['pending_revoke'] = true;
        $state['marketing_opt_in'] = false;
        $state['messages'] = [];
        $this->save($state);
        Http::withToken($state['token'])->acceptJson()->connectTimeout(5)->timeout(20)
            ->delete($this->base().'/'.$state['id'])->throw();
        $this->save(['id' => $state['id'], 'token' => bin2hex(random_bytes(32)), 'registered' => false, 'pending_revoke' => false, 'messages' => []]);
    }
}
