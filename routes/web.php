<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\CredentialUpdateController;
use App\Http\Controllers\ProfileCompletionController;
use App\Http\Controllers\PollingUnitAgentController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CampaignNotificationController;
use App\Http\Controllers\AdminLicenseController;

require __DIR__.'/install.php';





// Ajax Routes
Route::post('/get-group-recipients', [AjaxController::class, 'getGroupRecipients'])->name('getGroupRecipients');

Route::get('/get-regions', [AjaxController::class, 'getRegions'])->name('getRegions');
Route::post('/get-states', [AjaxController::class, 'ajaxGetStates'])->name('getStates');
Route::post('/get-lgas', [AjaxController::class, 'ajaxGetLgas'])->name('getLgas');
Route::post('/get-wards', [AjaxController::class, 'ajaxGetWards'])->name('getWards');
Route::post('/get-polling-units', [AjaxController::class, 'ajaxGetPollingUnits'])->name('getPollingUnits');
Route::post('/get-senatorial-districts', [AjaxController::class, 'ajaxGetSenatorialDistricts'])->name('getSenatorialDistricts');
Route::post('/get-federal-constituencies-by-state', [AjaxController::class, 'ajaxGetFederalConstituenciesByState'])->name('getFederalConstituenciesByState');
Route::post('/get-federal-constituencies-by-senatorial-district', [AjaxController::class, 'ajaxGetFederalConstituenciesBySenatorialDistrict'])->name('getFederalConstituenciesBySenatorialDistrict');

Route::get('/get-roles-by-access-level/{accessLevel}', [AjaxController::class, 'getRolesByAccessLevel'])->name('ajaxGetRolesByAccessLevel');

Route::get('/lga/data', [AjaxController::class, 'getLgaData'])->name('lgas.data')->middleware(['auth']);
Route::get('/senatorial-district/data', [AjaxController::class, 'getSenatorialDistrictData'])->name('senatorial-districts.data')->middleware(['auth']);
Route::get('/federal-constituency/data', [AjaxController::class, 'getFederalConstituencyData'])->name('federal-constituencies.data')->middleware(['auth']);

Route::get('/ward/data', [AjaxController::class, 'getWardData'])->name('wards.data')->middleware(['auth']);

Route::get('/pu/data', [AjaxController::class, 'getPUData'])->name('pus.data')->middleware(['auth']);

Route::get('/election/{uuid}/chart-data', [ElectionController::class, 'getElectionResultChartData'])
    ->name('election.chart.data');

Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [CampaignNotificationController::class, 'index'])->name('campaign.notifications.index');
    Route::get('/notifications/unread-count', [CampaignNotificationController::class, 'unreadCount'])->name('campaign.notifications.unread');
    Route::post('/notifications/{notification}/read', [CampaignNotificationController::class, 'markRead'])->name('campaign.notifications.read');
    Route::post('/notifications/read-all', [CampaignNotificationController::class, 'readAll'])->name('campaign.notifications.readAll');
    Route::post('/notifications/{notification}/dismiss', [CampaignNotificationController::class, 'dismiss'])->name('campaign.notifications.dismiss');
    Route::post('/notifications/clear-read', [CampaignNotificationController::class, 'clearRead'])->name('campaign.notifications.clearRead');
});

Route::get('/admin/license', [AdminLicenseController::class, 'show'])
    ->name('admin.license.show')
    ->middleware(['auth', 'access_level:superadmin,nationaladmin']);



    // Credential update route
    Route::get('/update-credentials', [CredentialUpdateController::class, 'showUpdateForm'])
        ->name('update.credentials')
        ->middleware(['auth']);

    // Process credential update
    Route::post('/update-credentials', [CredentialUpdateController::class, 'update']) ->name('update.credentials.store')
    ->middleware(['auth']);

    // Profile completion route
    Route::get('/complete-profile', [ProfileCompletionController::class, 'showProfileForm'])
        ->name('complete.profile')
        ->middleware(['auth']);

    // Process profile completion
    Route::post('/complete-profile', [ProfileCompletionController::class, 'complete'])->name('complete.profile.store')
    ->middleware(['auth']);

    //Handle Volunteer group selection
    Route::get('/select-support-group', [ProfileCompletionController::class, 'selectSupportGroup'])->name('select.support.group')->middleware('auth');
    Route::post('/store-support-group', [ProfileCompletionController::class, 'storeSupportGroup'])->name('store.support.group')->middleware('auth');

  //Export members
 Route::post('/members/export', [MembersController::class, 'exportMembers'])
    ->name('members.export')->middleware(['auth','access_level:superadmin,nationaladmin,regionaladmin,stateadmin,senatorialadmin,federaladmin,lgaadmin,wardadmin,puadmin']);

Route::middleware(['auth', 'check_onboarding'])->group(function () {
    Route::post('/election/result-record/{uuid}/verify', [ElectionController::class, 'verifyResult'])
        ->name('election.result.verify');
    Route::post('/election/result-record/{uuid}/dispute', [ElectionController::class, 'markResultDisputed'])
        ->name('election.result.dispute');
    Route::post('/election/result-record/{uuid}/dispute/clear', [ElectionController::class, 'clearResultDispute'])
        ->name('election.result.dispute.clear');
    Route::get('/election/result-record/{uuid}/sheet', [ElectionController::class, 'resultSheetView'])
        ->name('election.result.sheet');
    Route::get('/election/{uuid}/report/print', [ElectionController::class, 'printPollingUnitReport'])
        ->name('election.report.print');
    Route::get('/election/{uuid}/report/scope-snapshot', [ElectionController::class, 'votesScopeSnapshot'])
        ->name('election.report.scope-snapshot');
    Route::get('/election/{uuid}/report/polling-units.csv', [ElectionController::class, 'exportPollingUnitResultsCsv'])
        ->name('election.report.polling-units.csv');
    Route::get('/election/{uuid}/report/summary.csv', [ElectionController::class, 'exportElectionSummaryCsv'])
        ->name('election.report.summary.csv');
    Route::get('/election/{uuid}/report/polling-units.xlsx', [ElectionController::class, 'exportPollingUnitResultsExcel'])
        ->name('election.report.polling-units.excel');
    Route::get('/election/{uuid}/report/summary.xlsx', [ElectionController::class, 'exportElectionSummaryExcel'])
        ->name('election.report.summary.excel');
});




//Application Landing Page
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        $dashboardRoute = match ($user->access_level) {
            'superadmin' => route('superadmin.dashboard'),
            'nationaladmin' => route('nationaladmin.dashboard'),
            'regionaladmin' => route('regionaladmin.dashboard'),
            'stateadmin' => route('stateadmin.dashboard'),
            'senatorialadmin' => route('senatorialadmin.dashboard'),
            'federaladmin' => route('federaladmin.dashboard'),
            'lgaadmin' => route('lgaadmin.dashboard'),
            'wardadmin' => route('wardadmin.dashboard'),
            'puadmin' => route('puadmin.dashboard'),
            'user' => route('user.dashboard'),
            // Add more levels here as needed
            default => route('login'),
        };
        return redirect($dashboardRoute);
    }
    return redirect()->route('login');
});

//Emergency exit

Route::get('/emergency/exit', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->name('emergency.exit');

//Front Pages
Route::middleware(['auth','check_onboarding', 'kuyak_eyen_ino', 'eyen_ino_no_way', 'community.enabled'])->group(function () {
    // comunity pages
    Route::get('/community/feed', [HomeController::class,'index'])->name('timeline');
    Route::get('/community/profile', [HomeController::class,'profileTimeline'])->name('profile.timeline');
    Route::post('/community/post/store', [HomeController::class,'storePost'])->name('posts.store');
        //Get feed:
    Route::get('/community/posts', [HomeController::class, 'fetchPosts'])->name('posts.fetch');
    Route::get('/community/profile/posts', [HomeController::class, 'fetchAuthUserPosts'])->name('user.posts.fetch');
    Route::get('/community/{username}/profile/timeline', [HomeController::class, 'ViewUserPosts'])->name('view.user.posts');
    Route::get('/community/{username}/profile/posts', [HomeController::class, 'viewUserProfilePost'])->name('view.user.posts.feed');

    //Manage Posts
    Route::get('/posts/{post}/edit', [HomeController::class, 'editPost'])->name('posts.edit'); // Show edit form
    Route::put('/posts/{post}', [HomeController::class, 'updatePost'])->name('posts.update'); // Update the post
    Route::delete('/posts/{post}', [HomeController::class, 'destroyPost'])->name('posts.destroy'); // Delete the post
    Route::post('/posts/{post}/like', [HomeController::class, 'likePost'])->name('posts.like');
    Route::get('/posts/{post}/likers', [HomeController::class, 'postLikers'])->name('posts.likers');

    //Manage Post comments
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store'); // Create a comment
    Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('comments.index'); // Fetch comments for a post
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update'); // Update a comment
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy'); // Delete a comment

    // Search (suggest + full results)
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // Notifications
    Route::get('/community/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markRead'])->name('notifications.markRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::get('/community/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/go/{id}', [NotificationController::class, 'followLink'])->name('notifications.go');

    // Followers and following views / actions
    Route::get('/community/followers', [HomeController::class, 'getFollowers']);
    Route::get('/community/following', [HomeController::class, 'getFollowing']);
    Route::get('/community/users/{user}/followers', [HomeController::class, 'followersOf'])->name('users.followers');
    Route::get('/community/users/{user}/following', [HomeController::class, 'followingOf'])->name('users.following');
    Route::get('/community/me/following/ids', [HomeController::class, 'myFollowingIds'])->name('me.following.ids');
    Route::post('/community/users/{user}/follow', [HomeController::class, 'followUser'])->name('users.follow');
    Route::delete('/community/users/{user}/follow', [HomeController::class, 'unfollowUser'])->name('users.unfollow');

    //Update frontend cover and profile images
    Route::post('/update-profile-photo', [HomeController::class, 'updateProfilePhoto'])->name('update.profile.photo');
    Route::post('/update-cover-photo', [HomeController::class, 'updateCoverPhoto'])->name('update.cover.photo');

});

Route::middleware(['auth','check_onboarding', 'kuyak_eyen_ino', 'eyen_ino_no_way'])->group(function () {
    Route::get('/messages', [HomeController::class, 'messagesPage'])->name('messages.page');
    Route::get('/messages/conversations', [\App\Http\Controllers\MessageController::class, 'conversations'])->name('messages.conversations');
    Route::get('/messages/conversations/{conversation}/messages', [\App\Http\Controllers\MessageController::class, 'messages'])->name('messages.conversation.messages');
    Route::post('/messages/start', [\App\Http\Controllers\MessageController::class, 'startConversation'])->name('messages.start');
    Route::post('/messages/ensure', [\App\Http\Controllers\MessageController::class, 'ensureConversation'])->name('messages.ensure');
    Route::post('/messages/conversations/{conversation}/messages', [\App\Http\Controllers\MessageController::class, 'sendMessage'])->name('messages.conversation.send');
    Route::post('/messages/conversations/{conversation}/typing', [\App\Http\Controllers\MessageController::class, 'typing'])->name('messages.conversation.typing');
    Route::post('/messages/conversations/{conversation}/read', [\App\Http\Controllers\MessageController::class, 'markRead'])->name('messages.conversation.read');
    Route::get('/messages/recipients', [\App\Http\Controllers\MessageController::class, 'recipients'])->name('messages.recipients');
    Route::get('/messages/unread-count', [\App\Http\Controllers\MessageController::class, 'unreadCount'])->name('messages.unread-count');
});

foreach ([
    'superadmin' => 'superadmin',
    'nationaladmin' => 'national',
    'regionaladmin' => 'regional',
    'stateadmin' => 'state',
    'senatorialadmin' => 'senatorial',
    'federaladmin' => 'federal',
    'lgaadmin' => 'lga',
    'wardadmin' => 'ward',
    'puadmin' => 'pu',
    'user' => '',
] as $accessLevel => $prefix) {
    Route::middleware(['auth', 'access_level:'.$accessLevel])
        ->prefix(trim($prefix.'/agents', '/'))
        ->name($accessLevel.'.agents.')
        ->group(function () {
            Route::get('/', [PollingUnitAgentController::class, 'index'])->name('index');
            Route::get('/request', [PollingUnitAgentController::class, 'createRequest'])->name('request');
            Route::post('/request', [PollingUnitAgentController::class, 'storeRequest'])->name('request.store');
            Route::get('/status', [PollingUnitAgentController::class, 'myStatus'])->name('status');
            Route::get('/nominate', [PollingUnitAgentController::class, 'createNomination'])->name('nominate');
            Route::post('/nominate', [PollingUnitAgentController::class, 'storeNomination'])->name('nominate.store');
            Route::get('/direct-assign', [PollingUnitAgentController::class, 'directAssignForm'])->name('direct-assign');
            Route::post('/direct-assign', [PollingUnitAgentController::class, 'directAssignStore'])->name('direct-assign.store');
            Route::get('/locations/user/{user}', [PollingUnitAgentController::class, 'userLocation'])->name('locations.user');
            Route::get('/locations/states', [PollingUnitAgentController::class, 'locationStates'])->name('locations.states');
            Route::get('/locations/lgas', [PollingUnitAgentController::class, 'locationLgas'])->name('locations.lgas');
            Route::get('/locations/wards', [PollingUnitAgentController::class, 'locationWards'])->name('locations.wards');
            Route::get('/locations/polling-units', [PollingUnitAgentController::class, 'locationPollingUnits'])->name('locations.polling-units');
            Route::get('/{uuid}', [PollingUnitAgentController::class, 'show'])->name('show');
            Route::post('/{uuid}/identity', [PollingUnitAgentController::class, 'updateIdentity'])->name('identity.store');
            Route::post('/{uuid}/identity/verify', [PollingUnitAgentController::class, 'verifyIdentity'])->name('identity.verify');
            Route::post('/{uuid}/identity/reject', [PollingUnitAgentController::class, 'rejectIdentity'])->name('identity.reject');
            Route::post('/{uuid}/approve', [PollingUnitAgentController::class, 'approve'])->name('approve');
            Route::post('/{uuid}/approve-directly', [PollingUnitAgentController::class, 'approveDirectly'])->name('approve-directly');
            Route::post('/{uuid}/reject', [PollingUnitAgentController::class, 'reject'])->name('reject');
            Route::post('/{uuid}/suspend', [PollingUnitAgentController::class, 'suspend'])->name('suspend');
            Route::post('/{uuid}/revoke', [PollingUnitAgentController::class, 'revoke'])->name('revoke');
            Route::post('/{uuid}/reactivate', [PollingUnitAgentController::class, 'reactivate'])->name('reactivate');
            Route::get('/{uuid}/document/{type}', [PollingUnitAgentController::class, 'document'])->name('document');
        });
}

//Require Super Admin Routes file
require __DIR__.'/superAdmin.php';

Route::middleware([ 'kuyak_eyen_ino', 'eyen_ino_no_way'])->group(function () {

//Require National Admin Routes file
require __DIR__.'/nationalAdmin.php';

//Require Regional Admin Routes file
require __DIR__.'/RegionalAdmin.php';

//Require State Admin Routes file
require __DIR__.'/stateAdmin.php';

//Require Senatorial Admin Routes file
require __DIR__.'/senatorialAdmin.php';

//Require Federal Admin Routes file
require __DIR__.'/federalAdmin.php';

//Require LGA Admin Routes file
require __DIR__.'/lgaAdmin.php';

//Require Ward Admin Routes file
require __DIR__.'/wardAdmin.php';

//Require User Routes file
require __DIR__.'/puAdmin.php';


//Require User Routes file
require __DIR__.'/memberAdmin.php';
});

//Require Auth Routes file
require __DIR__.'/auth.php';
