import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                local('Instrument Sans', {
                    alias: 'instrument-sans',
                    variable: '--font-instrument-sans',
                    display: 'swap',
                    preload: true,
                    fallbacks: [
                        'ui-sans-serif',
                        'system-ui',
                        'sans-serif',
                        'Apple Color Emoji',
                        'Segoe UI Emoji',
                    ],
                    variants: [
                        {
                            src: 'resources/fonts/InstrumentSans[wght].woff2',
                            weight: '400 700',
                            style: 'normal',
                        },
                    ],
                }),
                local('Martian Mono', {
                    alias: 'martian-mono',
                    variable: '--font-martian-mono',
                    display: 'swap',
                    preload: true,
                    fallbacks: [
                        'ui-monospace',
                        'SFMono-Regular',
                        'Menlo',
                        'monospace',
                    ],
                    variants: [
                        {
                            src: 'resources/fonts/MartianMono[wght].woff2',
                            weight: '400 700',
                            style: 'normal',
                        },
                    ],
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
});
