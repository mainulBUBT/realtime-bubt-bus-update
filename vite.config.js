import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/css/bus-app.css',
                'resources/css/bus-tracker.css',
                'resources/css/track-map.css',
                'resources/css/livewire-integration.css',
                'resources/css/admin.css',
                'resources/css/admin-auth.css',
                'resources/js/bus-tracker.js',
                'resources/js/device-fingerprint.js',
                'resources/js/connection-manager.js',
                'resources/js/websocket-client.js',
                'resources/js/livewire-app.js',
                'resources/js/livewire-track.js',
                'resources/js/map.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
