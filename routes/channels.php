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
    return (bool) $user; // Only authenticated users can listen
});

