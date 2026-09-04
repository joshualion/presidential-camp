<?php

namespace Tests;

use App\Services\InstallationStateService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
