<template>
    <div>
      <form @submit.prevent="submitEdit">
        <textarea
          v-model="content"
          class="form-control mb-3"
          rows="3"
          placeholder="Edit your post..."
        ></textarea>
        <button type="submit" class="btn btn-primary btn-sm">Update</button>
        <button type="button" class="btn btn-secondary btn-sm ms-2" @click="$emit('cancel')">Cancel</button>
      </form>
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
    data() {
      return {
        content: this.post.content, // Initialize with the current content
      };
    },
    methods: {
      async submitEdit() {
        try {
          await this.$emit("updatePost", { ...this.post, content: this.content });
        } catch (error) {
          console.error("Error updating post:", error);
        }
      },
    },
  };
  </script>
  