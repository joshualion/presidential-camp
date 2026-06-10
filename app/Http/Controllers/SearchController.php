<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{


     // Function to get the profile data
     private function getProfileData()
     {
         $id = Auth::user()->id;
         return User::find($id);
     }

    public function suggest(Request $request)
    {
        $term = trim($request->input('query', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['people' => [], 'posts' => []]);
        }

        $user = Auth::user();

        try {
            $like = "%{$term}%";
            $people = User::query()
                ->select(['id', 'firstname', 'lastname', 'username', 'photo'])
                ->where(function ($q) use ($like) {
                    $q->where('firstname', 'like', $like)
                        ->orWhere('lastname', 'like', $like)
                        ->orWhere('username', 'like', $like)
                        ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", [$like]);
                })
                ->orderBy('firstname')
                ->limit(5)
                ->get()
                ->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => trim($u->firstname . ' ' . $u->lastname),
                        'subtitle' => '@' . ($u->username ?? 'user'),
                        'avatar' => $u->photo ? url('uploads/member_images/' . $u->photo) : url('uploads/no_image.jpg'),
                        'url' => $u->username
                            ? url("/community/{$u->username}/profile/timeline")
                            : url('/community/profile'),
                    ];
                });

            $posts = Post::query()
                ->with('user:id,firstname,lastname,username,photo')
                ->where('content', 'like', $like)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->filter(function ($post) use ($user) {
                    return $this->canSeePost($user, $post);
                })
                ->values()
                ->map(function ($post) {
                    $author = $post->user;
                    return [
                        'id' => $post->id,
                        'title' => mb_substr(strip_tags((string) $post->content), 0, 60),
                        'snippet' => mb_substr(strip_tags((string) $post->content), 0, 120),
                        'url' => url('/community/feed') . '#post-' . $post->id,
                        'author' => $author ? trim($author->firstname . ' ' . $author->lastname) : 'Unknown',
                    ];
                });

            return response()->json([
                'people' => $people,
                'posts' => $posts,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Search suggest failed', ['error' => $e->getMessage()]);
            return response()->json(['people' => [], 'posts' => []]);
        }
    }

    public function index(Request $request)
    {
        $term = trim($request->input('query', ''));
        $type = $request->input('type', 'all');
        $user = Auth::user();

        $people = collect();
        $posts = collect();

        if ($term !== '') {
            if ($type === 'all' || $type === 'people') {
                $like = "%{$term}%";
                $people = User::query()
                    ->select(['id', 'firstname', 'lastname', 'username', 'photo'])
                    ->where(function ($q) use ($like) {
                        $q->where('firstname', 'like', $like)
                            ->orWhere('lastname', 'like', $like)
                            ->orWhere('username', 'like', $like)
                            ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", [$like]);
                    })
                    ->orderBy('firstname')
                    ->paginate(10, ['*'], 'people_page')
                    ->withQueryString();
            }

            if ($type === 'all' || $type === 'posts') {
                $postsQuery = Post::query()
                    ->with('user:id,firstname,lastname,username,photo')
                    ->where('content', 'like', "%{$term}%")
                    ->orderByDesc('created_at');

                $posts = $postsQuery->paginate(10, ['*'], 'posts_page')->withQueryString();
                $posts->getCollection()->transform(function ($post) use ($user) {
                    $post->can_view = $this->canSeePost($user, $post);
                    return $post;
                });
            }
        }

        $pageTitle = 'Search Results';
        $profileData = $this->getProfileData();

        return view('frontend.search', [
            'term' => $term,
            'type' => $type,
            'people' => $people,
            'posts' => $posts,
            'pageTitle' => $pageTitle,
            'profileData' => $profileData,
        ]);
    }

    private function canSeePost($user, $post): bool
    {
        // Placeholder for audience-aware logic; allow all for now.
        return true;
    }
}
