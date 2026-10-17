<?php

namespace App\Services\Updates;

use App\Services\Updates\UpdateException as RuntimeException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class UpdatePortalClient
{
    public function packageType(): string
    {
        $value = Schema::hasTable('local_licenses') ? DB::table('local_licenses')->latest('id')->value('package_type') : null;
        $value ??= DB::table('system_settings')->value('package');

        return match (strtolower(trim((string) $value))) {
            'presidential','national' => 'presidential',
            'governorship','state' => 'governorship',
            'senatorial','senatorial_district' => 'senatorial',
            'federal_constituency','federal' => 'federal_constituency',
            'chairmanship','lga','ward','pu','local_government' => 'lga',
            default => throw new RuntimeException('Configure the campaign scope before requesting automatic-update pricing.'),
        };
    }

    private function identity(): array
    {
        $p = storage_path('app/private/deployment-registration.json');
        if (! is_file($p)) {
            throw new RuntimeException('Complete the one-time installation registration in Settings first.');
        }
        $s = json_decode(Crypt::decryptString(file_get_contents($p)), true, 64, JSON_THROW_ON_ERROR);
        $host = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
        if (empty($s['registered']) || empty($s['id']) || empty($s['token']) || ($s['domain'] ?? null) !== $host || ! empty($s['pending_revoke'])) {
            throw new RuntimeException('Confirm the installation registration and current domain before using paid updates.');
        }

        return $s;
    }

    private function base(): string
    {
        $base = rtrim(config('campaign.portal_api_base') ?: 'https://campaignmanager.ng/api/v2', '/');
        if (parse_url($base, PHP_URL_SCHEME) !== 'https' || parse_url($base, PHP_URL_USER) || parse_url($base, PHP_URL_QUERY) || parse_url($base, PHP_URL_FRAGMENT)) {
            throw new RuntimeException('Automatic updates require an HTTPS Portal API.');
        }

        return $base.'/campaign-manager/deployments';
    }

    public function request(string $method, string $suffix, array $data): array
    {
        $s = $this->identity();
        $http = Http::acceptJson()->withToken($s['token'])->connectTimeout(5)->timeout(25);
        $url = $this->base().'/'.$s['id'].'/automatic-update'.$suffix;
        $response = $method === 'GET' ? $http->get($url, $data) : $http->post($url, $data);
        if (! $response->successful()) {
            throw new RuntimeException($response->status() === 503 ? 'Automatic-update pricing or payment is not configured for this campaign scope. Use the free manual download or contact support.' : ($response->status() === 402 ? 'Complete the automatic-update payment first.' : 'The Portal could not confirm this update. Your purchase remains available for retry.'));
        }
        $result = $response->json();
        if (! is_array($result)) {
            throw new RuntimeException('Invalid Portal update response.');
        }

        return $result;
    }

    public function status(string $version, string $hash): array
    {
        $result = $this->request('GET', '', ['version' => $version, 'sha256' => $hash, 'package_type' => $this->packageType()]);
        if (($result['package_type'] ?? '') !== $this->packageType()) {
            throw new RuntimeException('The Portal quote does not match this campaign scope.');
        }
        $url = $result['checkout_url'] ?? '';
        $portal = rtrim(config('campaign.portal_web_base') ?: 'https://campaignmanager.ng', '/');
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== parse_url($portal, PHP_URL_HOST) || parse_url($url, PHP_URL_USER)) {
            throw new RuntimeException('Invalid automatic-update checkout address.');
        }

        return $result;
    }

    public function authorize(array $job): array
    {
        $s = $this->identity();
        $package = $this->packageType();
        if (isset($job['package_type']) && $job['package_type'] !== $package) {
            throw new RuntimeException('The installation campaign scope changed after this update was requested.');
        }
        $p = $this->request('POST', '/authorize', ['version' => $job['target_version'], 'sha256' => $job['package_sha256'], 'job_uuid' => $job['id'], 'package_type' => $package]);
        foreach (['deployment_uuid' => $s['id'], 'domain' => $s['domain'], 'target_version' => $job['target_version'], 'package_sha256' => $job['package_sha256'], 'job_uuid' => $job['id'], 'package_type' => $package] as $key => $value) {
            if (($p[$key] ?? null) !== $value) {
                throw new RuntimeException('Update authorisation does not match this installation and package.');
            }
        }
        if (($p['authorized'] ?? null) !== true) {
            throw new RuntimeException('Automatic update is not authorised.');
        }

        return $p;
    }

    public function finish(array $job, bool $successful): void
    {
        $this->request('POST', '/finish', ['job_uuid' => $job['id'], 'successful' => $successful]);
    }
}
