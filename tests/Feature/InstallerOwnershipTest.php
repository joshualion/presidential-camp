<?php

use App\Services\Installer\InstallerAccessService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->markApplicationNotInstalledForTests();
    $this->withSession(['installer.owner_proof' => null]);
    config(['campaign.deployment_mode' => 'self_hosted']);
});

test('a visitor cannot enter installer steps without server ownership proof', function () {
    $this->get(route('install.welcome'))->assertRedirect(route('install.access'));
    $this->getJson(route('install.database'))->assertForbidden();
});

test('ownership screen never discloses the local setup code', function () {
    $this->get(route('install.access'))->assertOk()->assertDontSee(str_repeat('a', 48));
});

test('an invalid setup code is rejected and never flashed', function () {
    $this->post(route('install.access.verify'), ['setup_code' => 'invalid-private-code'])
        ->assertSessionHasErrors('setup_code')->assertSessionMissing('_old_input.setup_code');
    expect(session('installer.owner_proof'))->toBeNull();
});

test('the hosting owner can enter the free installer with the local code', function () {
    $this->post(route('install.access.verify'), ['setup_code' => str_repeat('a', 48)])
        ->assertRedirect(route('install.welcome'));
    $this->get(route('install.welcome'))->assertOk();
});

test('the local ownership code is durable and private', function () {
    $path = storage_path('framework/testing/installer-owner-'.Illuminate\Support\Str::uuid().'/code');
    $service = new class($path) extends InstallerAccessService {
        public function __construct(private string $testPath) {}
        public function path(): string { return $this->testPath; }
    };
    config(['campaign.installer_access_token' => null]);
    try {
        $token = $service->token();
        expect(strlen($token))->toBe(48)->and($service->token())->toBe($token)
            ->and(fileperms($path) & 0777)->toBe(0600);
    } finally {
        File::deleteDirectory(dirname($path));
    }
});
