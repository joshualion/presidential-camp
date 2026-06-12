<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\LocationScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function conversations(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([]);
        }

        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->with(['participants:id,firstname,lastname,username,photo', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount([
                'messages as unread_count' => function ($q) use ($userId) {
                    $q->whereNull('read_at')->where('sender_id', '!=', $userId);
                },
            ])
            ->latest()
            ->paginate(10);

        return response()->json($conversations);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $userId = Auth::id();
        if (!$conversation->participants()->where('user_id', $userId)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = $conversation->messages()
            ->with('sender:id,firstname,lastname,username,photo')
            ->latest()
            ->paginate(50);

        // Mark unread as read for this user
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    public function startConversation(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $sender = Auth::user();
        $recipient = User::findOrFail($request->recipient_id);

        [$canStart, $existing] = $this->canInitiate($sender, $recipient);
        if (!$canStart) {
            return response()->json(['message' => 'Not allowed to start conversation'], 403);
        }

        $conversation = $existing ?? DB::transaction(function () use ($sender, $recipient) {
            $conv = Conversation::create([
                'created_by' => $sender->id,
                'type' => 'direct',
            ]);
            $conv->participants()->sync([$sender->id, $recipient->id]);
            return $conv;
        });

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $request->message,
        ]);

        broadcast(new MessageSent($message, [$sender->id, $recipient->id]))->toOthers();

        return response()->json([
            'conversation' => $conversation->load('participants'),
            'message' => $message->load('sender'),
        ]);
    }

    public function recipients(Request $request)
    {
        $sender = Auth::user();
        if (!$sender) {
            return response()->json([]);
        }
        $query = trim($request->input('q', ''));
        $groups = [];

        if ($sender->access_level === 'puadmin') {
            // Ward administrators in same ward
            $wardAdmins = User::query()
                ->select(['id', 'firstname', 'lastname', 'username', 'photo', 'access_level'])
                ->where('access_level', 'wardadmin')
                ->where('ward_id', $sender->ward_id)
                ->when($query, function ($q) use ($query) {
                    $like = "%{$query}%";
                    $q->where(function ($sub) use ($like) {
                        $sub->where('firstname', 'like', $like)
                            ->orWhere('lastname', 'like', $like)
                            ->orWhere('username', 'like', $like)
                            ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", [$like]);
                    });
                })
                ->get()
                ->map(fn ($u) => $this->mapRecipient($u));

            // PU members/admins in same PU
            $puMembers = User::query()
                ->select(['id', 'firstname', 'lastname', 'username', 'photo', 'access_level'])
                ->where('polling_unit_id', $sender->polling_unit_id)
                ->whereIn('access_level', ['puadmin', 'user'])
                ->when($query, function ($q) use ($query) {
                    $like = "%{$query}%";
                    $q->where(function ($sub) use ($like) {
                        $sub->where('firstname', 'like', $like)
                            ->orWhere('lastname', 'like', $like)
                            ->orWhere('username', 'like', $like)
                            ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", [$like]);
                    });
                })
                ->get()
                ->map(fn ($u) => $this->mapRecipient($u));

            $groups[] = ['label' => 'Ward Administrators', 'options' => $wardAdmins];
            $groups[] = ['label' => 'PU Members', 'options' => $puMembers];
        } else {
            $targets = $this->allowedTargetLevels($sender->access_level);
            $users = User::query()
                ->select(['id', 'firstname', 'lastname', 'username', 'photo', 'access_level'])
                ->whereIn('access_level', $targets);

            app(LocationScopeService::class)->applyScope($users, $sender, 'users', 'users');

            $users = $users
                ->when($query, function ($q) use ($query) {
                    $like = "%{$query}%";
                    $q->where(function ($sub) use ($like) {
                        $sub->where('firstname', 'like', $like)
                            ->orWhere('lastname', 'like', $like)
                            ->orWhere('username', 'like', $like)
                            ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", [$like]);
                    });
                })
                ->get();

            $byLevel = $users->groupBy('access_level');
            foreach ($byLevel as $level => $collection) {
                $groups[] = [
                    'label' => ucfirst($level),
                    'options' => $collection->map(fn ($u) => $this->mapRecipient($u))->values(),
                ];
            }
        }

        return response()->json(['data' => $groups]);
    }

    protected function mapRecipient(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? '')) ?: ($u->username ?? 'User'),
            'username' => $u->username,
            'photo' => $u->photo ? url('uploads/member_images/' . $u->photo) : url('uploads/no_image.jpg'),
            'access_level' => $u->access_level,
        ];
    }

    /**
     * Ensure a direct conversation exists (no message required).
     */
    public function ensureConversation(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
        ]);

        $sender = Auth::user();
        $recipient = User::findOrFail($request->recipient_id);

        [$canStart, $existing] = $this->canInitiate($sender, $recipient);
        if (!$canStart) {
            return response()->json(['message' => 'Not allowed to start conversation'], 403);
        }

        $conversation = $existing ?? DB::transaction(function () use ($sender, $recipient) {
            $conv = Conversation::create([
                'created_by' => $sender->id,
                'type' => 'direct',
            ]);
            $conv->participants()->sync([$sender->id, $recipient->id]);
            return $conv;
        });

        return response()->json([
            'conversation' => $conversation->load('participants'),
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $request->validate(['message' => 'required|string']);
        $sender = Auth::user();
        if (!$conversation->participants()->where('user_id', $sender->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $request->message,
        ]);

        $participantIds = $conversation->participants()->pluck('users.id')->toArray();
        broadcast(new MessageSent($message, $participantIds))->toOthers();

        return response()->json(['message' => $message->load('sender')]);
    }

    public function markRead(Conversation $conversation)
    {
        $userId = Auth::id();
        if (!$conversation->participants()->where('user_id', $userId)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    public function typing(Request $request, Conversation $conversation)
    {
        $sender = Auth::user();
        if (!$sender || !$conversation->participants()->where('user_id', $sender->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $participantIds = $conversation->participants()->pluck('users.id')->toArray();
        broadcast(new \App\Events\MessageTyping($conversation, $sender->id, $participantIds))->toOthers();
        return response()->json(['status' => 'ok']);
    }

    public function unreadCount()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['unread' => 0]);
        }

        $count = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->withCount([
                'messages as unread_count' => function ($q) use ($userId) {
                    $q->whereNull('read_at')->where('sender_id', '!=', $userId);
                },
            ])
            ->get()
            ->sum('unread_count');

        return response()->json(['unread' => $count]);
    }

    /**
     * Determine whether sender can initiate with recipient.
     * Allow same level and defined upward chain.
     */
    protected function canInitiate(User $sender, User $recipient): array
    {
        // try to find existing direct conversation
        $existing = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $sender->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $recipient->id))
            ->first();

        $allowed = $this->allowedTargetLevels($sender->access_level);
        if (in_array($recipient->access_level, $allowed, true) && $this->recipientIsInsideSenderScope($sender, $recipient)) {
            return [true, $existing];
        }
        // Allow existing conversation if already created
        if ($existing) {
            return [true, $existing];
        }
        return [false, null];
    }

    protected function allowedTargetLevels(?string $level): array
    {
        return match ($level) {
            'superadmin', 'nationaladmin' => ['nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'regionaladmin' => ['regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'stateadmin' => ['stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'senatorialadmin' => ['senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'federaladmin' => ['federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'lgaadmin' => ['lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'wardadmin' => ['wardadmin', 'puadmin', 'user'],
            'puadmin' => ['puadmin', 'user'],
            default => [$level],
        };
    }

    protected function recipientIsInsideSenderScope(User $sender, User $recipient): bool
    {
        if (in_array($sender->access_level, ['superadmin', 'nationaladmin'], true)) {
            return true;
        }

        $query = User::query()->whereKey($recipient->getKey());
        app(LocationScopeService::class)->applyScope($query, $sender, 'users', 'users');

        return $query->exists();
    }
}
