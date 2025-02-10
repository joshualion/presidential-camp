<template>
    <div v-if="post.user?.id && post.user.id === profileData.id">
      <div v-if="editingPost && editingPost.id === post.id">
        <EditPost :post="post" @updatePost="updatePost" @cancel="editingPost = null" />
      </div>
      <div v-else-if="deletingPost && deletingPost.id === post.id">
        <DeletePost :post="post" @deletePost="confirmDelete" @cancel="deletingPost = null" />
      </div>
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
  </template>
  
  <script>
  import EditPost from './EditPost.vue';
  import DeletePost from './DeletePost.vue';
  
  export default {
    components: {
      EditPost,
      DeletePost,
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
    data() {
      return {
        editingPost: null,
        deletingPost: null,
      };
    },
    methods: {
      editPost(post) {
        this.editingPost = post;
      },
      deletePost(post) {
        this.deletingPost = post;
      },
      updatePost(updatedPost) {
        this.$emit('update-post', updatedPost);
      },
      confirmDelete(post) {
        this.$emit('delete-post', post);
      },
    },
  };
  </script>