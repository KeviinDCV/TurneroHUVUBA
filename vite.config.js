import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0', // Escuchar en todas las interfaces de red
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost', // Cambiar por la IP de tu servidor si accedes desde otro PC
        },
    },
});
