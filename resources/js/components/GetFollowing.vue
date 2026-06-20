<template>
    <div v-if="isFollowingVisible">
      <h5 class="card-title">Following</h5>
      <ul>
        <li v-for="followedUser in following.data" :key="followedUser.id">{{ followedUser.name }}</li>
      </ul>
      <pagination :links="following.links" @page-changed="fetchFollowing"/>
    </div>
</template>

<script>
import axios from 'axios';
import { cmRoute } from '../utils/campaignManager';

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
        axios.get(`${cmRoute('followingIds')}?page=${page}`)
        .then(response => {
            this.following = response.data.following || { data: response.data.ids || [], links: [] };
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

