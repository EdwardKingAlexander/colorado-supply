import serverModule from '@inertiajs/server'
import { createInertiaApp } from '@inertiajs/vue3'
import { renderToString } from '@vue/server-renderer'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createSSRApp, h } from 'vue'
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/index.esm.js'

const appName = process.env.VITE_APP_NAME || 'Laravel'

// unwrap the odd export shape
const createServer = serverModule.default?.default ?? serverModule.default

if (typeof createServer !== 'function') {
  console.error('❌ Could not load createServer. Got:', serverModule)
  process.exit(1)
}

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    resolve: (name) =>
      resolvePageComponent(
        `./resources/js/Pages/${name}.vue`,
        import.meta.glob('./resources/js/Pages/**/*.vue', { eager: true }) // 👈 key fix
      ),
    setup: ({ App, props, plugin }) =>
      createSSRApp({ render: () => h(App, props) })
        .use(plugin)
        .use(ZiggyVue)
        .mixin({
          methods: {
            appName: () => appName,
          },
        }),
  })
)
