import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/css/bus-tracker.css',
                'resources/css/track-map.css',
                'resources/js/bus-tracker.js',
                'resources/js/device-fingerprint.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
