<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ScopedMessagingRecipientService
{
    public function __construct(
        private LocationScopeService $locationScopeService,
        private LicensedScopeQueryService $licensedScopeQueryService
    ) {
    }

    public function groupsFor(User $sender, ?string $search = null): array
    {
        $search = trim((string) $search);

        if ($sender->access_level === 'puadmin') {
            return $this->pollingUnitGroups($sender, $search);
        }

        $users = $this->queryFor($sender, $search)->get();

        return $users
            ->groupBy('access_level')
            ->map(fn ($collection, $level) => [
                'label' => ucfirst((string) $level),
                'options' => $collection->map(fn (User $user) => $this->mapRecipient($user))->values(),
            ])
            ->values()
            ->all();
    }

    public function queryFor(User $sender, ?string $search = null): Builder
    {
        $query = User::query()
            ->select(['id', 'firstname', 'lastname', 'username', 'photo', 'access_level'])
            ->where('id', '!=', $sender->getKey())
            ->whereIn('access_level', $this->allowedTargetLevels($sender->access_level));

        $this->locationScopeService->applyScope($query, $sender, 'users', 'users');
        $this->licensedScopeQueryService->applyToUsersQuery($query);

        return $this->applySearch($query, $search);
    }

    public function canMessage(User $sender, User $recipient): bool
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

    public function allowedTargetLevels(?string $level): array
    {
        return match ($level) {
            'superadmin', 'nationaladmin' => ['nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'regionaladmin' => ['regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'stateadmin' => ['stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'senatorialadmin' => ['senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'federaladmin' => ['federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'lgaadmin' => ['lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'wardadmin' => ['wardadmin', 'puadmin', 'user'],
            'puadmin' => ['wardadmin', 'puadmin', 'user'],
            default => array_values(array_filter([(string) $level])),
        };
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

    private function pollingUnitGroups(User $sender, ?string $search): array
    {
        $wardAdmins = User::query()
            ->select(['id', 'firstname', 'lastname', 'username', 'photo', 'access_level'])
            ->where('id', '!=', $sender->getKey())
            ->where('access_level', 'wardadmin')
            ->where('ward_id', $sender->ward_id);

        $this->licensedScopeQueryService->applyToUsersQuery($wardAdmins);
        $wardAdmins = $this->applySearch($wardAdmins, $search)
            ->get()
            ->map(fn (User $user) => $this->mapRecipient($user))
            ->values();

        $puMembers = User::query()
            ->select(['id', 'firstname', 'lastname', 'username', 'photo', 'access_level'])
            ->where('id', '!=', $sender->getKey())
            ->where('polling_unit_id', $sender->polling_unit_id)
            ->whereIn('access_level', ['puadmin', 'user']);

        $this->licensedScopeQueryService->applyToUsersQuery($puMembers);
        $puMembers = $this->applySearch($puMembers, $search)
            ->get()
            ->map(fn (User $user) => $this->mapRecipient($user))
            ->values();

        return [
            ['label' => 'Ward Administrators', 'options' => $wardAdmins],
            ['label' => 'PU Members', 'options' => $puMembers],
        ];
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
