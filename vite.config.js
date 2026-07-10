import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
    server: {
        // Bind IPv4 loopback explicitly. The site is secured via `herd secure`,
        // so laravel-vite-plugin advertises the dev server as
        // https://colorado-supply.test:5173 — that hostname resolves to
        // 127.0.0.1 (hosts file), so the listener must be bound there (Node
        // otherwise resolves `localhost` to [::1] and connections fail).
        // Serving dev assets on the site's own HTTPS hostname also avoids
        // Chrome's Local Network Access blocking, which kills image requests
        // to IP-literal loopback URLs from non-secure pages (Chrome 142+).
        host: '127.0.0.1',
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@images': path.resolve(__dirname, 'resources/images'),
        },
    },
})

