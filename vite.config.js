import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

// Dua frontend dalam satu build: Blade (Livewire + Alpine) dan React (Inertia + SSR).
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/site.css', 'resources/js/blade.js', 'resources/js/react/app.tsx'],
            ssr: 'resources/js/react/ssr.tsx',
            refresh: true,
        }),
        react(),
    ],
});
