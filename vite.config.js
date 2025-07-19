import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        vue(),
    ],
    build: {
        // outDir: '../public/build',
        outDir: 'public/build',
        chunkSizeWarningLimit: 1600,
    },
    define: {
        'import.meta.env.VITE_APP_URL': JSON.stringify(process.env.VITE_APP_URL || 'https://taskmgrv2-latest.onrender.com'),
    },
});
