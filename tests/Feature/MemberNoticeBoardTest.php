<?php

use App\Models\Announcement;
use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use App\Services\AnnouncementAudienceService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

function noticeLocationTree(string $suffix): array
{
    $country = Country::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Country '.$suffix]);
    $region = Region::query()->create(['name' => 'Region '.$suffix, 'country_id' => $country->id]);
    $state = State::query()->create(['name' => 'State '.$suffix, 'region_id' => $region->id]);
    $senatorial = SenatorialDistrict::query()->create(['state_id' => $state->id, 'name' => 'Senate '.$suffix, 'normalized_name' => 'senate-'.$suffix]);
    $federal = FederalConstituency::query()->create([
        'state_id' => $state->id,
        'senatorial_district_id' => $senatorial->id,
        'name' => 'Federal '.$suffix,
        'normalized_name' => 'federal-'.$suffix,
    ]);
    $lga = LocalGovernmentArea::query()->create([
        'name' => 'LGA '.$suffix,
        'state_id' => $state->id,
        'senatorial_district_id' => $senatorial->id,
        'federal_constituency_id' => $federal->id,
    ]);
    $ward = Ward::query()->create(['name' => 'Ward '.$suffix, 'lga_id' => $lga->id]);
    $pollingUnit = PollingUnit::query()->create([
        'name' => 'PU '.$suffix,
        'ward_id' => $ward->id,
        'senatorial_district_id' => $senatorial->id,
        'federal_constituency_id' => $federal->id,
    ]);

    return compact('country', 'region', 'state', 'senatorial', 'federal', 'lga', 'ward', 'pollingUnit');
}

function noticeUser(array $attributes = []): User
{
    return User::query()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'username' => 'notice-'.Str::lower(Str::random(8)),
        'email' => Str::lower(Str::random(8)).'@example.test',
        'phone' => '+234'.random_int(3000000000, 3999999999),
        'password' => Hash::make('Password1'),
        'access_level' => 'user',
        'status' => 'active',
    ], $attributes));
}

function noticeRecord(array $attributes = []): Announcement
{
    return Announcement::query()->create(array_merge([
        'title' => 'Campaign notice '.Str::random(5),
        'message' => 'Important campaign information.',
        'audience' => 'public',
        'scope_type' => 'public',
        'is_active' => true,
        'priority' => 1,
        'published_at' => now()->subMinute(),
    ], $attributes));
}

test('notice board returns only active current notices matching every member boundary', function () {
    $inside = noticeLocationTree('inside');
    $outside = noticeLocationTree('outside');
    $member = noticeUser([
        'region_id' => $inside['region']->id,
        'state_id' => $inside['state']->id,
        'senatorial_district_id' => $inside['senatorial']->id,
        'federal_constituency_id' => $inside['federal']->id,
        'lga_id' => $inside['lga']->id,
        'ward_id' => $inside['ward']->id,
        'polling_unit_id' => $inside['pollingUnit']->id,
    ]);

    noticeRecord(['title' => 'Public']);
    noticeRecord(['title' => 'Region', 'scope_type' => 'region', 'region_id' => $inside['region']->id]);
    noticeRecord(['title' => 'State', 'scope_type' => 'state', 'state_id' => $inside['state']->id]);
    noticeRecord(['title' => 'Senate', 'scope_type' => 'senatorial', 'senatorial_district_id' => $inside['senatorial']->id]);
    noticeRecord(['title' => 'Federal', 'scope_type' => 'federal', 'federal_constituency_id' => $inside['federal']->id]);
    noticeRecord(['title' => 'LGA', 'scope_type' => 'lga', 'lga_id' => $inside['lga']->id]);
    noticeRecord(['title' => 'Ward', 'scope_type' => 'ward', 'ward_id' => $inside['ward']->id]);
    noticeRecord(['title' => 'PU', 'scope_type' => 'polling_unit', 'polling_unit_id' => $inside['pollingUnit']->id]);
    noticeRecord(['title' => 'Outside', 'scope_type' => 'state', 'state_id' => $outside['state']->id]);
    noticeRecord(['title' => 'Inactive', 'is_active' => false]);
    noticeRecord(['title' => 'Future', 'published_at' => now()->addDay()]);
    noticeRecord(['title' => 'Expired', 'expires_at' => now()->subMinute()]);

    $titles = app(AnnouncementAudienceService::class)->visibleToMember($member)->pluck('title')->all();

    expect($titles)->toHaveCount(8)
        ->toContain('Public', 'Region', 'State', 'Senate', 'Federal', 'LGA', 'Ward', 'PU')
        ->not->toContain('Outside', 'Inactive', 'Future', 'Expired');
});

test('publisher scope is derived from administrator jurisdiction and cannot be expanded by request', function () {
    $inside = noticeLocationTree('publisher');
    $outside = noticeLocationTree('forged');
    $publisher = noticeUser([
        'access_level' => 'stateadmin',
        'region_id' => $inside['region']->id,
        'state_id' => $inside['state']->id,
        'polling_unit_id' => $inside['pollingUnit']->id,
    ]);
    $this->withoutMiddleware()->actingAs($publisher);

    $this->post(route('stateadmin.announcements.store'), [
        'title' => 'Scoped notice',
        'message' => 'Only the assigned state should receive this.',
        'priority' => 2,
        'is_active' => 1,
        'published_at' => now()->format('Y-m-d H:i:s'),
        'scope_type' => 'state',
        'state_id' => $outside['state']->id,
    ])->assertRedirect(route('stateadmin.announcements.index'));

    $announcement = Announcement::query()->where('title', 'Scoped notice')->firstOrFail();
    expect($announcement->created_by)->toBe($publisher->id)
        ->and($announcement->scope_type)->toBe('state')
        ->and($announcement->state_id)->toBe($inside['state']->id)
        ->and($announcement->state_id)->not->toBe($outside['state']->id);
});

test('every administrator level publishes to its exact assigned jurisdiction', function () {
    $tree = noticeLocationTree('levels');
    $service = app(AnnouncementAudienceService::class);
    $cases = [
        'regionaladmin' => ['region_id', $tree['region']->id, 'region'],
        'stateadmin' => ['state_id', $tree['state']->id, 'state'],
        'senatorialadmin' => ['senatorial_district_id', $tree['senatorial']->id, 'senatorial'],
        'federaladmin' => ['federal_constituency_id', $tree['federal']->id, 'federal'],
        'lgaadmin' => ['lga_id', $tree['lga']->id, 'lga'],
        'wardadmin' => ['ward_id', $tree['ward']->id, 'ward'],
        'puadmin' => ['polling_unit_id', $tree['pollingUnit']->id, 'polling_unit'],
    ];

    foreach ($cases as $level => [$column, $id, $scopeType]) {
        $publisher = (new User())->forceFill([
            'access_level' => $level,
            'status' => 'active',
            'region_id' => $tree['region']->id,
            'state_id' => $tree['state']->id,
            'senatorial_district_id' => $tree['senatorial']->id,
            'federal_constituency_id' => $tree['federal']->id,
            'lga_id' => $tree['lga']->id,
            'ward_id' => $tree['ward']->id,
            'polling_unit_id' => $tree['pollingUnit']->id,
        ]);
        $scope = $service->publicationScope($publisher);

        expect($scope['scope_type'])->toBe($scopeType)
            ->and($scope[$column])->toBe($id);
    }

    $national = (new User())->forceFill(['access_level' => 'superadmin', 'status' => 'active']);
    expect($service->publicationScope($national)['scope_type'])->toBe('public');
});

test('lower administrators manage only their own notices while superadmin can manage all', function () {
    $tree = noticeLocationTree('management');
    $first = noticeUser(['access_level' => 'lgaadmin', 'lga_id' => $tree['lga']->id]);
    $second = noticeUser(['access_level' => 'lgaadmin', 'lga_id' => $tree['lga']->id]);
    $superadmin = noticeUser(['access_level' => 'superadmin']);
    $announcement = noticeRecord(['created_by' => $first->id, 'scope_type' => 'lga', 'lga_id' => $tree['lga']->id]);
    $service = app(AnnouncementAudienceService::class);

    expect($service->canManage($first, $announcement))->toBeTrue()
        ->and($service->canManage($second, $announcement))->toBeFalse()
        ->and($service->canManage($superadmin, $announcement))->toBeTrue()
        ->and($service->manageableBy($second)->count())->toBe(0)
        ->and($service->manageableBy($superadmin)->count())->toBe(1);
});

test('announcement management and member notice routes enforce their contracts', function () {
    expect(Route::getRoutes()->getByName('stateadmin.announcements.index')->gatherMiddleware())
        ->toContain('access_level:stateadmin', 'announcement.manager')
        ->and(Route::getRoutes()->getByName('user.notices')->gatherMiddleware())
        ->toContain('member.capability:announcements.board')
        ->and(Route::getRoutes()->getByName('user.notices.show')->gatherMiddleware())
        ->toContain('member.capability:announcements.board');
});

test('notice detail urls are uuid based and namespaced for every access level', function () {
    $announcement = noticeRecord();
    $paths = [
        'user.notices.show' => '/member/notices/',
        'puadmin.notices.show' => '/pu/notices/',
        'wardadmin.notices.show' => '/ward/notices/',
        'lgaadmin.notices.show' => '/lga/notices/',
        'federaladmin.notices.show' => '/federal/notices/',
        'senatorialadmin.notices.show' => '/senatorial/notices/',
        'stateadmin.notices.show' => '/state/notices/',
        'regionaladmin.notices.show' => '/regional/notices/',
        'nationaladmin.notices.show' => '/national/notices/',
        'superadmin.notices.show' => '/superadmin/notices/',
    ];

    expect($announcement->getRouteKeyName())->toBe('uuid')
        ->and($announcement->uuid)->not->toBeNull();

    foreach ($paths as $routeName => $prefix) {
        $url = route($routeName, $announcement, false);
        expect($url)->toBe($prefix.$announcement->uuid);
    }
});

test('legacy announcements with a null uuid are repaired when loaded', function () {
    $announcement = noticeRecord();
    DB::table('announcements')->where('id', $announcement->id)->update(['uuid' => null]);

    $legacy = Announcement::query()->findOrFail($announcement->id);

    expect($legacy->uuid)->not->toBeNull()
        ->and(DB::table('announcements')->where('id', $announcement->id)->value('uuid'))->toBe($legacy->uuid)
        ->and(route('user.notices.show', $legacy, false))->toBe('/member/notices/'.$legacy->uuid);
});
