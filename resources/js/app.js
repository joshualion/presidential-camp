import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler.js'; 
import Lightbox from "./components/LightBox.vue"; 
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
        ProfileFeed,
        Lightbox
    }

   
});
app.mount('#app');





import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();