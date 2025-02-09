<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\FileStorageService;
use App\Events\NewPostCreated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

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
         // Get followers and following data
        $followers = $profileData->followers()->get(); // Users following this profile
        $following = $profileData->following()->get(); // Users this profile is following
     
       

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
         // Get followers and following data
        $followers = $profileData->followers()->get(); // Users following this profile
        $following = $profileData->following()->get(); // Users this profile is following
     
       

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


   
    public function storePost(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'content' => 'nullable|string|required_without_all:photo,video',
            'photo' => 'nullable|image|max:2048|required_without_all:content,video', // Max 2MB
            'video' => 'nullable|mimes:mp4,mov,avi|max:10240|required_without_all:content,photo', // Max 10MB
            'audience' => 'required|string',
        ], [
            'content.required_without_all' => 'You must provide content, a photo, or a video.',
            'photo.required_without_all' => 'You must provide content, a photo, or a video.',
            'video.required_without_all' => 'You must provide content, a photo, or a video.',
        ]);

        // Initialize file paths
        $photoFilename = null;
        $videoFilename = null;

        // Store photo if uploaded
        if ($request->hasFile('photo')) {
            $photoFilename = $this->fileStorageService->storeFile(
                $request->file('photo'),
                'community/photos'
            );
        }

        // Store video if uploaded
        if ($request->hasFile('video')) {
            $videoFilename = $this->fileStorageService->storeFile(
                $request->file('video'),
                'community/videos'
            );
        }

        // Retrieve additional profile data
        $profileData = $this->getProfileData();
        $country_id = $profileData->country_id;  // Assuming the user has a country relationship
        $region_id = $profileData->region_id;    // Assuming the user has a region relationship
        $state_id = $profileData->state_id;      // Assuming the user has a state relationship
        $lga_id = $profileData->lga_id;          // Assuming the user has a LGA relationship
        $ward_id = $profileData->ward_id;        // Assuming the user has a ward relationship
        $pu_id = $profileData->polling_unit_id ; // Assuming the user has a polling unit relationship

        // Create the post
        $post = $profileData->posts()->create([
            'content' => $validatedData['content'],
            'image_url' => $photoFilename,
            'video_url' => $videoFilename,
            'audience' => $validatedData['audience'],
            'country_id' => $country_id,
            'region_id' => $region_id,
            'state_id' => $state_id,
            'lga_id' => $lga_id,
            'ward_id' => $ward_id,
            'pu_id' => $pu_id,
            'post_type' => $photoFilename && $videoFilename ? 'mixed' : ($photoFilename ? 'image' : ($videoFilename ? 'video' : 'text')),
        ]);


           // Only broadcast if WebSocket is available (local environment)
            // if (app()->WEB_SOCKET_AVAILABILITY==true) {
                // Broadcast the event
                broadcast(new NewPostCreated($post))->toOthers();
           // }

        // Respond with the new post (to update the feed dynamically)
        return response()->json([
            'status' => 'success',
            'post' => $post,
            'photo_url' => $photoFilename ? $this->fileStorageService->getFileUrl('uploads/community/photos/' . $photoFilename) : null,
            'video_url' => $videoFilename ? $this->fileStorageService->getFileUrl('uploads/community/videos/' . $videoFilename) : null,
        ]);
    }


  
    public function fetchPosts(Request $request)
{
    // Get the audience, page, and last_post_timestamp parameters from the request
    $audience = $request->input('audience', 'public');
    $lastPostTimestamp = $request->input('last_post_timestamp'); // Timestamp of the latest post in the feed
    $perPage = $request->input('per_page', 10); // Number of posts per page

    // Initialize the query builder for posts
    $query = Post::with('user');  // Assuming the relationship is named 'user'

    // Get profile data for filtering
    $profileData = $this->getProfileData();

    // Check audience type and apply filters accordingly
    switch ($audience) {
        case 'public':
            // No additional filtering for public audience
            $query->where('audience', 'public');
            break;
        case 'region':
            // Filter posts based on the region_id (user's region vs post's region)
            $regionId = $profileData->region_id;
            $query->where(function($q) use ($regionId) {
                $q->where('audience', 'public')
                ->orWhere('region_id', $regionId);
            });
            break;
        case 'state':
            // Filter posts based on the state_id (user's state vs post's state)
            $stateId = $profileData->state_id;
            $query->where(function($q) use ($stateId) {
                $q->where('audience', 'public')
                ->orWhere('state_id', $stateId);
            });
            break;
        case 'lga':
            // Filter posts based on the lga_id (user's LGA vs post's LGA)
            $lgaId = $profileData->lga_id;
            $query->where(function($q) use ($lgaId) {
                $q->where('audience', 'public')
                ->orWhere('lga_id', $lgaId);
            });
            break;
        case 'ward':
            // Filter posts based on the ward_id (user's ward vs post's ward)
            $wardId = $profileData->ward_id;
            $query->where(function($q) use ($wardId) {
                $q->where('audience', 'public')
                ->orWhere('ward_id', $wardId);
            });
            break;
        case 'pu':
            // Filter posts based on the pu_id (user's polling unit vs post's polling unit)
            $puId = $profileData->polling_unit_id;
            $query->where(function($q) use ($puId) {
                $q->where('audience', 'public')
                ->orWhere('pu_id', $puId);
            });
            break;
        default:
            // If the audience is not recognized, fall back to public
            $query->where('audience', 'public');
            break;
    }

    // Filter posts created after the last_post_timestamp
    if ($lastPostTimestamp) {
        $query->where('created_at', '>', $lastPostTimestamp);
    }

    // Order by creation date and paginate the results
    $posts = $query->latest()->paginate($perPage);

    return response()->json($posts);
}
    public function fetchAuthUserPosts(Request $request)
    {
       
        // Get the audience, page, and last_post_timestamp parameters from the request
        $audience = $request->input('audience', 'public');
        $lastPostTimestamp = $request->input('last_post_timestamp'); // Timestamp of the latest post in the feed
    
        // Get profile data for filtering
        $profileData = $this->getProfileData();
        // Initialize the query builder for posts
        $query = Post::with('user')->where('user_id', $profileData->id);  // Assuming the relationship is named 'user'

    
        // Check audience type and apply filters accordingly
        switch ($audience) {
            case 'public':
                // No additional filtering for public audience
                $query->where('audience', 'public');
                break;
            case 'region':
                // Filter posts based on the region_id (user's region vs post's region)
                $regionId = $profileData->region_id;
                $query->where(function($q) use ($regionId) {
                    $q->where('audience', 'public')
                    ->orWhere('region_id', $regionId);
                });
                break;
            case 'state':
                // Filter posts based on the state_id (user's state vs post's state)
                $stateId = $profileData->state_id;
                $query->where(function($q) use ($stateId) {
                    $q->where('audience', 'public')
                    ->orWhere('state_id', $stateId);
                });
                break;
            case 'lga':
                // Filter posts based on the lga_id (user's LGA vs post's LGA)
                $lgaId = $profileData->lga_id;
                $query->where(function($q) use ($lgaId) {
                    $q->where('audience', 'public')
                    ->orWhere('lga_id', $lgaId);
                });
                break;
            case 'ward':
                // Filter posts based on the ward_id (user's ward vs post's ward)
                $wardId = $profileData->ward_id;
                $query->where(function($q) use ($wardId) {
                    $q->where('audience', 'public')
                    ->orWhere('ward_id', $wardId);
                });
                break;
            case 'pu':
                // Filter posts based on the pu_id (user's polling unit vs post's polling unit)
                $puId = $profileData->polling_unit_id;
                $query->where(function($q) use ($puId) {
                    $q->where('audience', 'public')
                    ->orWhere('pu_id', $puId);
                });
                break;
            default:
                // If the audience is not recognized, fall back to public
                $query->where('audience', 'public');
                break;
        }
    
        // Filter posts created after the last_post_timestamp
        if ($lastPostTimestamp) {
            $query->where('created_at', '>', $lastPostTimestamp);
        }
    
        // Order by creation date and get the results
        $posts = $query->latest()->get();
    
        return response()->json($posts);
    }



     // Show the edit form
     public function editPost(Post $post)
     {
         return response()->json([
             'post' => $post,
         ]);
     }
 
     // Update the post
     public function updatePost(Request $request, Post $post)
     {
         // Validate incoming request
         $validatedData = $request->validate([
             'title' => 'required|string|max:255',
             'content' => 'required|string',
         ]);
 
         // Update the post
         $post->update($validatedData);
 
         return response()->json([
             'message' => 'Post updated successfully!',
             'post' => $post,
         ]);
     }
 
     // Delete the post
     public function destroyPost(Post $post)
     {
         // Delete the post
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
             'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:1024', // 1MB max
         ]);
     
         if ($request->hasFile('photo')) {
             $file = $request->file('photo');
             $filename = date('YmdHi') . '_' . $file->getClientOriginalName();
             
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
             'cover_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:1024', // 1MB max
         ]);
     
         if ($request->hasFile('cover_image')) {
             $file = $request->file('cover_image');
             $filename = date('YmdHi') . '_' . $file->getClientOriginalName();
             
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
     

    
    


}
