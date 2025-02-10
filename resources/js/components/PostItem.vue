<template>
    <div class="card mb-3">
      <div class="card-body">
        <div class="row">
          <div class="d-flex justify-content-between w-100">
            <h6 class="card-title username d-flex align-items-center">
              <a :href="post.user?.id === profileData.id ? '/community/profile' : `/community/${post.user?.username}/profile/timeline`">
                <img
                  :src="post.user?.photo ? '/uploads/member_images/' + post.user.photo : '/uploads/no_image.jpg'"
                  alt="Profile Picture"
                  class="rounded-circle me-3"
                  style="width: 50px; height: 50px; object-fit: cover;"
                />
              </a>
              <a :href="post.user?.id === profileData.id ? '/community/profile' : `/community/${post.user?.username}/profile/timeline`">
                {{ post.user?.firstname || 'Unknown' }} {{ post.user?.lastname || 'User' }}
              </a>
            </h6>
            <div class="d-flex align-items-center">
              <span class="text-secondary d-block" style="font-size: 0.55rem; margin-top: -4px;">
                {{ formatDate(post.created_at) }}
              </span>
              <PostActions :post="post" :profileData="profileData" @edit="editPost" @delete="deletePost" />
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
  </template>
  
  <script>
  import PostActions from './PostActions.vue';
  import { formatDate } from '../utils/dateUtils';
  
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
        required: true,
      },
    },
    methods: {
      editPost(post) {
        this.$emit('edit', post);
      },
      deletePost(post) {
        this.$emit('delete', post);
      },
      likePost(postId) {
        this.$emit('like', postId);
      },
      toggleComments(postId) {
        this.$emit('toggle-comments', postId);
      },
      postComment(postId) {
        this.$emit('post-comment', postId);
      },
      formatDate,
    },
  };
  </script>
  <style scoped>
  a {
    text-decoration: none !important;
  }
  </style>