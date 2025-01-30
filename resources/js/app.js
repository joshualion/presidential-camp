import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler.js'; 

import SendMessage from './components/SendMessage.vue';
import GetFollowers from './components/GetFollowers.vue';
import GetFollowing from './components/GetFollowing.vue';
import NewsFeed from './components/NewsFeed.vue';
import ProfileFeed from './components/ProfileFeed.vue';


const app = createApp({
    components:{
        SendMessage,
        GetFollowers,
        GetFollowing,
        NewsFeed,
        ProfileFeed
    }

   
});
app.mount('#app');

import Echo from "laravel-echo";
import io from "socket.io-client";

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname + ':8080', // Reverb server
});




import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();