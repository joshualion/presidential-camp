<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Support\SafeDatabase;

class ActivityNotification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('scope', 'public')
                ->orWhere(function ($q) use ($user) {
                    $q->where('scope', 'direct')->where('scope_id', $user->id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('scope', 'region')->where('scope_id', $user->region_id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('scope', 'state')->where('scope_id', $user->state_id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('scope', 'senatorial_district')->where('scope_id', $user->senatorial_district_id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('scope', 'federal_constituency')->where('scope_id', $user->federal_constituency_id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('scope', 'lga')->where('scope_id', $user->lga_id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('scope', 'ward')->where('scope_id', $user->ward_id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('scope', 'pu')->where('scope_id', $user->polling_unit_id);
                });

            if (
                method_exists($user, 'supportGroups')
                && SafeDatabase::hasTable('support_groups')
                && SafeDatabase::hasTable('user_support_group')
            ) {
                $groupIds = $user->supportGroups()->pluck('support_groups.id');
                if ($groupIds->isNotEmpty()) {
                    $q->orWhere(function ($q) use ($groupIds) {
                        $q->where('scope', 'support_group')->whereIn('scope_id', $groupIds);
                    });
                }
            }
        });
    }
}
