import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      ssr: 'resources/js/ssr.js', // 👈 entry point
      refresh: true,
    }),
    vue(),
    tailwindcss(),
  ],
  build: {
    ssr: 'resources/js/ssr.js',
    outDir: 'bootstrap/ssr',     // 👈 force output directory
    rollupOptions: {
      input: {
        ssr: resolve(__dirname, 'resources/js/ssr.js'),
      },
      output: {
        entryFileNames: 'ssr.mjs', // 👈 force filename
      },
    },
  },
  ssr: {
    noExternal: ['@inertiajs/server'],
  },
  server: {
    cors: true,
  },
})
