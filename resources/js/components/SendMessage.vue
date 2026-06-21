<template>
  <div class="internal-messenger">
    <aside class="conversation-pane">
      <div class="conversation-pane-header">
        <strong class="card-title">Conversations</strong>
      </div>
      <ul class="user conversation-list">
        <li v-for="c in conversations" :key="c.id" @click="selectConversation(c)" :class="{'active': activeConversation?.id === c.id}">
          <a href="javascript:void(0)">
            <img
              :src="firstParticipantPhoto(c) || routeUrl('noImage')"
              alt="UserImage"
              class="rounded-circle avatar-img me-3"
            />
            <span class="username text-right">
              {{ conversationName(c) }}
            </span>
          </a>
        </li>
      </ul>
    </aside>

    <section class="message-pane">
      <div class="message-card">
        <div class="active-contact message-header d-flex align-items-center">
          <strong class="card-title mb-0">
            {{ activeConversation ? conversationName(activeConversation) : 'Select a conversation' }}
          </strong>
          <span v-if="otherTyping" class="ms-2 text-muted" style="font-size: 0.85rem;">typing...</span>
        </div>
        <div class="message-body chat-msg" v-if="activeConversation">
          <ul class="chat">
            <li
              v-for="msg in messages"
              :key="msg.id"
              :class="isOwnMessage(msg) ? 'buyer clearfix bubble-right' : 'sender clearfix bubble-left'"
            >
              <div class="bubble" :class="isOwnMessage(msg) ? 'from-me' : 'from-them'">
                <p class="mb-1">{{ msg.body }}</p>
                <small class="text-muted">{{ formatDate(msg.created_at) }}</small>
              </div>
            </li>
          </ul>
        </div>
        <div class="message-body text-center text-muted d-flex align-items-center justify-content-center" v-else>
          Select a conversation to start messaging.
        </div>
        <div class="message-toolbar border-top">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small">Need to reach someone new?</span>
            <button class="btn btn-sm btn-primary text-white" @click="showComposer = true">
              Start New Conversation
            </button>
          </div>
          <div v-if="showComposer" class="mt-3">
            <new-conversation :routes="routes" @started="handleComposerStart" @cancel="showComposer = false" />
          </div>
        </div>
        <div class="message-composer" v-if="activeConversation">
          <div class="input-group">
            <textarea
              v-model="newMessage"
              class="form-control input-sm styled-textarea"
              placeholder="Type your message here..."
              @input="notifyTyping"
              @keyup.enter.exact.prevent="sendMessage"
            ></textarea>
            <span class="input-group-btn">
              <button class="btn btn-primary" @click="sendMessage"><i class="fa fa-paper-plane"></i></button>
            </span>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import axios from 'axios';
import dayjs from 'dayjs';
import NewConversation from './NewConversation.vue';
import { cmIntervals, cmMedia, cmRealtime, cmRoute } from '../utils/campaignManager';

export default {
  components: {
    NewConversation,
  },
  props: {
    darkThemeColor: {
      type: String,
      default: '#008751', // fallback in case it's not passed
    },
    profileData: {
      type: Object,
      required: true,
    },
    routes: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      conversations: [],
      activeConversation: null,
      messages: [],
      newMessage: '',
      otherTyping: false,
      typingTimeout: null,
      connectionReady: false,
      pollInterval: null,
      showComposer: false,
    };
  },
  computed: {
    headerParticipant() {
      if (!this.activeConversation) return null;
      return this.otherParticipant(this.activeConversation) || this.activeConversation.participants?.[0] || null;
    },
  },
  mounted() {
    this.fetchConversations();
    this.subscribeTyping();
    this.subscribeMessages();
    this.monitorConnection();
    this.startPollingFallback();
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
    handleComposerStart(payload) {
      const conv = payload?.conversation;
      const msg = payload?.message;
      this.showComposer = false;
      this.fetchConversations().then(async () => {
        const found = this.conversations.find((c) => c.id === (conv?.id));
        if (found) {
          this.activeConversation = found;
          this.messages = [];
          await this.fetchMessages(found.id);
        } else if (conv?.id) {
          this.activeConversation = conv;
          this.messages = [];
          if (msg) {
            this.upsertMessage(msg);
          } else {
            await this.fetchMessages(conv.id);
          }
        }
      });
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
      // Poll frequently if websocket isn't confirmed
      this.pollInterval = setInterval(() => {
        if (this.connectionReady) return;
        if (document.visibilityState === 'hidden') return;
        if (this.activeConversation) {
          this.fetchMessages(this.activeConversation.id);
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
    scrollToBottom() {
      this.$nextTick(() => {
        const container = this.$el.querySelector('.chat-msg');
        if (container) {
          container.scrollTop = container.scrollHeight;
        }
      });
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
          // If message belongs to active conversation, append and keep order
          if (this.activeConversation && this.activeConversation.id === convId) {
            this.messages.push(message);
            this.messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            this.scrollToBottom();
          }
          // If conversation is not active, refresh list so latest appears
          this.fetchConversations();
        });
    },
    async fetchConversations() {
      try {
        const response = await axios.get(this.routeUrl('conversations'));
        this.conversations = response.data?.data || response.data || [];
        if (this.conversations.length && !this.activeConversation) {
          this.selectConversation(this.conversations[0]);
        }
      } catch (error) {
        console.error('Unable to load conversations', error);
      }
    },
    async selectConversation(conv) {
      this.activeConversation = conv;
      await this.fetchMessages(conv.id);
      this.scrollToBottom();
    },
    async fetchMessages(conversationId) {
      try {
        const response = await axios.get(this.routeUrl('conversationMessages', { conversation: conversationId }));
        const data = response.data?.data || response.data || [];
        this.messages = [...data].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        this.scrollToBottom();
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
        }
      } catch (error) {
        console.error('Unable to send message', error);
      }
    },
    notifyTyping() {
      if (!this.activeConversation) return;
      axios.post(this.routeUrl('conversationTyping', { conversation: this.activeConversation.id })).catch(() => {});
    },
    conversationName(conv) {
      const other = this.otherParticipant(conv) || conv.participants?.[0];
      return other ? `${other.firstname ?? ''} ${other.lastname ?? other.username ?? ''}`.trim() : 'Conversation';
    },
    firstParticipantPhoto(conv) {
      const other = this.otherParticipant(conv) || conv.participants?.[0];
      return other?.photo ? `${this.routeUrl('memberImageBase')}/${other.photo}` : null;
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
        this.scrollToBottom();
      }
    },
  },
};
</script>

<style scoped>
.internal-messenger {
  display: flex;
  min-height: min(680px, calc(100vh - 230px));
  width: 100%;
  background: #fff;
  border: 0;
  border-radius: 0;
  overflow: hidden;
}

.conversation-pane {
  width: 320px;
  flex: 0 0 320px;
  background: #f8fafc;
  border-right: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.conversation-pane-header,
.message-header,
.message-toolbar,
.message-composer {
  padding: 14px 16px;
}

.conversation-pane-header {
  border-bottom: 1px solid #e5e7eb;
}

.message-pane {
  flex: 1;
  min-width: 0;
  display: flex;
}

.message-card {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.message-header {
  border-bottom: 1px solid #e5e7eb;
}

.message-body {
  flex: 1;
  min-height: 0;
  padding: 16px;
}

.message-toolbar,
.message-composer {
  background: #fff;
}

.user {
  list-style: none;
  padding: 0;
  margin: 0;
  overflow-y: auto;
  flex: 1;
}

.user li a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  text-decoration: none;
  color: #111;
}

.user li.active,
.user li a:hover {
  background: #f1f3f5;
}

.avatar-img {
  width: 36px;
  height: 36px;
  object-fit: cover;
}

.username {
  font-weight: 600;
  font-size: 0.95rem;
}

.active-contact {
  min-height: 56px;
}

.chat-msg {
  overflow-y: auto;
  max-height: none;
}

.bubble {
  max-width: 70%;
  padding: 10px 12px;
  border-radius: 12px;
  margin: 4px 0;
}

.bubble-left {
  display: flex;
  justify-content: flex-start;
}

.bubble-right {
  display: flex;
  justify-content: flex-end;
}

.from-me {
  background: #d1fae5;
  border: 1px solid #a7f3d0;
}

.from-them {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}

.recipient-results {
  max-height: 200px;
  overflow-y: auto;
  position: absolute;
  z-index: 5;
  width: 100%;
}

@media (max-width: 768px) {
  .internal-messenger {
    flex-direction: column;
    min-height: 560px;
  }

  .conversation-pane {
    width: 100%;
    flex-basis: auto;
    max-height: 260px;
    border-right: 0;
    border-bottom: 1px solid #e5e7eb;
  }
}
</style>


