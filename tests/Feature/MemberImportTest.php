<?php

use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\LocalLicense;
use App\Models\MemberImportBatch;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Ward;
use App\Services\MemberImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function memberImportLocations(): array
{
    $country = Country::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    $region = Region::query()->create(['name' => 'South West', 'country_id' => $country->id]);
    $state = State::query()->create(['name' => 'Lagos', 'region_id' => $region->id]);
    $otherState = State::query()->create(['name' => 'Ogun', 'region_id' => $region->id]);
    $senatorial = SenatorialDistrict::query()->create(['state_id' => $state->id, 'name' => 'Lagos Central', 'normalized_name' => 'lagos central']);
    $federal = FederalConstituency::query()->create(['state_id' => $state->id, 'senatorial_district_id' => $senatorial->id, 'name' => 'Lagos Island I', 'normalized_name' => 'lagos island i']);
    $lga = LocalGovernmentArea::query()->create(['name' => 'Lagos Island', 'state_id' => $state->id, 'senatorial_district_id' => $senatorial->id, 'federal_constituency_id' => $federal->id]);
    $ward = Ward::query()->create(['name' => 'Ward A', 'lga_id' => $lga->id]);
    $pollingUnit = PollingUnit::query()->create([
        'name' => 'PU 001',
        'ward_id' => $ward->id,
        'inec_pu_code' => '001',
        'inec_full_code' => 'LA/001/001',
        'senatorial_district_id' => $senatorial->id,
        'federal_constituency_id' => $federal->id,
    ]);
    $otherLga = LocalGovernmentArea::query()->create(['name' => 'Abeokuta South', 'state_id' => $otherState->id]);

    return compact('country', 'region', 'state', 'otherState', 'senatorial', 'federal', 'lga', 'ward', 'pollingUnit', 'otherLga');
}

function memberImportRole(string $name = 'Member', string $group = 'user'): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ], [
        'group_name' => $group,
    ]);
}

function memberImportActor(array $attributes = []): User
{
    return User::query()->create(array_merge([
        'firstname' => 'Import',
        'lastname' => 'Admin',
        'username' => 'import_admin_'.Str::random(6),
        'email' => 'import_admin_'.Str::random(6).'@example.com',
        'password' => 'password',
        'access_level' => 'superadmin',
        'status' => 'active',
        'requires_update' => false,
    ], $attributes));
}

function memberImportSettings(): void
{
    SystemSetting::query()->create([
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'copyright' => null,
        'campaign_slogan' => '',
    ]);
}

function memberImportCsv(array $rows): string
{
    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, MemberImportService::HEADERS);
    foreach ($rows as $row) {
        fputcsv($handle, array_map(fn ($header) => $row[$header] ?? '', MemberImportService::HEADERS));
    }
    rewind($handle);

    return stream_get_contents($handle);
}

function memberImportFile(array $rows): UploadedFile
{
    return UploadedFile::fake()->createWithContent('members.csv', memberImportCsv($rows));
}

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('authorized admin can access import page and download csv template', function () {
    memberImportRole();
    memberImportSettings();
    $actor = memberImportActor(['access_level' => 'nationaladmin']);

    $this->withoutMiddleware()
        ->actingAs($actor)
        ->get(route('nationaladmin.member.import'))
        ->assertOk()
        ->assertSee('Import Members')
        ->assertSee('Download CSV Template');

    $template = $this->withoutMiddleware()
        ->actingAs($actor)
        ->get(route('nationaladmin.member.import.template'))
        ->assertOk();

    expect($template->streamedContent())->toContain('firstname')
        ->not->toContain('state_id');
});

test('valid csv imports a scoped member and records report counts', function () {
    $locations = memberImportLocations();
    memberImportRole();
    memberImportSettings();
    $actor = memberImportActor(['access_level' => 'nationaladmin']);

    $response = $this->withoutMiddleware()->actingAs($actor)->post(route('nationaladmin.member.import.store'), [
        'import_file' => memberImportFile([[
            'firstname' => 'Ada',
            'lastname' => 'Nwosu',
            'username' => 'ada.nwosu',
            'email' => 'ada@example.com',
            'phone' => '08030000001',
            'gender' => 'female',
            'access_level' => 'user',
            'role' => 'Member',
            'state' => 'Lagos',
            'lga' => 'Lagos Island',
            'ward' => 'Ward A',
            'polling_unit' => 'LA/001/001',
            'valid_voter' => 'yes',
        ]]),
    ]);

    $response->assertOk()->assertSee('Import completed successfully');

    $user = User::query()->where('email', 'ada@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->polling_unit_id)->toBe($locations['pollingUnit']->id)
        ->and((bool) $user->requires_update)->toBeTrue()
        ->and($user->hasRole('Member'))->toBeTrue();

    $batch = MemberImportBatch::query()->latest('id')->first();
    expect($batch->imported_rows)->toBe(1)
        ->and($batch->failed_rows)->toBe(0)
        ->and($batch->skipped_rows)->toBe(0);
});

test('duplicate email rows are skipped without overwriting existing users', function () {
    memberImportLocations();
    memberImportRole();
    $actor = memberImportActor();
    User::query()->create([
        'firstname' => 'Existing',
        'lastname' => 'Duplicate',
        'username' => 'existing_duplicate',
        'email' => 'duplicate@example.com',
        'password' => 'password',
        'access_level' => 'user',
        'status' => 'active',
    ]);

    app(MemberImportService::class)->import($actor, memberImportFile([[
        'firstname' => 'Bad',
        'lastname' => 'Duplicate',
        'username' => 'bad.duplicate',
        'email' => 'duplicate@example.com',
        'gender' => 'male',
        'access_level' => 'user',
        'polling_unit' => 'LA/001/001',
    ]]));

    $batch = MemberImportBatch::query()->latest('id')->first();
    expect($batch->imported_rows)->toBe(0)
        ->and($batch->skipped_rows)->toBe(1)
        ->and(User::query()->where('email', 'duplicate@example.com')->count())->toBe(1);
});

test('invalid access level role and superadmin import are rejected', function () {
    memberImportLocations();
    memberImportRole();
    $actor = memberImportActor();

    app(MemberImportService::class)->import($actor, memberImportFile([
        [
            'firstname' => 'Root',
            'lastname' => 'Admin',
            'username' => 'root.admin',
            'email' => 'root@example.com',
            'gender' => 'male',
            'access_level' => 'superadmin',
            'polling_unit' => 'LA/001/001',
        ],
        [
            'firstname' => 'Bad',
            'lastname' => 'Role',
            'username' => 'bad.role',
            'email' => 'badrole@example.com',
            'gender' => 'female',
            'access_level' => 'user',
            'role' => 'Unknown Role',
            'polling_unit' => 'LA/001/001',
        ],
    ]));

    $batch = MemberImportBatch::query()->latest('id')->first();
    expect($batch->imported_rows)->toBe(0)
        ->and($batch->failed_rows)->toBe(2)
        ->and(User::query()->whereIn('email', ['root@example.com', 'badrole@example.com'])->count())->toBe(0);
});

test('state scoped license rejects out of scope locations', function () {
    $locations = memberImportLocations();
    memberImportRole();

    $license = LocalLicense::query()->create([
        'license_key' => 'CM-MEMBER-IMPORT-SCOPE',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
    ]);
    $license->scope()->create([
        'scope_type' => 'state',
        'scope_name' => 'Lagos',
        'state_id' => $locations['state']->id,
        'state_name' => 'Lagos',
    ]);

    $actor = memberImportActor(['access_level' => 'stateadmin', 'state_id' => $locations['state']->id]);

    app(MemberImportService::class)->import($actor, memberImportFile([[
        'firstname' => 'Out',
        'lastname' => 'Scope',
        'username' => 'out.scope',
        'email' => 'outscope@example.com',
        'gender' => 'male',
        'access_level' => 'user',
        'state' => 'Ogun',
        'lga' => 'Abeokuta South',
        'polling_unit' => 'PU 001',
    ]]));

    $batch = MemberImportBatch::query()->latest('id')->first();
    expect($batch->imported_rows)->toBe(0)
        ->and($batch->failed_rows)->toBe(1)
        ->and(User::query()->where('email', 'outscope@example.com')->exists())->toBeFalse();
});
