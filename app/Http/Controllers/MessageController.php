<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ScopedMessagingRecipientService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    public function __construct(private ScopedMessagingRecipientService $recipientService)
    {
    }

    public function conversations(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([]);
        }

        $visible = $this->recipientService->visibleConversationsFor($user);
        $perPage = 10;
        $page = max(1, LengthAwarePaginator::resolveCurrentPage());
        $conversations = new LengthAwarePaginator(
            $visible->forPage($page, $perPage)->values(),
            $visible->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($conversations);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $user = Auth::user();
        if (!$user || !$this->recipientService->canAccessConversation($user, $conversation)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = $conversation->messages()
            ->with('sender:id,firstname,lastname,username,photo')
            ->latest()
            ->paginate(50);

        // Mark unread as read for this user
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    public function startConversation(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);
        $body = $this->validatedBody($validated['message']);

        $sender = Auth::user();
        $recipient = User::findOrFail($validated['recipient_id']);

        $existing = $this->recipientService->directConversationBetween($sender, $recipient);
        $allowed = $existing
            ? $this->recipientService->canContinueConversation($sender, $existing)
            : $this->recipientService->canInitiateConversation($sender, $recipient);
        if (!$allowed) {
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
            'body' => $body,
        ]);
        $conversation->touch();

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

        $existing = $this->recipientService->directConversationBetween($sender, $recipient);
        $allowed = $existing
            ? $this->recipientService->canContinueConversation($sender, $existing)
            : $this->recipientService->canInitiateConversation($sender, $recipient);
        if (!$allowed) {
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
        $validated = $request->validate(['message' => 'required|string']);
        $body = $this->validatedBody($validated['message']);
        $sender = Auth::user();
        if (!$sender || !$this->recipientService->canContinueConversation($sender, $conversation)) {
            return response()->json(['message' => 'Conversation is outside your messaging scope.'], 403);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]);
        $conversation->touch();

        $participantIds = $conversation->participants()->pluck('users.id')->toArray();
        $this->broadcastMessage($message, $participantIds);

        return response()->json(['message' => $message->load('sender')]);
    }

    public function markRead(Conversation $conversation)
    {
        $user = Auth::user();
        if (!$user || !$this->recipientService->canAccessConversation($user, $conversation)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    public function typing(Request $request, Conversation $conversation)
    {
        $sender = Auth::user();
        if (!$sender || !$this->recipientService->canContinueConversation($sender, $conversation)) {
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
        $user = Auth::user();
        if (!$user) {
            return response()->json(['unread' => 0]);
        }

        $count = $this->recipientService->visibleConversationsFor($user)->sum('unread_count');

        return response()->json(['unread' => $count]);
    }

    private function validatedBody(string $message): string
    {
        $body = trim($message);

        if ($body === '') {
            throw ValidationException::withMessages([
                'message' => 'The message field must contain visible text.',
            ]);
        }

        return $body;
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
