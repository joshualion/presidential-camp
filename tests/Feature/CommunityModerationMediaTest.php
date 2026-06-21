<?php

use App\Models\AgeGrade;
use App\Models\ActivityNotification;
use App\Models\Comment;
use App\Models\Country;
use App\Models\LocalLicense;
use App\Models\Post;
use App\Models\Religion;
use App\Models\SupportGroup;
use App\Models\SystemSetting;
use App\Models\User;
use App\Http\Middleware\CheckUserOnboarding;
use App\Http\Middleware\KuyakEyenIno;
use App\Http\Middleware\ValidateLicense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->withoutMiddleware([
        CheckUserOnboarding::class,
        KuyakEyenIno::class,
        ValidateLicense::class,
    ]);

    File::ensureDirectoryExists(storage_path('app'));
    File::put(storage_path('app/installed.lock'), json_encode(['deployment_mode' => 'self_hosted']));

    config([
        'campaign.deployment_mode' => 'self_hosted',
        'broadcasting.default' => 'log',
        'app.web_socket_availability' => false,
    ]);

    SystemSetting::query()->updateOrCreate(['id' => 1], [
        'package' => 'governorship',
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'frontend_community' => 1,
        'frontend_registration' => 0,
        'require_bank_details' => 0,
        'enable_local_storage' => 1,
        'enable_s3_storage' => 0,
        'community_post_max_length' => 750,
        'community_comment_max_length' => 750,
        'community_daily_post_limit' => 50,
        'community_daily_comment_limit' => 200,
        'community_attachment_max_mb' => 5,
        'community_image_max_mb' => 5,
        'community_video_max_mb' => 20,
        'community_allow_images' => 1,
        'community_allow_videos' => 0,
    ]);

    Country::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Nigeria',
    ]);

    DB::table('regions')->updateOrInsert(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'South South',
        'country_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('states')->updateOrInsert(['id' => 9], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Cross River',
        'region_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('states')->updateOrInsert(['id' => 10], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Delta',
        'region_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('local_government_areas')->updateOrInsert(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Calabar Municipal',
        'state_id' => 9,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('local_government_areas')->updateOrInsert(['id' => 2], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Oshimili South',
        'state_id' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('local_government_areas')->updateOrInsert(['id' => 3], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Ogoja',
        'state_id' => 9,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('wards')->updateOrInsert(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Ward One',
        'lga_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('wards')->updateOrInsert(['id' => 2], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Ward Two',
        'lga_id' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('wards')->updateOrInsert(['id' => 3], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Ward Three',
        'lga_id' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('polling_units')->updateOrInsert(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'PU One',
        'ward_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('polling_units')->updateOrInsert(['id' => 2], [
        'uuid' => (string) Str::uuid(),
        'name' => 'PU Two',
        'ward_id' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('polling_units')->updateOrInsert(['id' => 3], [
        'uuid' => (string) Str::uuid(),
        'name' => 'PU Three',
        'ward_id' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Religion::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Christianity',
    ]);

    AgeGrade::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Adult',
    ]);

    communityModerationLicense(true);
});

afterEach(function () {
    if (File::exists(storage_path('app/installed.lock'))) {
        File::delete(storage_path('app/installed.lock'));
    }
});

function communityModerationLicense(bool $communityEnabled): void
{
    $license = LocalLicense::query()->create([
        'license_key' => 'CM-COMMUNITY-MOD-'.Str::upper(Str::random(6)),
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
        'app_url' => 'http://localhost',
        'domain' => 'localhost',
        'activated_at' => now(),
        'last_checked_at' => now(),
    ]);

    $license->scope()->create([
        'scope_type' => 'state',
        'scope_name' => 'Cross River',
        'state_id' => 9,
        'state_name' => 'Cross River',
    ]);

    $license->modules()->createMany([
        ['module_key' => 'members', 'enabled' => true],
        ['module_key' => 'community', 'enabled' => $communityEnabled],
    ]);
}

function communityModerationUser(array $attributes = [], ?string $roleName = 'Member'): User
{
    $user = User::withoutEvents(fn () => User::factory()->create(array_merge([
        'firstname' => 'Community',
        'lastname' => 'User',
        'email' => Str::uuid().'@test.local',
        'phone' => '+234'.random_int(1000000000, 9999999999),
        'password' => Hash::make('secret-password'),
        'access_level' => 'user',
        'status' => 'active',
        'requires_update' => false,
        'country_id' => 1,
        'region_id' => null,
        'state_id' => 9,
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
        'religion_id' => 1,
        'age_grade_id' => 1,
    ], $attributes)));

    $group = SupportGroup::query()->firstOrCreate(['name' => 'Community Volunteers']);
    $user->supportGroups()->sync([$group->id]);

    if ($roleName) {
        $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);
    }

    return $user;
}

test('community disabled blocks post creation', function () {
    SystemSetting::query()->whereKey(1)->update(['frontend_community' => 0]);

    $response = $this->actingAs(communityModerationUser())->postJson(route('posts.store'), [
        'content' => 'This should not post.',
        'audience' => 'pu',
    ]);

    $response->assertForbidden();
});

test('unlicensed community blocks post creation', function () {
    LocalLicense::query()
        ->latest('id')
        ->first()
        ?->modules()
        ->where('module_key', 'community')
        ->update(['enabled' => false]);

    $response = $this->actingAs(communityModerationUser())->postJson(route('posts.store'), [
        'content' => 'This should not post.',
        'audience' => 'pu',
    ]);

    $response->assertForbidden();
});

test('user can create text only post when community is enabled and permitted', function () {
    $response = $this->actingAs(communityModerationUser())->postJson(route('posts.store'), [
        'content' => 'Campaign community update.',
        'audience' => 'pu',
    ]);

    $response->assertOk()
        ->assertJson(['status' => 'success']);

    expect(Post::query()->where('content', 'Campaign community update.')->exists())->toBeTrue();
});

test('home feed includes own scoped post after returning to feed', function () {
    $user = communityModerationUser();

    $this->actingAs($user)->postJson(route('posts.store'), [
        'content' => 'Visible after returning to feed.',
        'audience' => 'pu',
    ])->assertOk();

    $response = $this->actingAs($user)->getJson(route('posts.fetch', [
        'audience' => 'pu',
    ]));

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('content'))
        ->toContain('Visible after returning to feed.');
});

test('audience options expose own scope and package safe global', function () {
    $user = communityModerationUser([
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
    ]);

    $response = $this->actingAs($user)->getJson(route('community.audience.options'));

    $response->assertOk()
        ->assertJsonPath('default.audience_type', 'global')
        ->assertJsonPath('default.label', 'Global')
        ->assertJsonPath('support_group_enabled', true);

    $labels = collect($response->json('options'))->pluck('label');

    expect(collect($response->json('options'))->pluck('audience_type'))
        ->toContain('global')
        ->toContain('support_group')
        ->and($labels)->toContain('Cross River')
        ->and($labels)->toContain('Calabar Municipal')
        ->and($labels)->toContain('Ward One')
        ->and($labels)->toContain('PU One')
        ->and($labels)->toContain('Community Volunteers');
});

test('out of scope specific audience is rejected', function () {
    $user = communityModerationUser([
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
    ]);

    $this->actingAs($user)->postJson(route('posts.store'), [
        'content' => 'Bad target.',
        'audience_type' => 'specific_scope',
        'audience_scope_type' => 'pu',
        'audience_scope_id' => 2,
    ])->assertStatus(422);
});

test('post without audience selection defaults to package safe global', function () {
    $poster = communityModerationUser([
        'email' => 'default-global-poster@test.local',
        'state_id' => 9,
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
    ]);
    $sameState = communityModerationUser([
        'email' => 'default-global-same-state@test.local',
        'state_id' => 9,
        'lga_id' => 3,
        'ward_id' => 3,
        'polling_unit_id' => 3,
    ]);

    $this->actingAs($poster)->postJson(route('posts.store'), [
        'content' => 'Default global post.',
    ])->assertOk();

    $post = Post::query()->where('content', 'Default global post.')->first();

    expect($post->audience_type)->toBe('global')
        ->and(collect($this->actingAs($sameState)->getJson(route('posts.fetch'))->json('data'))->pluck('content'))
        ->toContain('Default global post.');
});

test('support group audience is visible only to group members', function () {
    $group = SupportGroup::query()->firstOrCreate(['name' => 'Ward Mobilizers']);
    $poster = communityModerationUser([
        'email' => 'group-poster@test.local',
        'state_id' => 9,
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
    ]);
    $member = communityModerationUser([
        'email' => 'group-member@test.local',
        'state_id' => 9,
        'lga_id' => 3,
        'ward_id' => 3,
        'polling_unit_id' => 3,
    ]);
    $nonMember = communityModerationUser([
        'email' => 'group-non-member@test.local',
        'state_id' => 9,
        'lga_id' => 3,
        'ward_id' => 3,
        'polling_unit_id' => 3,
    ]);

    $poster->supportGroups()->sync([$group->id]);
    $member->supportGroups()->sync([$group->id]);
    $nonMember->supportGroups()->sync([SupportGroup::query()->firstOrCreate(['name' => 'Other Group'])->id]);

    $this->actingAs($poster)->postJson(route('posts.store'), [
        'content' => 'Group-only post.',
        'audience_type' => 'support_group',
        'audience_group_id' => $group->id,
    ])->assertOk();

    expect(collect($this->actingAs($member)->getJson(route('posts.fetch'))->json('data'))->pluck('content'))
        ->toContain('Group-only post.');

    expect(collect($this->actingAs($nonMember)->getJson(route('posts.fetch'))->json('data'))->pluck('content'))
        ->not->toContain('Group-only post.');
});

test('polling unit own scope post is visible only in that polling unit', function () {
    $poster = communityModerationUser([
        'email' => 'poster-pu@test.local',
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
    ]);
    $samePu = communityModerationUser([
        'email' => 'same-pu@test.local',
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
    ]);
    $otherPu = communityModerationUser([
        'email' => 'other-pu@test.local',
        'state_id' => 10,
        'lga_id' => 2,
        'ward_id' => 2,
        'polling_unit_id' => 2,
    ]);

    $this->actingAs($poster)->postJson(route('posts.store'), [
        'content' => 'PU scoped post.',
        'audience_type' => 'own_scope',
    ])->assertOk();

    expect(collect($this->actingAs($samePu)->getJson(route('posts.fetch'))->json('data'))->pluck('content'))
        ->toContain('PU scoped post.');

    expect(collect($this->actingAs($otherPu)->getJson(route('posts.fetch'))->json('data'))->pluck('content'))
        ->not->toContain('PU scoped post.');
});

test('governorship global post is visible in licensed state but not outside state', function () {
    $poster = communityModerationUser([
        'email' => 'global-poster@test.local',
        'state_id' => 9,
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
    ]);
    $sameState = communityModerationUser([
        'email' => 'same-state@test.local',
        'state_id' => 9,
        'lga_id' => 3,
        'ward_id' => 3,
        'polling_unit_id' => 3,
    ]);
    $outsideState = communityModerationUser([
        'email' => 'outside-state@test.local',
        'state_id' => 10,
        'lga_id' => 2,
        'ward_id' => 2,
        'polling_unit_id' => 2,
    ]);

    $this->actingAs($poster)->postJson(route('posts.store'), [
        'content' => 'State campaign global post.',
        'audience_type' => 'global',
    ])->assertOk();

    expect(collect($this->actingAs($sameState)->getJson(route('posts.fetch'))->json('data'))->pluck('content'))
        ->toContain('State campaign global post.');

    expect(collect($this->actingAs($outsideState)->getJson(route('posts.fetch'))->json('data'))->pluck('content'))
        ->not->toContain('State campaign global post.');
});

test('global post creates package-wide notification with post deep link', function () {
    $poster = communityModerationUser([
        'email' => 'notify-global-poster@test.local',
        'state_id' => 9,
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
    ]);

    $this->actingAs($poster)->postJson(route('posts.store'), [
        'content' => 'Notify global post.',
        'audience_type' => 'global',
    ])->assertOk();

    $post = Post::query()->where('content', 'Notify global post.')->first();
    $notification = ActivityNotification::query()
        ->where('type', 'post')
        ->where('subject_id', $post->id)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->scope)->toBe('public')
        ->and($notification->scope_id)->toBeNull()
        ->and($notification->data['url'])->toContain("/community/feed#post-{$post->id}");
});

test('old legacy posts without audience metadata remain safely visible by legacy audience', function () {
    $poster = communityModerationUser([
        'email' => 'legacy-poster@test.local',
        'state_id' => 9,
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
    ]);
    $viewer = communityModerationUser([
        'email' => 'legacy-viewer@test.local',
        'state_id' => 9,
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
    ]);
    $outsidePu = communityModerationUser([
        'email' => 'legacy-outside@test.local',
        'state_id' => 9,
        'lga_id' => 3,
        'ward_id' => 3,
        'polling_unit_id' => 3,
    ]);

    Post::query()->create([
        'user_id' => $poster->id,
        'content' => 'Legacy PU post.',
        'post_type' => 'text',
        'audience' => 'pu',
        'state_id' => 9,
        'lga_id' => 1,
        'ward_id' => 1,
        'pu_id' => 1,
    ]);

    expect(collect($this->actingAs($viewer)->getJson(route('posts.fetch'))->json('data'))->pluck('content'))
        ->toContain('Legacy PU post.');

    expect(collect($this->actingAs($outsidePu)->getJson(route('posts.fetch'))->json('data'))->pluck('content'))
        ->not->toContain('Legacy PU post.');
});

test('community sidebar hides finance widgets and shows safe election watch empty state', function () {
    $user = communityModerationUser();

    $this->actingAs($user);
    $html = view('frontend.right-sidebar', [
        'profileData' => $user,
        'SystemSetting' => SystemSetting::query()->first(),
    ])->render();

    expect($html)
        ->toContain('Election Watch')
        ->toContain('No active election watch at the moment.')
        ->not->toContain('Donate')
        ->not->toContain('Sponsored')
        ->not->toContain('Store')
        ->not->toContain('Wallet');
});

test('post watch shows countdown for upcoming election', function () {
    $user = communityModerationUser();
    $partyId = DB::table('political_parties')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'name' => 'Campaign Party',
        'acronym' => 'CP',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('elections')->insert([
        'uuid' => (string) Str::uuid(),
        'party_id' => $partyId,
        'name' => 'Upcoming Governorship Election',
        'year' => now()->addDays(10)->toDateString(),
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user);
    $html = view('frontend.right-sidebar', [
        'profileData' => $user,
        'SystemSetting' => SystemSetting::query()->first(),
    ])->render();

    expect($html)
        ->toContain('Upcoming Governorship Election')
        ->toContain('remaining');
});

test('image upload validation accepts valid image and rejects oversized image', function () {
    SystemSetting::query()->whereKey(1)->update(['community_image_max_mb' => 1]);
    $user = communityModerationUser();

    $this->actingAs($user)->postJson(route('posts.store'), [
        'content' => 'Image update.',
        'audience' => 'pu',
        'photo' => UploadedFile::fake()->image('photo.jpg')->size(512),
    ])->assertOk();

    $this->actingAs($user)->postJson(route('posts.store'), [
        'content' => 'Large image.',
        'audience' => 'pu',
        'photo' => UploadedFile::fake()->image('large.jpg')->size(2048),
    ])->assertStatus(422);
});

test('video upload is disabled by default and validates when explicitly enabled', function () {
    $superadmin = communityModerationUser(['access_level' => 'superadmin'], null);

    $this->actingAs($superadmin)->postJson(route('posts.store'), [
        'content' => 'Video disabled.',
        'audience' => 'pu',
        'video' => UploadedFile::fake()->create('clip.mp4', 512, 'video/mp4'),
    ])->assertStatus(422);

    SystemSetting::query()->whereKey(1)->update([
        'community_allow_videos' => 1,
        'community_video_max_mb' => 1,
    ]);

    $this->actingAs($superadmin)->postJson(route('posts.store'), [
        'content' => 'Video enabled.',
        'audience' => 'pu',
        'video' => UploadedFile::fake()->create('clip.mp4', 512, 'video/mp4'),
    ])->assertOk();
});

test('post owner can delete own post and media file is cleaned up', function () {
    $user = communityModerationUser();
    File::ensureDirectoryExists(public_path('uploads/community/photos'));
    File::put(public_path('uploads/community/photos/delete-me.jpg'), 'test');

    $post = Post::query()->create([
        'user_id' => $user->id,
        'content' => 'Delete me.',
        'image_url' => 'delete-me.jpg',
        'post_type' => 'image',
        'audience' => 'pu',
        'state_id' => 9,
    ]);

    $this->actingAs($user)->deleteJson(route('posts.destroy', $post))->assertOk();

    expect(Post::query()->whereKey($post->id)->exists())->toBeFalse()
        ->and(File::exists(public_path('uploads/community/photos/delete-me.jpg')))->toBeFalse();
});

test('unauthorized user cannot delete another user post', function () {
    $owner = communityModerationUser(['email' => 'owner@test.local']);
    $other = communityModerationUser(['email' => 'other@test.local']);
    $post = Post::query()->create([
        'user_id' => $owner->id,
        'content' => 'Protected post.',
        'post_type' => 'text',
        'audience' => 'pu',
        'state_id' => 9,
    ]);

    $this->actingAs($other)->deleteJson(route('posts.destroy', $post))->assertForbidden();
});

test('ict director can moderate in scope post but not out of scope post', function () {
    $moderator = communityModerationUser([
        'access_level' => 'stateadmin',
        'state_id' => 9,
        'email' => 'moderator@test.local',
    ], 'State ICT Director');
    $inScopeAuthor = communityModerationUser(['email' => 'in-scope@test.local', 'state_id' => 9]);
    $outScopeAuthor = communityModerationUser(['email' => 'out-scope@test.local', 'state_id' => 10]);

    $inScopePost = Post::query()->create([
        'user_id' => $inScopeAuthor->id,
        'content' => 'In scope.',
        'post_type' => 'text',
        'audience' => 'state',
        'state_id' => 9,
    ]);
    $outScopePost = Post::query()->create([
        'user_id' => $outScopeAuthor->id,
        'content' => 'Out of scope.',
        'post_type' => 'text',
        'audience' => 'state',
        'state_id' => 10,
    ]);

    $this->actingAs($moderator)->deleteJson(route('posts.destroy', $inScopePost))->assertOk();
    $this->actingAs($moderator)->deleteJson(route('posts.destroy', $outScopePost))->assertForbidden();
});

test('comment owner can delete own comment and unauthorized moderation is rejected', function () {
    $postOwner = communityModerationUser(['email' => 'post-owner@test.local']);
    $commentOwner = communityModerationUser(['email' => 'comment-owner@test.local']);
    $other = communityModerationUser(['email' => 'comment-other@test.local']);
    $post = Post::query()->create([
        'user_id' => $postOwner->id,
        'content' => 'Comment target.',
        'post_type' => 'text',
        'audience' => 'pu',
        'state_id' => 9,
    ]);
    $comment = Comment::query()->create([
        'post_id' => $post->id,
        'user_id' => $commentOwner->id,
        'content' => 'Own comment.',
    ]);

    $this->actingAs($other)->deleteJson(route('comments.destroy', $comment))->assertForbidden();
    $this->actingAs($commentOwner)->deleteJson(route('comments.destroy', $comment))->assertOk();
});

test('broadcast log mode does not prevent post and comment creation', function () {
    config(['broadcasting.default' => 'log', 'app.web_socket_availability' => false]);

    $user = communityModerationUser();
    $post = Post::query()->create([
        'user_id' => $user->id,
        'content' => 'Existing post.',
        'post_type' => 'text',
        'audience' => 'pu',
        'state_id' => 9,
    ]);

    $this->actingAs($user)->postJson(route('posts.store'), [
        'content' => 'Log mode post.',
        'audience' => 'pu',
    ])->assertOk();

    $this->actingAs($user)->postJson(route('comments.store', $post), [
        'content' => 'Log mode comment.',
    ])->assertOk();
});
