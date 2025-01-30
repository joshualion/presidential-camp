<template>
    <div>
      <p>Are you sure you want to delete this post?</p>
      <button class="btn btn-danger btn-sm" @click="confirmDelete">Delete</button>
      <button class="btn btn-secondary btn-sm ms-2" @click="$emit('cancel')">Cancel</button>
    </div>
  </template>
  
  <script>
  export default {
    props: {
      post: {
        type: Object,
        required: true,
      },
    },
    methods: {
      async confirmDelete() {
        try {
          await this.$emit("deletePost", this.post);
        } catch (error) {
          console.error("Error deleting post:", error);
        }
      },
    },
  };
  </script>
  