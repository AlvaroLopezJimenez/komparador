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
    build: {
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: false, // Mantener console para depuración si es necesario
                drop_debugger: true,
            },
            mangle: {
                toplevel: true, // Ofuscar nombres de variables y funciones en el nivel superior
                properties: false, // No ofuscar propiedades de objetos (puede romper código)
            },
            format: {
                comments: false, // Eliminar comentarios
            },
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
