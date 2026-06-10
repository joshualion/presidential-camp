<template>
  <div class="row">
    <div class="col-md-3 myUser card">
      <div class="card-header mb-3 text-left myrow">
        <strong class="card-title">Conversations</strong>
      </div>
      <ul class="user">
        <li v-for="c in conversations" :key="c.id" @click="selectConversation(c)" :class="{'active': activeConversation?.id === c.id}">
          <a href="javascript:void(0)">
            <img
              :src="firstParticipantPhoto(c) || '/uploads/no_image.jpg'"
              alt="UserImage"
              class="rounded-circle avatar-img me-3"
            />
            <span class="username text-right">
              {{ conversationName(c) }}
            </span>
          </a>
        </li>
      </ul>
    </div>

    <div class="col-md-9">
      <div class="card card-primary">
        <div class="active-contact card-header text-left myrow d-flex align-items-center">
          <strong class="card-title mb-0">
            {{ activeConversation ? conversationName(activeConversation) : 'Select a conversation' }}
          </strong>
          <span v-if="otherTyping" class="ms-2 text-muted" style="font-size: 0.85rem;">typing...</span>
        </div>
        <div class="card-body chat-msg" v-if="activeConversation">
          <ul class="chat">
            <li
              v-for="msg in messages"
              :key="msg.id"
              :class="msg.sender_id === profileData.id ? 'buyer clearfix bubble-right' : 'sender clearfix bubble-left'"
            >
              <div class="bubble" :class="msg.sender_id === profileData.id ? 'from-me' : 'from-them'">
                <p class="mb-1">{{ msg.body }}</p>
                <small class="text-muted">{{ formatDate(msg.created_at) }}</small>
              </div>
            </li>
          </ul>
        </div>
        <div class="card-body text-center text-muted" v-else>
          Select a conversation to start messaging.
        </div>
        <div class="card-footer border-top">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small">Need to reach someone new?</span>
            <button class="btn btn-sm btn-primary text-white" @click="showComposer = true">
              Start New Conversation
            </button>
          </div>
          <div v-if="showComposer" class="mt-3">
            <new-conversation @started="handleComposerStart" @cancel="showComposer = false" />
          </div>
        </div>
        <div class="card-footer" v-if="activeConversation">
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
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import dayjs from 'dayjs';
import NewConversation from './NewConversation.vue';

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
      return (this.activeConversation.participants || []).find((p) => p.id !== this.profileData.id) || this.activeConversation.participants?.[0] || null;
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
        if (this.activeConversation) {
          this.fetchMessages(this.activeConversation.id);
        } else {
          this.fetchConversations();
        }
      }, 1000);
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
        const response = await axios.get('/messages/conversations');
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
        const response = await axios.get(`/messages/conversations/${conversationId}/messages`);
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
          `/messages/conversations/${this.activeConversation.id}/messages`,
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
      axios.post(`/messages/conversations/${this.activeConversation.id}/typing`).catch(() => {});
    },
    conversationName(conv) {
      const other = (conv.participants || []).find((p) => p.id !== this.profileData.id) || conv.participants?.[0];
      return other ? `${other.firstname ?? ''} ${other.lastname ?? other.username ?? ''}`.trim() : 'Conversation';
    },
    firstParticipantPhoto(conv) {
      const other = (conv.participants || []).find((p) => p.id !== this.profileData.id) || conv.participants?.[0];
      return other?.photo ? `/uploads/member_images/${other.photo}` : null;
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
.user {
  list-style: none;
  padding: 0;
  margin: 0;
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
</style>


