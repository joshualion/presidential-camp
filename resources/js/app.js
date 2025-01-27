import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler.js'; 

import SendMessage from './components/SendMessage.vue';
import GetFollowers from './components/GetFollowers.vue';
import GetFollowing from './components/GetFollowing.vue';
import NewsFeed from './components/NewsFeed.vue';

const app = createApp({
    components:{
        SendMessage,
        GetFollowers,
        GetFollowing,
        NewsFeed
    }

   
});
app.mount('#app');






import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();