<?php

namespace Tests;

use App\Services\InstallationStateService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        // Match the installer middleware's file driver before seeding ownership proof.
        config(['session.driver' => 'file']);

        // Installer feature fixtures represent a server owner who has confirmed access.
        config(['campaign.installer_access_token' => str_repeat('a', 48)]);
        $this->withSession(['installer.owner_proof' => hash('sha256', str_repeat('a', 48))]);

        $this->markApplicationInstalledForTests();
    }

    protected function markApplicationInstalledForTests(array $metadata = []): void
    {
        $path = storage_path('app/installed.lock');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(array_merge([
            'installation_mode' => 'community',
            'deployment_mode' => 'self_hosted',
            'source' => 'test',
            'remote_validation_required' => false,
            'installed_at' => now()->toIso8601String(),
        ], $metadata), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function markApplicationNotInstalledForTests(): void
    {
        app(InstallationStateService::class)->remove();
    }
}

