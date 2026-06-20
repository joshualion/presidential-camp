<template>
  <div>
    <h5>Followers</h5>
    <ul v-if="followers.length > 0">
      <li v-for="follower in followers" :key="follower.id">{{ follower.name }}</li>
    </ul>
    <p v-else>No followers yet.</p>
  </div>
</template>

<script>
import axios from "axios";
import { cmRoute } from '../utils/campaignManager';

export default {
  props: ["userId"],
  data() {
    return {
      followers: []
    };
  },
  watch: {
    userId: {
      immediate: true,
      handler(newUserId) {
        this.fetchFollowers(newUserId);
      }
    }
  },
  methods: {
    async fetchFollowers(userId) {
      try {
        const response = await axios.get(cmRoute('userFollowers', { user: userId }));
        this.followers = response.data?.followers?.data || response.data?.followers || [];
      } catch (error) {
        console.error("Error fetching followers:", error);
      }
    }
  }
};
</script>
