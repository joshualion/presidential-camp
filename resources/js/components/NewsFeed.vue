<template>
  <div>
    <!-- Scrollable container with a fixed height to enable scrolling -->
    <div class="infinite-scroll-area"  @scroll="handleScroll">
      <!-- Display posts -->
      <div v-for="post in posts" :key="post.id" class="card mb-3">
        <div class="card-body">
          <div class="row">
            <!-- <div class="d-flex justify-content-between w-100">
              <h6 class="card-title username d-flex align-items-center">
                <img
                  :src="post.user?.photo ? '/uploads/member_images/' + post.user.photo : '/uploads/no_image.jpg'"
                  alt="Profile Picture"
                  class="rounded-circle me-3"
                  style="width: 50px; height: 50px; object-fit: cover;"
                />
                {{ post.user?.firstname || 'Unknown' }} {{ post.user?.lastname || 'User' }}
              </h6>
              <span class="text-secondary d-block" style="font-size: 0.75rem; margin-top: -4px;">
      {{ formatDate(post.created_at) }}
    </span>
            </div> -->
            <div class="d-flex justify-content-between w-100">
  <h6 class="card-title username d-flex align-items-center">
    <img
      :src="post.user?.photo ? '/uploads/member_images/' + post.user.photo : '/uploads/no_image.jpg'"
      alt="Profile Picture"
      class="rounded-circle me-3"
      style="width: 50px; height: 50px; object-fit: cover;"
    />
    {{ post.user?.firstname || 'Unknown' }} {{ post.user?.lastname || 'User' }}
  </h6>
  <div class="d-flex align-items-center">
    <span class="text-secondary d-block" style="font-size: 0.55rem; margin-top: -4px;">
      {{ formatDate(post.created_at) }}
    </span>

    <!-- Gear icon dropdown -->
<div v-if="post.user?.id && post.user.id === profileData.id">
  <!-- Show edit or delete form if editing or deleting -->
  <div v-if="editingPost && editingPost.id === post.id">
    <EditPost :post="post" @updatePost="updatePost" @cancel="editingPost = null" />
  </div>
  <div v-else-if="deletingPost && deletingPost.id === post.id">
    <DeletePost :post="post" @deletePost="confirmDelete" @cancel="deletingPost = null" />
  </div>
  <!-- Default gear dropdown -->
  <div v-else class="dropdown">
    <span
      class="text-small dropdown-toggle d-block ml-3"
      data-bs-toggle="dropdown"
      aria-expanded="false"
      style="font-size: 0.65rem; margin-top: -25px;"
    >
      <i class="fas fa-cog"></i>
    </span>
    <ul class="dropdown-menu">
      <li>
        <a class="dropdown-item" href="#" @click="editPost(post)">Edit Post</a>
      </li>
      <li>
        <a class="dropdown-item text-danger" href="#" @click="deletePost(post)">Delete Post</a>
      </li>
    </ul>
  </div>
</div>

  </div>
</div>

          </div>
          <p class="card-text">{{ post.content }}</p>
          <img
            v-if="post.image_url"
            class="card-img img-responsive"
            :src="'/uploads/community/photos/' + post.image_url"
            alt="image"
            style="width:100%; height:100%; object-fit: cover;"
          />
          <video
            v-if="post.video_url"
            class="card-video w-100"
            :src="'/uploads/community/videos/' + post.video_url"
            controls
            style="width:100%; height:100%; object-fit: cover;"
          >
            Your browser does not support the video tag.
          </video>
          <div class="d-flex justify-content-between mt-3">
            <div>
              <button class="btn btn-primary btn-xs text-white" @click="likePost(post.id)"><i class="fas fa-thumbs-up"></i></button>
              <span class="ms-2">{{ post.likes }}</span>
            </div>
            <div>
              <button class="btn btn-light btn-xs" @click="toggleComments(post.id)"><i class="fas fa-comment fa-lg"></i></button>
              <span class="ms-2 mt-3">{{ post.comments?.length || 0 }}</span>
            </div>
          </div>
          <div v-if="post.showComments">
            <div v-for="comment in post.comments || []" :key="comment.id">
              <small>{{ comment.content }}</small>
            </div>
            <input
              type="text"
              v-model="newComment"
              class="form-control mt-3"
              placeholder="Write a comment..."
            />
            <button class="btn btn-primary btn-sm mt-2 text-white" @click="postComment(post.id)">Comment</button>
          </div>
        </div>
      </div>
      <!-- Optional loading spinner -->
      <div v-if="loading" class="text-center my-3">
        <span>Loading...</span>
      </div>
    </div>
  </div>
</template>
<script>
import axios from "axios";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
import EditPost from "./EditPost.vue";
import DeletePost from "./DeletePost.vue";
import debounce from "lodash/debounce";
import Swal from "sweetalert2";

dayjs.extend(relativeTime);

export default {
  components: {
    EditPost,
    DeletePost,
  },
  data() {
    return {
      posts: [], // Array to hold posts
      newComment: {}, // Object to hold comments for each post
      currentPage: 1, // Current page for pagination
      hasMore: true, // Track if there are more posts to fetch
      loading: false, // Loading state for fetchPosts
      intervalId: null, // Store the interval ID to clear it later
      editingPost: null, // Track the post being edited
      deletingPost: null, // Track the post being deleted
    };
  },
  props: {
    audience: {
      type: String,
      default: "public",
      validator: (value) => ["public", "private", "friends"].includes(value), // Validate allowed values
    },
    profileData: {
      type: Object,
      default: () => ({}), // Default to an empty object if not provided
    },
  },
  watch: {
    audience: {
      handler(newAudience) {
        // Reset posts and pagination when audience changes
        this.posts = [];
        this.currentPage = 1;
        this.hasMore = true;
        this.fetchPosts();
      },
      immediate: true, // Trigger handler immediately on mount
    },
  },
  mounted() {
    // Start polling when the tab is focused
    window.addEventListener('focus', this.startPolling);
    window.addEventListener('blur', this.stopPolling);
    this.startPolling(); // Start polling initially
  },
  beforeUnmount() {
    // Clean up event listeners and stop polling
    window.removeEventListener('focus', this.startPolling);
    window.removeEventListener('blur', this.stopPolling);
    this.stopPolling();
  },
  methods: {
    startPolling() {
      // Only start polling if it's not already running
      if (!this.intervalId) {
        this.intervalId = setInterval(() => {
          if (!this.loading) this.fetchPosts();
        }, 10000); // Poll every 10 seconds
      }
    },
    stopPolling() {
      // Clear the interval if it's running
      if (this.intervalId) {
        clearInterval(this.intervalId);
        this.intervalId = null;
      }
    },
    async fetchPosts() {
      if (this.loading) return;

      this.loading = true;
      try {
        // Get the timestamp of the latest post in the current feed
        const lastPostTimestamp = this.posts.length > 0 ? this.posts[0].created_at : null;

        const response = await axios.get(`/community/posts`, {
          params: {
            audience: this.audience,
            last_post_timestamp: lastPostTimestamp, // Send the timestamp to the backend
          },
        });

        const newPosts = response.data;

        // Prepend new posts to the beginning of the array (for real-time updates)
        this.posts = [...newPosts, ...this.posts];
      } catch (error) {
        Swal.fire("Error", "Unable to fetch posts. Please try again later.", "error");
      } finally {
        this.loading = false;
      }
    },
    handleScroll() {
      if (this.loading) return; // Prevent overlapping fetches
      const scrollPosition = window.scrollY + window.innerHeight;
      const pageHeight = document.documentElement.scrollHeight;

      if (scrollPosition >= pageHeight - 200) {
        this.fetchPosts(); // Fetch more posts
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

        // Merge updated fields with the existing post
        const index = this.posts.findIndex((p) => p.id === updatedPost.id);
        if (index !== -1) {
          this.posts[index] = { ...this.posts[index], ...response.data };
        }

        this.editingPost = null; // Reset editingPost
      } catch (error) {
        Swal.fire("Error", "Unable to update post. Please try again.", "error");
      }
    },
    async deletePost(post) {
      const confirmation = await Swal.fire({
        title: "Are you sure?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
      });

      if (confirmation.isConfirmed) {
        try {
          await axios.delete(`/community/posts/${post.id}`);
          this.posts = this.posts.filter((p) => p.id !== post.id);
          this.deletingPost = null; // Reset deletingPost
          Swal.fire("Deleted!", "Your post has been deleted.", "success");
        } catch (error) {
          Swal.fire("Error", "Unable to delete post. Please try again.", "error");
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

        this.newComment[postId] = ""; // Clear the comment input for this post
      } catch (error) {
        Swal.fire("Error", "Unable to post comment. Please try again later.", "error");
      }
    },
    toggleComments(postId) {
      const post = this.posts.find((p) => p.id === postId);
      if (post) {
        post.showComments = !post.showComments;
      }
    },
    formatDate(date) {
      return dayjs(date).fromNow(); // Format dates using dayjs
    },
  },
};
</script>