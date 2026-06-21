<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ScopedMessagingRecipientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function __construct(private ScopedMessagingRecipientService $recipientService)
    {
    }

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

        $this->broadcastMessage($message, [$sender->id, $recipient->id]);

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

        return response()->json(['data' => $this->recipientService->groupsFor($sender, $request->input('q'))]);
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
        if (!$this->conversationAllowedForSender($conversation, $sender)) {
            return response()->json(['message' => 'Conversation is outside your messaging scope.'], 403);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $request->message,
        ]);

        $participantIds = $conversation->participants()->pluck('users.id')->toArray();
        $this->broadcastMessage($message, $participantIds);

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
        try {
            broadcast(new \App\Events\MessageTyping($conversation, $sender->id, $participantIds))->toOthers();
        } catch (\Throwable $exception) {
            Log::warning('Message typing broadcast failed; polling fallback will continue.', [
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'error' => $exception->getMessage(),
            ]);
        }

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

        if ($this->recipientService->canMessage($sender, $recipient)) {
            return [true, $existing];
        }

        return [false, null];
    }

    private function conversationAllowedForSender(Conversation $conversation, User $sender): bool
    {
        return $conversation->participants()
            ->where('users.id', '!=', $sender->id)
            ->get()
            ->every(fn (User $participant) => $this->recipientService->canReplyInExistingConversation($sender, $participant));
    }

    private function broadcastMessage(Message $message, array $participantIds): void
    {
        try {
            broadcast(new MessageSent($message, $participantIds))->toOthers();
        } catch (\Throwable $exception) {
            Log::warning('Message broadcast failed; polling fallback will continue.', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
