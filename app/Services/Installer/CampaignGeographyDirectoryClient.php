<?php

namespace App\Services\Installer;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class CampaignGeographyDirectoryClient
{
    public function states(): Collection
    {
        return $this->get('/campaign-locations/states');
    }

    public function senatorialDistricts(int|string $stateId): Collection
    {
        return $this->get("/campaign-locations/states/{$stateId}/senatorial-districts");
    }

    public function federalConstituencies(int|string $stateId): Collection
    {
        return $this->get("/campaign-locations/states/{$stateId}/federal-constituencies");
    }

    public function lgas(int|string $stateId): Collection
    {
        return $this->get("/campaign-locations/states/{$stateId}/lgas");
    }

    public function summary(string $scopeType, array $geography = []): array
    {
        $query = array_filter([
            'scope_type' => $scopeType,
            'state_id' => $geography['portal_state_id'] ?? $geography['state_id'] ?? null,
            'senatorial_district_id' => $geography['portal_senatorial_district_id'] ?? $geography['senatorial_district_id'] ?? null,
            'federal_constituency_id' => $geography['portal_federal_constituency_id'] ?? $geography['federal_constituency_id'] ?? null,
            'lga_id' => $geography['portal_lga_id'] ?? $geography['lga_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get(
                    config('campaign.portal_directory_base') . '/campaign-locations/summary',
                    $query
                );
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Unable to reach the Campaign Manager geography directory. Check the connection and retry.',
                0,
                $e
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'The Campaign Manager geography summary is temporarily unavailable.'
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException(
                'The Campaign Manager geography summary response was invalid.'
            );
        }

        $validator = Validator::make($payload, [
            'scope_type' => ['required', 'string'],
            'scope_name' => ['nullable', 'string'],
            'counts' => ['required', 'array'],
            'counts.*' => ['integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException(
                'The Campaign Manager geography summary response was invalid.'
            );
        }

        return [
            'ok' => true,
            'scope_type' => (string) $payload['scope_type'],
            'scope_name' => (string) (
                $payload['scope_name']
                ?? $geography['scope_name']
                ?? 'selected campaign geography'
            ),
            'counts' => $payload['counts'],
        ];
    }

    private function get(string $path): Collection
    {
        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get(config('campaign.portal_directory_base') . $path);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Unable to reach the Campaign Manager geography directory. Check the connection and retry.',
                0,
                $e
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'The Campaign Manager geography directory is unavailable. Check the connection and retry.'
            );
        }

        $body = $response->json();
        $items = is_array($body) ? $body : [];

        return collect($items)
            ->map(function ($item) {
                $code = data_get($item, 'code');
                $inecCode = data_get($item, 'inec_code');

                return (object) [
                    'id' => data_get($item, 'id'),
                    'name' => trim((string) data_get($item, 'name')),
                    'code' => $code !== null && $code !== ''
                        ? (string) $code
                        : null,
                    'inec_code' => $inecCode !== null && $inecCode !== ''
                        ? (string) $inecCode
                        : null,
                ];
            })
            ->filter(
                fn (object $item) =>
                    $item->id !== null
                    && $item->name !== ''
            )
            ->values();
    }
}
