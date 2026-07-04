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
use Illuminate\Support\Facades\DB;
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

function messagingPollingUnit(int $stateId = 9): array
{
    DB::table('local_government_areas')->insert([
        'id' => 901,
        'uuid' => (string) Str::uuid(),
        'name' => 'Messaging LGA',
        'state_id' => $stateId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('wards')->insert([
        'id' => 902,
        'uuid' => (string) Str::uuid(),
        'name' => 'Messaging Ward',
        'lga_id' => 901,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('polling_units')->insert([
        'id' => 903,
        'uuid' => (string) Str::uuid(),
        'name' => 'Messaging Polling Unit',
        'ward_id' => 902,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return ['lga_id' => 901, 'ward_id' => 902, 'polling_unit_id' => 903];
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

    $response = $this->actingAs($sender)->getJson(route($sender->access_level.'.messages.recipients'));

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
        ->postJson(route($sender->access_level.'.messages.start'), [
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
        ->postJson(route($sender->access_level.'.messages.start'), [
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
        ->postJson(route($sender->access_level.'.messages.conversation.send', $conversation), ['message' => 'No Reverb required'])
        ->assertOk();

    expect($conversation->messages()->where('body', 'No Reverb required')->exists())->toBeTrue();
});

test('lower level participant can reply to existing higher initiated conversation', function () {
    messagingState(9, 'Cross River');
    messagingLicense(9);
    $memberLocation = messagingPollingUnit(9);

    $higher = messagingUser('higher@test.local', ['access_level' => 'stateadmin', 'state_id' => 9]);
    $lower = messagingUser('lower@test.local', array_merge(['access_level' => 'user', 'state_id' => 9], $memberLocation));

    $conversation = Conversation::query()->create([
        'created_by' => $higher->id,
        'type' => 'direct',
    ]);
    $conversation->participants()->sync([$higher->id, $lower->id]);

    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);

    $this->actingAs($lower)
        ->postJson(route($lower->access_level.'.messages.conversation.send', $conversation), ['message' => 'Reply upward'])
        ->assertOk();

    expect($conversation->messages()->where('sender_id', $lower->id)->where('body', 'Reply upward')->exists())->toBeTrue();
});

test('lower level user cannot initiate upward conversation', function () {
    messagingState(9, 'Cross River');
    messagingLicense(9);
    $memberLocation = messagingPollingUnit(9);

    $higher = messagingUser('higher-init@test.local', ['access_level' => 'stateadmin', 'state_id' => 9]);
    $lower = messagingUser('lower-init@test.local', array_merge(['access_level' => 'user', 'state_id' => 9], $memberLocation));

    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);

    $this->actingAs($lower)
        ->postJson(route($lower->access_level.'.messages.start'), [
            'recipient_id' => $higher->id,
            'message' => 'Can I start upward?',
        ])
        ->assertForbidden();
});

test('unread message count returns count for authenticated participant', function () {
    messagingState(9, 'Cross River');
    messagingLicense(9);
    $memberLocation = messagingPollingUnit(9);

    $sender = messagingUser('unread-sender@test.local', ['access_level' => 'stateadmin', 'state_id' => 9]);
    $recipient = messagingUser('unread-recipient@test.local', array_merge(['access_level' => 'user', 'state_id' => 9], $memberLocation));

    $conversation = Conversation::query()->create([
        'created_by' => $sender->id,
        'type' => 'direct',
    ]);
    $conversation->participants()->sync([$sender->id, $recipient->id]);
    $conversation->messages()->create(['sender_id' => $sender->id, 'body' => 'Unread one']);
    $conversation->messages()->create(['sender_id' => $recipient->id, 'body' => 'Own message']);

    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);

    $this->actingAs($recipient)
        ->getJson(route($recipient->access_level.'.messages.unread-count'))
        ->assertOk()
        ->assertJson(['unread' => 1]);
});

test('backend messages route is not blocked when community is disabled', function () {
    messagingState(9, 'Cross River');
    messagingLicense(9);

    $sender = messagingUser('backend@test.local', ['state_id' => 9]);

    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);

    $this->actingAs($sender)
        ->get(route($sender->access_level.'.messages.page'))
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
        ->assertRedirect(route($sender->access_level.'.messages.page'));

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
