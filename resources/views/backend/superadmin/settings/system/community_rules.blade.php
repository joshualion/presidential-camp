<div class="form-group row">
    <label for="community_post_max_length" class="col-sm-4 col-form-label">Max Post Length</label>
    <div class="col-sm-8">
        <input type="number" class="form-control" id="community_post_max_length" name="community_post_max_length"
               min="50" max="5000"
               value="{{ old('community_post_max_length', $SystemSetting->community_post_max_length ?? 750) }}">
        <small class="text-muted">Limit characters per post (50 - 5000).</small>
    </div>
</div>

<div class="form-group row">
    <label for="community_comment_max_length" class="col-sm-4 col-form-label">Max Comment Length</label>
    <div class="col-sm-8">
        <input type="number" class="form-control" id="community_comment_max_length" name="community_comment_max_length"
               min="50" max="3000"
               value="{{ old('community_comment_max_length', $SystemSetting->community_comment_max_length ?? 750) }}">
        <small class="text-muted">Limit characters per comment (50 - 3000).</small>
    </div>
</div>

<div class="form-group row">
    <label for="community_daily_post_limit" class="col-sm-4 col-form-label">Daily Post Limit</label>
    <div class="col-sm-8">
        <input type="number" class="form-control" id="community_daily_post_limit" name="community_daily_post_limit"
               min="1" max="500"
               value="{{ old('community_daily_post_limit', $SystemSetting->community_daily_post_limit ?? 50) }}">
        <small class="text-muted">Maximum posts per user per day.</small>
    </div>
</div>

<div class="form-group row">
    <label for="community_daily_comment_limit" class="col-sm-4 col-form-label">Daily Comment Limit</label>
    <div class="col-sm-8">
        <input type="number" class="form-control" id="community_daily_comment_limit" name="community_daily_comment_limit"
               min="1" max="2000"
               value="{{ old('community_daily_comment_limit', $SystemSetting->community_daily_comment_limit ?? 200) }}">
        <small class="text-muted">Maximum comments per user per day.</small>
    </div>
</div>

<div class="form-group row">
    <label for="community_image_max_mb" class="col-sm-4 col-form-label">Image Max Size (MB)</label>
    <div class="col-sm-8">
        <input type="number" class="form-control" id="community_image_max_mb" name="community_image_max_mb"
               min="1" max="10"
               value="{{ old('community_image_max_mb', $SystemSetting->community_image_max_mb ?? $SystemSetting->community_attachment_max_mb ?? 5) }}">
        <input type="hidden" name="community_attachment_max_mb" value="{{ old('community_image_max_mb', $SystemSetting->community_image_max_mb ?? $SystemSetting->community_attachment_max_mb ?? 5) }}">
        <small class="text-muted">Maximum image upload size is 10MB.</small>
    </div>
</div>

<div class="form-group row">
    <label for="community_video_max_mb" class="col-sm-4 col-form-label">Video Max Size (MB)</label>
    <div class="col-sm-8">
        <input type="number" class="form-control" id="community_video_max_mb" name="community_video_max_mb"
               min="1" max="20"
               value="{{ old('community_video_max_mb', $SystemSetting->community_video_max_mb ?? 20) }}">
        <small class="text-muted">Video uploads require compatible server upload limits and storage.</small>
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-4 col-form-label">Allowed Media</label>
    <div class="col-sm-8 d-flex gap-3">
        <div class="form-check">
            <input type="hidden" name="community_allow_images" value="0">
            <input class="form-check-input" type="checkbox" id="community_allow_images" name="community_allow_images" value="1"
                   {{ old('community_allow_images', $SystemSetting->community_allow_images ?? 1) ? 'checked' : '' }}>
            <label class="form-check-label" for="community_allow_images">Images</label>
        </div>
        <div class="form-check">
            <input type="hidden" name="community_allow_videos" value="0">
            <input class="form-check-input" type="checkbox" id="community_allow_videos" name="community_allow_videos" value="1"
                   {{ old('community_allow_videos', $SystemSetting->community_allow_videos ?? 0) ? 'checked' : '' }}>
            <label class="form-check-label" for="community_allow_videos">Videos</label>
        </div>
    </div>
    <div class="offset-sm-4 col-sm-8">
        <small class="text-muted">Allowed images: JPG, JPEG, PNG, WEBP. Allowed videos when enabled: MP4, WEBM, MOV.</small>
    </div>
</div>

<div class="form-group row">
    <label for="community_guidelines" class="col-sm-4 col-form-label">Community Guidelines</label>
    <div class="col-sm-8">
        <textarea class="form-control" id="community_guidelines" name="community_guidelines" rows="5"
                  placeholder="Outline rules for posting, prohibited content, moderation actions, etc.">{{ old('community_guidelines', $SystemSetting->community_guidelines ?? '') }}</textarea>
    </div>
</div>
