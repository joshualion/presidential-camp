import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    define: {
        __VUE_PROD_DEVTOOLS__: false,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),

        vue({
            template:{
                transformAssetUrls:{
                    base:null,
                    includeAbsolute: false,
                },
            },
        })
    ],

    // server: {
    //     host: '0.0.0.0', // Bind to all IPs
    //     port: 5173,      // Default Vite port
    //     hmr: {
    //         host: '192.168.3.47', // Your computer's IP address
    //     },
    // },
});
