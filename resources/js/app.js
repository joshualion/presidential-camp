import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import Lightbox from './components/LightBox.vue';
import SendMessage from './components/SendMessage.vue';
import GetFollowers from './components/GetFollowers.vue';
import GetFollowing from './components/GetFollowing.vue';
import NewsFeed from './components/NewsFeed.vue';
import ProfileFeed from './components/ProfileFeed.vue';
import MessengerWidget from './components/MessengerWidget.vue';
import SearchBox from './components/SearchBox.vue';
import ActivityBell from './components/ActivityBell.vue';
import NewConversation from './components/NewConversation.vue';

// Keep a global reference so devtools/console always have a value
window.__VUE_APP__ = null;

const mountVueApp = () => {
    const mountTargets = ['app', 'vue-widgets', 'search-root', 'mobile-search-root', 'search-results-root', 'activity-bell-desktop', 'activity-bell-mobile', 'backend-send-message'];
    mountTargets.forEach((id) => {
        const root = document.getElementById(id);
        if (!root) {
            return;
        }
        if (root.__VUE_APP__) {
            window.__VUE_APP__ = root.__VUE_APP__;
            return;
        }

        const app = createApp({});

        app.component('send-message', SendMessage);
        app.component('get-followers', GetFollowers);
        app.component('get-following', GetFollowing);
        app.component('news-feed', NewsFeed);
        app.component('profile-feed', ProfileFeed);
        app.component('lightbox', Lightbox);
        app.component('messenger-widget', MessengerWidget);
        app.component('search-box', SearchBox);
        app.component('activity-bell', ActivityBell);
        app.component('new-conversation', NewConversation);

        const isDev = typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.DEV;
        app.config.devtools = !!isDev;
        if (typeof window !== 'undefined' && window.__VUE_DEVTOOLS_GLOBAL_HOOK__) {
            window.__VUE_DEVTOOLS_GLOBAL_HOOK__.enabled = !!isDev;
        }

        const vm = app.mount(root);
        root.__VUE_APP__ = vm;
        window.__VUE_APP__ = vm;
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountVueApp);
} else {
    mountVueApp();
}

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
