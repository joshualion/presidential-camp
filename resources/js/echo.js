import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const appKey = import.meta.env.VITE_REVERB_APP_KEY;
const host =
    import.meta.env.VITE_REVERB_HOST ||
    (typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1');
const port = Number(import.meta.env.VITE_REVERB_PORT || 8080);
const scheme =
    import.meta.env.VITE_REVERB_SCHEME ||
    (typeof window !== 'undefined' && window.location.protocol === 'https:' ? 'https' : 'http');

// Only initialize Echo/Pusher when a key is present to avoid runtime crashes
if (appKey) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: appKey,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
} else {
    console.warn('Realtime disabled: VITE_REVERB_APP_KEY is not set. Vue will continue without websockets.');
    window.Echo = null;
}
