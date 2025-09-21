import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      ssr: 'resources/js/ssr.js', // 👈 add this for Inertia SSR
      refresh: true,
    }),
    vue(),          // 👈 enable Vue SFCs
    tailwindcss(),  // keep Tailwind plugin
  ],
  server: {
    cors: true,
  },
  ssr: {
    noExternal: ['@inertiajs/server'], // 👈 required for SSR bundle
  },
})
