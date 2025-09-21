import { createInertiaApp } from '@inertiajs/vue3'
import { renderToString } from '@vue/server-renderer'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createSSRApp, h } from 'vue'

// IMPORTANT: @inertiajs/server is CommonJS.
// On Node 22 ESM, grab the default export via top-level await.
const { default: createServer } = await import('@inertiajs/server')

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    resolve: (name) =>
      resolvePageComponent(
        `./resources/js/Pages/${name}.vue`,
        import.meta.glob('./resources/js/Pages/**/*.vue')
      ),
    setup: ({ App, props, plugin }) =>
      createSSRApp({ render: () => h(App, props) }).use(plugin),
  })
)
