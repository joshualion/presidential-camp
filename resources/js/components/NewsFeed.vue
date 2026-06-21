<template>
  <div>
    <Lightbox />
    <div class="infinite-scroll-area" @scroll="handleScroll">
      <PostItem
        v-for="post in posts"
        :key="post.id"
        :post="post"
        :profileData="profileData"
        :followingIds="followingIds"
        @edit-post="openEditModal"
        @delete="deletePost"
        @like="likePost"
        @toggle-comments="toggleComments"
        @post-comment="postComment"
        @update-comment="updateComment"
        @delete-comment="deleteComment"
        @show-likers="openLikersModal"
        @toggle-follow="toggleFollow"
      />

      <div v-if="!loading && posts.length === 0" class="text-center text-muted py-4">
        No posts to display yet.
      </div>

      <div v-if="loading" class="text-center my-3">
        <span>Loading...</span>
      </div>
    </div>

    <!-- Likers Modal -->
    <div
      v-if="likersModalOpen"
      class="modal-backdrop-custom likers-modal"
      @click.self="closeLikersModal"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
          <div class="modal-header border-0">
            <h6 class="modal-title mb-0">Liked by</h6>
            <button type="button" class="btn-close" aria-label="Close" @click="closeLikersModal"></button>
          </div>
          <div class="modal-body likers-list">
            <div v-if="likersLoading" class="text-center py-3">
              <small>Loading...</small>
            </div>
            <div v-else-if="likers.length === 0" class="text-center py-3 text-muted">
              <small>No likes yet.</small>
            </div>
            <ul v-else class="list-unstyled mb-0">
              <li v-for="liker in likers" :key="liker.id" class="d-flex align-items-center py-2 border-bottom">
                <a :href="liker.profile_url || '#'" class="d-flex align-items-center text-reset text-decoration-none w-100">
                  <div class="avatar placeholder me-3">
                    <img
                      v-if="liker.photo_url"
                      :src="liker.photo_url"
                      alt="profile"
                      class="rounded-circle avatar-img"
                    />
                    <i v-else class="fas fa-user"></i>
                  </div>
                  <div class="text-truncate">
                    <div class="fw-semibold text-truncate">
                      {{ liker.name || 'User' }}
                    </div>
                  </div>
                </a>
              </li>
            </ul>
          </div>
          <div class="modal-footer d-flex justify-content-between align-items-center flex-wrap gap-2 border-0">
            <small class="text-muted">
              Page {{ likersPage }} of {{ likersLastPage }} - {{ formatCount(likersTotal) }} total
            </small>
            <div class="d-flex gap-2 ms-auto">
              <button
                type="button"
                class="btn btn-outline-dark btn-sm"
                :disabled="likersPage <= 1 || likersLoading"
                @click="loadPrevLikers"
              >
                Prev
              </button>
              <button
                type="button"
                class="btn btn-dark btn-sm text-white"
                :disabled="likersPage >= likersLastPage || likersLoading"
                @click="loadMoreLikers"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Post Modal -->
    <div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editPostModalLabel">Edit Post</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <textarea
              v-model="editFormContent"
              class="form-control"
              rows="4"
              placeholder="Update your post..."
              :maxlength="postMaxLength"
            ></textarea>
            <div class="text-end small text-muted mt-1">{{ editFormContent?.length || 0 }}/{{ postMaxLength }}</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="!editFormContent?.trim() || editFormContent.length > postMaxLength"
              @click="editingPost ? updatePost(editingPost) : null"
            >
              Save Changes
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Lightbox from './LightBox.vue';
import PostItem from './PostItem.vue';
import { formatDate } from '../utils/dateUtils';
import { cmIntervals, cmRealtime, cmRoute } from '../utils/campaignManager';

const Swal = window.Swal;
const allowedAudiences = ['public', 'region', 'state', 'lga', 'ward', 'pu'];

export default {
  components: {
    Lightbox,
    PostItem,
  },
  props: {
    audience: {
      type: String,
      default: 'public',
      validator: (value) => allowedAudiences.includes(value),
    },
    profileData: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      posts: [],
      editFormContent: '',
      currentPage: 1,
      hasMore: true,
      loading: false,
      useWebSocket: true,
      pollingInterval: null,
      isUserActive: true,
      editingPost: null,
      deletingPost: null,
      postsChannel: null,
      channelName: null,
      likersModalOpen: false,
      likersLoading: false,
      likers: [],
      likersPostId: null,
      likersPage: 1,
      likersLastPage: 1,
      likersTotal: 0,
      likersPerPage: 50,
      followingIds: [],
      postMaxLength: 750,
    };
  },
  computed: {},
  watch: {
    audience: {
      handler() {
        this.resetFeed();
        this.fetchPosts();
      },
      immediate: true,
    },
  },
  mounted() {
    this.useWebSocket = cmRealtime().realtimeAvailable;
    this.setupWebSocket();
    this.setupPollingFallback();
    this.setupUserActivityTracking();
    if (typeof this.fetchFollowingIds === 'function') {
      this.fetchFollowingIds();
    }
    window.addEventListener('community-post-created', this.handleLocalPostCreated);
  },
  beforeUnmount() {
    this.cleanupWebSocket();
    this.cleanupPolling();
    this.cleanupUserActivityTracking();
    window.removeEventListener('community-post-created', this.handleLocalPostCreated);
  },
  methods: {
    openEditModal(post) {
      this.editingPost = post;
      this.editFormContent = post?.content || '';
      if (typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getOrCreateInstance('#editPostModal');
        modal.show();
      }
    },
    resetFeed() {
      this.posts = [];
      this.currentPage = 1;
      this.hasMore = true;
    },
    injectPost(post, prepend = true) {
      if (!post || this.posts.some((existing) => existing.id === post.id)) {
        return;
      }
      this.posts = prepend ? [post, ...this.posts] : [...this.posts, post];
    },
    async fetchPosts() {
      if (this.loading || !this.hasMore) return;

      this.loading = true;
      try {
        const response = await axios.get(cmRoute('postsFetch'), {
          params: {
            audience: this.audience,
            page: this.currentPage,
          },
        });

        const newPosts = (response.data?.data ?? []).filter(
          (incoming) => !this.posts.some((existing) => existing.id === incoming.id),
        );

        if (newPosts.length) {
          this.posts = [
            ...this.posts,
            ...newPosts.map((p) => ({
              ...p,
              likes: p.likes_count ?? p.likes ?? 0,
              comments_count: p.comments_count ?? (p.comments ? p.comments.length : 0),
              liked_by_me: p.liked_by_me ?? false,
              liker_names: p.liker_names ?? [],
            })),
          ];
        }

        this.currentPage = (response.data?.current_page ?? this.currentPage) + 1;
        this.hasMore = (response.data?.current_page ?? 1) < (response.data?.last_page ?? 1);
      } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Unable to fetch posts. Please try again later.', 'error');
      } finally {
        this.loading = false;
      }
    },
    async refreshLatestPosts() {
      try {
        const response = await axios.get(cmRoute('postsFetch'), {
          params: {
            audience: this.audience,
            page: 1,
          },
        });

        const latest = response.data?.data ?? [];
        for (let index = latest.length - 1; index >= 0; index -= 1) {
          const item = latest[index];
          this.injectPost({
            ...item,
            likes: item.likes_count ?? item.likes ?? 0,
            comments_count: item.comments_count ?? (item.comments ? item.comments.length : 0),
            liked_by_me: item.liked_by_me ?? false,
            liker_names: item.liker_names ?? [],
          });
        }
      } catch (error) {
        console.warn('Unable to refresh posts', error);
      }
    },
    setupWebSocket() {
      if (!cmRealtime().realtimeAvailable) {
        this.useWebSocket = false;
        this.setupPollingFallback();
        return;
      }

      if (!window.Echo || typeof window.Echo.private !== 'function') {
        this.useWebSocket = false;
        this.setupPollingFallback();
        return;
      }

      try {
        this.postsChannel = window.Echo.private('posts').listen('.NewPostCreated', ({ post }) => {
          this.injectPost({
            ...post,
            likes: post.likes_count ?? post.likes ?? 0,
            comments_count: post.comments_count ?? (post.comments ? post.comments.length : 0),
            liked_by_me: post.liked_by_me ?? false,
            liker_names: post.liker_names ?? [],
          });
        });
        this.channelName = this.postsChannel?.name ?? 'private-posts';
        this.bindEchoConnectionEvents();
      } catch (error) {
        console.warn('Unable to initialize websocket channel. Falling back to polling.', error);
        this.useWebSocket = false;
        this.setupPollingFallback();
      }
    },
    bindEchoConnectionEvents() {
      const connector = window.Echo?.connector;
      if (!connector) {
        this.useWebSocket = false;
        this.setupPollingFallback();
        return;
      }

      const connection = connector.pusher?.connection;
      if (connection && typeof connection.bind === 'function') {
        connection.bind('connected', () => {
          this.useWebSocket = true;
        });
        connection.bind('error', (error) => {
          console.error('WebSocket connection error.', error);
          this.useWebSocket = false;
          this.setupPollingFallback();
        });
        return;
      }

      const socket = connector.socket;
      if (socket && typeof socket.on === 'function') {
        socket.on('connect', () => {
          this.useWebSocket = true;
        });
        socket.on('error', (error) => {
          console.error('WebSocket connection error.', error);
          this.useWebSocket = false;
          this.setupPollingFallback();
        });
        return;
      }

      this.useWebSocket = false;
      this.setupPollingFallback();
    },
    setupPollingFallback() {
      if (this.useWebSocket || this.pollingInterval) return;

      this.pollingInterval = setInterval(() => {
        if (this.isUserActive) {
          this.refreshLatestPosts();
        }
      }, cmIntervals().feed);
    },
    cleanupWebSocket() {
      if (window.Echo && this.postsChannel) {
        const channel = this.channelName || this.postsChannel.name;
        if (channel) {
          window.Echo.leave(channel);
        }
        this.postsChannel = null;
        this.channelName = null;
      }
    },
    cleanupPolling() {
      if (this.pollingInterval) {
        clearInterval(this.pollingInterval);
        this.pollingInterval = null;
      }
    },
    setupUserActivityTracking() {
      document.addEventListener('visibilitychange', this.handleVisibilityChange);
      window.addEventListener('focus', this.handleUserActive);
      window.addEventListener('blur', this.handleUserInactive);
    },
    cleanupUserActivityTracking() {
      document.removeEventListener('visibilitychange', this.handleVisibilityChange);
      window.removeEventListener('focus', this.handleUserActive);
      window.removeEventListener('blur', this.handleUserInactive);
    },
    handleVisibilityChange() {
      this.isUserActive = !document.hidden;
    },
    handleUserActive() {
      this.isUserActive = true;
    },
    handleUserInactive() {
      this.isUserActive = false;
    },
    handleScroll(event) {
      if (this.loading) return;
      const scrollableContainer = event.target;
      const scrollPosition = scrollableContainer.scrollTop + scrollableContainer.clientHeight;
      const scrollHeight = scrollableContainer.scrollHeight;

      if (scrollPosition >= scrollHeight - 200) {
        this.fetchPosts();
      }
    },
    handleLocalPostCreated(event) {
      this.injectPost({
        ...event.detail,
        likes: event.detail.likes_count ?? event.detail.likes ?? 0,
        comments_count: event.detail.comments_count ?? (event.detail.comments ? event.detail.comments.length : 0),
        liked_by_me: event.detail.liked_by_me ?? false,
        liker_names: event.detail.liker_names ?? [],
      });
    },
    async updatePost(updatedPost) {
      const trimmed = this.editFormContent?.trim();
      if (!trimmed) {
        Swal.fire('Empty post', 'Please add some content before saving.', 'warning');
        return;
      }
      if (trimmed.length > this.postMaxLength) {
        Swal.fire('Too long', `Posts are limited to ${this.postMaxLength} characters.`, 'warning');
        return;
      }
      try {
        const response = await axios.put(cmRoute('postsUpdate', { post: updatedPost.id }), {
          content: trimmed || updatedPost.content,
        });

        const index = this.posts.findIndex((p) => p.id === updatedPost.id);
        if (index !== -1) {
          const payload = response.data?.post || response.data;
          this.posts[index] = {
            ...this.posts[index],
            ...payload,
            likes: payload.likes_count ?? this.posts[index].likes ?? 0,
            comments_count: payload.comments_count ?? this.posts[index].comments_count ?? 0,
          };
        }

        this.editingPost = null;
        if (typeof bootstrap !== 'undefined') {
          const modal = bootstrap.Modal.getInstance('#editPostModal');
          if (modal) modal.hide();
        }
      } catch (error) {
        Swal.fire('Error', 'Unable to update post. Please try again.', 'error');
      }
    },
    async deletePost(post) {
      const confirmation = await Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
      });

      if (confirmation.isConfirmed) {
        try {
          await axios.delete(cmRoute('postsDestroy', { post: post.id }));
          this.posts = this.posts.filter((p) => p.id !== post.id);
          this.deletingPost = null;
          Swal.fire('Deleted!', 'Your post has been deleted.', 'success');
        } catch (error) {
          Swal.fire('Error', 'Unable to delete post. Please try again.', 'error');
        }
      }
    },
    async likePost(postId) {
      const post = this.posts.find((p) => p.id === postId);
      if (!post) return;

      const wasLiked = !!post.liked_by_me;

      // Optimistic toggle
      post.liked_by_me = !wasLiked;
      post.likes = (post.likes || 0) + (post.liked_by_me ? 1 : -1);

      try {
        const response = await axios.post(cmRoute('postsLike', { post: postId }));
        const serverLikes = response.data?.likes;
        const liked = response.data?.liked;
        if (typeof serverLikes === 'number') {
          post.likes = serverLikes;
        }
        if (typeof liked === 'boolean') {
          post.liked_by_me = liked;
        }
      } catch (error) {
        console.warn('Unable to like post.', error);
        // revert on failure
        post.liked_by_me = wasLiked;
        post.likes = (post.likes || 0) + (wasLiked ? -1 : 1);
        Swal.fire('Error', 'Unable to like this post right now.', 'error');
      }
    },
    async postComment(payload) {
      const { postId, content } = payload || {};
      const trimmed = content?.trim();
      if (!postId || !trimmed) {
        Swal.fire('Empty comment', 'Please write something before posting.', 'warning');
        return;
      }
      if (trimmed.length > 750) {
        Swal.fire('Too long', 'Comments are limited to 750 characters.', 'warning');
        return;
      }

      try {
        const response = await axios.post(cmRoute('commentsStore', { post: postId }), {
          content: trimmed,
        });

        const post = this.posts.find((p) => p.id === postId);
        if (post) {
          if (!post.comments) post.comments = [];
          const newComment = response.data?.comment || {
            id: response.data?.id || Date.now(),
            content,
            created_at: response.data?.created_at || new Date().toISOString(),
            user: response.data?.user || this.profileData,
          };
          post.comments.unshift(newComment);
          post.comments_count = (post.comments_count ?? post.comments.length ?? 0) + 1;
        }
      } catch (error) {
        Swal.fire('Error', 'Unable to post comment. Please try again later.', 'error');
      }
    },
    async updateComment({ commentId, content, postId }) {
      const trimmed = content?.trim();
      if (!commentId || !trimmed || !postId) {
        Swal.fire('Empty comment', 'Please write something before saving.', 'warning');
        return;
      }
      if (trimmed.length > 750) {
        Swal.fire('Too long', 'Comments are limited to 750 characters.', 'warning');
        return;
      }
      try {
        const response = await axios.put(cmRoute('commentsUpdate', { comment: commentId }), { content: trimmed });
        const updated = response.data?.comment;
        const post = this.posts.find((p) => p.id === postId);
        if (post?.comments) {
          const idx = post.comments.findIndex((c) => c.id === commentId);
          if (idx !== -1) {
            post.comments[idx] = {
              ...post.comments[idx],
              ...updated,
              user: updated?.user || post.comments[idx].user,
            };
          }
        }
      } catch (error) {
        Swal.fire('Error', 'Unable to update comment right now.', 'error');
      }
    },
    async deleteComment({ commentId, postId }) {
      if (!commentId || !postId) return;
      const confirm = await Swal.fire({
        title: 'Delete comment?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
      });
      if (!confirm.isConfirmed) return;

      try {
        await axios.delete(cmRoute('commentsDestroy', { comment: commentId }));
        const post = this.posts.find((p) => p.id === postId);
        if (post?.comments) {
          post.comments = post.comments.filter((c) => c.id !== commentId);
          post.comments_count = Math.max(0, (post.comments_count ?? post.comments.length ?? 0) - 1);
        }
      } catch (error) {
        Swal.fire('Error', 'Unable to delete comment right now.', 'error');
      }
    },
    async toggleComments(postId) {
      const post = this.posts.find((p) => p.id === postId);
      if (!post) return;

      post.showComments = !post.showComments;

      // Lazy load comments when first opened
      if (post.showComments && !post.commentsLoaded) {
        try {
          const response = await axios.get(cmRoute('commentsIndex', { post: postId }));
          post.comments = response.data?.comments || [];
          post.comments_count = post.comments.length;
          post.commentsLoaded = true;
        } catch (error) {
          console.warn('Unable to load comments', error);
          Swal.fire('Error', 'Unable to load comments right now.', 'error');
        }
      }
    },
    async openLikersModal(postId) {
      if (!postId) return;
      this.likersModalOpen = true;
      this.likersLoading = true;
      this.likers = [];
      this.likersPostId = postId;
      this.likersPage = 1;
      try {
        await this.fetchLikersPage();
      } catch (error) {
        Swal.fire('Error', 'Unable to load likers.', 'error');
      } finally {
        this.likersLoading = false;
      }
    },
    async fetchLikersPage() {
      if (!this.likersPostId) return;
      this.likersLoading = true;
      try {
        const response = await axios.get(cmRoute('postsLikers', { post: this.likersPostId }), {
          params: {
            page: this.likersPage,
            per_page: this.likersPerPage,
          },
        });
        this.likers = response.data?.likers || [];
        this.likersTotal = response.data?.meta?.total ?? this.likers.length;
        this.likersLastPage = response.data?.meta?.last_page ?? this.likersPage;
      } catch (error) {
        Swal.fire('Error', 'Unable to load likers.', 'error');
      } finally {
        this.likersLoading = false;
      }
    },
    async loadMoreLikers() {
      if (this.likersPage >= this.likersLastPage) return;
      this.likersPage += 1;
      await this.fetchLikersPage();
    },
    async loadPrevLikers() {
      if (this.likersPage <= 1) return;
      this.likersPage -= 1;
      await this.fetchLikersPage();
    },
    closeLikersModal() {
      this.likersModalOpen = false;
      this.likersLoading = false;
      this.likers = [];
      this.likersPostId = null;
      this.likersPage = 1;
      this.likersLastPage = 1;
      this.likersTotal = 0;
    },
    async fetchFollowingIds() {
      try {
        const response = await axios.get(cmRoute('followingIds'));
        this.followingIds = response.data?.ids || [];
      } catch (error) {
        console.warn('Unable to load following ids', error);
      }
    },
    async toggleFollow({ userId, isFollowing }) {
      if (!userId || userId === this.profileData?.id) return;
      try {
        if (isFollowing) {
          await axios.delete(cmRoute('userFollow', { user: userId }));
          this.followingIds = this.followingIds.filter((id) => id !== userId);
        } else {
          await axios.post(cmRoute('userFollow', { user: userId }));
          if (!this.followingIds.includes(userId)) {
            this.followingIds.push(userId);
          }
        }
      } catch (error) {
        Swal.fire('Error', 'Unable to update follow state right now.', 'error');
      }
    },
    formatDate,
    formatCount(value) {
      const num = Number(value) || 0;
      if (num >= 1_000_000_000) return `${(num / 1_000_000_000).toFixed(1)}B`;
      if (num >= 1_000_000) return `${(num / 1_000_000).toFixed(1)}M`;
      if (num >= 1_000) return `${(num / 1_000).toFixed(1)}K`;
      return num.toString();
    },
  },
};
</script>

<style>
a {
  text-decoration: none !important;
}

.modal-backdrop-custom {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1055;
}

.likers-modal .modal-dialog {
  max-width: 420px;
  width: 92%;
}

.likers-modal .modal-content {
  border-radius: 10px;
  overflow: hidden;
  background: #ffffff;
  box-shadow: 0 14px 45px rgba(0, 0, 0, 0.18), 0 10px 18px rgba(0, 0, 0, 0.16);
  padding: 0.75rem 0.75rem 0;
}

.avatar.placeholder {
  width: 32px;
  height: 32px;
  background: #f1f3f5;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  font-size: 0.75rem;
}

.avatar-img {
  width: 32px;
  height: 32px;
  object-fit: cover;
}

.likers-list {
  max-height: 60vh;
  overflow-y: auto;
  padding: 0.75rem 1rem;
}

.likers-modal .modal-header,
.likers-modal .modal-footer {
  padding: 0.75rem 1rem;
  border: none;
}

.likers-modal .modal-body {
  background: #ffffff;
}
</style>
