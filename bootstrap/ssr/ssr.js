import { createServer } from '@inertiajs/server'
import { createInertiaApp } from '@inertiajs/vue3'
import { renderToString } from '@vue/server-renderer'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createSSRApp, h } from 'vue'

const { createServer } = pkg

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
