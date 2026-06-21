<template>
  <div class="activity-shell" :class="modeClass">
    <button
      class="micro-icon position-relative"
      :style="bellStyle"
      type="button"
      @click="togglePanel"
      :title="titleText"
    >
      <i class="fas fa-bell"></i>
      <span
        v-if="unreadCount > 0"
        class="micro-badge bg-danger text-white"
        :class="{ 'badge-mobile': mode === 'mobile' }"
      >{{ unreadCount }}</span>
    </button>

    <div v-if="open" class="activity-dropdown card shadow-sm" :class="modeClass">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Notifications</h6>
        <button class="btn btn-link btn-sm" @click="markAll">Mark all as read</button>
      </div>
      <div class="card-body p-0" style="max-height: 360px; overflow-y: auto;">
        <div v-if="loading" class="p-3 text-center small text-muted">Loading…</div>
        <div v-else-if="!notifications.length" class="p-3 text-center small text-muted">
          No notifications yet.
        </div>
        <ul class="list-unstyled mb-0">
          <li
            v-for="item in notifications"
            :key="item.id"
            class="d-flex align-items-start px-3 py-2 border-bottom"
            :class="{ 'bg-light': !item.read_at }"
            @click="openItem(item)"
          >
            <img
              :src="item.actor?.avatar || noImageUrl"
              alt="avatar"
              class="rounded-circle me-2"
              width="36"
              height="36"
              style="object-fit: cover;"
            />
            <div class="flex-fill">
              <div class="fw-semibold">{{ item.actor?.name || 'Someone' }}</div>
              <div class="small text-muted text-truncate">{{ item.data?.message || 'New activity' }}</div>
              <small class="text-muted">{{ formatTime(item.created_at) }}</small>
            </div>
            <span v-if="!item.read_at" class="badge bg-primary ms-2">New</span>
          </li>
        </ul>
      </div>
      <div class="card-footer text-center">
        <a :href="notificationsUrl" class="small">View all</a>
      </div>
    </div>

    <!-- Flash toast for online users -->
    <div v-if="flash" class="activity-flash shadow">
      <div class="fw-semibold">{{ flash.actor }}</div>
      <div class="small text-muted">{{ flash.message }}</div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { cmIntervals, cmMedia, cmRealtime, cmRoute } from '../utils/campaignManager';

export default {
  name: 'ActivityBell',
  props: {
    mode: {
      type: String,
      default: 'desktop',
    },
    userScope: {
      type: Object,
      default: () => ({}),
    },
    fetchUrl: {
      type: String,
      required: true,
    },
    countUrl: {
      type: String,
      required: true,
    },
    markUrl: {
      type: String,
      required: true,
    },
    markAllUrl: {
      type: String,
      required: true,
    },
    bellColor: {
      type: String,
      default: '#008751',
    },
  },
  data() {
    return {
      unreadCount: 0,
      notifications: [],
      loading: false,
      open: false,
      poller: null,
      flash: null,
    };
  },
  computed: {
    modeClass() {
      return this.mode === 'mobile' ? 'activity-mobile' : 'activity-desktop';
    },
    bellStyle() {
      if (this.mode === 'mobile') {
        return {
          background: this.bellColor,
          width: '35px',
          height: '35px',
          color: '#ffffff',
        };
      }
      return {
        background: '#ffffff',
        color: this.bellColor,
      };
    },
    titleText() {
      return `Notifications${this.unreadCount ? ` (${this.unreadCount})` : ''}`;
    },
    noImageUrl() {
      return cmMedia('noImage');
    },
    notificationsUrl() {
      return `${cmRoute('timeline')}#notifications`;
    },
  },
  mounted() {
    this.fetchCount();
    this.fetchFeed();
    this.poller = setInterval(() => {
      if (document.visibilityState === 'hidden') return;
      this.fetchCount();
    }, cmIntervals().notifications);
    this.bindEcho();
    document.addEventListener('click', this.handleOutside);
  },
  beforeUnmount() {
    if (this.poller) clearInterval(this.poller);
    document.removeEventListener('click', this.handleOutside);
  },
  methods: {
    handleOutside(e) {
      if (!this.$el.contains(e.target)) {
        this.open = false;
      }
    },
    togglePanel() {
      this.open = !this.open;
      if (this.open) {
        this.fetchFeed();
        this.markAll();
      }
    },
    async fetchCount() {
      try {
        const res = await axios.get(this.countUrl);
        this.unreadCount = res.data?.unread || 0;
      } catch (e) {
        // ignore
      }
    },
    async fetchFeed() {
      this.loading = true;
      try {
        const res = await axios.get(this.fetchUrl);
        const payload = res.data;
        if (Array.isArray(payload)) {
          this.notifications = payload;
        } else if (Array.isArray(payload?.data)) {
          this.notifications = payload.data;
        } else if (Array.isArray(payload?.data?.data)) {
          this.notifications = payload.data.data;
        } else {
          this.notifications = [];
        }
      } catch (e) {
        this.notifications = [];
      } finally {
        this.loading = false;
      }
    },
    async markAll() {
      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        await axios.post(this.markAllUrl, {}, { headers: { 'X-CSRF-TOKEN': token } });
        this.unreadCount = 0;
        this.notifications = this.notifications.map((n) => ({ ...n, read_at: new Date().toISOString() }));
      } catch (e) {
        // ignore
      }
    },
    async openItem(item) {
      if (!item?.id) return;
      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        await axios.post(this.markUrl, { id: item.id }, { headers: { 'X-CSRF-TOKEN': token } });
      } catch (e) {
        // ignore
      }
      if (this.unreadCount > 0) this.unreadCount -= 1;
      const targetUrl = item.data?.url || (item.data?.post_id ? `${cmRoute('timeline')}#post-${item.data.post_id}` : null);
      if (targetUrl) window.location.href = targetUrl;
    },
    bindEcho() {
      if (!cmRealtime().realtimeAvailable) return;
      if (!window.Echo) return;
      const scopes = [];
      const s = this.userScope || {};
      scopes.push('activity-public');
      if (s.region_id) scopes.push(`activity-region-${s.region_id}`);
      if (s.state_id) scopes.push(`activity-state-${s.state_id}`);
      if (s.senatorial_district_id) scopes.push(`activity-senatorial_district-${s.senatorial_district_id}`);
      if (s.federal_constituency_id) scopes.push(`activity-federal_constituency-${s.federal_constituency_id}`);
      if (s.lga_id) scopes.push(`activity-lga-${s.lga_id}`);
      if (s.ward_id) scopes.push(`activity-ward-${s.ward_id}`);
      if (s.pu_id) scopes.push(`activity-pu-${s.pu_id}`);
      (s.support_group_ids || []).forEach((id) => {
        if (id) scopes.push(`activity-support_group-${id}`);
      });

      scopes.forEach((ch) => {
        try {
          window.Echo.private(ch).listen('.NewActivityNotification', () => {
            this.unreadCount += 1;
            this.fetchFeed();
            this.showFlash();
          });
        } catch (e) {
          // ignore channel binding errors
        }
      });
    },
    showFlash() {
      const latest = this.notifications[0];
      if (!latest) return;
      this.flash = {
        message: latest.data?.message || 'New notification',
        actor: latest.actor?.name || 'Someone',
      };
      setTimeout(() => {
        this.flash = null;
      }, 4000);
    },
    formatTime(ts) {
        if (!ts) return '';
        const date = new Date(ts);
        return date.toLocaleString();
    },
  },
};
</script>

<style scoped>
.micro-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #111;
  position: relative;
  border: 1px solid #e5e7eb;
}

.micro-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #facc15;
  color: #000;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 999px;
}

.badge-mobile {
  top: -6px;
  right: -6px;
}

.activity-dropdown {
  position: absolute;
  right: 0;
  top: 42px;
  width: 320px;
  z-index: 2200;
}

.activity-mobile .activity-dropdown {
  position: fixed;
  right: 10px;
  left: 10px;
  top: 70px;
  width: auto;
}

.activity-flash {
  position: fixed;
  bottom: 16px;
  right: 16px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px 12px;
  z-index: 4000;
  max-width: 260px;
}
</style>
