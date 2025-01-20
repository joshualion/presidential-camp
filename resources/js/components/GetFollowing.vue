<template>
    <div v-if="isFollowingVisible">
      <h3>Following</h3>
      <ul>
        <li v-for="followedUser in following.data" :key="followedUser.id">{{ followedUser.name }}</li>
      </ul>
      <pagination :links="following.links" @page-changed="fetchFollowing"/>
    </div>
</template>

<script>
export default {
  data() {
    return {      
      isFollowingVisible: false,  // Add the missing property
      following: {
        data: [],
        links: []
      }
    };
  },
  methods: {
    fetchFollowing(page = 1) {
        axios.get('/user/following?page=' + page)
        .then(response => {
            this.following = response.data.following;
        });
    },
    showFollowing() {
      this.isFollowingVisible = true;
      this.fetchFollowing();
    }
  },
  mounted() {
    this.$root.$on('show-following', this.showFollowing);
  }
};
</script>
