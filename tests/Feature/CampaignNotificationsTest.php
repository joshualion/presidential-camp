<?php

use App\Models\CampaignNotification;
use App\Models\CampaignNotificationRecipient;
use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\PollingUnit;
use App\Models\User;
use App\Services\Notifications\CampaignNotificationService;
use App\Services\Notifications\NotificationContext;
use App\Services\Notifications\NotificationContextResolver;
use App\Services\Notifications\NotificationRecipientResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function campaignNotificationLocationFixture(): array
{
    $now = now();

    DB::table('countries')->insert([
        ['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Nigeria', 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::table('regions')->insert([
        ['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'South East', 'country_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'uuid' => (string) Str::uuid(), 'name' => 'South South', 'country_id' => 1, 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::table('states')->insert([
        ['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Anambra', 'region_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'uuid' => (string) Str::uuid(), 'name' => 'Cross River', 'region_id' => 2, 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::table('senatorial_districts')->insert([
        ['id' => 1, 'uuid' => (string) Str::uuid(), 'state_id' => 1, 'name' => 'Anambra North', 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::table('federal_constituencies')->insert([
        ['id' => 1, 'uuid' => (string) Str::uuid(), 'state_id' => 1, 'senatorial_district_id' => 1, 'name' => 'Awka Federal', 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::table('local_government_areas')->insert([
        ['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Awka South', 'state_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'uuid' => (string) Str::uuid(), 'name' => 'Calabar Municipal', 'state_id' => 2, 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::table('wards')->insert([
        ['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Ward 04', 'lga_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'uuid' => (string) Str::uuid(), 'name' => 'Ward 09', 'lga_id' => 2, 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::table('polling_units')->insert([
        [
            'id' => 1,
            'uuid' => (string) Str::uuid(),
            'name' => 'PU 21/04/08/003',
            'ward_id' => 1,
            'senatorial_district_id' => 1,
            'federal_constituency_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'id' => 2,
            'uuid' => (string) Str::uuid(),
            'name' => 'PU CR/01',
            'ward_id' => 2,
            'senatorial_district_id' => null,
            'federal_constituency_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    return [
        'anambra' => NotificationContext::fromArray([
            'region_id' => 1,
            'state_id' => 1,
            'senatorial_district_id' => 1,
            'federal_constituency_id' => 1,
            'lga_id' => 1,
            'ward_id' => 1,
            'polling_unit_id' => 1,
        ]),
        'cross_river' => NotificationContext::fromArray([
            'region_id' => 2,
            'state_id' => 2,
            'lga_id' => 2,
            'ward_id' => 2,
            'polling_unit_id' => 2,
        ]),
    ];
}

function campaignNotificationUser(string $accessLevel, array $attributes = []): User
{
    return User::withoutEvents(fn () => User::factory()->create(array_merge([
        'country_id' => null,
        'region_id' => null,
        'state_id' => null,
        'senatorial_district_id' => null,
        'federal_constituency_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => null,
        'religion_id' => null,
        'age_grade_id' => null,
        'access_level' => $accessLevel,
        'status' => 'active',
    ], $attributes)));
}

test('recipient resolver delivers upward within jurisdiction only', function () {
    $locations = campaignNotificationLocationFixture();

    $super = campaignNotificationUser('superadmin');
    $national = campaignNotificationUser('nationaladmin');
    $southEast = campaignNotificationUser('regionaladmin', ['region_id' => 1]);
    $southSouth = campaignNotificationUser('regionaladmin', ['region_id' => 2]);
    $anambra = campaignNotificationUser('stateadmin', ['state_id' => 1]);
    $crossRiver = campaignNotificationUser('stateadmin', ['state_id' => 2]);
    $senatorial = campaignNotificationUser('senatorialadmin', ['senatorial_district_id' => 1]);
    $federal = campaignNotificationUser('federaladmin', ['federal_constituency_id' => 1]);
    $lga = campaignNotificationUser('lgaadmin', ['lga_id' => 1]);
    $ward = campaignNotificationUser('wardadmin', ['ward_id' => 1]);
    $pu = campaignNotificationUser('puadmin', ['polling_unit_id' => 1]);
    $sidewaysPu = campaignNotificationUser('puadmin', ['polling_unit_id' => 2]);
    $inactive = campaignNotificationUser('stateadmin', ['state_id' => 1, 'status' => 'inactive']);
    $member = campaignNotificationUser('user', ['state_id' => 1]);
    $actor = campaignNotificationUser('stateadmin', ['state_id' => 1]);

    $ids = app(NotificationRecipientResolver::class)
        ->resolve('result.uploaded', $actor, $locations['anambra'])
        ->pluck('id');

    expect($ids)->toContain($super->id, $national->id, $southEast->id, $anambra->id, $senatorial->id, $federal->id, $lga->id, $ward->id, $pu->id)
        ->and($ids)->not->toContain($southSouth->id, $crossRiver->id, $sidewaysPu->id, $inactive->id, $member->id, $actor->id);
});

test('evidence notification inherits incident location', function () {
    campaignNotificationLocationFixture();

    $incident = new ElectionIncident([
        'region_id' => 1,
        'state_id' => 1,
        'senatorial_district_id' => 1,
        'federal_constituency_id' => 1,
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
        'incident_type' => 'security',
        'severity' => 'warning',
        'status' => 'open',
    ]);

    $evidence = new PictureEvidence([
        'file_path' => 'evidence.jpg',
    ]);
    $evidence->setRelation('incident', $incident);

    $context = app(NotificationContextResolver::class)->resolve($evidence);

    expect($context->stateId)->toBe(1)
        ->and($context->pollingUnitId)->toBe(1);
});

test('read mark all dismiss and ownership checks are user scoped', function () {
    campaignNotificationLocationFixture();

    $user = campaignNotificationUser('stateadmin', ['state_id' => 1]);
    $otherUser = campaignNotificationUser('stateadmin', ['state_id' => 1]);

    $notification = CampaignNotification::create([
        'type' => 'incident.created',
        'title' => 'New incident reported',
        'message' => 'An incident was recorded.',
        'severity' => 'warning',
        'state_id' => 1,
        'occurred_at' => now(),
        'expires_at' => now()->addHours(24),
    ]);

    CampaignNotificationRecipient::create(['notification_id' => $notification->id, 'user_id' => $user->id]);
    CampaignNotificationRecipient::create(['notification_id' => $notification->id, 'user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->getJson(route('stateadmin.campaign.notifications.unread'))
        ->assertOk()
        ->assertJson(['unread' => 1]);

    $this->actingAs($user)
        ->withSession(['_token' => 'test-token'])
        ->postJson(route('stateadmin.campaign.notifications.read', $notification), ['_token' => 'test-token'])
        ->assertOk()
        ->assertJson(['unread_count' => 0]);

    expect(CampaignNotificationRecipient::where('user_id', $user->id)->first()->read_at)->not->toBeNull()
        ->and(CampaignNotificationRecipient::where('user_id', $otherUser->id)->first()->read_at)->toBeNull();

    $this->actingAs($user)
        ->withSession(['_token' => 'test-token'])
        ->postJson(route('stateadmin.campaign.notifications.dismiss', $notification), ['_token' => 'test-token'])
        ->assertOk();

    expect(CampaignNotificationRecipient::where('user_id', $user->id)->first()->dismissed_at)->not->toBeNull()
        ->and(CampaignNotificationRecipient::where('user_id', $otherUser->id)->first()->dismissed_at)->toBeNull();

    $otherNotification = CampaignNotification::create([
        'type' => 'result.uploaded',
        'title' => 'Other user notification',
        'message' => 'Private notification.',
        'severity' => 'info',
        'state_id' => 1,
        'occurred_at' => now(),
        'expires_at' => now()->addHours(24),
    ]);
    CampaignNotificationRecipient::create(['notification_id' => $otherNotification->id, 'user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->withSession(['_token' => 'test-token'])
        ->postJson(route('stateadmin.campaign.notifications.read', $otherNotification), ['_token' => 'test-token'])
        ->assertNotFound();
});

test('expired notifications do not appear in count or unread list and duplicates are ignored', function () {
    campaignNotificationLocationFixture();

    $user = campaignNotificationUser('stateadmin', ['state_id' => 1]);
    $active = CampaignNotification::create([
        'type' => 'result.uploaded',
        'title' => 'Active',
        'message' => 'Active notification.',
        'severity' => 'info',
        'state_id' => 1,
        'occurred_at' => now(),
        'expires_at' => now()->addHours(24),
    ]);
    $expired = CampaignNotification::create([
        'type' => 'incident.created',
        'title' => 'Expired',
        'message' => 'Expired notification.',
        'severity' => 'warning',
        'state_id' => 1,
        'occurred_at' => now()->subDays(2),
        'expires_at' => now()->subHour(),
    ]);

    CampaignNotificationRecipient::insertOrIgnore([
        ['notification_id' => $active->id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
        ['notification_id' => $active->id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
        ['notification_id' => $expired->id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    expect(CampaignNotificationRecipient::where('notification_id', $active->id)->where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)
        ->getJson(route('stateadmin.campaign.notifications.index', ['unread' => 1]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Active')
        ->assertJsonPath('unread_count', 1);
});
