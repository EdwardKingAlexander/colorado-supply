import { createInertiaApp } from '@inertiajs/vue3'
import createServer from '@inertiajs/server'
import { createSSRApp, h } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    resolve: (name) =>
      resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ App, props, plugin }) {
      return createSSRApp({ render: () => h(App, props) }).use(plugin)
    },
  })
)
