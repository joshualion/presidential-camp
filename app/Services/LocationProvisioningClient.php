<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class LocationProvisioningClient
{
    public function createSession(array $payload): array
    {
        return $this->post('/campaign-manager/location-provisioning/session', $payload);
    }

    public function fetchBatch(array $payload): array
    {
        return $this->post('/campaign-manager/location-provisioning/batch', $payload);
    }

    public function complete(array $payload): array
    {
        return $this->post('/campaign-manager/location-provisioning/complete', $payload);
    }

    private function post(string $path, array $payload): array
    {
        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->asJson()
                ->post(config('campaign.portal_api_base').$path, $payload);
        } catch (ConnectionException) {
            throw new LocationProvisioningException(
                'Unable to reach the Campaign Manager portal. Complete location provisioning later with php artisan campaign:locations:provision.',
                0
            );
        }

        $body = $response->json();
        $body = is_array($body) ? $body : [];

        if (!$response->successful()) {
            throw new LocationProvisioningException(
                (string) data_get($body, 'message', $response->reason() ?: 'Location provisioning request failed.'),
                $response->status()
            );
        }

        return $body;
    }
}
