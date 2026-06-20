<template>
  <div>
    <div class="messenger-launcher" @click="togglePanel" :style="{ background: themeColor }">
      <i class="fas fa-comment-dots"></i>
      <span v-if="totalUnread > 0" class="badge-unread">{{ totalUnread }}</span>
    </div>

    <div v-if="open" class="messenger-panel card shadow">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Messages</strong>
        <button class="btn btn-sm btn-light" @click="togglePanel"><i class="fas fa-times"></i></button>
      </div>
      <div class="card-body messenger-body">
        <div v-if="!activeConversation" class="conversation-list single-column">
          <div
            v-for="c in conversations"
            :key="c.id"
            class="conversation-item d-flex align-items-center"
            :class="{ active: activeConversation?.id === c.id }"
            @click="selectConversation(c)"
          >
            <div class="avatar placeholder me-1">
              <img v-if="firstParticipantPhoto(c)" :src="firstParticipantPhoto(c)" alt="user" class="avatar-img">
              <i v-else class="fas fa-user"></i>
            </div>
            <div class="flex-grow-1">
              <div class="fw-semibold conversation-name">{{ conversationName(c) }}</div>
              <div class="text-muted small">{{ latestMessagePreview(c) }}</div>
            </div>
            <span v-if="c.unread_count > 0" class="badge-unread">{{ c.unread_count }}</span>
          </div>
        </div>

        <div v-else class="conversation-thread single-column">
          <div class="thread-header d-flex align-items-center justify-content-between">
            <div class="fw-semibold">{{ conversationName(activeConversation) }}</div>
            <div class="d-flex align-items-center gap-2">
              <span v-if="otherTyping" class="text-muted small">typing...</span>
              <button class="btn btn-sm btn-outline-secondary" @click="backToList">
                <i class="fas fa-arrow-left"></i>
              </button>
            </div>
          </div>
          <div class="thread-messages" ref="thread">
            <div
              v-for="msg in messages"
              :key="msg.id"
              :class="isOwnMessage(msg) ? 'bubble from-me' : 'bubble from-them'"
            >
              <div class="mb-1">{{ msg.body }}</div>
              <small class="text-muted">{{ formatDate(msg.created_at) }}</small>
            </div>
          </div>
          <div class="thread-input">
            <textarea
              v-model="newMessage"
              class="form-control"
              rows="2"
              placeholder="Type a message..."
              @input="notifyTyping"
              @keyup.enter.exact.prevent="sendMessage"
            ></textarea>
            <button class="btn btn-primary btn-sm text-white mt-2" @click="sendMessage">Send</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import dayjs from 'dayjs';
import { cmIntervals, cmMedia, cmRealtime, cmRoute } from '../utils/campaignManager';

export default {
  props: {
    profileData: {
      type: Object,
      required: true,
    },
    themeColor: {
      type: String,
      default: '#008751',
    },
    routes: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      open: false,
      conversations: [],
      activeConversation: null,
      messages: [],
      newMessage: '',
      otherTyping: false,
      typingTimeout: null,
      connectionReady: false,
      pollInterval: null,
      pendingOpenUserId: null,
    };
  },
  computed: {
    totalUnread() {
      return this.conversations.reduce((sum, c) => sum + (c.unread_count || 0), 0);
    },
  },
  mounted() {
    this.open = localStorage.getItem('messenger_widget_open') === '1';
    this.fetchConversations();
    this.subscribeTyping();
    this.subscribeMessages();
    this.monitorConnection();
    this.startPollingFallback();
    window.addEventListener('open-messenger', this.handleOpen);
  },
  beforeUnmount() {
    window.removeEventListener('open-messenger', this.handleOpen);
  },
  methods: {
    routeUrl(name, params = {}) {
      const fallbackMap = {
        conversations: 'messagesConversations',
        conversationMessages: 'messagesConversationMessages',
        conversationSend: 'messagesConversationSend',
        conversationTyping: 'messagesConversationTyping',
        conversationRead: 'messagesConversationRead',
        recipients: 'messagesRecipients',
        start: 'messagesStart',
        ensure: 'messagesEnsure',
      };
      if (name === 'noImage') return this.routes?.noImage || cmMedia('noImage');
      if (name === 'memberImageBase') return this.routes?.memberImageBase || cmMedia('memberImages');
      let url = this.routes?.[name] || cmRoute(fallbackMap[name] || name, params);
      Object.entries(params).forEach(([key, value]) => {
        url = url.replace(`__${key.toUpperCase()}__`, encodeURIComponent(value));
      });
      return url;
    },
    isOwnMessage(message) {
      return String(message?.sender_id) === String(this.profileData?.id);
    },
    otherParticipant(conv) {
      return (conv.participants || []).find((p) => String(p.id) !== String(this.profileData.id)) || null;
    },
    setOpen(state) {
      this.open = state;
      localStorage.setItem('messenger_widget_open', state ? '1' : '0');
    },
    upsertConversation(conv) {
      if (!conv) return;
      const idx = this.conversations.findIndex((c) => c.id === conv.id);
      if (idx >= 0) {
        this.conversations.splice(idx, 1, conv);
      } else {
        this.conversations.unshift(conv);
      }
    },
    backToList() {
      this.activeConversation = null;
      this.messages = [];
    },
    async handleOpen(event) {
      const targetUserId = event?.detail?.userId;
      this.pendingOpenUserId = targetUserId || null;
      this.setOpen(true);
      if (targetUserId) {
        await this.ensureConversationWithUser(targetUserId);
        this.pendingOpenUserId = null;
      } else {
        this.fetchConversations();
      }
    },
    togglePanel() {
      this.setOpen(!this.open);
      if (this.open) {
        this.fetchConversations();
      }
    },
    async ensureConversationWithUser(userId) {
      if (!userId) return;
      await this.fetchConversations(true);
      const existing = this.conversations.find((c) =>
        (c.participants || []).some((p) => String(p.id) === String(userId))
      );
      if (existing) {
        this.selectConversation(existing);
        return;
      }
      try {
        const response = await axios.post(this.routeUrl('ensure'), { recipient_id: userId });
        const conv = response.data?.conversation || response.data;
        if (conv) {
          this.upsertConversation(conv);
          this.selectConversation(conv);
        }
      } catch (e) {
        console.error('Unable to open conversation', e);
      }
    },
    monitorConnection() {
      if (!cmRealtime().realtimeAvailable) return;
      if (!window.Echo || !window.Echo.connector) return;
      const conn = window.Echo.connector?.pusher?.connection || window.Echo.connector?.connection;
      if (conn?.bind) {
        conn.bind('connected', () => {
          this.connectionReady = true;
          this.stopPollingFallback();
        });
        conn.bind('error', () => {
          this.connectionReady = false;
          this.startPollingFallback();
        });
      }
    },
    startPollingFallback() {
      if (this.pollInterval) return;
      this.pollInterval = setInterval(() => {
        if (this.connectionReady) return;
        if (document.visibilityState === 'hidden') return;
        if (this.activeConversation) {
          this.fetchMessages(this.activeConversation.id, false);
        } else {
          this.fetchConversations();
        }
      }, this.activeConversation ? cmIntervals().message_thread : cmIntervals().conversation_list);
    },
    stopPollingFallback() {
      if (this.pollInterval) {
        clearInterval(this.pollInterval);
        this.pollInterval = null;
      }
    },
    subscribeTyping() {
      if (!cmRealtime().realtimeAvailable) return;
      if (!window.Echo || !this.profileData?.id) return;
      window.Echo.private(`messages.user.${this.profileData.id}`)
        .listen('MessageTyping', (payload) => {
          if (!this.activeConversation || payload.conversation_id !== this.activeConversation.id) return;
          this.otherTyping = true;
          clearTimeout(this.typingTimeout);
          this.typingTimeout = setTimeout(() => {
            this.otherTyping = false;
          }, 1500);
        });
    },
    subscribeMessages() {
      if (!cmRealtime().realtimeAvailable) return;
      if (!window.Echo || !this.profileData?.id) return;
      window.Echo.private(`messages.user.${this.profileData.id}`)
        .listen('MessageSent', (payload) => {
          const { conversation_id: convId, message } = payload || {};
          if (!convId || !message) return;
          if (!this.open) {
            this.setOpen(true);
          }
          if (this.activeConversation && this.activeConversation.id === convId) {
            this.upsertMessage(message);
            this.scrollToBottom();
            this.markRead(convId);
          }
          this.fetchConversations().then(() => {
            if (!this.activeConversation || this.activeConversation.id !== convId) {
              const target = this.conversations.find((c) => c.id === convId);
              if (target) {
                this.selectConversation(target);
              }
            }
          });
        });
    },
    async fetchConversations(skipSelect = false) {
      try {
        const response = await axios.get(this.routeUrl('conversations'));
        this.conversations = response.data?.data || response.data || [];
        if (!skipSelect && !this.activeConversation && this.conversations.length) {
          this.selectConversation(this.conversations[0]);
        }
      } catch (error) {
        console.error('Unable to load conversations', error);
      }
    },
    async selectConversation(conv) {
      this.activeConversation = conv;
      await this.fetchMessages(conv.id, true);
      this.markRead(conv.id);
      this.scrollToBottom();
    },
    async fetchMessages(conversationId, scroll = false) {
      try {
        const response = await axios.get(this.routeUrl('conversationMessages', { conversation: conversationId }));
        const data = response.data?.data || response.data || [];
        this.messages = [...data].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        if (scroll) this.scrollToBottom();
      } catch (error) {
        console.error('Unable to load messages', error);
      }
    },
    async sendMessage() {
      if (!this.newMessage.trim() || !this.activeConversation) return;
      const body = this.newMessage.trim();
      this.newMessage = '';
      try {
        const response = await axios.post(
          this.routeUrl('conversationSend', { conversation: this.activeConversation.id }),
          { message: body },
        );
        const msg = response.data?.message || response.data;
        if (msg) {
          this.upsertMessage(msg);
          this.scrollToBottom();
          this.markRead(this.activeConversation.id);
        }
      } catch (error) {
        console.error('Unable to send message', error);
      }
    },
    notifyTyping() {
      if (!this.activeConversation) return;
      axios.post(this.routeUrl('conversationTyping', { conversation: this.activeConversation.id })).catch(() => {});
    },
    async markRead(conversationId) {
      try {
        await axios.post(this.routeUrl('conversationRead', { conversation: conversationId }));
        // refresh conversation unread counts
        this.fetchConversations();
      } catch (e) {
        // ignore
      }
    },
    conversationName(conv) {
      const other = this.otherParticipant(conv) || conv.participants?.[0];
      return other ? `${other.firstname ?? ''} ${other.lastname ?? other.username ?? ''}`.trim() : 'Conversation';
    },
    firstParticipantPhoto(conv) {
      const other = this.otherParticipant(conv) || conv.participants?.[0];
      return other?.photo ? `${this.routeUrl('memberImageBase')}/${other.photo}` : null;
    },
    latestMessagePreview(conv) {
      const msg = conv.messages?.[0] || conv.messages?.data?.[0];
      return msg?.body ? msg.body.slice(0, 40) : '';
    },
    formatDate(date) {
      return dayjs(date).format('MMM D, h:mm A');
    },
    upsertMessage(msg) {
      if (!msg || !msg.id) return;
      const exists = this.messages.some((m) => m.id === msg.id);
      if (!exists) {
        this.messages.push(msg);
        this.messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
      }
    },
    scrollToBottom() {
      this.$nextTick(() => {
        const el = this.$refs.thread;
        if (el) {
          el.scrollTop = el.scrollHeight;
        }
      });
    },
  },
};
</script>

<style scoped>
.messenger-launcher {
  position: fixed;
  right: 18px;
  bottom: 18px;
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #0f172a;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 16px rgba(0,0,0,0.24);
  cursor: pointer;
  z-index: 2000;
}
.badge-unread {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #ef4444;
  color: #fff;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 999px;
}
.messenger-panel {
  position: fixed;
  right: 18px;
  bottom: 80px;
  width: 360px;
  height: 520px;
  display: flex;
  flex-direction: column;
  z-index: 1999;
}
.messenger-body {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
  padding: 10px;
}
.conversation-list {
  border-right: 1px solid #e5e7eb;
  overflow-y: auto;
  max-height: 100%;
}
.conversation-list.single-column {
  border-right: none;
  width: 100%;
  height: 100%;
  min-height: 0;
  flex: 1;
}
.conversation-thread.single-column {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
.single-column {
  width: 100%;
}
.conversation-item {
  padding: 8px;
  cursor: pointer;
  border-radius: 8px;
}
.conversation-item.active,
.conversation-item:hover {
  background: #f1f5f9;
}
.conversation-name {
  font-size: 0.95rem;
}
.conversation-thread {
  display: flex;
  flex-direction: column;
}
.thread-header {
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 6px;
  margin-bottom: 6px;
  min-height: 36px;
}
.thread-messages {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-right: 4px;
}
.thread-empty {
  flex: 1;
  min-height: 200px;
  padding: 16px;
  text-align: center;
}
.bubble {
  max-width: 80%;
  padding: 10px 12px;
  border-radius: 12px;
}
.from-me {
  align-self: flex-end;
  background: #d1fae5;
  border: 1px solid #a7f3d0;
}
.from-them {
  align-self: flex-start;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}
.thread-input {
  margin-top: 6px;
}
.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #f3f4f6;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.avatar-img {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
}
</style>
