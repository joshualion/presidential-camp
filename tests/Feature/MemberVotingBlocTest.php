<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\Country;
use App\Models\Referral;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\MemberVotingBlocService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

function votingBlocUser(array $attributes = []): User
{
    return User::query()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'username' => 'bloc-'.Str::lower(Str::random(10)),
        'email' => Str::lower(Str::random(10)).'@example.test',
        'phone' => '+234'.random_int(2000000000, 2999999999),
        'password' => Hash::make('Password1'),
        'validVoter' => 'no',
        'access_level' => 'user',
        'status' => 'active',
    ], $attributes));
}

test('voting bloc report counts direct and multi level member referrals consistently', function () {
    $root = votingBlocUser();
    $directEligible = votingBlocUser(['referred_by' => $root->id, 'validVoter' => 'yes']);
    votingBlocUser(['referred_by' => $root->id, 'validVoter' => 'no']);
    votingBlocUser(['referred_by' => $directEligible->id, 'validVoter' => 'yes']);
    votingBlocUser(['referred_by' => $root->id, 'access_level' => 'stateadmin']);

    $report = app(MemberVotingBlocService::class)->report($root);

    expect($report['direct_count'])->toBe(2)
        ->and($report['total_count'])->toBe(3)
        ->and($report['eligible_count'])->toBe(2)
        ->and($report['ineligible_count'])->toBe(1)
        ->and($report['levels']->all())->toBe([1 => 2, 2 => 1])
        ->and($report['truncated'])->toBeFalse();
});

test('voting bloc traversal does not duplicate members when legacy data contains a cycle', function () {
    $root = votingBlocUser();
    $child = votingBlocUser(['referred_by' => $root->id]);
    $grandchild = votingBlocUser(['referred_by' => $child->id]);
    $root->forceFill(['referred_by' => $grandchild->id])->save();

    $report = app(MemberVotingBlocService::class)->report($root);

    expect($report['total_count'])->toBe(2)
        ->and($report['members']->pluck('id')->all())->toBe([$child->id, $grandchild->id]);
});

test('direct referral page source is users referred by relationship rather than audit mirror', function () {
    $root = votingBlocUser();
    $child = votingBlocUser(['referred_by' => $root->id]);

    expect(Referral::query()->count())->toBe(0);

    $referrals = app(MemberVotingBlocService::class)->directReferrals($root);

    expect($referrals->total())->toBe(1)
        ->and($referrals->first()->id)->toBe($child->id);
});

test('member voting bloc and referral pages render with the shared profile context', function () {
    $capabilities = Mockery::mock(App\Services\MemberCapabilityService::class);
    $capabilities->shouldReceive('allows')->andReturnTrue();
    $capabilities->shouldReceive('isMember')->andReturnTrue();
    $capabilities->shouldReceive('denialMessage')->andReturn('Not available.');
    app()->instance(App\Services\MemberCapabilityService::class, $capabilities);
    SystemSetting::query()->create([
        'package' => 'governorship',
        'frontend_registration' => 1,
    ]);
    $member = votingBlocUser();

    $this->withoutMiddleware()->actingAs($member)
        ->get('/member/bloc')
        ->assertOk();

    $this->withoutMiddleware()->actingAs($member)
        ->get('/member/referrals')
        ->assertOk();
});

test('public referral registration writes parent relationship and audit mirror atomically', function () {
    $country = Country::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => 'Nigeria',
    ]);
    SystemSetting::query()->create([
        'package' => 'governorship',
        'system_country' => $country->name,
        'frontend_community' => 0,
        'frontend_registration' => 1,
    ]);
    Role::query()->create(['name' => 'Member', 'guard_name' => 'web', 'group_name' => 'user']);
    $referrer = votingBlocUser();
    Route::post('/phase-four-register', [RegisteredUserController::class, 'store'])->middleware('web');
    RateLimiter::clear('register:127.0.0.1');

    $response = $this->withSession(['logic_question_answer' => 4])->post('/phase-four-register', [
        'username' => 'new-referred-member',
        'firstname' => 'New',
        'lastname' => 'Member',
        'email' => 'new-referred@example.test',
        'password' => 'SecurePassword1',
        'password_confirmation' => 'SecurePassword1',
        'logic_question' => '4',
        'referral_code' => $referrer->referral_code,
    ]);

    $response
        ->assertRedirect(route('user.account.complete-profile'))
        ->assertSessionHas('success', 'Registration successful. Welcome new-referred-member. Please complete your profile.');
    $this->assertAuthenticated();
    $child = User::query()->where('email', 'new-referred@example.test')->firstOrFail();
    $audit = Referral::query()->where('referee_id', $child->id)->firstOrFail();

    expect($child->referred_by)->toBe($referrer->id)
        ->and($audit->referrer_id)->toBe($referrer->id)
        ->and($audit->accepted_at)->not->toBeNull()
        ->and($child->hasRole('Member'))->toBeTrue();
});
