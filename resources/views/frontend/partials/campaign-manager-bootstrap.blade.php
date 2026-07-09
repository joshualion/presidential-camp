@php
    $communityRealtime = app(\App\Services\CommunityRealtimeService::class);
    $communityMediaPolicy = app(\App\Services\CommunityModerationService::class)->mediaPolicy();
    $messageRoutes = [
        'messagesConversations' => null,
        'messagesConversationMessages' => null,
        'messagesConversationSend' => null,
        'messagesConversationTyping' => null,
        'messagesConversationRead' => null,
        'messagesRecipients' => null,
        'messagesStart' => null,
        'messagesEnsure' => null,
        'messagesUnreadCount' => null,
    ];

    if ($messageRoutePrefix = auth()->user()?->access_level) {
        $messageRoutes = [
            'messagesConversations' => route($messageRoutePrefix.'.messages.conversations'),
            'messagesConversationMessages' => route($messageRoutePrefix.'.messages.conversation.messages', ['conversation' => '__CONVERSATION__']),
            'messagesConversationSend' => route($messageRoutePrefix.'.messages.conversation.send', ['conversation' => '__CONVERSATION__']),
            'messagesConversationTyping' => route($messageRoutePrefix.'.messages.conversation.typing', ['conversation' => '__CONVERSATION__']),
            'messagesConversationRead' => route($messageRoutePrefix.'.messages.conversation.read', ['conversation' => '__CONVERSATION__']),
            'messagesRecipients' => route($messageRoutePrefix.'.messages.recipients'),
            'messagesStart' => route($messageRoutePrefix.'.messages.start'),
            'messagesEnsure' => route($messageRoutePrefix.'.messages.ensure'),
            'messagesUnreadCount' => route($messageRoutePrefix.'.messages.unread-count'),
        ];
    }

    $campaignManagerBootstrap = [
        'baseUrl' => url('/'),
        'mediaBaseUrl' => asset('uploads'),
        'routes' => [
            'timeline' => route('timeline'),
            'profileTimeline' => route('profile.timeline'),
            'postsFetch' => route('posts.fetch'),
            'audienceOptions' => route('community.audience.options'),
            'userPostsFetch' => route('user.posts.fetch'),
            'viewUserPosts' => url('/community/__USERNAME__/profile/timeline'),
            'viewUserPostsFeed' => url('/community/__USERNAME__/profile/posts'),
            'postsStore' => route('posts.store'),
            'postsUpdate' => url('/posts/__POST__'),
            'postsDestroy' => url('/posts/__POST__'),
            'postsLike' => url('/posts/__POST__/like'),
            'postsLikers' => url('/posts/__POST__/likers'),
            'commentsStore' => url('/posts/__POST__/comments'),
            'commentsIndex' => url('/posts/__POST__/comments'),
            'commentsUpdate' => url('/comments/__COMMENT__'),
            'commentsDestroy' => url('/comments/__COMMENT__'),
            'followingIds' => route('me.following.ids'),
            'userFollowers' => url('/community/users/__USER__/followers'),
            'userFollowing' => url('/community/users/__USER__/following'),
            'userFollow' => url('/community/users/__USER__/follow'),
            'searchSuggest' => route('search.suggest'),
            'searchIndex' => route('search.index'),
            'notificationsFeed' => route('notifications.feed'),
            'notificationsUnread' => route('notifications.unread'),
            'notificationsIndex' => route('notifications.index'),
            'notificationsMarkRead' => route('notifications.markRead'),
            'notificationsMarkAllRead' => route('notifications.markAllRead'),
            ...$messageRoutes,
        ],
        'media' => [
            'noImage' => asset('uploads/no_image.jpg'),
            'memberImages' => asset('uploads/member_images'),
            'communityPhotos' => asset('uploads/community/photos'),
            'communityVideos' => asset('uploads/community/videos'),
        ],
        'community' => [
            'enabled' => $communityRealtime->enabled(),
            'mode' => $communityRealtime->mode(),
            'pollingMode' => $communityRealtime->mode() === 'polling',
            'realtimeAvailable' => $communityRealtime->realtimeAvailable(),
            'intervals' => $communityRealtime->pollingIntervals(),
            'media' => $communityMediaPolicy,
        ],
    ];
@endphp

<script>
    window.CampaignManager = Object.assign(window.CampaignManager || {}, @json($campaignManagerBootstrap));
</script>
