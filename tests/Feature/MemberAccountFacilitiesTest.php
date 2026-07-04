<?php

use App\Models\Country;
use App\Models\AgeGrade;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\Religion;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use App\Services\LicensedScopeQueryService;
use App\Services\LocationSelectionValidationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

function memberAccountLocationTree(string $suffix = ''): array
{
    $country = Country::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Nigeria '.$suffix]);
    $region = Region::query()->create(['name' => 'Region '.$suffix, 'country_id' => $country->id]);
    $state = State::query()->create(['name' => 'State '.$suffix, 'region_id' => $region->id]);
    $senatorial = SenatorialDistrict::query()->create([
        'state_id' => $state->id,
        'name' => 'District '.$suffix,
        'normalized_name' => 'district-'.$suffix,
    ]);
    $federal = FederalConstituency::query()->create([
        'state_id' => $state->id,
        'senatorial_district_id' => $senatorial->id,
        'name' => 'Constituency '.$suffix,
        'normalized_name' => 'constituency-'.$suffix,
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

function memberAccountUser(array $attributes = []): User
{
    return User::query()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'username' => 'member-'.Str::lower(Str::random(8)),
        'email' => Str::lower(Str::random(8)).'@example.test',
        'phone' => '+234'.random_int(1000000000, 1999999999),
        'password' => Hash::make('OldPassword1'),
        'access_level' => 'user',
        'status' => 'active',
    ], $attributes));
}

test('member location validation returns canonical electoral boundaries', function () {
    $tree = memberAccountLocationTree('one');
    $scope = Mockery::mock(LicensedScopeQueryService::class);
    $scope->shouldReceive('payloadErrors')->once()->andReturn([]);

    $location = (new LocationSelectionValidationService($scope))->validateMemberSelection([
        'region_id' => $tree['region']->id,
        'state_id' => $tree['state']->id,
        'lga_id' => $tree['lga']->id,
        'ward_id' => $tree['ward']->id,
        'polling_unit_id' => $tree['pollingUnit']->id,
    ]);

    expect($location)
        ->state_id->toBe($tree['state']->id)
        ->senatorial_district_id->toBe($tree['senatorial']->id)
        ->federal_constituency_id->toBe($tree['federal']->id)
        ->polling_unit_id->toBe($tree['pollingUnit']->id);
});

test('member location validation rejects a mixed hierarchy', function () {
    $first = memberAccountLocationTree('first');
    $second = memberAccountLocationTree('second');
    $scope = Mockery::mock(LicensedScopeQueryService::class);
    $scope->shouldReceive('payloadErrors')->once()->andReturn([]);

    expect(fn () => (new LocationSelectionValidationService($scope))->validateMemberSelection([
        'region_id' => $first['region']->id,
        'state_id' => $first['state']->id,
        'lga_id' => $second['lga']->id,
        'ward_id' => $second['ward']->id,
        'polling_unit_id' => $second['pollingUnit']->id,
    ]))->toThrow(ValidationException::class);
});

test('member password change enforces strength and verifies the old password', function () {
    $member = memberAccountUser();
    $this->withoutMiddleware()->actingAs($member);

    $this->from(route('user.change.password'))->post(route('user.update.password'), [
        'old_password' => 'OldPassword1',
        'new_password' => 'weak',
        'new_password_confirmation' => 'weak',
    ])->assertRedirect(route('user.change.password'))->assertSessionHasErrors('new_password');

    expect(Hash::check('OldPassword1', $member->fresh()->password))->toBeTrue();

    $this->from(route('user.change.password'))->post(route('user.update.password'), [
        'old_password' => 'OldPassword1',
        'new_password' => 'NewSecurePassword2',
        'new_password_confirmation' => 'NewSecurePassword2',
    ])->assertRedirect(route('user.change.password'));

    expect(Hash::check('NewSecurePassword2', $member->fresh()->password))->toBeTrue();
});

test('member profile cannot take another users email address', function () {
    $tree = memberAccountLocationTree('profile');
    $ageGrade = AgeGrade::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Adult']);
    $religion = Religion::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Other']);
    $existing = memberAccountUser(['email' => 'existing@example.test']);
    $member = memberAccountUser([
        'firstname' => 'Current',
        'lastname' => 'Member',
        'country_id' => $tree['country']->id,
        'region_id' => $tree['region']->id,
        'state_id' => $tree['state']->id,
        'senatorial_district_id' => $tree['senatorial']->id,
        'federal_constituency_id' => $tree['federal']->id,
        'lga_id' => $tree['lga']->id,
        'ward_id' => $tree['ward']->id,
        'polling_unit_id' => $tree['pollingUnit']->id,
        'age_grade_id' => $ageGrade->id,
        'religion_id' => $religion->id,
        'gender' => 'male',
    ]);
    $this->withoutMiddleware()->actingAs($member);

    $this->from(route('user.profile'))->post(route('user.profile.store'), [
        'firstname' => 'Current',
        'lastname' => 'Member',
        'email' => $existing->email,
        'phone' => $member->phone,
        'validvoter' => 'yes',
        'vin' => null,
        'age_grade_id' => $ageGrade->id,
        'gender' => 'male',
        'region_id' => $tree['region']->id,
        'state_id' => $tree['state']->id,
        'lga_id' => $tree['lga']->id,
        'ward_id' => $tree['ward']->id,
        'polling_unit_id' => $tree['pollingUnit']->id,
        'religion_id' => $religion->id,
        'bank' => null,
        'bank_account_number' => null,
        'address' => null,
        'occupation' => null,
        'qualification' => null,
    ])->assertRedirect(route('user.profile'))->assertSessionHasErrors('email');

    expect($member->fresh()->email)->toBe($member->email);
});

test('logout is post only for member accounts', function () {
    $logout = Route::getRoutes()->getByName('logout');

    expect($logout)->not->toBeNull()
        ->and($logout->methods())->toContain('POST')
        ->and($logout->methods())->not->toContain('GET');
});
