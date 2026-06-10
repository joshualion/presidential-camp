<template>
  <div v-if="post.user?.id && post.user.id === profileData.id">
    <div class="dropdown">
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
          <a class="dropdown-item" href="#" @click.prevent="$emit('edit-request', post)">Edit Post</a>
        </li>
        <li>
          <a class="dropdown-item text-danger" href="#" @click.prevent="$emit('delete-request', post)">Delete Post</a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    post: {
      type: Object,
      required: true,
    },
    profileData: {
      type: Object,
      default: () => ({}),
    },
  },
};
</script>
