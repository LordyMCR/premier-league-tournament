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
            includeAssets: ['favicon.ico', 'robots.txt'],
            workbox: {
                globDirectory: 'public',
                globPatterns: [
                    '**/*.{js,css,html,ico,png,jpg,jpeg,svg,woff,woff2,ttf,eot}'
                ],
                // Skip revision for these files (they have hashes in filename)
                dontCacheBustURLsMatching: /\.\w{8}\./,
                maximumFileSizeToCacheInBytes: 5000000,
                // Cache strategy for API calls
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/api\./,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'api-cache',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 24 * 60 * 60 // 24 hours
                            }
                        }
                    }
                ]
            },
            manifest: {
                name: 'Premier League Tournament',
                short_name: 'PL Tournament',
                description: 'Premier League prediction tournaments - pick teams, compete with friends!',
                theme_color: '#0f172a',
                background_color: '#0f172a',
                display: 'standalone',
                start_url: '/',
                scope: '/',
                categories: ['sports', 'entertainment', 'games'],
                icons: [
                    {
                        src: '/pwa-192x192.jpg',
                        sizes: '192x192',
                        type: 'image/jpeg',
                    },
                    {
                        src: '/pwa-512x512.jpg',
                        sizes: '512x512',
                        type: 'image/jpeg',
                    },
                    {
                        src: '/pwa-512x512.jpg',
                        sizes: '512x512',
                        type: 'image/jpeg',
                        purpose: 'any maskable',
                    },
                ],
                screenshots: [
                    {
                        src: '/screenshot-mobile.jpg',
                        sizes: '540x720',
                        type: 'image/jpeg',
                        form_factor: 'narrow'
                    },
                    {
                        src: '/screenshot-desktop.jpg', 
                        sizes: '1280x720',
                        type: 'image/jpeg',
                        form_factor: 'wide'
                    }
                ]
            },
        }),
    ],
});
