<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\FileStorageService;
use App\Services\CommunityAudienceService;
use App\Services\CommunityModerationService;
use App\Services\LicensedScopeQueryService;
use App\Events\NewPostCreated;
use App\Events\NewActivityNotification;
use App\Models\ActivityNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class HomeController extends Controller
{
    protected $fileStorageService;

     // Constructor with dependency injection
     public function __construct(FileStorageService $fileStorageService)
     {
         $this->fileStorageService = $fileStorageService;
 
         // Your existing logic
         $pageTitle = 'Community';
         View::share('pageTitle', $pageTitle);
     }

   

     // Function to get the profile data
     private function getProfileData()
     {
         $id = Auth::user()->id;
         return User::find($id);
     }

     public function index(){
        $pageTitle = "Home - Community";
        $profileData = $this->getProfileData();
        $userSupportGroups = $profileData->supportGroups()->pluck('name')->toArray();
        // Get followers and following counts
        $followersCount = $profileData->followers()->count();
        $followingCount = $profileData->following()->count();  
        
        // Get paginated followers and following data
        $followers = $profileData->followers()->paginate(10); // Paginate with 10 items per page
        $following = $profileData->following()->paginate(10); // Paginate with 10 items per pagefollowing = $profileData->following()->get(); // Users this profile is following
        
       

        return view('frontend.index', compact(
            'profileData',
             'userSupportGroups',
            'followersCount',
            'followingCount',
            'followers',
            'following',
            'pageTitle'
        ));

    }

  

    public function profileTimeline(){
        $pageTitle = "Profile Timeline";
        $profileData = $this->getProfileData();
        $userSupportGroups = $profileData->supportGroups()->pluck('name')->toArray();
        // Get followers and following counts
        $followersCount = $profileData->followers()->count();
        $followingCount = $profileData->following()->count();
         // Get paginated followers and following data
        $followers = $profileData->followers()->paginate(10); // Paginate with 10 items per page
        $following = $profileData->following()->paginate(10); // Paginate with 10 items per pagefollowing = $profileData->following()->get(); // Users this profile is following
        
       

        return view('frontend.profile', compact(
            'profileData',
             'userSupportGroups',
            'followersCount',
            'followingCount',
            'followers',
            'following',
            'pageTitle'
        ));
    }

    public function legalPage(string $page)
    {
        $settings = SystemSetting::first();
        $profileData = $this->getProfileData();
        $pages = [
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'field' => 'privacy_policy',
                'empty' => 'The privacy policy has not been published yet.',
            ],
            'terms-and-conditions' => [
                'title' => 'Terms & Conditions',
                'field' => 'tos',
                'empty' => 'The terms and conditions have not been published yet.',
            ],
            'disclaimer' => [
                'title' => 'Disclaimer',
                'field' => 'disclaimer',
                'empty' => 'The disclaimer has not been published yet.',
            ],
        ];

        abort_unless(isset($pages[$page]), 404);

        $meta = $pages[$page];
        $pageTitle = $meta['title'];
        $content = trim((string) ($settings?->{$meta['field']} ?? ''));

        return view('frontend.legal-page', [
            'profileData' => $profileData,
            'pageTitle' => $pageTitle,
            'legalTitle' => $meta['title'],
            'legalContent' => $content,
            'emptyMessage' => $meta['empty'],
        ]);
    }


    // HomeController.php
    public function followUser(User $user)
    {
        $me = Auth::user();
        if (!$me || $me->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Invalid follow request'], 422);
        }

        $me->follow($user);

        return response()->json([
            'success' => true,
            'following' => true,
            'followers_count' => $user->followers()->count(),
        ]);
    }

    public function unfollowUser(User $user)
    {
        $me = Auth::user();
        if (!$me || $me->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Invalid unfollow request'], 422);
        }

        $me->unfollow($user);

        return response()->json([
            'success' => true,
            'following' => false,
            'followers_count' => $user->followers()->count(),
        ]);
    }

    public function followersOf(User $user)
    {
        $followers = $user->followers()
            ->select('users.id', 'users.firstname', 'users.lastname', 'users.username', 'users.photo')
            ->paginate(20);

        $followers->getCollection()->transform(function ($u) {
            return [
                'id' => $u->id,
                'name' => trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? '')) ?: ($u->username ?? 'User'),
                'username' => $u->username,
                'photo_url' => $u->photo ? url('uploads/member_images/' . $u->photo) : url('uploads/no_image.jpg'),
            ];
        });

        return response()->json(['followers' => $followers]);
    }

    public function followingOf(User $user)
    {
        $following = $user->following()
            ->select('users.id', 'users.firstname', 'users.lastname', 'users.username', 'users.photo')
            ->paginate(20);

        $following->getCollection()->transform(function ($u) {
            return [
                'id' => $u->id,
                'name' => trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? '')) ?: ($u->username ?? 'User'),
                'username' => $u->username,
                'photo_url' => $u->photo ? url('uploads/member_images/' . $u->photo) : url('uploads/no_image.jpg'),
            ];
        });

        return response()->json(['following' => $following]);
    }

    public function myFollowingIds()
    {
        $me = Auth::user();
        if (!$me) {
            return response()->json(['ids' => []]);
        }
        $ids = $me->following()->pluck('users.id');
        return response()->json(['ids' => $ids]);
    }


   
    // public function storePost(Request $request)
    // {
    //     // Validate the request
    //     $validatedData = $request->validate([
    //         'content' => 'nullable|string|required_without_all:photo,video',
    //         'photo' => 'nullable|image|max:2048|required_without_all:content,video', // Max 2MB
    //         'video' => 'nullable|mimes:mp4,mov,avi|max:10240|required_without_all:content,photo', // Max 10MB
    //         'audience' => 'required|string',
    //     ], [
    //         'content.required_without_all' => 'You must provide content, a photo, or a video.',
    //         'photo.required_without_all' => 'You must provide content, a photo, or a video.',
    //         'video.required_without_all' => 'You must provide content, a photo, or a video.',
    //     ]);

    //     // Initialize file paths
    //     $photoFilename = null;
    //     $videoFilename = null;

    //     // Store photo if uploaded
    //     if ($request->hasFile('photo')) {
    //         $photoFilename = $this->fileStorageService->storeFile(
    //             $request->file('photo'),
    //             'community/photos'
    //         );
    //     }

    //     // Store video if uploaded
    //     if ($request->hasFile('video')) {
    //         $videoFilename = $this->fileStorageService->storeFile(
    //             $request->file('video'),
    //             'community/videos'
    //         );
    //     }

    //     // Retrieve additional profile data
    //     $profileData = $this->getProfileData();
    //     $country_id = $profileData->country_id;  // Assuming the user has a country relationship
    //     $region_id = $profileData->region_id;    // Assuming the user has a region relationship
    //     $state_id = $profileData->state_id;      // Assuming the user has a state relationship
    //     $lga_id = $profileData->lga_id;          // Assuming the user has a LGA relationship
    //     $ward_id = $profileData->ward_id;        // Assuming the user has a ward relationship
    //     $pu_id = $profileData->polling_unit_id ; // Assuming the user has a polling unit relationship

    //     // Create the post
    //     $post = $profileData->posts()->create([
    //         'content' => $validatedData['content'],
    //         'image_url' => $photoFilename,
    //         'video_url' => $videoFilename,
    //         'audience' => $validatedData['audience'],
    //         'country_id' => $country_id,
    //         'region_id' => $region_id,
    //         'state_id' => $state_id,
    //         'lga_id' => $lga_id,
    //         'ward_id' => $ward_id,
    //         'pu_id' => $pu_id,
    //         'post_type' => $photoFilename && $videoFilename ? 'mixed' : ($photoFilename ? 'image' : ($videoFilename ? 'video' : 'text')),
    //     ]);


    //        // Only broadcast if WebSocket is available (local environment)
    //         // if (app()->WEB_SOCKET_AVAILABILITY==true) {
    //             // Broadcast the event
    //             broadcast(new NewPostCreated($post))->toOthers();
    //        // }

    //     // Respond with the new post (to update the feed dynamically)
    //     return response()->json([
    //         'status' => 'success',
    //         'post' => $post,
    //         'photo_url' => $photoFilename ? $this->fileStorageService->getFileUrl('uploads/community/photos/' . $photoFilename) : null,
    //         'video_url' => $videoFilename ? $this->fileStorageService->getFileUrl('uploads/community/videos/' . $videoFilename) : null,
    //     ]);
    // }



    public function storePost(Request $request)
    {
        try {
            $settings = SystemSetting::first();
            $dailyPostLimit = $settings->community_daily_post_limit ?? null;
            $profileData = $this->getProfileData();

            if (!$profileData) {
                throw new \Exception('Profile data not found.');
            }

            app(CommunityModerationService::class)->assertCommunityAvailable($profileData, 'community.post.create');
            app(CommunityModerationService::class)->assertCanUploadMedia(
                $profileData,
                $request->file('photo'),
                $request->file('video')
            );

            // Enforce daily post limit
            if ($dailyPostLimit) {
                $todayCount = Post::where('user_id', Auth::id())->whereDate('created_at', Carbon::today())->count();
                if ($todayCount >= $dailyPostLimit) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Daily post limit reached. Try again tomorrow.',
                    ], 429);
                }
            }

            $validatedData = $request->validate(app(CommunityModerationService::class)->postValidationRules(), [
                'content.required_without_all' => 'You must provide content, a photo, or a video.',
                'photo.required_without_all' => 'You must provide content, a photo, or a video.',
                'video.required_without_all' => 'You must provide content, a photo, or a video.',
                'photo.prohibited' => 'Image uploads are disabled for the Community Forum.',
                'video.prohibited' => 'Video uploads are disabled for the Community Forum.',
                'photo.max' => 'The image is larger than the allowed Community Forum image size.',
                'video.max' => 'The video is larger than the allowed Community Forum video size.',
            ]);

            $audience = app(CommunityAudienceService::class)->validateSelection($profileData, $request->all());
    
            // Initialize file paths
            $photoFilename = null;
            $videoFilename = null;
    
            // Store photo if uploaded
            if ($request->hasFile('photo')) {
                $photoFilename = $this->fileStorageService->storeFile(
                    $request->file('photo'),
                    'community/photos'
                );
                if (!$photoFilename) {
                    throw new \Exception('Failed to store the photo.');
                }
            }
    
            // Store video if uploaded
            if ($request->hasFile('video')) {
                $videoFilename = $this->fileStorageService->storeFile(
                    $request->file('video'),
                    'community/videos'
                );
                if (!$videoFilename) {
                    throw new \Exception('Failed to store the video.');
                }
            }
    
            $country_id = $profileData->country_id;  // Assuming the user has a country relationship
            $region_id = $profileData->region_id;    // Assuming the user has a region relationship
            $state_id = $profileData->state_id;      // Assuming the user has a state relationship
            $lga_id = $profileData->lga_id;          // Assuming the user has a LGA relationship
            $ward_id = $profileData->ward_id;        // Assuming the user has a ward relationship
            $pu_id = $profileData->polling_unit_id;  // Assuming the user has a polling unit relationship
    
            $postPayload = [
                'content' => $validatedData['content'] ?? null,
                'image_url' => $photoFilename,
                'video_url' => $videoFilename,
                'audience' => $audience['audience'],
                'country_id' => $country_id,
                'region_id' => $region_id,
                'state_id' => $state_id,
                'lga_id' => $lga_id,
                'ward_id' => $ward_id,
                'pu_id' => $pu_id,
                'post_type' => $photoFilename && $videoFilename ? 'mixed' : ($photoFilename ? 'image' : ($videoFilename ? 'video' : 'text')),
            ];

            if (app(CommunityAudienceService::class)->hasAudienceColumns()) {
                $postPayload += [
                    'audience_type' => $audience['audience_type'],
                    'audience_scope_type' => $audience['audience_scope_type'],
                    'audience_scope_id' => $audience['audience_scope_id'],
                    'audience_group_id' => $audience['audience_group_id'],
                    'audience_metadata' => $audience['audience_metadata'],
                ];
            }

            // Create the post
            $post = $profileData->posts()->create($postPayload);

            if (!$post) {
                throw new \Exception('Failed to create the post.');
            }

            $post->load('user');
            app(CommunityModerationService::class)->annotatePost($post, $profileData);

            // Broadcast the event (if WebSocket is available), but fail soft to allow polling fallback
            if (config('app.web_socket_availability')) {
                try {
                    broadcast(new NewPostCreated($post))->toOthers();
                } catch (\Throwable $e) {
                    Log::warning('Post broadcast failed, falling back to polling', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Create activity notification for this post without blocking the saved post.
            try {
                $this->createActivityNotificationForPost($profileData, $post, $audience);
            } catch (\Throwable $e) {
                Log::warning('Post notification creation failed, continuing with polling fallback', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);
            }
    
            // Respond with the new post
            return response()->json([
                'status' => 'success',
                'post' => $post,
                'photo_url' => $photoFilename ? $this->fileStorageService->getFileUrl('uploads/community/photos/' . $photoFilename) : null,
                'video_url' => $videoFilename ? $this->fileStorageService->getFileUrl('uploads/community/videos/' . $videoFilename) : null,
            ]);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (HttpExceptionInterface $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?: 'This community action is not allowed.',
            ], $e->getStatusCode());
    
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error in storePost: ' . $e->getMessage());
    
            // Return a generic error message
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing your request. Please try again.',
            ], 500);
        }
    }

    private function createActivityNotificationForPost(User $actor, Post $post, array $audience): void
    {
        $targetScope = 'public';
        $scopeId = null;

        if (($audience['audience_type'] ?? null) === CommunityAudienceService::TYPE_SUPPORT_GROUP) {
            $targetScope = 'support_group';
            $scopeId = $audience['audience_group_id'] ?? null;
        } elseif (($audience['audience_type'] ?? null) !== CommunityAudienceService::TYPE_GLOBAL) {
            $targetScope = (string) ($audience['audience_scope_type'] ?? 'public');
            $scopeId = $audience['audience_scope_id'] ?? null;
        }

        $notification = ActivityNotification::create([
            'type' => 'post',
            'actor_id' => $actor->id,
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'scope' => $targetScope,
            'scope_id' => $scopeId,
            'data' => [
                'message' => trim(($actor->firstname ?? '') . ' ' . ($actor->lastname ?? '')) . ' made a new post',
                'post_id' => $post->id,
                'snippet' => mb_substr(strip_tags((string) $post->content), 0, 100),
                'url' => url("/community/feed#post-{$post->id}"),
            ],
        ]);

        try {
            broadcast(new NewActivityNotification($notification))->toOthers();
        } catch (\Throwable $e) {
            // Silent failover to polling
        }
    }

public function fetchPosts(Request $request)
{
    // Get the audience, page, and last_post_timestamp parameters from the request
    $lastPostTimestamp = $request->input('last_post_timestamp'); // Timestamp of the latest post in the feed
    $perPage = $request->input('per_page', 10); // Number of posts per page
    $userId = Auth::id();

    // Initialize the query builder for posts
    $query = Post::with('user')->withCount('comments')->withCount('likedBy as likes_count');

    // Get profile data for filtering
    $profileData = $this->getProfileData();
    $this->applyPostAuthorScope($query, $profileData);

    app(CommunityAudienceService::class)->applyVisibility($query, $profileData);

    // Filter posts created after the last_post_timestamp
    if ($lastPostTimestamp) {
        $query->where('created_at', '>', $lastPostTimestamp);
    }

    // Order by creation date and paginate the results
    $posts = $query->latest()->paginate($perPage);

    // Append liked_by_me and liker_names for UI
    $posts->getCollection()->transform(function ($post) use ($userId, $profileData) {
        $post->liked_by_me = $userId ? $post->likedBy()->where('user_id', $userId)->exists() : false;
        $post->liker_names = $post->likedBy()
            ->limit(5)
            ->get()
            ->map(function ($u) {
                $name = trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? ''));
                return $name ?: ($u->name ?? $u->email ?? 'Unknown');
            })
            ->filter()
            ->values();
        return app(CommunityModerationService::class)->annotatePost($post, $profileData);
    });

    return response()->json($posts);
  }

    public function audienceOptions()
    {
        return response()->json(app(CommunityAudienceService::class)->options(Auth::user()));
    }

   
    public function fetchAuthUserPosts(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([]);
        }

        $perPage = (int) $request->input('per_page', 10);

        $posts = Post::with('user')
            ->withCount('comments')
            ->withCount('likedBy as likes_count')
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);

        $actor = Auth::user();
        $posts->getCollection()->transform(function ($post) use ($userId, $actor) {
            $post->liked_by_me = $post->likedBy()->where('user_id', $userId)->exists();
            $post->liker_names = $post->likedBy()
                ->limit(5)
                ->get()
                ->map(function ($u) {
                    $name = trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? ''));
                    return $name ?: ($u->username ?? 'User');
                })
                ->filter()
                ->values();
            return app(CommunityModerationService::class)->annotatePost($post, $actor);
        });

        return response()->json($posts);
    }

    public function viewUserPosts($username){
        $userData= User::Where("username",$username)->firstOrFail();
        $pageTitle = $userData->firstname.' '.$userData->lastname." Profile Timeline";
        $profileData = $this->getProfileData();
        $userSupportGroups = $userData->supportGroups()->pluck('name')->toArray();
        // Get followers and following counts
        $followersCount = $userData->followers()->count();
        $followingCount = $userData->following()->count();
        $postsCount = $userData->posts()->count();
         // Get paginated followers and following data
        $followers = $userData->followers()->paginate(10); // Paginate with 10 items per page
        $following = $userData->following()->paginate(10); // Paginate with 10 items per pagefollowing = $profileData->following()->get(); // Users this profile is following
        
       

        return view('frontend.ViewUserprofile', compact(
            'profileData',
        'userData',
             'userSupportGroups',
            'followersCount',
            'followingCount',
            'postsCount',
            'followers',
            'following',
            'pageTitle'
        ));
    }


    public function viewUserProfilePost(Request $request, $username)
    {
        $me = Auth::id();
        $perPage = (int) $request->input('per_page', 10);
        $userData = User::where('username', $username)->firstOrFail();

        $posts = Post::with('user')
            ->withCount('comments')
            ->withCount('likedBy as likes_count')
            ->where('user_id', $userData->id);

        $actor = Auth::user();
        app(CommunityAudienceService::class)->applyVisibility($posts, $actor);

        $posts = $posts
            ->latest()
            ->paginate($perPage);
        $posts->getCollection()->transform(function ($post) use ($me, $actor) {
            $post->liked_by_me = $me ? $post->likedBy()->where('user_id', $me)->exists() : false;
            $post->liker_names = $post->likedBy()
                ->limit(5)
                ->get()
                ->map(function ($u) {
                    $name = trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? ''));
                    return $name ?: ($u->username ?? 'User');
                })
                ->filter()
                ->values();
            return app(CommunityModerationService::class)->annotatePost($post, $actor);
        });

        return response()->json($posts);
    }

    public function messagesPage()
    {
        $pageTitle = "Internal Communication";
        $profileData = $this->getProfileData();
        return view('backend.messages', compact('profileData', 'pageTitle'));
    }

     // Show the edit form
     public function editPost(Post $post)
     {
         $this->ensureCanManagePost($post);
         return response()->json([
             'post' => $post,
         ]);
     }
 
     // Update the post
     public function updatePost(Request $request, Post $post)
     {
         $this->ensureCanManagePost($post);
         $settings = SystemSetting::first();
         $postMaxLength = $settings->community_post_max_length ?? 750;
         // Validate incoming request
         $validatedData = $request->validate([
             'content' => "required|string|max:{$postMaxLength}",
         ]);

         // Update the post
         $post->update([
             'content' => $validatedData['content'],
         ]);

     return response()->json([
         'message' => 'Post updated successfully!',
         'post' => $post->fresh('user'),
     ]);
 }

    public function likePost(Post $post)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        if (!app(CommunityModerationService::class)->postInActorScope(Auth::user(), $post)) {
            return response()->json(['status' => 'error', 'message' => 'Post is outside your community scope.'], 403);
        }

        $alreadyLiked = $post->likedBy()->where('user_id', $userId)->exists();

        if ($alreadyLiked) {
            // Unlike
            $post->likedBy()->detach($userId);
        } else {
            // Like
            $post->likedBy()->attach($userId);
            try {
                $this->notifyPostLike($post, Auth::user());
            } catch (\Throwable $e) {
                Log::warning('Like notification creation failed, continuing with polling fallback', [
                    'post_id' => $post->id,
                    'actor_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $likes = $post->likedBy()->count();

        return response()->json([
            'status' => 'success',
            'likes' => $likes,
            'liked' => !$alreadyLiked,
        ]);
    }

    private function notifyPostLike(Post $post, User $actor): void
    {
        if (!$actor || $post->user_id === $actor->id) {
            return;
        }

        $scopeOrder = ['pu' => 0, 'ward' => 1, 'lga' => 2, 'state' => 3, 'region' => 4, 'public' => 5];
        $accessScopeMap = [
            'superadmin' => 'public',
            'nationaladmin' => 'public',
            'regionaladmin' => 'region',
            'stateadmin' => 'state',
            'lgaadmin' => 'lga',
            'wardadmin' => 'ward',
            'puadmin' => 'pu',
            'user' => 'pu',
        ];

        $actorScope = $accessScopeMap[$actor->access_level] ?? 'pu';
        $actorRank = $scopeOrder[$actorScope] ?? 0;
        $postAudience = $post->audience ?? 'pu';
        $audienceRank = $scopeOrder[$postAudience] ?? $actorRank;
        $targetScope = $actorRank <= $audienceRank ? $actorScope : $postAudience;

        $scopeId = null;
        switch ($targetScope) {
            case 'region':
                $scopeId = $actor->region_id;
                break;
            case 'state':
                $scopeId = $actor->state_id;
                break;
            case 'lga':
                $scopeId = $actor->lga_id;
                break;
            case 'ward':
                $scopeId = $actor->ward_id;
                break;
            case 'pu':
                $scopeId = $actor->polling_unit_id;
                break;
            default:
                $scopeId = null;
        }

        $postOwner = $post->user;
        $ownerUsername = $postOwner?->username;
        $postUrl = $ownerUsername
            ? url("/community/{$ownerUsername}/profile/timeline#post-{$post->id}")
            : url("/community/feed#post-{$post->id}");

        $notification = ActivityNotification::create([
            'type' => 'like',
            'actor_id' => $actor->id,
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'scope' => 'direct',
            'scope_id' => $post->user_id, // notify post owner only
            'data' => [
                'message' => trim(($actor->firstname ?? '') . ' ' . ($actor->lastname ?? '')) . ' liked your post',
                'post_id' => $post->id,
                'url' => $postUrl,
            ],
        ]);

        try {
            broadcast(new NewActivityNotification($notification))->toOthers();
        } catch (\Throwable $e) {
            // ignore broadcast failure
        }
    }

    public function postLikers(Request $request, Post $post)
    {
        if (!Auth::user() || !app(CommunityModerationService::class)->postInActorScope(Auth::user(), $post)) {
            return response()->json(['status' => 'error', 'message' => 'Post is outside your community scope.'], 403);
        }

        $page = max((int) $request->input('page', 1), 1);
        $perPage = min(max((int) $request->input('per_page', 50), 1), 200);

        $paginator = $post->likedBy()
            ->select('users.id', 'users.firstname', 'users.lastname', 'users.email', 'users.username', 'users.photo')
            ->paginate($perPage, ['*'], 'page', $page);

        $likers = $paginator->getCollection()->map(function ($u) {
            $displayName = trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? ''));
            $displayName = $displayName ?: ($u->email ?? $u->username ?? 'Unknown');

            $photoUrl = $u->photo
                ? url('uploads/member_images/' . $u->photo)
                : url('uploads/no_image.jpg');

            $profileUrl = $u->username
                ? url('/community/' . $u->username . '/profile/timeline')
                : '#';

            return [
                'id' => $u->id,
                'name' => $displayName,
                'email' => $u->email,
                'username' => $u->username,
                'photo_url' => $photoUrl,
                'profile_url' => $profileUrl,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'likers' => $likers,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
 
     // Delete the post
     public function destroyPost(Post $post)
     {
         $this->ensureCanDeletePost($post);
         app(CommunityModerationService::class)->cleanupPostMedia($post);
         $post->delete();
 
         return response()->json([
             'message' => 'Post deleted successfully!',
         ]);
     }



    

     public function updateProfilePhoto(Request $request)
     {
         $user = Auth::user();
     
         // Validate file type and size
         $request->validate([
             'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
         ]);
     
         if ($request->hasFile('photo')) {
             $file = $request->file('photo');
             $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
             
             // Move file to both locations
             $file->move(public_path('uploads/member_images/'), $filename);
             copy(public_path('uploads/member_images/' . $filename), public_path('uploads/community/photos/' . $filename));
     
             // Delete old images if they exist
             if ($user->photo) {
                 $oldProfilePath = public_path('uploads/member_images/' . $user->photo);
                 $oldCommunityPath = public_path('uploads/community/photos/' . $user->photo);
                 
                 if (file_exists($oldProfilePath)) unlink($oldProfilePath);
                 if (file_exists($oldCommunityPath)) unlink($oldCommunityPath);
             }
     
             // Update user's profile photo
             $user->photo = $filename;
             $user->save();

             // Retrieve additional profile data
            $profileData = $this->getProfileData();
            $country_id = $profileData->country_id;  // Assuming the user has a country relationship
            $region_id = $profileData->region_id;    // Assuming the user has a region relationship
            $state_id = $profileData->state_id;      // Assuming the user has a state relationship
            $lga_id = $profileData->lga_id;          // Assuming the user has a LGA relationship
            $ward_id = $profileData->ward_id;        // Assuming the user has a ward relationship
            $pu_id = $profileData->polling_unit_id ; // Assuming the user has a polling unit relationship

     
             // Create a post with correct image URL
             Post::create([
                 'user_id' => $user->id,
                 'content' => $user->firstname . ' ' . $user->lastname . ' has updated their profile photo.',
                 'image_url' => $filename, // Post uses community photos
                 'post_type' => 'image',                 
                'audience' => 'pu',
                'country_id' => $country_id,
                'region_id' => $region_id,
                'state_id' => $state_id,
                'lga_id' => $lga_id,
                'ward_id' => $ward_id,
                'pu_id' => $pu_id,
             ]);
     
             return response()->json([
                 'success' => true,
                 'image_url' => url('uploads/member_images/' . $filename),
             ]);
         }
     
         return response()->json(['success' => false, 'error' => 'File upload failed']);
     }
     

     public function updateCoverPhoto(Request $request)
     {
         $user = Auth::user();
     
         // Validate file type and size
         $request->validate([
             'cover_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
         ]);
     
         if ($request->hasFile('cover_image')) {
             $file = $request->file('cover_image');
             $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
             
             // Move file to both locations
             $file->move(public_path('uploads/member_images/'), $filename);
             copy(public_path('uploads/member_images/' . $filename), public_path('uploads/community/photos/' . $filename));
     
             // Delete old images if they exist
             if ($user->cover_image) {
                 $oldCoverPath = public_path('uploads/member_images/' . $user->cover_image);
                 $oldCommunityPath = public_path('uploads/community/photos/' . $user->cover_image);
                 
                 if (file_exists($oldCoverPath)) unlink($oldCoverPath);
                 if (file_exists($oldCommunityPath)) unlink($oldCommunityPath);
             }
     
             // Update user's cover image
             $user->cover_image = $filename;
             $user->save();

               // Retrieve additional profile data
            $profileData = $this->getProfileData();
            $country_id = $profileData->country_id;  // Assuming the user has a country relationship
            $region_id = $profileData->region_id;    // Assuming the user has a region relationship
            $state_id = $profileData->state_id;      // Assuming the user has a state relationship
            $lga_id = $profileData->lga_id;          // Assuming the user has a LGA relationship
            $ward_id = $profileData->ward_id;        // Assuming the user has a ward relationship
            $pu_id = $profileData->polling_unit_id ; // Assuming the user has a polling unit relationship

     
             // Create a post with correct image URL
             Post::create([
                 'user_id' => $user->id,
                 'content' => $user->firstname . ' ' . $user->lastname . ' has updated their cover photo.',
                 'image_url' => $filename, // Post uses community photos
                 'post_type' => 'image',                
                 'audience' => 'pu',
                 'country_id' => $country_id,
                 'region_id' => $region_id,
                 'state_id' => $state_id,
                 'lga_id' => $lga_id,
                 'ward_id' => $ward_id,
                 'pu_id' => $pu_id,
             ]);
     
             return response()->json([
                 'success' => true,
                 'image_url' => url('uploads/member_images/' . $filename),
             ]);
         }
     
         return response()->json(['success' => false, 'error' => 'File upload failed']);
     }
    
    
    
    private function ensureCanManagePost(Post $post): void
    {
        $user = Auth::user();

        if (!$user || !app(CommunityModerationService::class)->canEditPost($user, $post)) {
            abort(403, 'You are not allowed to modify this post.');
        }
    }

    private function ensureCanDeletePost(Post $post): void
    {
        $user = Auth::user();

        if (!$user || !app(CommunityModerationService::class)->canDeletePost($user, $post)) {
            abort(403, 'You are not allowed to delete this post.');
        }
    }

    private function applyPostAuthorScope($query, ?User $actor): void
    {
        if (!$actor) {
            $query->whereRaw('1 = 0');
            return;
        }

        $authorQuery = User::query()->select('users.id');
        app(LicensedScopeQueryService::class)->applyToUsersQuery($authorQuery);

        $query->whereIn('user_id', $authorQuery);
    }
}
