import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost', // O tu IP si accedes desde otra máquina
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/fingerprint.js',
                'resources/js/carga-datos-dinamica.js',
            ],
            refresh: true,
        }),
    ],
});
