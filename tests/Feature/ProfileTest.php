<?php

use App\Models\User;
use App\Services\AccessLevelRouteService;
use Illuminate\Support\Facades\Route;

test('generic breeze profile endpoint is not part of campaign manager routing', function () {
    expect(Route::has('profile.edit'))->toBeFalse()
        ->and(Route::has('profile.update'))->toBeFalse()
        ->and(Route::has('profile.destroy'))->toBeFalse();

    $this->actingAs(User::factory()->create(['access_level' => 'user']))
        ->get('/profile')
        ->assertNotFound();
});

test('member account profile uses the access-level prefixed route contract', function () {
    $routes = app(AccessLevelRouteService::class);
    $user = User::factory()->create(['access_level' => 'user', 'requires_update' => false]);

    expect($routes->sharedRouteForUser($user, 'profile'))->toBe('user.profile')
        ->and(Route::getRoutes()->getByName('user.profile')->uri())->toBe('member/profile')
        ->and(Route::getRoutes()->getByName('user.profile.store')->uri())->toBe('member/profile/store');
});