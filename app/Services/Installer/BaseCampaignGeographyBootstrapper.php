<?php

namespace App\Services\Installer;

use App\Services\LocationProvisioningImportService;
use Illuminate\Support\Facades\DB;

class BaseCampaignGeographyBootstrapper
{
    public function __construct(private LocationProvisioningImportService $importer)
    {
    }

    public function bootstrap(array $campaignScope, array $campaignGeography): array
    {
        $scopeType = (string) ($campaignScope['scope_type'] ?? 'national');

        return DB::transaction(function () use ($scopeType, $campaignGeography) {
            if ($scopeType === 'national') {
                $this->importer->bootstrapBaseRecords([
                    'countries' => [['name' => 'Nigeria']],
                    'regions' => collect($this->stateRegions())->unique()->map(fn (string $region) => ['country_name' => 'Nigeria', 'name' => $region])->values()->all(),
                ]);

                return [
                    'country_name' => 'Nigeria',
                    'scope_name' => 'Nigeria',
                    'state_id' => null,
                    'senatorial_district_id' => null,
                    'federal_constituency_id' => null,
                    'lga_id' => null,
                ];
            }

            $stateName = (string) ($campaignGeography['state_name'] ?? $campaignGeography['scope_name'] ?? '');
            $stateCode = $this->stateCode($stateName);
            $stateRegion = $this->stateRegion($stateName);

            $records = [
                'states' => [[
                    'country_name' => 'Nigeria',
                    'region_name' => $stateRegion,
                    'state_code' => $stateCode,
                    'name' => $stateName,
                ]],
            ];

            if ($scopeType === 'senatorial_district') {
                $records['senatorial_districts'] = [[
                    'state_code' => $stateCode,
                    'state_name' => $stateName,
                    'name' => $campaignGeography['senatorial_district_name'] ?? $campaignGeography['scope_name'],
                ]];
            }

            if ($scopeType === 'federal_constituency') {
                $records['federal_constituencies'] = [[
                    'state_code' => $stateCode,
                    'state_name' => $stateName,
                    'name' => $campaignGeography['federal_constituency_name'] ?? $campaignGeography['scope_name'],
                ]];
            }

            if ($scopeType === 'lga') {
                $records['local_government_areas'] = [[
                    'state_code' => $stateCode,
                    'state_name' => $stateName,
                    'name' => $campaignGeography['lga_name'] ?? $campaignGeography['scope_name'],
                ]];
            }

            $ids = $this->importer->bootstrapBaseRecords($records);

            return [
                'country_name' => 'Nigeria',
                'scope_name' => $campaignGeography['scope_name'] ?? $stateName,
                'state_id' => $ids['state_id'] ?? null,
                'senatorial_district_id' => $ids['senatorial_district_id'] ?? null,
                'federal_constituency_id' => $ids['federal_constituency_id'] ?? null,
                'lga_id' => $ids['lga_id'] ?? null,
                'portal_state_id' => $campaignGeography['portal_state_id'] ?? null,
                'portal_senatorial_district_id' => $campaignGeography['portal_senatorial_district_id'] ?? null,
                'portal_federal_constituency_id' => $campaignGeography['portal_federal_constituency_id'] ?? null,
                'portal_lga_id' => $campaignGeography['portal_lga_id'] ?? null,
            ];
        });
    }

    private function stateRegion(string $stateName): string
    {
        return $this->stateRegions()[$stateName] ?? 'Unknown Region';
    }

    private function stateCode(string $stateName): ?string
    {
        return null;
    }

    private function stateRegions(): array
    {
        return [
            'Abia' => 'South East', 'Adamawa' => 'North East', 'Akwa Ibom' => 'South South', 'Anambra' => 'South East',
            'Bauchi' => 'North East', 'Bayelsa' => 'South South', 'Benue' => 'North Central', 'Borno' => 'North East',
            'Cross River' => 'South South', 'Delta' => 'South South', 'Ebonyi' => 'South East', 'Edo' => 'South South',
            'Ekiti' => 'South West', 'Enugu' => 'South East', 'FCT' => 'North Central', 'Gombe' => 'North East',
            'Imo' => 'South East', 'Jigawa' => 'North West', 'Kaduna' => 'North West', 'Kano' => 'North West',
            'Katsina' => 'North West', 'Kebbi' => 'North West', 'Kogi' => 'North Central', 'Kwara' => 'North Central',
            'Lagos' => 'South West', 'Nasarawa' => 'North Central', 'Niger' => 'North Central', 'Ogun' => 'South West',
            'Ondo' => 'South West', 'Osun' => 'South West', 'Oyo' => 'South West', 'Plateau' => 'North Central',
            'Rivers' => 'South South', 'Sokoto' => 'North West', 'Taraba' => 'North East', 'Yobe' => 'North East',
            'Zamfara' => 'North West',
        ];
    }
}
