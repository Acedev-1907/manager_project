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
        chunkSizeWarningLimit: 8000,
    },
    // server: {
    //     host: '0.0.0.0',        // để máy khác truy cập
    //     port: 5173,
    //     hmr: {
    //         host: '192.168.1.84',
    //     },
    // },
});
