<?php

namespace App\Services\Enterprise;

use App\Services\DeploymentRegistrationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use RuntimeException;

class EntitlementService
{
    public function __construct(private DeploymentRegistrationService $registration, private SignedEntitlement $verifier) {}

    protected function path(): string { return storage_path('app/private/enterprise-entitlement.json'); }

    private function identity(): array
    {
        $state = $this->registration->summary();
        if (empty($state['registered']) || ! empty($state['pending_revoke'])
            || parse_url(config('app.url'), PHP_URL_SCHEME) !== 'https'
            || ($state['domain'] ?? null) !== $this->registration->domain()) {
            throw new RuntimeException('Register this installation and its current domain first.');
        }
        return [$state['id'], $state['domain']];
    }

    private function verify(array $envelope): array
    {
        [$id, $domain] = $this->identity();
        $scope = app(\App\Services\PackageScopeService::class)->current();
        if ($scope->fallback_used) {
            throw new RuntimeException('Configure the installation campaign scope before using add-ons.');
        }
        $package = $scope->package_type === 'chairmanship' ? 'lga' : $scope->package_type;
        $keys = array_merge([(string) config('enterprise.public_key')], (array) config('enterprise.previous_public_keys', []));
        foreach (array_slice($keys, 0, 3) as $key) {
            if (!is_string($key) || $key === '') { continue; }
            try {
                return $this->verifier->verify($envelope, $key, $id, $domain, time(), $package);
            } catch (\Throwable) { /* Never trust keys supplied by the API response. */ }
        }
        throw new RuntimeException('The entitlement could not be verified with an installed trust anchor.');
    }

    public function cached(): ?array
    {
        try {
            if (! File::isFile($this->path())) { return null; }
            return $this->verify(json_decode(Crypt::decryptString(File::get($this->path())), true, 32, JSON_THROW_ON_ERROR));
        } catch (\Throwable) { return null; }
    }

    public function refresh(): array
    {
        // Serialize network refresh and cache writes so an older response cannot overwrite revocation.
        File::ensureDirectoryExists(dirname($this->path()), 0700);
        $lock = fopen($this->path().'.lock', 'c');
        if (! $lock) { throw new RuntimeException('Unable to lock entitlement synchronization.'); }
        chmod($this->path().'.lock', 0600);
        try {
            if (! flock($lock, LOCK_EX)) { throw new RuntimeException('Unable to lock entitlement synchronization.'); }
            $response = $this->registration->enterpriseEntitlements();
            if ($response->status() !== 200 || strlen($response->body()) > 98304) {
                if ($response->status() >= 500) { throw new ConnectionException('The Portal is temporarily unavailable.'); }
                File::delete($this->path());
                throw new RuntimeException('Portal authorization was rejected. Confirm installation registration.');
            }
            try {
                $envelope = $response->json();
                if (! is_array($envelope)) { throw new RuntimeException('Invalid Portal response.'); }
                $claims = $this->verify($envelope);
            } catch (\Throwable $exception) {
                File::delete($this->path());
                throw new RuntimeException('The Portal entitlement response failed validation.', 0, $exception);
            }
            File::replace($this->path(), Crypt::encryptString(json_encode($envelope, JSON_THROW_ON_ERROR)), 0600);
            return $claims;
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    public function active(string $feature, bool $requireOnline = false): bool
    {
        if (! array_key_exists($feature, config('enterprise.features', []))) { return false; }
        $claims = $this->cached();
        if ($requireOnline || ! $claims || $claims['refresh_after'] <= time()) {
            try { $claims = $this->refresh(); }
            catch (ConnectionException) { if ($requireOnline) { return false; } }
            catch (\Throwable) { return false; }
        }
        // Expiry is checked on every read, including offline reads of previously valid grants.
        $grant = $claims['features'][$feature] ?? null;
        return $claims && $claims['valid_until'] > time() && $grant && $grant['active'] === true
            && $grant['starts_at'] <= time() && (! isset($grant['expires_at']) || $grant['expires_at'] > time());
    }
}
