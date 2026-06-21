<template>
  <div class="card mb-3" :id="`post-${post.id}`">
    <div class="card-body">
      <div class="row">
        <div class="d-flex justify-content-between w-100">
          <h6 class="card-title username d-flex align-items-center">
            <a :href="profileLink">
              <img
                :src="post.user?.photo ? memberImageUrl(post.user.photo) : noImageUrl"
                alt="Profile Picture"
                class="rounded-circle me-3"
              />
            </a>
            <a :href="profileLink">
              {{ post.user?.firstname || 'Unknown' }} {{ post.user?.lastname || 'User' }}
            </a>
            <button
              v-if="canFollow && !isSelf"
              class="btn btn-sm ms-2 follow-btn-icon"
              :class="isFollowingUser ? 'btn-outline-secondary' : 'btn-primary text-white'"
              :title="isFollowingUser ? 'Following' : 'Follow'"
              aria-label="Follow"
              @click="toggleFollow"
            >
              <i :class="isFollowingUser ? 'fas fa-user-check' : 'fas fa-user-plus'"></i>
              <span class="follow-label ms-1">{{ isFollowingUser ? 'Following' : 'Follow' }}</span>
            </button>
          </h6>
          <div class="d-flex align-items-center">
            <span class="text-secondary d-block timestamp">
              {{ formatDate(post.created_at) }}
            </span>
          <PostActions
            :post="post"
            :profileData="profileData"
            @edit-request="$emit('edit-post', post)"
            @delete-request="$emit('delete', post)"
          />
          </div>
        </div>
      </div>

      <p class="card-text">{{ post.content }}</p>

      <img
        v-if="post.image_url && post.image_url.trim() !== ''"
        class="card-img img-responsive"
        :src="post.image_full_url || communityPhotoUrl(post.image_url)"
        alt="image"
      />

      <video
        v-if="post.video_url && post.video_url.trim() !== ''"
        class="card-video w-100"
        :src="post.video_full_url || communityVideoUrl(post.video_url)"
        controls
      >
        Your browser does not support the video tag.
      </video>

      <div class="d-flex justify-content-between mt-3">
        <div>
          <button class="btn btn-primary btn-xs text-white" @click="likePost(post.id)">
            <i class="fas fa-thumbs-up" :class="post.liked_by_me ? 'text-warning' : ''"></i>
          </button>
          <span
            class="ms-2"
            :title="likerNamesTooltip"
            style="cursor: pointer;"
            @click="showLikers(post.id)"
          >
            {{ post.likes || 0 }}
          </span>
        </div>
        <div>
          <button class="btn btn-light btn-xs" @click="toggleComments(post.id)">
            <i class="fas fa-comment fa-lg"></i>
          </button>
          <span class="ms-2">{{ commentCount }}</span>
        </div>
      </div>

      <div v-if="post.showComments">
        <div v-for="comment in (post.comments || [])" :key="comment.id" class="mb-2">
          <div class="d-flex align-items-center">
            <strong class="me-2" style="font-size: 0.85rem;">
              {{ comment.user?.firstname || comment.user?.username || 'Unknown' }}  {{ comment.user?.lastname || 'Unknown' }}
            </strong>
            <small class="text-muted">{{ formatDate(comment.created_at) }}</small>
            <div class="ms-auto" v-if="canEditComment(comment) || canDeleteComment(comment)">
              <button
                v-if="canEditComment(comment)"
                class="btn btn-link btn-sm p-0 me-2 text-muted"
                title="Edit comment"
                aria-label="Edit comment"
                @click="startEditComment(comment)"
              >
                <i class="fas fa-pen"></i>
              </button>
              <button
                v-if="canDeleteComment(comment)"
                class="btn btn-link btn-sm p-0 text-danger"
                title="Delete comment"
                aria-label="Delete comment"
                @click="deleteComment(comment)"
              >
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          <div v-if="editingCommentId === comment.id">
            <textarea
              v-model="editingCommentContent"
              class="form-control form-control-sm mb-1"
              :maxlength="commentMaxLength"
              placeholder="Edit your comment..."
            ></textarea>
            <div class="d-flex justify-content-between align-items-center mb-1">
              <small class="text-muted">{{ editingCommentContent.length }}/{{ commentMaxLength }}</small>
              <small v-if="editCommentError" class="text-danger">{{ editCommentError }}</small>
            </div>
            <button class="btn btn-primary btn-xs text-white me-2" @click="saveComment(comment)">Save</button>
            <button class="btn btn-light btn-xs" @click="cancelEdit">Cancel</button>
          </div>
          <div v-else>
            <small v-html="renderComment(comment.content, comment.user)"></small>
          </div>
        </div>
        <input
          type="text"
          v-model="newComment"
          class="form-control mt-3"
          placeholder="Write a comment..."
          :maxlength="commentMaxLength"
        />
        <div class="d-flex justify-content-between align-items-center mt-1">
          <small class="text-muted">{{ newComment.length }}/{{ commentMaxLength }}</small>
          <small v-if="commentError" class="text-danger">{{ commentError }}</small>
        </div>
        <button class="btn btn-primary btn-sm mt-2 text-white" @click="postComment(post.id)">Comment</button>
      </div>
    </div>
  </div>
</template>

<script>
import PostActions from './PostActions.vue';
import { formatDate } from '../utils/dateUtils';
import { cmMedia, cmRoute } from '../utils/campaignManager';

export default {
  components: {
    PostActions,
  },
  props: {
    post: {
      type: Object,
      required: true,
    },
    profileData: {
      type: Object,
      default: () => ({}),
    },
    followingIds: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      newComment: '',
      editingCommentId: null,
      editingCommentContent: '',
      commentError: '',
      editCommentError: '',
    };
  },
  computed: {
    noImageUrl() {
      return cmMedia('noImage');
    },
    profileLink() {
      if (this.post.user?.id === this.profileData?.id) {
        return cmRoute('profileTimeline');
      }
      if (this.post.user?.username) {
        return cmRoute('viewUserPosts', { username: this.post.user.username });
      }
      return '#';
    },
    isSelf() {
      return this.post.user?.id && this.profileData?.id && this.post.user.id === this.profileData.id;
    },
    isFollowingUser() {
      return this.followingIds.includes(this.post.user?.id);
    },
    canFollow() {
      return !!this.post.user?.id && !!this.profileData?.id;
    },
    commentCount() {
      if (typeof this.post.comments_count === 'number') return this.post.comments_count;
      return (this.post.comments || []).length;
    },
    likerNamesTooltip() {
      if (!this.post.liker_names || !this.post.liker_names.length) return '';
      return this.post.liker_names.join(', ');
    },
    commentMaxLength() {
      return 750;
    },
  },
  methods: {
    memberImageUrl(filename) {
      return cmMedia('memberImages', filename);
    },
    communityPhotoUrl(filename) {
      return cmMedia('communityPhotos', filename);
    },
    communityVideoUrl(filename) {
      return cmMedia('communityVideos', filename);
    },
    likePost(postId) {
      if (!postId) return;
      this.$emit('like', postId);
    },
    toggleComments(postId) {
      this.$emit('toggle-comments', postId);
    },
    postComment(postId) {
      this.commentError = '';
      const trimmed = this.newComment.trim();
      if (!postId || !trimmed) {
        this.commentError = 'Comment cannot be empty.';
        return;
      }
      if (trimmed.length > this.commentMaxLength) {
        this.commentError = `Comments are limited to ${this.commentMaxLength} characters.`;
        return;
      }
      this.$emit('post-comment', { postId, content: this.newComment });
      this.newComment = '';
    },
    canEditComment(comment) {
      const userId = this.profileData?.id;
      return (
        Number(comment.user?.id) === Number(userId) ||
        this.post.can_moderate === true ||
        false
      );
    },
    canDeleteComment(comment) {
      const userId = this.profileData?.id;
      return (
        Number(comment.user?.id) === Number(userId) ||
        Number(this.post.user?.id) === Number(userId) ||
        this.post.can_moderate === true ||
        false
      );
    },
    startEditComment(comment) {
      this.editingCommentId = comment.id;
      this.editingCommentContent = comment.content;
      this.editCommentError = '';
    },
    cancelEdit() {
      this.editingCommentId = null;
      this.editingCommentContent = '';
      this.editCommentError = '';
    },
    saveComment(comment) {
      this.editCommentError = '';
      const trimmed = this.editingCommentContent.trim();
      if (!trimmed) {
        this.editCommentError = 'Comment cannot be empty.';
        return;
      }
      if (trimmed.length > this.commentMaxLength) {
        this.editCommentError = `Comments are limited to ${this.commentMaxLength} characters.`;
        return;
      }
      this.$emit('update-comment', {
        commentId: comment.id,
        content: trimmed,
        postId: this.post.id,
      });
      this.cancelEdit();
    },
    deleteComment(comment) {
      this.$emit('delete-comment', { commentId: comment.id, postId: this.post.id });
    },
    showLikers(postId) {
      this.$emit('show-likers', postId);
    },
    toggleFollow() {
      this.$emit('toggle-follow', { userId: this.post.user?.id, isFollowing: this.isFollowingUser });
    },
    renderComment(content) {
      if (!content) return '';
      const escapeHtml = (str) => str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

      const escaped = escapeHtml(content);
      return escaped.replace(/(^|\\s)@([\\w.-]+)/g, '$1<span class=\"mention\">@$2</span>');
    },
    formatDate,
  },
};
</script>

<style scoped>
a {
  text-decoration: none !important;
}

.rounded-circle {
  width: 50px;
  height: 50px;
  object-fit: cover;
}

.timestamp {
  font-size: 0.55rem;
  margin-top: -4px;
}

.btn-xs {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}

.follow-btn-icon {
  padding: 0.25rem 0.55rem;
  font-size: 0.8rem;
  line-height: 1;
  white-space: nowrap;
}

.mention {
  color: #0d6efd;
  font-weight: 600;
}
</style>
