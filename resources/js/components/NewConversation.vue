<template>
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0">Start New Conversation</h6>
      <button type="button" class="btn btn-sm btn-light" @click="$emit('cancel')">Close</button>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small text-muted mb-1">Recipient</label>
          <select class="form-select" v-model="selectedRecipientId">
            <option value="">Select recipient</option>
            <optgroup v-for="group in recipientGroups" :key="group.label" :label="group.label">
              <option v-for="opt in group.options" :key="opt.id" :value="opt.id">
                {{ opt.name }} ({{ opt.access_level }})
              </option>
            </optgroup>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small text-muted mb-1">Message</label>
          <textarea
            class="form-control"
            rows="3"
            v-model="composeMessage"
            placeholder="Type a message to start..."
          ></textarea>
        </div>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-end">
      <button class="btn btn-sm btn-light me-2" @click="$emit('cancel')">Cancel</button>
      <button class="btn btn-sm btn-primary text-white" :disabled="!selectedRecipientId || !composeMessage.trim()" @click="start">
        Start Conversation
      </button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { cmRoute } from '../utils/campaignManager';

export default {
  name: 'NewConversation',
  emits: ['started', 'cancel'],
  props: {
    routes: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      recipientGroups: [],
      selectedRecipientId: '',
      composeMessage: '',
    };
  },
  mounted() {
    this.loadRecipients();
  },
  methods: {
    route(name) {
      const fallbackMap = {
        recipients: 'messagesRecipients',
        start: 'messagesStart',
      };
      return this.routes?.[name] || cmRoute(fallbackMap[name] || name);
    },
    async loadRecipients() {
      try {
        const res = await axios.get(this.route('recipients'));
        this.recipientGroups = res.data?.data || res.data || [];
      } catch (e) {
        this.recipientGroups = [];
      }
    },
    selectedRecipient() {
      if (!this.selectedRecipientId) return null;
      for (const group of this.recipientGroups) {
        const found = (group.options || []).find((o) => String(o.id) === String(this.selectedRecipientId));
        if (found) return found;
      }
      return null;
    },
    async start() {
      const target = this.selectedRecipient();
      if (!target || !this.composeMessage.trim()) return;
      try {
        const response = await axios.post(this.route('start'), {
          recipient_id: target.id,
          message: this.composeMessage.trim(),
        });
        this.composeMessage = '';
        this.selectedRecipientId = '';
        this.$emit('started', {
          conversation: response.data?.conversation || response.data,
          message: response.data?.message,
        });
      } catch (e) {
        console.error('Unable to start conversation', e);
      }
    },
  },
};
</script>
