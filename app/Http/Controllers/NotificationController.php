<?php

namespace App\Http\Controllers;

use App\Models\ActivityNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function unreadCount()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['unread' => 0]);
        }

        $count = ActivityNotification::visibleTo($user)
            ->where(function ($q) use ($user) {
                $q->whereNotIn('type', ['like', 'comment'])
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereIn('type', ['like', 'comment'])
                            ->where('scope', 'direct')
                            ->where('scope_id', $user->id);
                    });
            })
            ->where(function ($q) use ($user) {
                $q->where('type', '!=', 'post')
                    ->orWhere('actor_id', '!=', $user->id);
            })
            ->whereNull('read_at')
            ->count();

        return response()->json(['unread' => $count]);
    }

    public function feed(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['data' => []]);
        }

        $notifications = ActivityNotification::with('actor:id,firstname,lastname,photo')
            ->visibleTo($user)
            ->where(function ($q) use ($user) {
                $q->whereNotIn('type', ['like', 'comment'])
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereIn('type', ['like', 'comment'])
                            ->where('scope', 'direct')
                            ->where('scope_id', $user->id);
                    });
            })
            ->where('actor_id', '!=', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $notifications->getCollection()->transform(function ($n) {
            $actor = $n->actor;
            return [
                'id' => $n->id,
                'type' => $n->type,
                'scope' => $n->scope,
                'scope_id' => $n->scope_id,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
                'actor' => $actor ? [
                    'id' => $actor->id,
                    'name' => trim(($actor->firstname ?? '') . ' ' . ($actor->lastname ?? '')),
                    'avatar' => $actor->photo ? url('uploads/member_images/' . $actor->photo) : url('uploads/no_image.jpg'),
                ] : null,
                'data' => $n->data ?? [],
            ];
        });

        return response()->json($notifications);
    }

    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $notifications = ActivityNotification::with('actor:id,firstname,lastname,photo')
            ->visibleTo($user)
            ->where(function ($q) use ($user) {
                $q->whereNotIn('type', ['like', 'comment'])
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereIn('type', ['like', 'comment'])
                            ->where('scope', 'direct')
                            ->where('scope_id', $user->id);
                    });
            })
            ->where('actor_id', '!=', $user->id)
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('frontend.notifications', compact('notifications', 'user'));
    }

    public function followLink($id)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $notification = ActivityNotification::visibleTo($user)
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->update(['read_at' => now()]);
        }

        $url = $notification->data['url'] ?? ($notification->data['post_id'] ?? null
            ? url("/community/feed#post-" . $notification->data['post_id'])
            : url('/community/feed'));

        return redirect($url);
    }

    public function markRead(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'ok']);
        }

        $id = $request->input('id');
        if (!$id) {
            return response()->json(['status' => 'ok']);
        }

        ActivityNotification::visibleTo($user)
            ->where('id', $id)
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    public function markAllRead()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'ok']);
        }

        try {
            ActivityNotification::visibleTo($user)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Mark all read failed', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'ok']);
    }
}
