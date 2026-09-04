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
use App\Http\Controllers\EmailNotificationController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\SmsComposeController;
use App\Http\Controllers\SmsReportController;
use App\Http\Controllers\SmsSettingsController;
use Illuminate\Support\Facades\Auth;
use App\Services\AccessLevelRouteService;

require __DIR__.'/install.php';





// Ajax Routes
Route::post('/get-group-recipients', [AjaxController::class, 'getGroupRecipients'])
    ->name('getGroupRecipients')
    ->middleware(['auth', 'access_level:superadmin,nationaladmin,regionaladmin,stateadmin,senatorialadmin,federaladmin,lgaadmin,wardadmin,puadmin']);

Route::get('/get-regions', [AjaxController::class, 'getRegions'])->name('getRegions')->middleware('auth');
Route::post('/get-states', [AjaxController::class, 'ajaxGetStates'])->name('getStates')->middleware('auth');
Route::post('/get-lgas', [AjaxController::class, 'ajaxGetLgas'])->name('getLgas')->middleware('auth');
Route::post('/get-wards', [AjaxController::class, 'ajaxGetWards'])->name('getWards')->middleware('auth');
Route::post('/get-polling-units', [AjaxController::class, 'ajaxGetPollingUnits'])->name('getPollingUnits')->middleware('auth');
Route::post('/get-senatorial-districts', [AjaxController::class, 'ajaxGetSenatorialDistricts'])->name('getSenatorialDistricts')->middleware('auth');
Route::post('/get-federal-constituencies-by-state', [AjaxController::class, 'ajaxGetFederalConstituenciesByState'])->name('getFederalConstituenciesByState')->middleware('auth');
Route::post('/get-federal-constituencies-by-senatorial-district', [AjaxController::class, 'ajaxGetFederalConstituenciesBySenatorialDistrict'])->name('getFederalConstituenciesBySenatorialDistrict')->middleware('auth');

Route::get('/get-roles-by-access-level/{accessLevel}', [AjaxController::class, 'getRolesByAccessLevel'])
    ->name('ajaxGetRolesByAccessLevel')
    ->middleware(['auth', 'access_level:superadmin,nationaladmin,regionaladmin,stateadmin,senatorialadmin,federaladmin,lgaadmin,wardadmin,puadmin']);

Route::get('/lga/data', [AjaxController::class, 'getLgaData'])->name('lgas.data')->middleware(['auth']);
Route::get('/senatorial-district/data', [AjaxController::class, 'getSenatorialDistrictData'])->name('senatorial-districts.data')->middleware(['auth']);
Route::get('/federal-constituency/data', [AjaxController::class, 'getFederalConstituencyData'])->name('federal-constituencies.data')->middleware(['auth']);

Route::get('/ward/data', [AjaxController::class, 'getWardData'])->name('wards.data')->middleware(['auth']);

Route::get('/pu/data', [AjaxController::class, 'getPUData'])->name('pus.data')->middleware(['auth']);

Route::get('/election/{uuid}/chart-data', [ElectionController::class, 'getElectionResultChartData'])
    ->name('election.chart.data')
    ->middleware(['auth', 'check_onboarding', 'module.enabled:elections', 'election.report.access']);

foreach (AccessLevelRouteService::prefixes() as $accessLevel => $prefix) {
    Route::middleware([
        'auth',
        'access_level:'.$accessLevel,
        'member.capability:notifications.view',
    ])->prefix($prefix.'/notifications')
        ->name($accessLevel.'.campaign.notifications.')
        ->group(function () {
            Route::get('/', [CampaignNotificationController::class, 'index'])->name('index');
            Route::get('/unread-count', [CampaignNotificationController::class, 'unreadCount'])->name('unread');
            Route::post('/{notification}/read', [CampaignNotificationController::class, 'markRead'])->name('read');
            Route::post('/read-all', [CampaignNotificationController::class, 'readAll'])->name('readAll');
            Route::post('/{notification}/dismiss', [CampaignNotificationController::class, 'dismiss'])->name('dismiss');
            Route::post('/clear-read', [CampaignNotificationController::class, 'clearRead'])->name('clearRead');
        });
}

Route::get('/notifications', function (AccessLevelRouteService $accessLevelRoutes) {
    $route = $accessLevelRoutes->sharedRouteForUser(Auth::user(), 'campaign.notifications.index');
    abort_unless($route, 403, 'No notification route is configured for this account.');

    return redirect()->route($route);
})->middleware('auth')->name('campaign.notifications.legacy');

Route::get('/admin/license', [AdminLicenseController::class, 'show'])
    ->name('admin.license.show')
    ->middleware(['auth', 'access_level:superadmin,nationaladmin']);



foreach (AccessLevelRouteService::prefixes() as $accessLevel => $prefix) {
    Route::middleware(['auth', 'access_level:'.$accessLevel])
        ->prefix($prefix)
        ->name($accessLevel.'.account.')
        ->group(function () {
            Route::get('/update-credentials', [CredentialUpdateController::class, 'showUpdateForm'])->name('update-credentials');
            Route::post('/update-credentials', [CredentialUpdateController::class, 'update'])->name('update-credentials.store');
            Route::get('/complete-profile', [ProfileCompletionController::class, 'showProfileForm'])->name('complete-profile');
            Route::post('/complete-profile', [ProfileCompletionController::class, 'complete'])->name('complete-profile.store');
            Route::get('/select-support-group', [ProfileCompletionController::class, 'selectSupportGroup'])->name('select-support-group');
            Route::post('/store-support-group', [ProfileCompletionController::class, 'storeSupportGroup'])->name('support-group.store');
        });
}

foreach ([
    'update-credentials' => 'account.update-credentials',
    'complete-profile' => 'account.complete-profile',
    'select-support-group' => 'account.select-support-group',
] as $legacyPath => $routeSuffix) {
    Route::get('/'.$legacyPath, function (AccessLevelRouteService $accessLevelRoutes) use ($routeSuffix) {
        $route = $accessLevelRoutes->sharedRouteForUser(Auth::user(), $routeSuffix);
        abort_unless($route, 403, 'No account route is configured for this account.');

        return redirect()->route($route);
    })->middleware('auth')->name('legacy.'.$legacyPath);
}

  //Export members
 Route::post('/members/export', [MembersController::class, 'exportMembers'])
    ->name('members.export')->middleware(['auth','access_level:superadmin,nationaladmin,regionaladmin,stateadmin,senatorialadmin,federaladmin,lgaadmin,wardadmin,puadmin']);

Route::middleware([
    'auth',
    'check_onboarding',
    'module.enabled:elections',
    'election.report.access',
    'access_level:superadmin,nationaladmin,regionaladmin,stateadmin,senatorialadmin,federaladmin,lgaadmin,wardadmin',
])->group(function () {
    Route::post('/election/result-record/{uuid}/verify', [ElectionController::class, 'verifyResult'])
        ->name('election.result.verify');
    Route::post('/election/result-record/{uuid}/dispute', [ElectionController::class, 'markResultDisputed'])
        ->name('election.result.dispute');
    Route::post('/election/result-record/{uuid}/dispute/clear', [ElectionController::class, 'clearResultDispute'])
        ->name('election.result.dispute.clear');
});

Route::middleware(['auth', 'check_onboarding', 'module.enabled:elections', 'election.report.access'])->group(function () {
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




// Compatibility entry point for framework defaults and older bookmarks.
// The destination remains the canonical access-level-prefixed dashboard.
Route::get('/dashboard', function (AccessLevelRouteService $accessLevelRoutes) {
    $dashboardRoute = $accessLevelRoutes->dashboardRouteForUser(Auth::user());

    abort_unless($dashboardRoute, 403, 'No dashboard is configured for this account.');

    return redirect()->route($dashboardRoute);
})->middleware('auth')->name('dashboard');

//Application Landing Page
Route::get('/', function (AccessLevelRouteService $accessLevelRoutes) {
    if (Auth::check()) {
        $dashboardRoute = $accessLevelRoutes->dashboardRouteForUser(Auth::user());

        return $dashboardRoute
            ? redirect()->route($dashboardRoute)
            : redirect()->route('login');
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
Route::middleware(['auth','check_onboarding', 'kuyak_eyen_ino', 'eyen_ino_no_way', 'member.capability:community.use', 'community.enabled', 'community.profile_complete'])->group(function () {
    // comunity pages
    Route::get('/community/feed', [HomeController::class,'index'])->name('timeline');
    Route::get('/community/profile', [HomeController::class,'profileTimeline'])->name('profile.timeline');
    Route::get('/community/pages/{page}', [HomeController::class, 'legalPage'])
        ->whereIn('page', ['privacy-policy', 'terms-and-conditions', 'disclaimer'])
        ->name('community.legal');
    Route::post('/community/post/store', [HomeController::class,'storePost'])->name('posts.store');
        //Get feed:
    Route::get('/community/posts', [HomeController::class, 'fetchPosts'])->name('posts.fetch');
    Route::get('/community/audience/options', [HomeController::class, 'audienceOptions'])->name('community.audience.options');
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

foreach (AccessLevelRouteService::prefixes() as $accessLevel => $prefix) {
    Route::middleware([
        'auth',
        'check_onboarding',
        'kuyak_eyen_ino',
        'eyen_ino_no_way',
        'access_level:'.$accessLevel,
        'member.capability:messaging.use',
    ])->prefix($prefix.'/messages')
        ->name($accessLevel.'.messages.')
        ->group(function () {
            Route::get('/', [HomeController::class, 'messagesPage'])->name('page');
            Route::get('/conversations', [\App\Http\Controllers\MessageController::class, 'conversations'])->name('conversations');
            Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\MessageController::class, 'messages'])->name('conversation.messages');
            Route::post('/start', [\App\Http\Controllers\MessageController::class, 'startConversation'])->name('start');
            Route::post('/ensure', [\App\Http\Controllers\MessageController::class, 'ensureConversation'])->name('ensure');
            Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\MessageController::class, 'sendMessage'])->name('conversation.send');
            Route::post('/conversations/{conversation}/typing', [\App\Http\Controllers\MessageController::class, 'typing'])->name('conversation.typing');
            Route::post('/conversations/{conversation}/read', [\App\Http\Controllers\MessageController::class, 'markRead'])->name('conversation.read');
            Route::get('/recipients', [\App\Http\Controllers\MessageController::class, 'recipients'])->name('recipients');
            Route::get('/unread-count', [\App\Http\Controllers\MessageController::class, 'unreadCount'])->name('unread-count');
        });
}

// Compatibility redirect for old bookmarks. All generated links use the
// access-level-prefixed route registered above.
Route::get('/messages', function (AccessLevelRouteService $accessLevelRoutes) {
    $route = $accessLevelRoutes->sharedRouteForUser(Auth::user(), 'messages.page');
    abort_unless($route, 403, 'No messaging route is configured for this account.');

    return redirect()->route($route);
})->middleware('auth')->name('messages.legacy');

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
] as $accessLevel => $prefix) {
    Route::middleware([
        'auth',
        'check_onboarding',
        'kuyak_eyen_ino',
        'eyen_ino_no_way',
        'access_level:'.$accessLevel,
        'email_notifications.enabled',
    ])
        ->prefix($prefix.'/email-notifications')
        ->name($accessLevel.'.email-notifications.')
        ->group(function () {
            Route::get('/', [EmailNotificationController::class, 'index'])->name('index');
            Route::get('/location-options', [EmailNotificationController::class, 'locationOptions'])->name('location-options');
            Route::get('/create', [EmailNotificationController::class, 'create'])->name('create');
            Route::post('/audience-preview', [EmailNotificationController::class, 'audiencePreview'])->name('audience-preview');
            Route::post('/preview', [EmailNotificationController::class, 'preview'])->name('preview');
            Route::post('/preview/back', [EmailNotificationController::class, 'backToEdit'])->name('preview.back');
            Route::post('/', [EmailNotificationController::class, 'store'])->name('store');
            Route::get('/{campaign}', [EmailNotificationController::class, 'show'])->whereUuid('campaign')->name('show');
        });
}

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
] as $accessLevel => $prefix) {
    $announcementMiddleware = [
        'auth',
        'check_onboarding',
        'kuyak_eyen_ino',
        'eyen_ino_no_way',
        'access_level:'.$accessLevel,
        'announcement.manager',
    ];

    Route::middleware($announcementMiddleware)
        ->prefix($prefix.'/announcements')
        ->name($accessLevel.'.announcements.')
        ->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('index');
            Route::get('/create', [AnnouncementController::class, 'create'])->name('create');
            Route::post('/', [AnnouncementController::class, 'store'])->name('store');
            Route::get('/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('edit');
            Route::put('/{announcement}', [AnnouncementController::class, 'update'])->name('update');
            Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])->name('destroy');
        });

    Route::middleware($announcementMiddleware)
        ->prefix($prefix.'/notices')
        ->name($accessLevel.'.notices.')
        ->group(function () {
            Route::get('/', [AnnouncementController::class, 'noticeIndex'])->name('index');
            Route::get('/{announcement}', [AnnouncementController::class, 'noticeShow'])->name('show');
        });
}

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
    'user' => 'member',
] as $accessLevel => $prefix) {
    $agentMiddleware = ['auth', 'check_onboarding', 'access_level:'.$accessLevel, 'module.enabled:agents'];
    if ($accessLevel === 'user') {
        $agentMiddleware[] = 'member.capability:agents.participate';
    }

    Route::middleware($agentMiddleware)
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

Route::middleware(['auth', 'access_level:superadmin'])
    ->prefix('superadmin/bulksms')->name('superadmin.bulksms.')->group(function () {
        Route::get('/send', function () {
            abort_if(app(\App\Services\ModuleGateService::class)->disabled('sms'), 403, 'Bulk SMS is not available in this build.');

            return redirect()->route('superadmin.sms.compose');
        })->name('send');
        Route::get('/setting', function () {
            abort_if(app(\App\Services\ModuleGateService::class)->disabled('sms'), 403, 'SMS settings are not available in this build.');

            return redirect()->route('superadmin.sms.settings.index');
        })->name('setting');
    });

Route::middleware(['auth', 'access_level:superadmin', 'module.enabled:sms'])
    ->prefix('superadmin/sms/settings')->name('superadmin.sms.settings.')->group(function () {
        Route::get('/', [SmsSettingsController::class, 'index'])->name('index');
        Route::put('/', [SmsSettingsController::class, 'update'])->name('update');
        Route::post('/test', [SmsSettingsController::class, 'test'])->name('test');
    });

foreach (AccessLevelRouteService::prefixes() as $accessLevel => $prefix) {
    Route::middleware(['auth', 'access_level:'.$accessLevel, 'module.enabled:sms'])
        ->prefix($prefix.'/sms')->name($accessLevel.'.sms.')->group(function () {
            Route::get('/', [SmsComposeController::class, 'dashboard'])->middleware('sms.access:sms.view')->name('dashboard');
            Route::get('/compose', [SmsComposeController::class, 'create'])->middleware('sms.access:sms.compose')->name('compose');
            Route::post('/compose/estimate', [SmsComposeController::class, 'estimate'])->middleware('sms.access:sms.compose')->name('compose.estimate');
            Route::post('/compose/send', [SmsComposeController::class, 'send'])->middleware('sms.access:sms.send')->name('compose.send');
            Route::get('/compose/locations/{type}', [SmsComposeController::class, 'locations'])->middleware('sms.access:sms.compose')->name('compose.locations');
            Route::get('/wallet', [SmsController::class, 'wallet'])->middleware('sms.access:sms.wallet.view')->name('wallet');
            Route::get('/organization-wallet', [SmsController::class, 'organizationWallet'])->middleware('sms.access:sms.organization_wallet.view')->name('organization-wallet');
            Route::post('/topups', [SmsController::class, 'initiateTopup'])->middleware('sms.access:sms.wallet.topup')->name('topups.store');
            Route::post('/credit-requests', [SmsController::class, 'createCreditRequest'])->middleware('sms.access:sms.credit.request')->name('credit-requests.store');
            Route::post('/transfers', [SmsController::class, 'transfer'])->middleware('sms.access:sms.wallet.transfer')->name('transfers.store');
            Route::post('/allocations/sync', [SmsController::class, 'syncAllocations'])->middleware('sms.access:sms.wallet.transfer')->name('allocations.sync');
            Route::get('/sender-ids', [SmsController::class, 'senderIds'])->middleware('sms.access:sms.view')->name('sender-ids');
            Route::post('/sender-ids', [SmsController::class, 'requestSenderId'])->middleware('sms.access:sms.sender_id.request')->name('sender-ids.store');
            Route::get('/reports', [SmsReportController::class, 'index'])->middleware('sms.access:sms.reports')->name('batches');
            Route::get('/reports/{batch}', [SmsReportController::class, 'show'])->middleware('sms.access:sms.reports')->name('batches.show');
            Route::post('/reports/{batch}/sync', [SmsReportController::class, 'sync'])->middleware('sms.access:sms.reports')->name('batches.sync');
        });
}

//Require Super Admin Routes file
require __DIR__.'/superAdmin.php';

Route::middleware([ 'kuyak_eyen_ino', 'eyen_ino_no_way'])->group(function () {

//Require National Admin Routes file
require __DIR__.'/nationalAdmin.php';

//Require Regional Admin Routes file
require __DIR__.'/regionalAdmin.php';

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
