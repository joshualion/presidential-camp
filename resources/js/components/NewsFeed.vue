<template>
    <div>
      <div v-for="post in posts" :key="post.id" class="card mb-3">
        <div class="card-body">
            <div class="row">
  <div class="d-flex justify-content-between w-100">
    <!-- Left side: User's profile picture and name -->
    <h6 class="card-title username d-flex align-items-center">
      <img 
        :src="post.user?.photo ? '/uploads/member_images/' + post.user.photo : '/uploads/no_image.jpg'" 
        alt="Profile Picture" 
        class="rounded-circle me-3" 
        style="width: 50px; height: 50px; object-fit: cover;" 
      />
      {{ post.user?.firstname || 'Unknown' }} {{ post.user?.lastname || 'User' }}
    </h6>
    
    <!-- Right side: Formatted creation date -->
    <span class="text-secondary text-small">
      {{ formatDate(post.created_at) }}
    </span>
  </div>
</div>

            
    <p class="card-text">{{ post.content }}</p>
          
          <img 
      v-if="post.image_url" 
      class="card-img img-responsive" 
      :src="'/uploads/community/photos/' + post.image_url" 
      alt="image" 
      style="width:500px; height:400px; object-fit: cover;" 
    />
      <!-- Check if the post has a video -->
      <video 
      v-if="post.video_url" 
      class="card-video w-100" 
      :src="'/uploads/community/videos/' + post.video_url" 
      controls 
      style="object-fit: cover;">
      Your browser does not support the video tag.
    </video>

          <div class="d-flex justify-content-between mt-3">
            <div>
              <button class="btn btn-success btn-sm text-white" @click="likePost(post.id)">Like</button>
              <span class="ms-2">{{ post.likes }}</span>
            </div>
            <div>
              <button class="btn btn-light btn-sm" @click="toggleComments(post.id)">Comment</button>
              <span class="ms-2">{{ post.comments?.length || 0 }}</span>
            </div>
          </div>
          <div v-if="post.showComments">
            <div v-for="comment in post.comments || []" :key="comment.id">
              <small>{{ comment.content }}</small>
            </div>
            <input
              type="text"
              v-model="newComment"
              class="form-control"
              placeholder="Write a comment..."
            />
            <button class="btn btn-success btn-sm mt-2" @click="postComment(post.id)">Post Comment</button>
          </div>
        </div>
      </div>
      <button class="btn btn-primary" v-if="hasMore" @click="loadMore">Load More</button>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  import dayjs from 'dayjs';

  import relativeTime from 'dayjs/plugin/relativeTime';

// Extend dayjs to include the relativeTime plugin
dayjs.extend(relativeTime);
  
  export default {
    data() {
      return {
        posts: [],        // Define posts array to hold post data
        newComment: '',    // Define newComment to handle user input
        currentPage: 1,    // Define currentPage for pagination
        hasMore: true,     // Define hasMore to track if there are more posts to load
        intervalId: null,  // Store the interval ID to clear it later
      };
    },
    props: {
      audience: {
        type: String,
        default: 'public',
      },
    },
    mounted() {
      this.fetchPosts();
      // Set interval to refresh posts every 30 seconds
      this.intervalId = setInterval(this.fetchPosts, 10000); // 10 seconds
    },
    beforeUnmount() {
      // Clear the interval to prevent memory leaks
      if (this.intervalId) {
        clearInterval(this.intervalId);
      }
    },
    methods: {
      async fetchPosts() {
        try {
          const response = await axios.get(`/community/posts`, {
            params: { audience: this.audience, page: this.currentPage },
          });
          this.posts = response.data.data; // Replace with the latest data
          this.hasMore = response.data.next_page_url !== null; // Check if there's more data
        } catch (error) {
          console.error(error);
        }
      },
      likePost(postId) {
        console.log(`Liked post ${postId}`);
      },
      toggleComments(postId) {
        const post = this.posts.find(p => p.id === postId);
        post.showComments = !post.showComments;
      },
      async postComment(postId) {
        if (!this.newComment) return;
  
        try {
          await axios.post(`/community/posts/${postId}/comments`, {
            content: this.newComment,
          });
          const post = this.posts.find(p => p.id === postId);
          post.comments.push({ content: this.newComment });
          this.newComment = '';
        } catch (error) {
          console.error(error);
        }
      },
      loadMore() {
        this.currentPage++;
        this.fetchPosts();
      },

      // Method to format the date in human-readable form
    formatDate(date) {
      return dayjs(date).fromNow();
    },
    },
  };
  </script>
  
  
