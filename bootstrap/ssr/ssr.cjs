const createServer = require('@inertiajs/server')
const { createInertiaApp } = require('@inertiajs/vue3')
const { renderToString } = require('@vue/server-renderer')
const { resolvePageComponent } = require('laravel-vite-plugin/inertia-helpers')
const { createSSRApp, h } = require('vue')

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
