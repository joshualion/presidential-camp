<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CommunityModerationService
{
    public function __construct(
        private CommunityRealtimeService $community,
        private CampaignPackagePermissionService $permissions,
        private LocationScopeService $locationScope,
        private LicensedScopeQueryService $licensedScope,
    ) {
    }

    public function assertCommunityAvailable(User $actor, string $capability): void
    {
        abort_unless($this->community->enabled(), 403, 'Community Forum is not enabled for this installation.');
        abort_unless($this->permissions->userCan($actor, $capability), 403, 'You are not allowed to perform this community action.');
    }

    public function canCreatePost(User $actor): bool
    {
        return $this->community->enabled() && $this->permissions->userCan($actor, 'community.post.create');
    }

    public function canCreateComment(User $actor): bool
    {
        return $this->community->enabled() && $this->permissions->userCan($actor, 'community.comment.create');
    }

    public function canEditPost(User $actor, Post $post): bool
    {
        if (!$this->postInActorScope($actor, $post)) {
            return false;
        }

        return (int) $post->user_id === (int) $actor->id
            ? $this->permissions->userCan($actor, 'community.post.edit_own')
            : $this->permissions->userCan($actor, 'community.post.moderate');
    }

    public function canDeletePost(User $actor, Post $post): bool
    {
        if (!$this->postInActorScope($actor, $post)) {
            return false;
        }

        return (int) $post->user_id === (int) $actor->id
            ? $this->permissions->userCan($actor, 'community.post.delete_own')
            : $this->permissions->userCan($actor, 'community.post.moderate');
    }

    public function canEditComment(User $actor, Comment $comment): bool
    {
        $post = $comment->post;

        if (!$post || !$this->postInActorScope($actor, $post)) {
            return false;
        }

        return (int) $comment->user_id === (int) $actor->id
            ? $this->permissions->userCan($actor, 'community.comment.edit_own')
            : $this->permissions->userCan($actor, 'community.comment.moderate');
    }

    public function canDeleteComment(User $actor, Comment $comment): bool
    {
        $post = $comment->post;

        if (!$post || !$this->postInActorScope($actor, $post)) {
            return false;
        }

        if ((int) $comment->user_id === (int) $actor->id) {
            return $this->permissions->userCan($actor, 'community.comment.delete_own');
        }

        if ((int) $post->user_id === (int) $actor->id) {
            return $this->permissions->userCan($actor, 'community.comment.delete_own');
        }

        return $this->permissions->userCan($actor, 'community.comment.moderate');
    }

    public function postInActorScope(User $actor, Post $post): bool
    {
        $author = $post->user ?: User::query()->find($post->user_id);

        if (!$author) {
            return false;
        }

        $query = User::query()->whereKey($author->getKey());
        $this->locationScope->applyScope($query, $actor, 'users', 'users');
        $this->licensedScope->applyToUsersQuery($query);

        return $query->exists();
    }

    public function mediaPolicy(): array
    {
        $settings = SystemSetting::query()->first();
        $selfHosted = config('campaign.deployment_mode') === 'self_hosted';

        $imageMaxMb = min((int) ($settings?->community_image_max_mb ?? config('campaign.community.media.image_max_mb', 5)), 10);
        $videoMaxMb = min((int) ($settings?->community_video_max_mb ?? config('campaign.community.media.video_max_mb', 20)), $selfHosted ? 20 : 50);

        return [
            'images_enabled' => (bool) ($settings?->community_allow_images ?? true),
            'videos_enabled' => (bool) ($settings?->community_allow_videos ?? false),
            'image_max_mb' => max(1, $imageMaxMb),
            'video_max_mb' => max(1, $videoMaxMb),
            'image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
            'video_mimes' => ['mp4', 'webm', 'mov'],
        ];
    }

    public function postValidationRules(): array
    {
        $settings = SystemSetting::query()->first();
        $policy = $this->mediaPolicy();
        $postMaxLength = (int) ($settings?->community_post_max_length ?? 750);

        return [
            'content' => "nullable|string|max:{$postMaxLength}|required_without_all:photo,video",
            'photo' => ($policy['images_enabled']
                ? 'nullable|file|mimes:'.implode(',', $policy['image_mimes']).'|mimetypes:image/jpeg,image/png,image/webp|max:'.($policy['image_max_mb'] * 1024)
                : 'prohibited').'|required_without_all:content,video',
            'video' => ($policy['videos_enabled']
                ? 'nullable|file|mimes:'.implode(',', $policy['video_mimes']).'|mimetypes:video/mp4,video/webm,video/quicktime|max:'.($policy['video_max_mb'] * 1024)
                : 'prohibited').'|required_without_all:content,photo',
            'audience' => 'nullable|string|in:public,region,state,lga,ward,pu',
            'audience_type' => 'nullable|string|in:own_scope,global,specific_scope,support_group',
            'audience_scope_type' => 'nullable|string|in:region,state,senatorial_district,federal_constituency,lga,ward,pu',
            'audience_scope_id' => 'nullable|integer|min:1',
            'audience_group_id' => 'nullable|integer|min:1',
        ];
    }

    public function assertCanUploadMedia(User $actor, ?UploadedFile $photo, ?UploadedFile $video): void
    {
        if (($photo || $video) && !$this->permissions->userCan($actor, 'community.media.upload')) {
            abort(403, 'You are not allowed to upload community media.');
        }

        if ($video && !$this->permissions->userCan($actor, 'community.media.video_upload')) {
            abort(403, 'You are not allowed to upload community videos.');
        }
    }

    public function cleanupPostMedia(Post $post): void
    {
        foreach ([
            $post->image_url ? public_path('uploads/community/photos/'.ltrim($post->image_url, '/')) : null,
            $post->video_url ? public_path('uploads/community/videos/'.ltrim($post->video_url, '/')) : null,
        ] as $path) {
            if (!$path || !is_file($path)) {
                continue;
            }

            try {
                @unlink($path);
            } catch (\Throwable $exception) {
                Log::warning('Unable to delete community media file', [
                    'post_id' => $post->id,
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function annotatePost(Post $post, ?User $actor): Post
    {
        if (!$actor) {
            $post->can_edit = false;
            $post->can_delete = false;
            $post->can_moderate = false;

            return $post;
        }

        $post->can_edit = $this->canEditPost($actor, $post);
        $post->can_delete = $this->canDeletePost($actor, $post);
        $post->can_moderate = $post->user_id !== $actor->id && $this->permissions->userCan($actor, 'community.post.moderate');

        return $post;
    }
}
