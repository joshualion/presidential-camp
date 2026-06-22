<?php

namespace App\Services;

use App\Models\LicenseCheckLog;
use App\Models\LocalLicense;
use App\Support\SafeDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocalLicenseService
{
    public function current(): ?LocalLicense
    {
        if (!SafeDatabase::hasTable('local_licenses')) {
            return null;
        }

        return LocalLicense::with(['scope', 'modules'])->latest('id')->first();
    }

    public function active(): ?LocalLicense
    {
        if (
            !SafeDatabase::hasTable('local_licenses')
            || !SafeDatabase::hasTable('local_license_scopes')
            || !SafeDatabase::hasTable('local_license_modules')
        ) {
            return null;
        }

        $license = $this->current();

        if (!$license || !in_array(strtolower((string) $license->status), ['active', 'valid'], true)) {
            return null;
        }

        if (!$license->scope || $license->modules->isEmpty()) {
            return null;
        }

        return $license;
    }

    public function isLocallyActivated(): bool
    {
        return $this->active() !== null;
    }

    public function persistActivation(string $licenseKey, array $payload): LocalLicense
    {
        return DB::transaction(function () use ($licenseKey, $payload) {
            $licenseData = data_get($payload, 'license', $payload);
            $scopeData = data_get($payload, 'scope', data_get($licenseData, 'scope', []));
            $modules = data_get($payload, 'modules', data_get($licenseData, 'modules', []));

            $license = LocalLicense::updateOrCreate(
                ['license_key' => $licenseKey],
                [
                    'portal_license_id' => data_get($licenseData, 'id'),
                    'deployment_mode' => data_get($licenseData, 'deployment_mode', config('campaign.deployment_mode')),
                    'package_type' => $this->packageType($payload),
                    'status' => data_get($licenseData, 'status', data_get($payload, 'status', 'active')),
                    'app_url' => data_get($licenseData, 'app_url', config('app.url')),
                    'domain' => data_get($licenseData, 'domain', parse_url((string) config('app.url'), PHP_URL_HOST)),
                    'activation_id' => data_get($payload, 'activation.id', data_get($payload, 'activation_id')),
                    'activated_at' => data_get($payload, 'activated_at', now()),
                    'last_checked_at' => now(),
                    'grace_until' => data_get($licenseData, 'grace_until'),
                    'maintenance_expires_at' => data_get($licenseData, 'maintenance_expires_at', data_get($payload, 'maintenance_expires_at')),
                    'updates_until' => data_get($licenseData, 'updates_until', data_get($payload, 'updates_until')),
                    'raw_payload' => $this->redact($payload),
                ]
            );

            $license->scope()->updateOrCreate(
                ['local_license_id' => $license->id],
                [
                'scope_type' => data_get($scopeData, 'scope_type', data_get($scopeData, 'type')),
                'state_id' => data_get($scopeData, 'state_id'),
                'state_name' => data_get($scopeData, 'state_name'),
                'senatorial_district_id' => data_get($scopeData, 'senatorial_district_id'),
                'senatorial_district_name' => data_get($scopeData, 'senatorial_district_name'),
                'federal_constituency_id' => data_get($scopeData, 'federal_constituency_id'),
                'federal_constituency_name' => data_get($scopeData, 'federal_constituency_name'),
                'lga_id' => data_get($scopeData, 'lga_id'),
                'lga_name' => data_get($scopeData, 'lga_name'),
                'state_constituency_id' => data_get($scopeData, 'state_constituency_id'),
                'state_constituency_name' => data_get($scopeData, 'state_constituency_name'),
                'scope_name' => $this->scopeName($scopeData),
                'raw_payload' => $scopeData,
                ]
            );

            foreach ($this->normalizeModules($modules) as $module) {
                $license->modules()->updateOrCreate(
                    ['module_key' => $module['module_key']],
                    $module
                );
            }

            return $license->fresh(['scope', 'modules']);
        });
    }

    public function displayPayload(array $payload): array
    {
        return [
            'package_type' => $this->packageType($payload),
            'deployment_mode' => data_get($payload, 'license.deployment_mode', data_get($payload, 'deployment_mode', config('campaign.deployment_mode'))),
            'scope_type' => data_get($payload, 'scope.scope_type', data_get($payload, 'scope.type')),
            'scope_name' => $this->scopeName((array) data_get($payload, 'scope', [])),
            'licensed_features' => collect($this->normalizeModules(data_get($payload, 'modules', [])))->where('enabled', true)->count(),
            'maintenance_expires_at' => data_get($payload, 'license.maintenance_expires_at', data_get($payload, 'maintenance_expires_at')),
            'updates_until' => data_get($payload, 'license.updates_until', data_get($payload, 'updates_until')),
            'activation_limit' => data_get($payload, 'license.activation_limit', data_get($payload, 'activation_limit')),
            'activation_count' => data_get($payload, 'license.activation_count', data_get($payload, 'activation_count')),
        ];
    }

    public function maskLicenseKey(?string $licenseKey): ?string
    {
        return $licenseKey ? Str::mask($licenseKey, '*', 4, max(strlen($licenseKey) - 8, 0)) : null;
    }

    public function customerMessage(?string $message, string $fallback = 'Request could not be completed.'): string
    {
        $message = trim((string) $message);
        if ($message === '') {
            return $fallback;
        }

        $message = preg_replace('/\bModules\s*:\s*[^.;\n\r]+[.;]?/i', 'Licensed package activated.', $message) ?: $message;

        $hiddenKeys = [
            'advanced_analytics',
            'agents',
            'ai_strategy',
            'bulk_sms',
            'elections',
            'evidence',
            'executives',
            'incidents',
            'members',
            'messaging',
            'operations_center',
            'results',
        ];

        foreach ($hiddenKeys as $key) {
            $message = preg_replace('/\b'.preg_quote($key, '/').'\b/i', 'licensed feature', $message) ?: $message;
        }

        return trim(preg_replace('/\s+/', ' ', $message) ?: $fallback);
    }

    public function log(?LocalLicense $license, ?string $licenseKey, string $action, string $status, ?string $message, array $payload = []): void
    {
        if (!SafeDatabase::hasTable('license_check_logs')) {
            return;
        }

        LicenseCheckLog::create([
            'local_license_id' => $license?->id,
            'license_key' => $this->maskLicenseKey($licenseKey),
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'checked_at' => now(),
            'raw_payload' => $this->redact($payload),
        ]);
    }

    public function packageType(array $payload): ?string
    {
        return data_get($payload, 'license.package_type', data_get($payload, 'package_type'));
    }

    private function scopeName(array $scopeData): ?string
    {
        return data_get($scopeData, 'scope_name')
            ?? data_get($scopeData, 'name')
            ?? data_get($scopeData, 'state_constituency_name')
            ?? data_get($scopeData, 'federal_constituency_name')
            ?? data_get($scopeData, 'senatorial_district_name')
            ?? data_get($scopeData, 'lga_name')
            ?? data_get($scopeData, 'state_name')
            ?? data_get($scopeData, 'country_name');
    }

    private function normalizeModules(mixed $modules): array
    {
        return collect($modules ?: [])->map(function ($value, $key) {
            $payload = is_array($value)
                ? $value
                : [
                    'module_key' => is_string($key) ? $key : $value,
                    'enabled' => is_bool($value) ? $value : true,
                ];

            return [
                'module_key' => (string) data_get($payload, 'module_key', data_get($payload, 'key')),
                'enabled' => filter_var(data_get($payload, 'enabled', true), FILTER_VALIDATE_BOOLEAN),
                'expires_at' => data_get($payload, 'expires_at'),
                'raw_payload' => $payload,
            ];
        })->filter(fn ($module) => filled($module['module_key']))->values()->all();
    }

    private function redact(array $payload): array
    {
        Arr::forget($payload, ['license_key', 'database.password', 'mail.password']);

        return $payload;
    }
}
