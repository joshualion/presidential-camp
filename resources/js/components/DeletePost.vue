<template>
  <div>
    <p>Are you sure you want to delete this post?</p>
    <button class="btn btn-danger btn-sm" @click="$emit('deletePost', post)">Delete</button>
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
};
</script>
