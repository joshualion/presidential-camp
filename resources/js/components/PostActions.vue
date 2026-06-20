<template>
  <div v-if="canShowActions">
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
          <a v-if="canEdit" class="dropdown-item" href="#" @click.prevent="$emit('edit-request', post)">Edit Post</a>
        </li>
        <li>
          <a v-if="canDelete" class="dropdown-item text-danger" href="#" @click.prevent="$emit('delete-request', post)">
            {{ isOwner ? 'Delete Post' : 'Remove as Moderator' }}
          </a>
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
  computed: {
    isOwner() {
      return !!this.post.user?.id && !!this.profileData?.id && Number(this.post.user.id) === Number(this.profileData.id);
    },
    canEdit() {
      return this.isOwner || this.post.can_edit === true;
    },
    canDelete() {
      return this.isOwner || this.post.can_delete === true;
    },
    canShowActions() {
      return this.canEdit || this.canDelete || this.post.can_moderate === true;
    },
  },
};
</script>
