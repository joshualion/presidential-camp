<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('posts', function ($user) {
    return (bool) $user; // Only authenticated users can listen
});

// BroadcastServiceProvider.php
Broadcast::channel('posts.{postId}', function ($user, $postId) {
    if (!$user) {
        return false;
    }
    $query = \App\Models\Post::query()->whereKey($postId);
    app(\App\Services\CommunityAudienceService::class)->applyVisibility($query, $user);
    $post = $query->first();

    return $post && app(\App\Services\CommunityModerationService::class)->postInActorScope($user, $post);
});

// Messaging private channel per user
Broadcast::channel('messages.user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('campaign.notifications.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('activity-{scope}-{id}', function ($user, $scope, $id) {
    if (!$user) {
        return false;
    }
    switch ($scope) {
        case 'public':
            return true;
        case 'region':
            return (int) $user->region_id === (int) $id;
        case 'state':
            return (int) $user->state_id === (int) $id;
        case 'lga':
            return (int) $user->lga_id === (int) $id;
        case 'ward':
            return (int) $user->ward_id === (int) $id;
        case 'pu':
            return (int) $user->polling_unit_id === (int) $id;
        default:
            return false;
    }
});

