<template>
  <div :class="['search-wrapper', modeClass]" ref="wrapper">
    <!-- Desktop / Inline Search -->
    <div
      v-if="mode !== 'mobile'"
      class="search-input-shell d-flex align-items-center"
      @click="openDropdown"
    >
      <i class="fas fa-search me-2 text-muted"></i>
      <input
        ref="input"
        v-model="query"
        type="text"
        class="form-control border-0 bg-transparent p-0"
        :placeholder="placeholder"
        @input="handleInput"
        @focus="openDropdown"
        @keydown="handleKeydown"
      />
      <button class="btn btn-sm text-muted ms-2" type="button" @click="submitSearch">
        Search
      </button>
    </div>

    <!-- Mobile trigger / inline -->
    <div
      v-else
      class="search-input-shell d-flex align-items-center"
      @click="openOverlay"
    >
      <i class="fas fa-search me-2 text-muted"></i>
      <input
        ref="input"
        v-model="query"
        type="text"
        class="form-control border-0 bg-transparent p-0"
        :placeholder="placeholder"
        readonly
      />
    </div>

    <!-- Desktop dropdown -->
    <div
      v-if="mode !== 'mobile'"
      class="search-dropdown card shadow-sm"
      v-show="showDropdown"
    >
      <div class="card-body p-0">
        <div v-if="loading" class="py-3 text-center small text-muted">Searching…</div>
        <div v-else>
          <section v-if="recentList.length" class="dropdown-section">
            <div class="section-title">Recent</div>
            <ul class="list-unstyled mb-0">
              <li
                v-for="(term, idx) in recentList"
                :key="`recent-${idx}`"
                :class="['dropdown-item', isActive(idx, 'recent') ? 'active' : '']"
                @mouseenter="setActive(idx, 'recent')"
                @mousedown.prevent="selectRecent(term)"
              >
                <i class="fas fa-history me-2 text-muted"></i>{{ term }}
              </li>
            </ul>
          </section>

          <section v-if="hasAnyResults" class="dropdown-section">
            <div v-if="suggestions.people.length" class="section-title">People</div>
            <ul class="list-unstyled mb-0">
              <li
                v-for="(person, idx) in suggestions.people"
                :key="`person-${person.id || idx}`"
                :class="['dropdown-item', isActive(idx, 'people') ? 'active' : '']"
                @mouseenter="setActive(idx, 'people')"
                @mousedown.prevent="goTo(person.url)"
              >
                <img
                  :src="person.avatar || noImageUrl"
                  alt="profile"
                  class="avatar me-2"
                />
                <div class="flex-fill">
                  <div class="fw-semibold">{{ person.name }}</div>
                  <small class="text-muted">{{ person.subtitle }}</small>
                </div>
              </li>
            </ul>

            <div v-if="suggestions.posts.length" class="section-title mt-2">Posts</div>
            <ul class="list-unstyled mb-0">
              <li
                v-for="(post, idx) in suggestions.posts"
                :key="`post-${post.id || idx}`"
                :class="['dropdown-item', isActive(idx, 'posts') ? 'active' : '']"
                @mouseenter="setActive(idx, 'posts')"
                @mousedown.prevent="goTo(post.url)"
              >
                <i class="fas fa-sticky-note me-2 text-primary"></i>
                <div class="flex-fill">
                  <div class="fw-semibold text-truncate">{{ post.title || post.snippet }}</div>
                  <small class="text-muted text-truncate">{{ post.snippet }}</small>
                </div>
              </li>
            </ul>
          </section>

          <div v-if="!loading && !hasAnyResults && !recentList.length" class="py-3 text-center small text-muted">
            No matches yet. Try another term.
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center border-top px-3 py-2 bg-light">
          <small class="text-muted">Press Enter to see all results</small>
          <button class="btn btn-sm btn-primary text-white" @mousedown.prevent="submitSearch">See all</button>
        </div>
      </div>
    </div>

    <!-- Mobile overlay -->
    <div v-if="mode === 'mobile' && overlayOpen" class="search-overlay">
      <div class="overlay-header d-flex align-items-center px-3 py-2">
        <button class="btn btn-link text-dark me-2" @click="closeOverlay">
          <i class="fas fa-arrow-left"></i>
        </button>
        <div class="flex-grow-1 d-flex align-items-center search-input-shell">
          <i class="fas fa-search me-2 text-muted"></i>
          <input
            ref="mobileInput"
            v-model="query"
            type="text"
            class="form-control border-0 bg-transparent p-0"
            :placeholder="placeholder"
            @input="handleInput"
            @keydown="handleKeydown"
            autofocus
          />
          <button class="btn btn-sm text-muted ms-2" type="button" @click="submitSearch">
            Search
          </button>
        </div>
      </div>
      <div class="overlay-body">
        <div v-if="loading" class="py-3 text-center small text-muted">Searching…</div>
        <div v-else>
          <section v-if="recentList.length" class="dropdown-section px-3">
            <div class="section-title">Recent</div>
            <ul class="list-unstyled mb-0">
              <li
                v-for="(term, idx) in recentList"
                :key="`m-recent-${idx}`"
                class="dropdown-item"
                @click="selectRecent(term)"
              >
                <i class="fas fa-history me-2 text-muted"></i>{{ term }}
              </li>
            </ul>
          </section>

          <section v-if="hasAnyResults" class="dropdown-section px-3">
            <div v-if="suggestions.people.length" class="section-title">People</div>
            <ul class="list-unstyled mb-0">
              <li
                v-for="(person, idx) in suggestions.people"
                :key="`m-person-${person.id || idx}`"
                class="dropdown-item"
                @click="goTo(person.url)"
              >
                <img
                  :src="person.avatar || noImageUrl"
                  alt="profile"
                  class="avatar me-2"
                />
                <div class="flex-fill">
                  <div class="fw-semibold">{{ person.name }}</div>
                  <small class="text-muted">{{ person.subtitle }}</small>
                </div>
              </li>
            </ul>

            <div v-if="suggestions.posts.length" class="section-title mt-2">Posts</div>
            <ul class="list-unstyled mb-0">
              <li
                v-for="(post, idx) in suggestions.posts"
                :key="`m-post-${post.id || idx}`"
                class="dropdown-item"
                @click="goTo(post.url)"
              >
                <i class="fas fa-sticky-note me-2 text-primary"></i>
                <div class="flex-fill">
                  <div class="fw-semibold text-truncate">{{ post.title || post.snippet }}</div>
                  <small class="text-muted text-truncate">{{ post.snippet }}</small>
                </div>
              </li>
            </ul>
          </section>

          <div v-if="!loading && !hasAnyResults && !recentList.length" class="py-3 text-center small text-muted">
            No matches yet. Try another term.
          </div>
        </div>
      </div>
      <div class="overlay-footer d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
        <small class="text-muted">Press Enter to see all results</small>
        <button class="btn btn-sm btn-primary text-white" @click="submitSearch">See all</button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { cmMedia, cmRoute } from '../utils/campaignManager';

export default {
  name: 'SearchBox',
  props: {
    mode: {
      type: String,
      default: 'desktop', // 'desktop' | 'mobile'
    },
    placeholder: {
      type: String,
      default: 'Search posts, people…',
    },
  },
  data() {
    return {
      query: '',
      suggestions: {
        people: [],
        posts: [],
      },
      loading: false,
      showDropdown: false,
      overlayOpen: false,
      recentList: [],
      debounceTimer: null,
      activeSection: null,
      activeIndex: -1,
    };
  },
  computed: {
    hasAnyResults() {
      return (this.suggestions.people && this.suggestions.people.length) ||
        (this.suggestions.posts && this.suggestions.posts.length);
    },
    modeClass() {
      return this.mode === 'mobile' ? 'search-mobile' : 'search-desktop';
    },
    noImageUrl() {
      return cmMedia('noImage');
    },
  },
  mounted() {
    this.loadRecent();
    document.addEventListener('click', this.handleOutsideClick);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleOutsideClick);
  },
  methods: {
    handleOutsideClick(event) {
      if (this.mode === 'mobile' && this.overlayOpen) return;
      const wrapper = this.$refs.wrapper;
      if (wrapper && !wrapper.contains(event.target)) {
        this.showDropdown = false;
        this.activeIndex = -1;
        this.activeSection = null;
      }
    },
    openDropdown() {
      if (this.mode === 'mobile') {
        this.openOverlay();
        return;
      }
      this.showDropdown = true;
      this.fetchSuggestions();
    },
    openOverlay() {
      this.overlayOpen = true;
      this.showDropdown = false;
      this.$nextTick(() => {
        if (this.$refs.mobileInput) this.$refs.mobileInput.focus();
      });
    },
    closeOverlay() {
      this.overlayOpen = false;
    },
    handleInput() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.fetchSuggestions();
      }, 300);
    },
    fetchSuggestions() {
      const term = (this.query || '').trim();
      if (!term) {
        this.suggestions = { people: [], posts: [] };
        this.loading = false;
        return;
      }
      this.loading = true;
      axios.get(cmRoute('searchSuggest'), {
        params: {
          query: term,
          limit: 5,
        },
      }).then((response) => {
        const data = response.data || {};
        this.suggestions = {
          people: data.people || [],
          posts: data.posts || [],
        };
      }).catch(() => {
        this.suggestions = { people: [], posts: [] };
      }).finally(() => {
        this.loading = false;
        this.showDropdown = this.mode !== 'mobile';
      });
    },
    submitSearch() {
      const term = (this.query || '').trim();
      if (!term) return;
      this.saveRecent(term);
      window.location.href = `${cmRoute('searchIndex')}?query=${encodeURIComponent(term)}`;
    },
    goTo(url) {
      if (!url) return;
      window.location.href = url;
    },
    saveRecent(term) {
      const key = 'community_search_recent';
      const existing = this.loadRecentInternal();
      const updated = [term, ...existing.filter((t) => t !== term)].slice(0, 5);
      try {
        localStorage.setItem(key, JSON.stringify(updated));
      } catch (e) {
        // ignore
      }
      this.recentList = updated;
    },
    loadRecentInternal() {
      const key = 'community_search_recent';
      try {
        const raw = localStorage.getItem(key);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        return [];
      }
    },
    loadRecent() {
      this.recentList = this.loadRecentInternal();
    },
    selectRecent(term) {
      this.query = term;
      this.submitSearch();
    },
    handleKeydown(event) {
      if (event.key === 'Escape') {
        this.showDropdown = false;
        if (this.mode === 'mobile') this.closeOverlay();
      }
      if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
        event.preventDefault();
        this.navigate(event.key === 'ArrowDown' ? 1 : -1);
      }
      if (event.key === 'Enter') {
        if (this.activeSection) {
          this.activateSelection();
        } else {
          this.submitSearch();
        }
      }
    },
    navigate(direction) {
      const items = [];
      const sections = [];
      this.recentList.forEach((term) => {
        items.push({ type: 'recent', value: term });
        sections.push('recent');
      });
      (this.suggestions.people || []).forEach((p) => {
        items.push({ type: 'people', value: p });
        sections.push('people');
      });
      (this.suggestions.posts || []).forEach((p) => {
        items.push({ type: 'posts', value: p });
        sections.push('posts');
      });
      if (!items.length) return;
      const nextIndex = (this.activeIndex + direction + items.length) % items.length;
      this.activeIndex = nextIndex;
      this.activeSection = items[nextIndex].type;
    },
    activateSelection() {
      const term = (this.query || '').trim();
      if (this.activeSection === 'recent') {
        const selectedTerm = this.recentList[this.activeIndex] || term;
        this.selectRecent(selectedTerm);
        return;
      }
      if (this.activeSection === 'people') {
        const person = (this.suggestions.people || [])[this.activeIndex - this.recentList.length];
        if (person) this.goTo(person.url);
        return;
      }
      if (this.activeSection === 'posts') {
        const offset = this.recentList.length + (this.suggestions.people || []).length;
        const post = (this.suggestions.posts || [])[this.activeIndex - offset];
        if (post) this.goTo(post.url);
      }
    },
    isActive(idx, section) {
      if (this.activeSection !== section) return false;
      if (section === 'recent') return this.activeIndex === idx;
      if (section === 'people') return this.activeIndex === (this.recentList.length + idx);
      if (section === 'posts') {
        const offset = this.recentList.length + (this.suggestions.people || []).length;
        return this.activeIndex === (offset + idx);
      }
      return false;
    },
    setActive(idx, section) {
      if (section === 'recent') {
        this.activeIndex = idx;
      } else if (section === 'people') {
        this.activeIndex = this.recentList.length + idx;
      } else if (section === 'posts') {
        const offset = this.recentList.length + (this.suggestions.people || []).length;
        this.activeIndex = offset + idx;
      }
      this.activeSection = section;
    },
  },
};
</script>

<style scoped>
.search-wrapper {
  position: relative;
  width: 100%;
}

.search-input-shell {
  background: #f0f2f5;
  border-radius: 999px;
  padding: 6px 14px;
  border: 1px solid #e5e7eb;
  min-height: 40px;
}

.search-dropdown {
  position: absolute;
  top: 46px;
  left: 0;
  width: 100%;
  z-index: 2100;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
}

.dropdown-section {
  padding: 8px 12px;
}

.section-title {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #6b7280;
  margin-bottom: 6px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 8px;
  cursor: pointer;
}

.dropdown-item:hover,
.dropdown-item.active {
  background: #f3f4f6;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid #e5e7eb;
}

.search-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.35);
  z-index: 3000;
  display: flex;
  flex-direction: column;
  backdrop-filter: blur(2px);
}

.overlay-header {
  border-bottom: 1px solid #e5e7eb;
  background: #ffffff;
}

.overlay-body {
  flex: 1;
  overflow-y: auto;
  padding-top: 6px;
  background: #ffffff;
}

.overlay-footer {
  position: sticky;
  bottom: 0;
  background: #ffffff;
}

@media (max-width: 991px) {
  .search-desktop .search-dropdown {
    width: 100%;
  }
}
</style>
