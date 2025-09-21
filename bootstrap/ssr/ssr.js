import pkg from '@inertiajs/server'
const { createServer } = pkg

import { createInertiaApp } from '@inertiajs/vue3'
import { renderToString } from '@vue/server-renderer'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createSSRApp, h } from 'vue'

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    resolve: (name) =>
      resolvePageComponent(
        `./resources/js/Pages/${name}.vue`,
        import.meta.glob('./resources/js/Pages/**/*.vue')
      ),
    setup: ({ App, props, plugin }) => {
      return createSSRApp({ render: () => h(App, props) }).use(plugin)
    },
  })
)