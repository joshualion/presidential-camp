<template>
    <div v-if="isFollowersVisible">
      <h5 class="card-title">Followers</h5>
      <ul>
        <li v-for="follower in followers.data" :key="follower.id">{{ follower.firstname }} {{ follower.lastname }}</li>
      </ul>
      <pagination :links="followers.links" @page-changed="fetchFollowers" />
    </div>
  </template>
  
  <script>
  export default {
    data() {
      return {
        isFollowersVisible: false,  // Add the missing property
        followers: {
          data: [],
          links: []
        }
      };
    },
    methods: {
      fetchFollowers(page = 1) {
        axios
          .get('/user/followers?page=' + page) // Make sure the URL is correct for your backend route
          .then((response) => {
            this.followers = response.data.followers;
          })
          .catch((error) => {
            console.error('Error fetching followers:', error);
          });
      },
      showFollowers() {
        console.log('showFollowers called');
        this.isFollowersVisible = true;
        console.log('isFollowersVisible:', this.isFollowersVisible);
        this.fetchFollowers();
      }
    },
    mounted() {
      this.$root.$on('show-followers', this.showFollowers); // Listen for the event from the parent component
    }
  };
  </script>
  