<?php

use App\Http\Middleware\CheckUserOnboarding;
use App\Http\Middleware\KuyakEyenIno;
use App\Http\Middleware\ValidateLicense;
use App\Models\Conversation;
use App\Models\Country;
use App\Models\LocalLicense;
use App\Models\Region;
use App\Models\State;
use App\Models\SupportGroup;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    File::ensureDirectoryExists(storage_path('app'));
    File::put(storage_path('app/installed.lock'), json_encode(['deployment_mode' => 'self_hosted']));

    config([
        'app.url' => 'http://localhost/senate/public',
        'campaign.deployment_mode' => 'self_hosted',
        'broadcasting.default' => 'log',
    ]);

    SystemSetting::query()->updateOrCreate(['id' => 1], [
        'package' => 'governorship',
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'frontend_community' => 0,
        'frontend_registration' => 0,
        'require_bank_details' => 0,
        'enable_local_storage' => 1,
        'enable_s3_storage' => 0,
    ]);
});

afterEach(function () {
    if (File::exists(storage_path('app/installed.lock'))) {
        File::delete(storage_path('app/installed.lock'));
    }
});

function messagingState(int $id, string $name = 'State'): State
{
    Country::query()->firstOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Nigeria',
    ]);

    Region::query()->firstOrCreate(['id' => 1], [
        'name' => 'South South',
        'country_id' => 1,
    ]);

    return State::query()->firstOrCreate(['id' => $id], [
        'name' => "{$name} {$id}",
        'region_id' => 1,
    ]);
}

function messagingLicense(int $stateId = 9): void
{
    $license = LocalLicense::query()->create([
        'license_key' => 'CM-MESSAGING-SCOPE-'.Str::upper(Str::random(6)),
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
    ]);

    $license->scope()->create([
        'scope_type' => 'state',
        'scope_name' => 'Cross River',
        'state_id' => $stateId,
        'state_name' => 'Cross River',
    ]);

    $license->modules()->createMany([
        ['module_key' => 'members', 'enabled' => true],
        ['module_key' => 'community', 'enabled' => false],
    ]);
}

function messagingUser(string $email, array $attributes = []): User
{
    $user = User::withoutEvents(fn () => User::factory()->create(array_merge([
        'firstname' => 'Message',
        'lastname' => 'User',
        'username' => Str::slug(str_replace('@', '-', $email)),
        'email' => $email,
        'password' => Hash::make('secret-password'),
        'access_level' => 'superadmin',
        'status' => 'active',
        'requires_update' => false,
        'country_id' => 1,
        'region_id' => null,
        'state_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => null,
        'religion_id' => null,
        'age_grade_id' => null,
    ], $attributes)));

    $group = SupportGroup::query()->firstOrCreate(['name' => 'Messaging Test Group']);
    $user->supportGroups()->syncWithoutDetaching([$group->id]);

    return $user;
}

test('recipient endpoint returns only in-scope users for self hosted governorship', function () {
    messagingState(9, 'Cross River');
    messagingState(10, 'Akwa Ibom');
    messagingLicense(9);

    $sender = messagingUser('sender@test.local', ['state_id' => 9]);
    messagingUser('inside@test.local', ['access_level' => 'stateadmin', 'state_id' => 9]);
    messagingUser('outside@test.local', ['access_level' => 'stateadmin', 'state_id' => 10]);

    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);

    $response = $this->actingAs($sender)->getJson(route('messages.recipients'));

    $response->assertOk();
    $payload = collect($response->json('data'))->pluck('options')->flatten(1);

    expect($payload->pluck('username')->contains('inside-testlocal'))->toBeTrue()
        ->and($payload->pluck('username')->contains('outside-testlocal'))->toBeFalse();
});

test('creating conversation with out of scope recipient is rejected', function () {
    messagingState(9, 'Cross River');
    messagingState(10, 'Akwa Ibom');
    messagingLicense(9);

    $sender = messagingUser('sender2@test.local', ['state_id' => 9]);
    $outside = messagingUser('outside2@test.local', ['access_level' => 'stateadmin', 'state_id' => 10]);

    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);

    $this->actingAs($sender)
        ->postJson(route('messages.start'), [
            'recipient_id' => $outside->id,
            'message' => 'Hello',
        ])
        ->assertForbidden();
});

test('sender cannot message self', function () {
    messagingState(9, 'Cross River');
    messagingLicense(9);

    $sender = messagingUser('self@test.local', ['state_id' => 9]);

    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);

    $this->actingAs($sender)
        ->postJson(route('messages.start'), [
            'recipient_id' => $sender->id,
            'message' => 'Self note',
        ])
        ->assertForbidden();
});

test('message creation succeeds with log broadcast default', function () {
    messagingState(9, 'Cross River');
    messagingLicense(9);

    $sender = messagingUser('sender3@test.local', ['state_id' => 9]);
    $recipient = messagingUser('recipient3@test.local', ['access_level' => 'stateadmin', 'state_id' => 9]);

    $conversation = Conversation::query()->create([
        'created_by' => $sender->id,
        'type' => 'direct',
    ]);
    $conversation->participants()->sync([$sender->id, $recipient->id]);

    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);

    $this->actingAs($sender)
        ->postJson(route('messages.conversation.send', $conversation), ['message' => 'No Reverb required'])
        ->assertOk();

    expect($conversation->messages()->where('body', 'No Reverb required')->exists())->toBeTrue();
});

test('backend messages route is not blocked when community is disabled', function () {
    messagingState(9, 'Cross River');
    messagingLicense(9);

    $sender = messagingUser('backend@test.local', ['state_id' => 9]);

    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);

    $this->actingAs($sender)
        ->get(route('messages.page'))
        ->assertOk()
        ->assertSee('Internal Communication');
});

test('legacy livechat page redirects and ajax endpoints do not expose legacy data', function () {
    messagingState(9, 'Cross River');
    messagingLicense(9);

    $sender = messagingUser('legacy@test.local', ['state_id' => 9]);

    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);

    $this->actingAs($sender)
        ->get(route('superadmin.livechat.send'))
        ->assertRedirect(route('messages.page'));

    $this->actingAs($sender)
        ->getJson(route('superadmin.livechat.fetch.messages', ['receiver_id' => 999]))
        ->assertStatus(410);

    $this->actingAs($sender)
        ->postJson(route('superadmin.livechat.send.messages'), [
            'receiver_id' => 999,
            'message' => 'Legacy',
        ])
        ->assertStatus(410);
});
