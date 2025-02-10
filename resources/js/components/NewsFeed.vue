<template>
  <div>
    <Lightbox />
    <div class="infinite-scroll-area" @scroll="handleScroll">
      <PostItem
        v-for="post in posts"
        :key="post.id"
        :post="post"
        :profileData="profileData"
        @edit="editPost"
        @delete="deletePost"
        @like="likePost"
        @toggle-comments="toggleComments"
        @post-comment="postComment"
      />
      <div v-if="loading" class="text-center my-3">
        <span>Loading...</span>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import PostItem from './PostItem.vue';
import { formatDate } from '../utils/dateUtils';

const Swal = window.Swal;

export default {
  components: {
    PostItem,
  },
  props: {
    audience: {
      type: String,
      default: 'public',
      validator: (value) => ['public', 'private', 'friends'].includes(value),
    },
    profileData: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      posts: [],
      newComment: {},
      currentPage: 1,
      hasMore: true,
      loading: false,
      useWebSocket: true,
      pollingInterval: null,
      isFormSubmitting: false,
      isUserActive: true,
    };
  },
  watch: {
    audience: {
      handler(newAudience) {
        this.posts = [];
        this.currentPage = 1;
        this.hasMore = true;
        this.fetchPosts();
      },
      immediate: true,
    },
  },
  mounted() {
    this.fetchPosts();
    this.setupWebSocket();
    this.setupPollingFallback();
    this.setupUserActivityTracking();
  },
  beforeUnmount() {
    this.cleanupWebSocket();
    this.cleanupPolling();
    this.cleanupUserActivityTracking();
  },
  methods: {
    async fetchPosts() {
      if (this.loading || !this.hasMore) return;

      this.loading = true;
      try {
        const response = await axios.get(`/community/posts`, {
          params: {
            audience: this.audience,
            page: this.currentPage,
          },
        });

        const newPosts = response.data.data.filter(
          newPost => !this.posts.some(existingPost => existingPost.id === newPost.id)
        );

        if (newPosts.length > 0) {
          this.posts = [...this.posts, ...newPosts];
          this.currentPage += 1;
        } else {
          this.hasMore = false;
        }
      } catch (error) {
        Swal.fire('Error', 'Unable to fetch posts. Please try again later.', 'error');
      } finally {
        this.loading = false;
      }
    },
    setupWebSocket() {
      if (!window.Echo) {
        console.warn('Echo is not initialized. Falling back to polling.');
        this.useWebSocket = false;
        return;
      }

      window.Echo.connector.socket.on('connect', () => {
        console.log('WebSocket connected.');
        this.useWebSocket = true;

        window.Echo.private('posts')
          .listen('.NewPostCreated', (data) => {
            this.posts.unshift(data.post);
          });
      });

      window.Echo.connector.socket.on('error', (error) => {
        console.error('WebSocket connection failed. Falling back to polling.', error);
        this.useWebSocket = false;
        this.setupPollingFallback();
      });
    },
    setupPollingFallback() {
      if (this.useWebSocket || this.pollingInterval) return;

      this.pollingInterval = setInterval(() => {
        if (this.isUserActive) {
          this.fetchPosts();
        }
      }, 10000);
    },
    cleanupWebSocket() {
      if (window.Echo) {
        window.Echo.leaveChannel('posts');
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
    editPost(post) {
      this.editingPost = post;
    },
    async updatePost(updatedPost) {
      try {
        const response = await axios.put(`/community/posts/${updatedPost.id}`, {
          content: updatedPost.content,
        });

        const index = this.posts.findIndex((p) => p.id === updatedPost.id);
        if (index !== -1) {
          this.posts[index] = { ...this.posts[index], ...response.data };
        }

        this.editingPost = null;
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
          await axios.delete(`/community/posts/${post.id}`);
          this.posts = this.posts.filter((p) => p.id !== post.id);
          this.deletingPost = null;
          Swal.fire('Deleted!', 'Your post has been deleted.', 'success');
        } catch (error) {
          Swal.fire('Error', 'Unable to delete post. Please try again.', 'error');
        }
      }
    },
    async postComment(postId) {
      if (!this.newComment[postId]) return;

      try {
        const response = await axios.post(`/community/posts/${postId}/comments`, {
          content: this.newComment[postId],
        });

        const post = this.posts.find((p) => p.id === postId);
        if (post) {
          if (!post.comments) post.comments = [];
          post.comments.push({
            id: response.data.id || Date.now(),
            content: this.newComment[postId],
            created_at: response.data.created_at || new Date().toISOString(),
          });
        }

        this.newComment[postId] = '';
      } catch (error) {
        Swal.fire('Error', 'Unable to post comment. Please try again later.', 'error');
      }
    },
    toggleComments(postId) {
      const post = this.posts.find((p) => p.id === postId);
      if (post) {
        post.showComments = !post.showComments;
      }
    },
    formatDate,
  },
};
</script>

<style>
a {
  text-decoration: none !important;
}
</style>