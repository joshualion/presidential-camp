<?php

namespace App\Services;

use App\Models\Election;
use App\Models\Vote;
use App\Support\SafeDatabase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class CommunityPostWatchService
{
    public function __construct(private LicensedScopeQueryService $licensedScope)
    {
    }

    public function summary(): array
    {
        if (!SafeDatabase::hasTable('elections')) {
            return $this->empty('Election watch is not available yet.');
        }

        try {
            $active = Election::query()
                ->whereIn('status', ['ongoing', 'inconclusive'])
                ->orderByDesc('year')
                ->first();

            if ($active) {
                return $this->activeSummary($active);
            }

            $upcoming = Election::query()
                ->whereIn('status', ['pending', 'ongoing'])
                ->whereDate('year', '>=', Carbon::today())
                ->orderBy('year')
                ->first();

            if ($upcoming) {
                $date = Carbon::parse($upcoming->year);

                return [
                    'state' => 'upcoming',
                    'title' => 'Election Watch',
                    'message' => $upcoming->name,
                    'countdown_label' => $date->isToday() ? 'Today' : $date->diffForHumans(null, true).' remaining',
                    'date' => $date->toFormattedDateString(),
                    'top' => [],
                ];
            }
        } catch (Throwable) {
            return $this->empty('No active election watch at the moment.');
        }

        return $this->empty('No active election watch at the moment.');
    }

    private function activeSummary(Election $election): array
    {
        if (!SafeDatabase::hasTable('votes')) {
            return $this->empty('No active election watch at the moment.');
        }

        $query = Vote::query()
            ->leftJoin('political_parties', 'votes.party_id', '=', 'political_parties.id')
            ->select('votes.party_id', 'political_parties.name as party_name', DB::raw('SUM(votes.quantity) as total_votes'))
            ->where('election_id', $election->id)
            ->groupBy('votes.party_id', 'political_parties.name')
            ->orderByDesc('total_votes');

        $this->licensedScope->applyToVotesQuery($query);

        $top = $query->limit(3)->get()->map(function (Vote $vote) {
            return [
                'party' => $vote->party_name ?? 'Party '.$vote->party_id,
                'votes' => (int) $vote->total_votes,
            ];
        })->values()->all();

        if ($top === []) {
            return [
                'state' => 'active_empty',
                'title' => 'Election Watch',
                'message' => 'No scoped results have been posted yet.',
                'top' => [],
            ];
        }

        return [
            'state' => 'active',
            'title' => 'Election Watch',
            'message' => $election->name,
            'leading' => $top[0],
            'top' => $top,
        ];
    }

    private function empty(string $message): array
    {
        return [
            'state' => 'empty',
            'title' => 'Election Watch',
            'message' => $message,
            'top' => [],
        ];
    }
}
