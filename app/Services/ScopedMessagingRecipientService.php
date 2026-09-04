<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ScopedMessagingRecipientService
{
    /**
     * Messaging hierarchy, from highest to lowest access level.
     */
    private const ACCESS_LEVELS = [
        'superadmin',
        'nationaladmin',
        'regionaladmin',
        'stateadmin',
        'senatorialadmin',
        'federaladmin',
        'lgaadmin',
        'wardadmin',
        'puadmin',
        'user',
    ];

    public function __construct(
        private LocationScopeService $locationScopeService,
        private LicensedScopeQueryService $licensedScopeQueryService
    ) {
    }

    public function groupsFor(User $sender, ?string $search = null): array
    {
        return $this->queryFor($sender, $search)
            ->get()
            ->groupBy('access_level')
            ->map(fn ($collection, $level) => [
                'label' => ucfirst((string) $level),
                'options' => $collection->map(fn (User $user) => $this->mapRecipient($user))->values(),
            ])
            ->values()
            ->all();
    }

    /**
     * Return recipients the sender may contact directly, plus higher-level
     * participants from already-authorized conversation contexts.
     */
    public function queryFor(User $sender, ?string $search = null): Builder
    {
        $contextParticipantIds = $this->existingContextParticipantIds($sender);

        $query = User::query()
            ->select(['id', 'firstname', 'lastname', 'username', 'photo', 'access_level'])
            ->where('id', '!=', $sender->getKey())
            ->where(function (Builder $scopeQuery) use ($sender, $contextParticipantIds) {
                $scopeQuery->where(function (Builder $directQuery) use ($sender) {
                    $directQuery->whereIn('access_level', $this->allowedTargetLevels($sender->access_level));
                    $this->locationScopeService->applyScope($directQuery, $sender, 'users', 'users');
                });

                if ($contextParticipantIds !== []) {
                    $scopeQuery->orWhereIn('id', $contextParticipantIds);
                }
            });

        $this->licensedScopeQueryService->applyToUsersQuery($query);

        return $this->applySearch($query, $search);
    }

    /**
     * Same-level and downward initiation is allowed within location/package scope.
     */
    public function canInitiateConversation(User $sender, User $recipient): bool
    {
        if ((int) $sender->getKey() === (int) $recipient->getKey()) {
            return false;
        }

        if (!in_array($recipient->access_level, $this->allowedTargetLevels($sender->access_level), true)) {
            return false;
        }

        $query = User::query()->whereKey($recipient->getKey());
        $this->locationScopeService->applyScope($query, $sender, 'users', 'users');
        $this->licensedScopeQueryService->applyToUsersQuery($query);

        return $query->exists();
    }

    /**
     * Existing direct threads permit upward replies only after the higher-level
     * participant has initiated the thread or communication already exists.
     */
    public function canContinueConversation(User $sender, Conversation $conversation): bool
    {
        $conversation->loadMissing('participants:id,access_level,region_id,state_id,senatorial_district_id,federal_constituency_id,lga_id,ward_id,polling_unit_id,status');

        if (!$conversation->participants->contains(fn (User $user) => (int) $user->getKey() === (int) $sender->getKey())) {
            return false;
        }

        if (!$this->userWithinLicensedScope($sender)) {
            return false;
        }

        foreach ($conversation->participants as $participant) {
            if ((int) $participant->getKey() === (int) $sender->getKey()) {
                continue;
            }

            if (!$this->userWithinLicensedScope($participant)) {
                return false;
            }

            $senderRank = $this->rankFor($sender->access_level);
            $participantRank = $this->rankFor($participant->access_level);

            if ($senderRank === null || $participantRank === null) {
                return false;
            }

            if ($senderRank >= $participantRank) {
                if (!$this->canInitiateConversation($sender, $participant)) {
                    return false;
                }

                continue;
            }

            if (!$this->canInitiateConversation($participant, $sender)) {
                return false;
            }

            $higherInitiated = (int) $conversation->created_by === (int) $participant->getKey();
            $communicationExists = $conversation->messages()
                ->whereIn('sender_id', [$sender->getKey(), $participant->getKey()])
                ->exists();

            if (!$higherInitiated && !$communicationExists) {
                return false;
            }
        }

        return true;
    }

    public function canAccessConversation(User $user, Conversation $conversation): bool
    {
        return $this->canContinueConversation($user, $conversation);
    }

    public function canOpenConversationWith(User $sender, User $recipient): bool
    {
        if ($this->canInitiateConversation($sender, $recipient)) {
            return true;
        }

        $conversation = $this->directConversationBetween($sender, $recipient);

        return $conversation !== null && $this->canContinueConversation($sender, $conversation);
    }

    public function directConversationBetween(User $first, User $second): ?Conversation
    {
        return Conversation::query()
            ->where('type', 'direct')
            ->whereHas('participants', fn (Builder $query) => $query->where('users.id', $first->getKey()))
            ->whereHas('participants', fn (Builder $query) => $query->where('users.id', $second->getKey()))
            ->whereDoesntHave('participants', fn (Builder $query) => $query->whereNotIn('users.id', [$first->getKey(), $second->getKey()]))
            ->first();
    }

    /**
     * Apply the same visibility guard used for opening and replying to threads.
     */
    public function visibleConversationsFor(User $user): Collection
    {
        return Conversation::query()
            ->whereHas('participants', fn (Builder $query) => $query->where('users.id', $user->getKey()))
            ->with([
                'participants:id,firstname,lastname,username,photo,access_level,region_id,state_id,senatorial_district_id,federal_constituency_id,lga_id,ward_id,polling_unit_id,status',
                'messages' => fn ($query) => $query->latest()->limit(1),
            ])
            ->withCount([
                'messages as unread_count' => function ($query) use ($user) {
                    $query->whereNull('read_at')->where('sender_id', '!=', $user->getKey());
                },
            ])
            ->latest('updated_at')
            ->get()
            ->filter(fn (Conversation $conversation) => $this->canAccessConversation($user, $conversation))
            ->values();
    }

    public function allowedTargetLevels(?string $level): array
    {
        $rank = $this->rankFor($level);

        if ($rank === null) {
            return [];
        }

        return array_values(array_filter(
            self::ACCESS_LEVELS,
            fn (string $targetLevel) => $this->rankFor($targetLevel) <= $rank
        ));
    }

    public function mapRecipient(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => trim(($user->firstname ?? '').' '.($user->lastname ?? '')) ?: ($user->username ?? 'User'),
            'username' => $user->username,
            'photo' => $user->photo ? url('uploads/member_images/'.$user->photo) : url('uploads/no_image.jpg'),
            'access_level' => $user->access_level,
        ];
    }

    private function existingContextParticipantIds(User $sender): array
    {
        return $this->visibleConversationsFor($sender)
            ->flatMap(fn (Conversation $conversation) => $conversation->participants->pluck('id'))
            ->reject(fn ($id) => (int) $id === (int) $sender->getKey())
            ->unique()
            ->values()
            ->all();
    }

    private function rankFor(?string $level): ?int
    {
        $index = array_search($level, self::ACCESS_LEVELS, true);

        return $index === false ? null : count(self::ACCESS_LEVELS) - $index;
    }

    private function userWithinLicensedScope(User $user): bool
    {
        $query = User::query()->whereKey($user->getKey());
        $this->licensedScopeQueryService->applyToUsersQuery($query);

        return $query->exists();
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        return $query->when($search !== '', function (Builder $builder) use ($search) {
            $like = "%{$search}%";
            $builder->where(function (Builder $subQuery) use ($like) {
                $subQuery->where('firstname', 'like', $like)
                    ->orWhere('lastname', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", [$like]);
            });
        });
    }
}
