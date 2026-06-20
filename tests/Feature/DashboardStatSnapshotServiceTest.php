<?php

use App\Models\DashboardStatSnapshot;
use App\Models\LocalLicense;
use App\Models\User;
use App\Observers\DashboardStatSnapshotObserver;
use App\Services\DashboardStatSnapshotService;

function createDashboardSnapshotTestLicense(): LocalLicense
{
    $license = LocalLicense::create([
        'license_key' => 'CM-DASHBOARD-SNAPSHOT',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
    ]);

    $license->scope()->create([
        'scope_type' => 'state',
        'state_id' => 9,
        'state_name' => 'Cross River',
        'scope_name' => 'Cross River',
    ]);

    return $license;
}

function createDashboardSnapshotTestUser(string $email = 'dashboard-snapshot@example.test'): User
{
    return User::query()->updateOrCreate(
        ['email' => $email],
        [
            'firstname' => 'Snapshot',
            'lastname' => 'Admin',
            'username' => str_replace(['@', '.'], '_', $email),
            'password' => 'password',
            'access_level' => 'superadmin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]
    );
}

test('governorship self hosted dashboard snapshot keeps package short and scope key license aware', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    createDashboardSnapshotTestLicense();
    $user = createDashboardSnapshotTestUser();

    DashboardStatSnapshot::query()->delete();

    app(DashboardStatSnapshotService::class)->refreshForUser($user);

    $snapshot = DashboardStatSnapshot::query()->firstOrFail();

    expect($snapshot->package)->toBe('governorship')
        ->and(strlen($snapshot->package))->toBeLessThanOrEqual(191)
        ->and($snapshot->scope_key)->toContain('license:self_hosted:governorship:state')
        ->and(strlen($snapshot->scope_key))->toBeLessThanOrEqual(255);
});

test('managed dashboard snapshot package behavior remains unchanged', function () {
    config(['campaign.deployment_mode' => 'managed']);
    $user = createDashboardSnapshotTestUser('managed-dashboard-snapshot@example.test');

    DashboardStatSnapshot::query()->delete();

    app(DashboardStatSnapshotService::class)->refreshForUser($user);

    $snapshot = DashboardStatSnapshot::query()->firstOrFail();

    expect($snapshot->package)->toBe('presidential')
        ->and($snapshot->scope_key)->not->toContain('license:');
});

test('dashboard snapshot observer does not fail user save when refresh is non critical', function () {
    app()->instance(DashboardStatSnapshotService::class, new class {
        public function queueAffectedRefreshes(): void
        {
            throw new RuntimeException('Synthetic snapshot queue failure.');
        }

        public function queueAffectedRefreshesForAttributes(): void
        {
            throw new RuntimeException('Synthetic snapshot previous-scope failure.');
        }
    });

    User::observe(DashboardStatSnapshotObserver::class);

    $user = createDashboardSnapshotTestUser('installer-retry@example.test');
    $user->update(['firstname' => 'Installer']);

    expect($user->fresh()->firstname)->toBe('Installer');
});
