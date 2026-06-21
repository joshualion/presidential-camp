<template>
  <div>
    <div class="infinite-scroll-area" @scroll="handleScroll">
      <PostItem
        v-for="post in posts"
        :key="post.id"
        :post="post"
        :profileData="profileData"
        :followingIds="followingIds"
        @like="likePost"
        @toggle-comments="toggleComments"
        @post-comment="postComment"
        @update-comment="updateComment"
        @delete-comment="deleteComment"
        @show-likers="openLikersModal"
        @toggle-follow="toggleFollow"
      />
      <div v-if="!loading && posts.length === 0" class="text-center text-muted py-4">
        No posts found yet.
      </div>
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
import { cmRoute } from '../utils/campaignManager';

export default {
  components: {
    PostItem,
  },
  props: {
    profileData: {
      type: Object,
      required: true,
    },
    username: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      posts: [],
      loading: false,
      currentPage: 1,
      hasMore: true,
      newComment: {},
      followingIds: [],
      commentMaxLength: 750,
    };
  },
  mounted() {
    this.fetchPosts();
    this.fetchFollowingIds();
  },
  methods: {
    async fetchPosts() {
      if (this.loading || !this.hasMore) return;
      this.loading = true;
      try {
        const endpoint = this.username
          ? cmRoute('viewUserPostsFeed', { username: this.username })
          : cmRoute('userPostsFetch');
        const response = await axios.get(endpoint, {
          params: { page: this.currentPage },
        });
        const payload = response.data || {};
        const data = payload.data || payload || [];
        const newPosts = (data || []).filter(
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

        this.currentPage = (payload.current_page ?? this.currentPage) + 1;
        this.hasMore = (payload.current_page ?? 1) < (payload.last_page ?? 1);
      } catch (error) {
        console.error(error);
      } finally {
        this.loading = false;
      }
    },
    handleScroll(event) {
      if (this.loading) return;
      const el = event.target;
      if (el.scrollTop + el.clientHeight >= el.scrollHeight - 200) {
        this.fetchPosts();
      }
    },
    formatDate,
    async likePost(postId) {
      const post = this.posts.find((p) => p.id === postId);
      if (!post) return;
      const wasLiked = !!post.liked_by_me;
      post.liked_by_me = !wasLiked;
      post.likes = (post.likes || 0) + (post.liked_by_me ? 1 : -1);
      try {
        const response = await axios.post(cmRoute('postsLike', { post: postId }));
        const serverLikes = response.data?.likes;
        const liked = response.data?.liked;
        if (typeof serverLikes === 'number') post.likes = serverLikes;
        if (typeof liked === 'boolean') post.liked_by_me = liked;
      } catch (error) {
        post.liked_by_me = wasLiked;
        post.likes = (post.likes || 0) + (wasLiked ? -1 : 1);
        console.error(error);
      }
    },
    async toggleComments(postId) {
      const post = this.posts.find((p) => p.id === postId);
      if (!post) return;
      post.showComments = !post.showComments;
      if (post.showComments && !post.commentsLoaded) {
        try {
          const response = await axios.get(cmRoute('commentsIndex', { post: postId }));
          post.comments = response.data?.comments || [];
          post.comments_count = post.comments.length;
          post.commentsLoaded = true;
        } catch (error) {
          console.warn('Unable to load comments', error);
        }
      }
    },
    async postComment(payload) {
      const postId = payload?.postId || payload;
      const content = payload?.content ?? this.newComment[postId];
      const trimmed = content?.trim();
      if (!trimmed) return;
      if (trimmed.length > this.commentMaxLength) {
        alert(`Comments are limited to ${this.commentMaxLength} characters.`);
        return;
      }
      try {
        const response = await axios.post(cmRoute('commentsStore', { post: postId }), { content: trimmed });
        const post = this.posts.find((p) => p.id === postId);
        if (post) {
          if (!post.comments) post.comments = [];
          const newComment = response.data?.comment || {
            id: Date.now(),
            content: trimmed,
            created_at: new Date().toISOString(),
            user: this.profileData,
          };
          post.comments.unshift(newComment);
          post.comments_count = (post.comments_count ?? post.comments.length ?? 0) + 1;
        }
        this.newComment[postId] = '';
      } catch (error) {
        console.error(error);
      }
    },
    async updateComment({ commentId, content, postId }) {
      const trimmed = content?.trim();
      if (!commentId || !trimmed || !postId) return;
      if (trimmed.length > this.commentMaxLength) {
        alert(`Comments are limited to ${this.commentMaxLength} characters.`);
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
        console.error(error);
      }
    },
    async deleteComment({ commentId, postId }) {
      if (!commentId || !postId) return;
      try {
        await axios.delete(cmRoute('commentsDestroy', { comment: commentId }));
        const post = this.posts.find((p) => p.id === postId);
        if (post?.comments) {
          post.comments = post.comments.filter((c) => c.id !== commentId);
          post.comments_count = Math.max(0, (post.comments_count ?? post.comments.length ?? 0) - 1);
        }
      } catch (error) {
        console.error(error);
      }
    },
    openLikersModal(postId) {
      this.$emit('show-likers', postId);
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
        console.error(error);
      }
    },
  },
};
</script>
