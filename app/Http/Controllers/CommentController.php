<?php
// CommentController.php
namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Events\NewCommentCreated;

class CommentController extends Controller
{
    // Store a new comment
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id', // For nested comments
        ]);

        $comment = $post->comments()->create([
            'user_id' => auth()->id(),
            'content' => $request->input('content'),
            'parent_id' => $request->input('parent_id'),
        ]);

        // Broadcast the NewCommentCreated event
        broadcast(new NewCommentCreated($comment))->toOthers();

        return response()->json([
            'status' => 'success',
            'comment' => $comment->load('user'), // Load the user relationship
        ]);
    }

    // Fetch comments for a post
    public function index(Post $post)
    {
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
        // Ensure the authenticated user owns the comment
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment deleted successfully',
        ]);
    }
}