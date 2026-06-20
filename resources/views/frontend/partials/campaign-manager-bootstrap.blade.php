@php
    $communityRealtime = app(\App\Services\CommunityRealtimeService::class);
    $communityMediaPolicy = app(\App\Services\CommunityModerationService::class)->mediaPolicy();

    $campaignManagerBootstrap = [
        'baseUrl' => url('/'),
        'mediaBaseUrl' => asset('uploads'),
        'routes' => [
            'timeline' => route('timeline'),
            'profileTimeline' => route('profile.timeline'),
            'postsFetch' => route('posts.fetch'),
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
            'messagesConversations' => route('messages.conversations'),
            'messagesConversationMessages' => url('/messages/conversations/__CONVERSATION__/messages'),
            'messagesConversationSend' => url('/messages/conversations/__CONVERSATION__/messages'),
            'messagesConversationTyping' => url('/messages/conversations/__CONVERSATION__/typing'),
            'messagesConversationRead' => url('/messages/conversations/__CONVERSATION__/read'),
            'messagesRecipients' => route('messages.recipients'),
            'messagesStart' => route('messages.start'),
            'messagesEnsure' => route('messages.ensure'),
            'messagesUnreadCount' => route('messages.unread-count'),
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
