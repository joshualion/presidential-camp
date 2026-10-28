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
        if (! File::isFile($this->path())) {
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
        if (! $host || ! preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9](?:[a-z0-9-]{0,57}[a-z0-9])?)$/D', $host)) {
            throw new RuntimeException('Configure a valid application domain before registering.');
        }

        return $host;
    }

    private function base(): string
    {
        $base = rtrim((string) config('campaign.portal_api_base'), '/');
        if (parse_url($base, PHP_URL_SCHEME) !== 'https' || ! parse_url($base, PHP_URL_HOST) || parse_url($base, PHP_URL_USER) || parse_url($base, PHP_URL_QUERY) || parse_url($base, PHP_URL_FRAGMENT)) {
            throw new RuntimeException('Deployment registration requires an HTTPS Portal connection.');
        }

        return $base.'/campaign-manager/deployments';
    }

    public function register(User $admin, bool $marketingOptIn): void
    {
        if ($admin->access_level !== 'superadmin') {
            throw new RuntimeException('Only the Super Admin can register this deployment.');
        }
        $this->registerIdentity($admin->email, $marketingOptIn, 'completed');
    }

    public function registerIdentity(string $email, bool $marketingOptIn, string $status = 'started'): void
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! in_array($status, ['started', 'completed'], true)) {
            throw new RuntimeException('A valid installation contact and status are required.');
        }
        File::ensureDirectoryExists(dirname($this->path()), 0700);
        $lock = fopen($this->path().'.lock', 'c');
        if (! $lock) {
            throw new RuntimeException('Cannot open private registration lock.');
        }
        chmod($this->path().'.lock', 0600);
        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Cannot lock installation registration.');
            }
            $state = $this->state();
            if (! empty($state['pending_revoke'])) {
                throw new RuntimeException('Finish deleting the previous registration before registering again.');
            }
            $domain = $this->domain();
            $this->save($state);
            $response = Http::acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(20)->post($this->base().'/register', [
                'deployment_id' => $state['id'], 'token' => $state['token'], 'domain' => $domain,
                'contact_email' => $email, 'installed_version' => app(OfficialUpdateService::class)->installedVersion(),
                'installation_status' => $status, 'registration_consent' => true, 'marketing_opt_in' => $marketingOptIn,
                'terms_version' => '2026-10-12',
            ]);
            if (! in_array($response->status(), [200, 201], true) || strlen($response->body()) > 65536) {
                throw new RuntimeException('Installation registration is unavailable or was rejected. Retry; existing records are preserved.');
            }
            $data = $response->json();
            $receipt = $data['receipt'] ?? [];
            if (($data['registered'] ?? false) !== true || ($receipt['installation_id'] ?? null) !== $state['id']
                || ($receipt['domain'] ?? null) !== $domain || ($receipt['contact_email'] ?? null) !== $email
                || ($receipt['terms_version'] ?? null) !== '2026-10-12' || ! is_string($receipt['registered_at'] ?? null)
                || strtotime($receipt['registered_at']) === false) {
                throw new RuntimeException('The Portal returned an invalid installation receipt.');
            }
            $state = array_merge($state, ['registered' => true, 'domain' => $domain, 'contact_email' => $email, 'receipt' => $receipt,
                'contact_verified' => (bool) ($data['contact_verified'] ?? false), 'marketing_opt_in' => $marketingOptIn, 'consented_at' => now()->toIso8601String()]);
            $this->save($state);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function assertInstallationReceipt(string $email): void
    {
        $state = $this->state();
        if (empty($state['registered']) || ($state['domain'] ?? null) !== $this->domain()
            || ($state['contact_email'] ?? null) !== $email || ($state['receipt']['terms_version'] ?? null) !== '2026-10-12') {
            throw new RuntimeException('Complete installation registration before finishing setup.');
        }
    }

    public function reportInstallationCompleted(): void
    {
        $state = $this->state();
        if (empty($state['registered'])) {
            return;
        }
        $state['failure_pending'] = false;
        $state['completion_pending'] = true;
        $this->save($state);
        try {
            $response = Http::withToken($state['token'])->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(20)
                ->post($this->base().'/'.$state['id'].'/heartbeat', ['event' => 'installation_completed', 'installed_version' => app(OfficialUpdateService::class)->installedVersion()])->throw();
            if ($response->json('accepted') !== true) {
                throw new RuntimeException('Status was not acknowledged.');
            }
            $state['completion_pending'] = false;
            $this->save($state);
        } catch (\Throwable $exception) { /* Accepted receipts survive status-reporting outages. */
        }
    }

    public function reportInstallationFailed(): void
    {
        $state = $this->state();
        if (empty($state['registered'])) {
            return;
        }
        $state['completion_pending'] = false;
        $state['failure_pending'] = true;
        $this->save($state);
        try {
            $response = Http::withToken($state['token'])->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(10)
                ->post($this->base().'/'.$state['id'].'/heartbeat', ['event' => 'installation_failed', 'installed_version' => app(OfficialUpdateService::class)->installedVersion(), 'diagnostic_code' => 'INSTALLER_FAILED'])->throw();
            if ($response->json('accepted') !== true) {
                throw new RuntimeException('Status not acknowledged.');
            }
            $state['failure_pending'] = false;
            $this->save($state);
        } catch (\Throwable $exception) { /* Retry disclosed status only; never send failure details. */
        }
    }

    public function requestDomainChange(): void
    {
        $state = $this->state();
        if (empty($state['registered'])) {
            throw new RuntimeException('Register this installation first.');
        }
        Http::withToken($state['token'])->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(20)
            ->post($this->base().'/'.$state['id'].'/domain-change', ['domain' => $this->domain()])->throw();
    }

    public function resendVerification(): void
    {
        $state = $this->state();
        if (empty($state['registered'])) {
            throw new RuntimeException('Register this installation first.');
        }
        Http::withToken($state['token'])->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(20)
            ->post($this->base().'/'.$state['id'].'/resend-verification')->throw();
    }

    public function preferences(bool $marketingOptIn): void
    {
        $state = $this->state();
        if (empty($state['registered'])) {
            throw new RuntimeException('This deployment is not registered.');
        }
        if (! $marketingOptIn) {
            $state['marketing_opt_in'] = false;
            $state['pending_marketing_opt_in'] = false;
            $state['messages'] = array_values(array_filter($state['messages'] ?? [], fn ($message) => ($message['type'] ?? '') !== 'promotion'));
            $this->save($state);
        }
        Http::withToken($state['token'])->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(20)
            ->put($this->base().'/'.$state['id'].'/preferences', ['marketing_opt_in' => $marketingOptIn])->throw();
        $state['marketing_opt_in'] = $marketingOptIn;
        unset($state['pending_marketing_opt_in']);
        $this->save($state);
    }

    public function sync(): void
    {
        $state = $this->state();
        if (! empty($state['pending_revoke'])) {
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
        if (! empty($state['completion_pending'])) {
            $this->reportInstallationCompleted();
            $state = $this->state();
        }
        $version = app(OfficialUpdateService::class)->installedVersion();
        $checked = data_get(app(OfficialUpdateService::class)->status(), 'checked_at');
        if ($checked && ($state['reported_update_check'] ?? null) !== $checked) {
            Http::withToken($state['token'])->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(20)
                ->post($this->base().'/'.$state['id'].'/heartbeat', ['event' => 'update_checked', 'installed_version' => $version])->throw();
            $state['reported_update_check'] = $checked;
            $this->save($state);
        }
        $response = Http::withToken($state['token'])->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(20)
            ->get($this->base().'/'.$state['id'].'/messages')->throw();
        if (strlen($response->body()) > 131072) {
            throw new RuntimeException('Portal messages exceed the allowed size.');
        }
        $messages = $response->json('messages', []);
        $state['contact_verified'] = (bool) $response->json('contact_verified', false);
        $state['marketing_opt_in'] = ! empty($state['marketing_opt_in']) && $response->json('marketing_opt_in', false) === true;
        $state['messages'] = collect(is_array($messages) ? $messages : [])->take(20)->map(fn ($message) => [
            'title' => mb_substr((string) ($message['title'] ?? ''), 0, 120),
            'body' => mb_substr((string) ($message['body'] ?? ''), 0, 2000),
            'type' => in_array($message['type'] ?? '', ['operational', 'support', 'update', 'promotion'], true) ? $message['type'] : 'operational',
        ])->filter(fn ($message) => $message['type'] !== 'promotion' || ! empty($state['marketing_opt_in']))->values()->all();
        $state['messages_checked_at'] = now()->toIso8601String();
        $this->save($state);
    }

    /** Read-only server-to-server confirmation. Browser input is never evidence. */
    public function refreshVerification(): void
    {
        $state = $this->state();
        if (empty($state['registered']) || ! empty($state['pending_revoke'])
            || ($state['domain'] ?? null) !== $this->domain()
            || parse_url(config('app.url'), PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('Confirm this installation and its current HTTPS domain first.');
        }
        $state['contact_verified'] = false;
        $this->save($state);
        $response = Http::withToken($state['token'])->acceptJson()->withoutRedirecting()
            ->connectTimeout(5)->timeout(20)->get($this->base().'/'.$state['id'].'/registration-status');
        if ($response->status() !== 200 || strlen($response->body()) > 8192) {
            throw new RuntimeException('Installation confirmation is temporarily unavailable.');
        }
        $data = $response->json();
        if (! is_array($data) || ($data['installation_id'] ?? null) !== $state['id']
            || ($data['domain'] ?? null) !== $this->domain()
            || ($data['contact_email'] ?? null) !== ($state['contact_email'] ?? null)
            || ($data['registered'] ?? null) !== true || ($data['contact_verified'] ?? null) !== true
            || ($data['domain_verified'] ?? null) !== true) {
            throw new RuntimeException('The Portal has not confirmed this installation and domain.');
        }
        $state['contact_verified'] = true;
        $this->save($state);
    }

    /** A domain-binding response has no login or update authority. */
    public function domainBinding(string $challenge): string
    {
        if (! preg_match('/^[a-f0-9]{64}$/D', $challenge)) {
            throw new RuntimeException('Invalid domain challenge.');
        }
        $state = $this->state();
        if (empty($state['registered']) || ! empty($state['pending_revoke'])
            || ($state['domain'] ?? null) !== $this->domain()) {
            throw new RuntimeException('No active installation binding.');
        }

        return hash_hmac('sha256', "campaign-manager-domain-v1\n".$state['id']."\n".$this->domain()."\n".$challenge, hash('sha256', $state['token']));
    }

    /** Reuse existing installation credentials without exposing them to controllers or views. */
    public function enterpriseEntitlements(): \Illuminate\Http\Client\Response
    {
        $state = $this->state();
        if (empty($state['registered']) || ! empty($state['pending_revoke'])
            || ($state['domain'] ?? null) !== $this->domain()) {
            throw new RuntimeException('Register this installation before synchronizing add-ons.');
        }
        return Http::withToken($state['token'])->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(15)
            ->get($this->base().'/'.$state['id'].'/enterprise-entitlements');
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
        Http::withToken($state['token'])->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(20)
            ->delete($this->base().'/'.$state['id'])->throw();
        $this->save(['id' => $state['id'], 'token' => bin2hex(random_bytes(32)), 'registered' => false, 'pending_revoke' => false, 'messages' => []]);
    }
}
