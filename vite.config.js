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
    build: {
        // Target modern browsers untuk bundle lebih kecil
        target: 'es2020',
        // Rolldown (Vite 8) handles minification natively
        cssMinify: true,
        rollupOptions: {
            output: {
                // Manual chunk splitting: pisahkan alpine dari app
                manualChunks: (id) => {
                    if (id.includes('alpinejs')) return 'alpine';
                },
                // Nama file dengan hash untuk cache busting
                entryFileNames: 'js/[name]-[hash].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: ({ name }) => {
                    if (/\.css$/.test(name ?? '')) return 'css/[name]-[hash][extname]';
                    if (/\.(png|jpe?g|svg|gif|webp)$/.test(name ?? '')) return 'images/[name]-[hash][extname]';
                    if (/\.(woff2?|ttf|eot)$/.test(name ?? '')) return 'fonts/[name]-[hash][extname]';
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
