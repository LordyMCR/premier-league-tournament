import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: ['favicon.ico', 'robots.txt', 'apple-touch-icon.png'],
            manifest: {
                name: 'Premier League Tournament',
                short_name: 'PL Tournament',
                description: 'A PWA Laravel + Vue app for managing Premier League tournaments',
                theme_color: '#0f172a',
                background_color: '#ffffff',
                display: 'standalone',
                start_url: '/',
                icons: [
                    {
                        src: '/pwa-192x192.jpg',
                        sizes: '192x192',
                        type: 'image/jpg',
                    },
                    {
                        src: '/pwa-512x512.jpg',
                        sizes: '512x512',
                        type: 'image/jpg',
                    },
                    {
                        src: '/pwa-512x512.jpg',
                        sizes: '512x512',
                        type: 'image/jpg',
                        purpose: 'any maskable',
                    },
                ],
            },
        }),
    ],
});
