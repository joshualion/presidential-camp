<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RemoteLicenseClient
{
    public function preview(array $payload): array
    {
        $response = $this->post('/licenses/entitlement', $payload);

        if (($response['ok'] ?? false) === true) {
            return $response;
        }

        return $this->post('/licenses/check', $payload);
    }

    public function activate(array $payload): array
    {
        return $this->post('/licenses/activate', $payload);
    }

    private function post(string $path, array $payload): array
    {
        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->asJson()
                ->post(config('campaign.portal_api_base').$path, $payload);

            $body = $response->json();

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'message' => data_get($body, 'message', $response->reason()),
                'data' => is_array($body) ? $body : [],
            ];
        } catch (ConnectionException $e) {
            return [
                'ok' => false,
                'status' => 0,
                'message' => 'Unable to reach the Campaign Manager portal. Check network access and try again.',
                'data' => [],
            ];
        }
    }

    public function maskedPayload(array $payload): array
    {
        if (isset($payload['license_key'])) {
            $payload['license_key'] = Str::mask((string) $payload['license_key'], '*', 4, max(strlen((string) $payload['license_key']) - 8, 0));
        }

        return $payload;
    }
}
