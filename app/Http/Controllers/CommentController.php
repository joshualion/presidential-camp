<?php
// CommentController.php
namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\ActivityNotification;
use Illuminate\Http\Request;
use App\Events\NewCommentCreated;
use App\Events\NewActivityNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\SystemSetting;
use App\Services\CommunityModerationService;
use Carbon\Carbon;

class CommentController extends Controller
{
    // Store a new comment
    public function store(Request $request, Post $post)
    {
        $actor = Auth::user();
        if (!$actor) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        if (!app(CommunityModerationService::class)->canCreateComment($actor)
            || !app(CommunityModerationService::class)->postInActorScope($actor, $post)) {
            return response()->json(['status' => 'error', 'message' => 'You are not allowed to comment on this post.'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:' . ($this->commentMaxLength()),
            'parent_id' => 'nullable|exists:comments,id', // For nested comments
        ]);

        if (!empty($validated['parent_id'])
            && !Comment::query()->whereKey($validated['parent_id'])->where('post_id', $post->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected parent comment does not belong to this post.',
            ], 422);
        }

        $settings = SystemSetting::first();
        $dailyCommentLimit = $settings->community_daily_comment_limit ?? null;
        if ($dailyCommentLimit) {
            $todayCount = Comment::where('user_id', Auth::id())->whereDate('created_at', Carbon::today())->count();
            if ($todayCount >= $dailyCommentLimit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Daily comment limit reached. Try again tomorrow.',
                ], 429);
            }
        }

        $comment = $post->comments()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        try {
            broadcast(new NewCommentCreated($comment))->toOthers();
        } catch (\Throwable $exception) {
            Log::warning('Comment broadcast failed, falling back to polling', [
                'comment_id' => $comment->id,
                'post_id' => $post->id,
                'error' => $exception->getMessage(),
            ]);
        }

        // Notify post author (if different), without blocking comment creation.
        try {
            $this->notifyPostAuthor($post, $comment);
        } catch (\Throwable $exception) {
            Log::warning('Comment notification creation failed, continuing with polling fallback', [
                'comment_id' => $comment->id,
                'post_id' => $post->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'comment' => $comment->load('user'), // Load the user relationship
        ]);
    }

    // Fetch comments for a post
    public function index(Post $post)
    {
        $actor = Auth::user();
        if (!$actor || !app(CommunityModerationService::class)->postInActorScope($actor, $post)) {
            return response()->json(['status' => 'error', 'message' => 'Post is outside your community scope.'], 403);
        }

        $comments = $post->comments()
            ->with('user', 'replies.user') // Load user and replies
            ->whereNull('parent_id') // Only top-level comments
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'comments' => $comments,
        ]);
    }

    // Delete a comment
    public function destroy(Comment $comment)
    {
        $actor = Auth::user();
        if (!$actor || !app(CommunityModerationService::class)->canDeleteComment($actor, $comment)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment deleted successfully',
        ]);
    }

    // Update a comment
    public function update(Request $request, Comment $comment)
    {
        $request->validate([
            'content' => 'required|string|max:' . ($this->commentMaxLength()),
        ]);

        $actor = Auth::user();
        if (!$actor || !app(CommunityModerationService::class)->canEditComment($actor, $comment)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $comment->update(['content' => $request->input('content')]);

        return response()->json([
            'status' => 'success',
            'comment' => $comment->load('user'),
        ]);
    }

    private function notifyPostAuthor(Post $post, Comment $comment): void
    {
        $actor = Auth::user();
        if (!$actor) {
            return;
        }
        // Don't notify if actor is the post author
        if ($post->user_id === $actor->id) {
            return;
        }

        $postOwner = $post->user;
        if (!$postOwner || $postOwner->id === $actor->id) {
            return;
        }

        $ownerUsername = $postOwner?->username;
        $postUrl = $ownerUsername
            ? url("/community/{$ownerUsername}/profile/timeline#post-{$post->id}")
            : url("/community/feed#post-{$post->id}");

        $notification = ActivityNotification::create([
            'type' => 'comment',
            'actor_id' => $actor->id,
            'subject_type' => Comment::class,
            'subject_id' => $comment->id,
            'scope' => 'direct', // notify post owner only
            'scope_id' => $postOwner->id,
            'data' => [
                'message' => trim(($actor->firstname ?? '') . ' ' . ($actor->lastname ?? '')) . ' commented on your post',
                'post_id' => $post->id,
                'comment_id' => $comment->id,
                'snippet' => mb_substr(strip_tags((string) $comment->content), 0, 100),
                'url' => $postUrl,
            ],
        ]);

        try {
            broadcast(new NewActivityNotification($notification))->toOthers();
        } catch (\Throwable $e) {
            // Ignore broadcast failures
        }
    }

    private function commentMaxLength(): int
    {
        $settings = SystemSetting::first();
        return (int) ($settings->community_comment_max_length ?? 750);
    }
}
