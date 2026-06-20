<?php

use App\Services\CampaignPackageUiService;
use App\Services\LocationScopeService;
use Illuminate\Support\Facades\URL;

function renderElectionWidget(array $dashboardMetrics = []): string
{
    return view('backend.shared.dashboard-election-stat-section', [
        'dashboardMetrics' => $dashboardMetrics,
    ])->render();
}

test('package ui election statistics title is scope aware', function () {
    $ui = app(CampaignPackageUiService::class);

    expect($ui->electionStatisticsTitleForScope(LocationScopeService::NATIONAL))->toBe('National Election Statistics')
        ->and($ui->electionStatisticsTitleForScope(LocationScopeService::STATE))->toBe('State Election Statistics')
        ->and($ui->electionStatisticsTitleForScope(LocationScopeService::SENATORIAL))->toBe('Senatorial District Election Statistics')
        ->and($ui->electionStatisticsTitleForScope(LocationScopeService::FEDERAL))->toBe('Federal Constituency Election Statistics')
        ->and($ui->electionStatisticsTitleForScope(LocationScopeService::LGA))->toBe('LGA Election Statistics')
        ->and($ui->electionStatisticsTitleForScope(LocationScopeService::WARD))->toBe('Ward Election Statistics')
        ->and($ui->electionStatisticsTitleForScope(LocationScopeService::POLLING_UNIT))->toBe('Polling Unit Election Statistics');
});

test('shared election statistics widget renders empty state safely', function () {
    $html = renderElectionWidget([
        'scope' => ['type' => LocationScopeService::LGA],
        'election' => [
            'title' => 'LGA Election Statistics',
            'elections' => 0,
            'votes' => 0,
            'results_submitted' => 0,
            'verified_results' => 0,
            'disputed_results' => 0,
            'results_pending' => 0,
            'incidents_reported' => 0,
            'pictures_uploaded' => 0,
            'videos_uploaded' => 0,
        ],
    ]);

    expect($html)->toContain('LGA Election Statistics')
        ->and($html)->toContain('No election data is available for this campaign scope yet.')
        ->and($html)->toContain('Results Submitted')
        ->and($html)->toContain('Verified Results')
        ->and($html)->toContain('Incidents Reported');
});

test('shared election statistics widget uses context title for detail dashboards', function () {
    $wardHtml = renderElectionWidget([
        'scope' => ['type' => LocationScopeService::WARD],
        'card_groups' => [
            'election_operations' => [
                ['key' => 'elections', 'label' => 'Elections', 'value' => 1, 'color' => 'bg-primary', 'icon' => 'fas fa-award', 'footerText' => 'Election metric'],
            ],
        ],
    ]);

    $puHtml = renderElectionWidget([
        'scope' => ['type' => LocationScopeService::POLLING_UNIT],
        'card_groups' => [
            'election_operations' => [
                ['key' => 'elections', 'label' => 'Elections', 'value' => 1, 'color' => 'bg-primary', 'icon' => 'fas fa-award', 'footerText' => 'Election metric'],
            ],
        ],
    ]);

    expect($wardHtml)->toContain('Ward Election Statistics')
        ->and($puHtml)->toContain('Polling Unit Election Statistics');
});

test('shared election statistics widget links respect configured base path', function () {
    URL::forceRootUrl('http://localhost/otu/public');

    $html = renderElectionWidget([
        'scope' => ['type' => LocationScopeService::NATIONAL],
        'card_groups' => [
            'election_operations' => [
                [
                    'key' => 'elections',
                    'label' => 'Elections',
                    'value' => 1,
                    'color' => 'bg-primary',
                    'icon' => 'fas fa-award',
                    'footerText' => 'Election metric',
                    'footerUrl' => url('superadmin/elections'),
                ],
            ],
        ],
    ]);

    expect($html)->toContain('href="http://localhost/otu/public/superadmin/elections"')
        ->and($html)->not->toContain('href="/superadmin/elections"');
});

