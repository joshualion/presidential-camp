<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Services\LocationScopeService;
use Illuminate\Database\Eloquent\Builder;

trait HasLocationScope
{
    public function scopeForUserScope(Builder $query, ?User $user, ?string $subject = null): Builder
    {
        app(LocationScopeService::class)->applyScope($query, $user, $subject);

        return $query;
    }

    public function scopeBoundary(Builder $query, string $boundaryType, int $boundaryId, ?string $subject = null): Builder
    {
        app(LocationScopeService::class)->applyBoundaryFilter($query, $boundaryType, $boundaryId, $subject);

        return $query;
    }
}
