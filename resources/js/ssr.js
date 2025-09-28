import serverModule from '@inertiajs/server'
import { createInertiaApp } from '@inertiajs/vue3'
import { renderToString } from '@vue/server-renderer'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createSSRApp, h } from 'vue'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'

const appName = import.meta.env.VITE_APP_NAME || 'Colorado Supply & Procurement LLC'

const createServer = serverModule.default?.default ?? serverModule.default

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    resolve: (name) =>
      resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
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
