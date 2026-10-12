<?php

use App\Events\NewPostCreated;
use App\Models\User;
use App\Models\Post;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use App\Services\Installer\InstallerService;
use App\Services\ReleaseIntegrityService;
use App\Services\ReleaseManifestSigner;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function installerPrivateCall(string $method, array $arguments): mixed
{
    $class = new ReflectionClass(InstallerService::class);
    return $class->getMethod($method)->invokeArgs($class->newInstanceWithoutConstructor(), $arguments);
}

test('failed migration command stops installer without leaking console output', function () {
    Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->andReturn(1);
    expect(fn () => installerPrivateCall('runArtisanCommand', ['migrate', ['--force' => true]]))
        ->toThrow(RuntimeException::class, 'did not complete successfully');
});

test('retry keeps the original administrator account and credentials', function () {
    $user = User::factory()->create(['access_level' => 'superadmin', 'password' => Hash::make('ExistingPassword1!')]);
    $passwordHash = $user->password;
    $result = installerPrivateCall('createSuperAdmin', [[
        'email' => $user->email, 'name' => 'Changed Name', 'password' => 'ExistingPassword1!',
    ]]);
    expect($result->id)->toBe($user->id)
        ->and($user->fresh()->password)->toBe($passwordHash)
        ->and($user->fresh()->firstname)->toBe($user->firstname);
});

test('installer cannot promote or replace an existing member', function () {
    $user = User::factory()->create(['access_level' => 'user']);
    expect(fn () => installerPrivateCall('createSuperAdmin', [[
        'email' => $user->email, 'name' => 'Intruder', 'password' => 'NewPassword1!',
    ]]))->toThrow(RuntimeException::class, 'cannot replace or promote');
    expect($user->fresh()->access_level)->toBe('user');
});

test('installer refuses a new administrator in a populated database', function () {
    User::factory()->create();
    expect(fn () => installerPrivateCall('createSuperAdmin', [[
        'email' => 'new-admin@example.test', 'name' => 'New Admin', 'password' => 'Password1!',
    ]]))->toThrow(RuntimeException::class, 'already contains users');
});

test('shared post broadcast contains no campaign content or author identity', function () {
    $event = new NewPostCreated((object) ['id' => 123, 'content' => 'Private campaign message']);
    expect($event->broadcastWith())->toBe(['refresh' => true]);
});

test('unsigned manifest is never reported as a verified clean release', function () {
    $path = storage_path('framework/testing/unsigned-manifest/'.Str::uuid());
    File::ensureDirectoryExists($path);
    try {
        File::put($path.'/release-manifest.json', json_encode(['version' => '1.0.9', 'protected_files' => []]));
        $result = (new ReleaseIntegrityService(app(ReleaseManifestSigner::class), $path))->check(false);
        expect($result['update_entitlement_state'])->toBe('signature_missing')
            ->and($result['manifest_signature_valid'])->toBeNull();
    } finally {
        File::deleteDirectory($path);
    }
});

test('regional routes load on a case sensitive filesystem', function () {
    expect(app('router')->getRoutes()->count())->toBeGreaterThan(100);
    expect(is_file(base_path('routes/RegionalAdmin.php')))->toBeTrue();
});

test('every registered controller action resolves to a public method', function () {
    $missing = [];
    foreach (app('router')->getRoutes() as $route) {
        $action = $route->getActionName();
        if (!str_contains($action, '@')) {
            continue;
        }
        [$class, $method] = explode('@', $action, 2);
        if (!method_exists($class, $method) || !(new ReflectionMethod($class, $method))->isPublic()) {
            $missing[] = $route->uri().' => '.$action;
        }
    }
    expect($missing)->toBe([]);
});

test('community feed exposes display identity but excludes private author fields', function () {
    $viewer = User::factory()->create(['access_level' => 'superadmin']);
    $author = User::factory()->create(['bank_account_number' => '1234567890', 'vin' => 'PRIVATE-VOTER-ID']);
    $post = Post::query()->create(['user_id' => $author->id, 'content' => 'Public post', 'audience' => 'public', 'audience_type' => 'global']);
    $this->actingAs($viewer);
    $response = app(HomeController::class)->fetchPosts(Request::create('/community/posts', 'GET', ['per_page' => 10000]));
    $payload = $response->getData(true);
    expect($payload['per_page'])->toBe(50)
        ->and($payload['data'][0]['id'])->toBe($post->id)
        ->and($payload['data'][0]['user']['username'])->toBe($author->username)
        ->and($payload['data'][0]['user'])->not->toHaveKeys(['email', 'phone', 'vin', 'bank_account_number', 'address']);
});
