import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import * as path from 'node:path';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({mode}) => {
    const env = loadEnv(mode, process.cwd());

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.ts'],
                refresh: false,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                    }),
                ],
            }),
            inertia(),
            tailwindcss(),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
            wayfinder({
                formVariants: true,
            }),
        ],
        resolve: {
            alias: {
                'ziggy-js': path.resolve('vendor/tightenco/ziggy'),
            },
        },
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            hmr: {
                host: 'localhost', // Браузер будет подключаться к localhost
                port: 5173,
            },
            watch: {
                    ignored: [
                        '**/node_modules/**',
                        '**/vendor/**',
                        '**/storage/**',
                        '**/.git/**',
                        '**/tmp/**',
                    ],
            },
        },
    }
});
