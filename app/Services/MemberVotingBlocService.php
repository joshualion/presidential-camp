<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MemberVotingBlocService
{
    public function report(User $member): array
    {
        $maxDepth = max(1, (int) config('member_access.referrals.max_depth', 25));
        $maxMembers = max(1, (int) config('member_access.referrals.max_members', 10000));
        $visited = [(int) $member->id => true];
        $frontier = [(int) $member->id];
        $members = collect();
        $depth = 0;
        $truncated = false;

        while ($frontier !== [] && $depth < $maxDepth && $members->count() < $maxMembers) {
            $depth++;
            $remaining = $maxMembers - $members->count();
            $next = $this->memberQuery()
                ->whereIn('referred_by', $frontier)
                ->orderBy('id')
                ->limit($remaining + 1)
                ->get();

            if ($next->count() > $remaining) {
                $truncated = true;
                $next = $next->take($remaining);
            }

            $frontier = [];
            foreach ($next as $descendant) {
                $id = (int) $descendant->id;
                if (isset($visited[$id])) {
                    continue;
                }

                $visited[$id] = true;
                $descendant->setAttribute('referral_depth', $depth);
                $members->push($descendant);
                $frontier[] = $id;
            }
        }

        if ($frontier !== []) {
            $truncated = true;
        }

        return [
            'members' => $members,
            'direct_count' => $members->where('referral_depth', 1)->count(),
            'total_count' => $members->count(),
            'eligible_count' => $members->where('validVoter', 'yes')->count(),
            'ineligible_count' => $members->where('validVoter', 'no')->count(),
            'active_count' => $members->where('status', 'active')->count(),
            'levels' => $members->groupBy('referral_depth')->map->count()->sortKeys(),
            'truncated' => $truncated,
        ];
    }

    public function directReferrals(User $member, int $perPage = 25)
    {
        return $this->memberQuery()
            ->where('referred_by', $member->id)
            ->latest()
            ->paginate($perPage);
    }

    private function memberQuery(): Builder
    {
        return User::query()
            ->where('access_level', 'user')
            ->with(['state:id,name', 'lga:id,name', 'ward:id,name', 'pollingUnit:id,name']);
    }
}
