import { mergeProps, withCtx, unref, createVNode, createBlock, openBlock, useSSRContext } from "vue";
import { ssrRenderComponent } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-DAY7LMhN.js";
import { Head } from "@inertiajs/vue3";
import "@headlessui/vue";
import "@heroicons/vue/24/outline";
const _sfc_main = {
  __name: "Public",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, mergeProps({
        appName: _ctx.$page.props.appName
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Head), null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<title${_scopeId2}>Store | Colorado Supply &amp; Procurement</title><meta name="description" content="Browse our catalog of industrial supplies and equipment for government agencies and commercial customers."${_scopeId2}>`);
                } else {
                  return [
                    createVNode("title", null, "Store | Colorado Supply & Procurement"),
                    createVNode("meta", {
                      name: "description",
                      content: "Browse our catalog of industrial supplies and equipment for government agencies and commercial customers."
                    })
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="min-h-screen bg-gray-50 dark:bg-gray-800"${_scopeId}><div class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-16"${_scopeId}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"${_scopeId}><h1 class="text-4xl md:text-5xl font-bold mb-4"${_scopeId}>Welcome to Our Store</h1><p class="text-xl md:text-2xl text-blue-100 mb-8"${_scopeId}> Quality industrial supplies for government agencies and commercial customers </p><p class="text-lg text-blue-50"${_scopeId}> Sign in to access exclusive pricing and personalized product recommendations </p></div></div><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12"${_scopeId}><div class="grid md:grid-cols-3 gap-8"${_scopeId}><div class="bg-white dark:bg-gray-700 rounded-lg shadow-md p-6"${_scopeId}><div class="text-blue-600 dark:text-blue-400 mb-4"${_scopeId}><svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"${_scopeId}></path></svg></div><h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white"${_scopeId}>Quality Products</h3><p class="text-gray-600 dark:text-gray-300"${_scopeId}> We offer only the highest quality industrial supplies and equipment from trusted manufacturers. </p></div><div class="bg-white dark:bg-gray-700 rounded-lg shadow-md p-6"${_scopeId}><div class="text-blue-600 dark:text-blue-400 mb-4"${_scopeId}><svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"${_scopeId}></path></svg></div><h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white"${_scopeId}>Competitive Pricing</h3><p class="text-gray-600 dark:text-gray-300"${_scopeId}> Get access to government contract pricing and exclusive discounts when you create an account. </p></div><div class="bg-white dark:bg-gray-700 rounded-lg shadow-md p-6"${_scopeId}><div class="text-blue-600 dark:text-blue-400 mb-4"${_scopeId}><svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"${_scopeId}></path></svg></div><h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white"${_scopeId}>Fast Delivery</h3><p class="text-gray-600 dark:text-gray-300"${_scopeId}> Quick turnaround times and reliable delivery to meet your project deadlines. </p></div></div><div class="mt-12 text-center"${_scopeId}><div class="bg-blue-50 dark:bg-gray-700 rounded-lg p-8"${_scopeId}><h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white"${_scopeId}> Ready to Get Started? </h2><p class="text-gray-600 dark:text-gray-300 mb-6"${_scopeId}> Create an account to browse our full catalog and access exclusive pricing </p><div class="flex flex-col sm:flex-row gap-4 justify-center"${_scopeId}><a href="/register" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition"${_scopeId}> Create Account </a><a href="/login" class="inline-flex items-center justify-center px-6 py-3 border border-blue-600 text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 dark:bg-gray-800 dark:text-blue-400 dark:hover:bg-gray-700 transition"${_scopeId}> Sign In </a></div></div></div></div></div>`);
          } else {
            return [
              createVNode(unref(Head), null, {
                default: withCtx(() => [
                  createVNode("title", null, "Store | Colorado Supply & Procurement"),
                  createVNode("meta", {
                    name: "description",
                    content: "Browse our catalog of industrial supplies and equipment for government agencies and commercial customers."
                  })
                ]),
                _: 1
              }),
              createVNode("div", { class: "min-h-screen bg-gray-50 dark:bg-gray-800" }, [
                createVNode("div", { class: "bg-gradient-to-br from-blue-600 to-blue-800 text-white py-16" }, [
                  createVNode("div", { class: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" }, [
                    createVNode("h1", { class: "text-4xl md:text-5xl font-bold mb-4" }, "Welcome to Our Store"),
                    createVNode("p", { class: "text-xl md:text-2xl text-blue-100 mb-8" }, " Quality industrial supplies for government agencies and commercial customers "),
                    createVNode("p", { class: "text-lg text-blue-50" }, " Sign in to access exclusive pricing and personalized product recommendations ")
                  ])
                ]),
                createVNode("div", { class: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" }, [
                  createVNode("div", { class: "grid md:grid-cols-3 gap-8" }, [
                    createVNode("div", { class: "bg-white dark:bg-gray-700 rounded-lg shadow-md p-6" }, [
                      createVNode("div", { class: "text-blue-600 dark:text-blue-400 mb-4" }, [
                        (openBlock(), createBlock("svg", {
                          class: "w-12 h-12",
                          fill: "none",
                          stroke: "currentColor",
                          viewBox: "0 0 24 24"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            "stroke-width": "2",
                            d: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                          })
                        ]))
                      ]),
                      createVNode("h3", { class: "text-xl font-bold mb-2 text-gray-900 dark:text-white" }, "Quality Products"),
                      createVNode("p", { class: "text-gray-600 dark:text-gray-300" }, " We offer only the highest quality industrial supplies and equipment from trusted manufacturers. ")
                    ]),
                    createVNode("div", { class: "bg-white dark:bg-gray-700 rounded-lg shadow-md p-6" }, [
                      createVNode("div", { class: "text-blue-600 dark:text-blue-400 mb-4" }, [
                        (openBlock(), createBlock("svg", {
                          class: "w-12 h-12",
                          fill: "none",
                          stroke: "currentColor",
                          viewBox: "0 0 24 24"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            "stroke-width": "2",
                            d: "M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                          })
                        ]))
                      ]),
                      createVNode("h3", { class: "text-xl font-bold mb-2 text-gray-900 dark:text-white" }, "Competitive Pricing"),
                      createVNode("p", { class: "text-gray-600 dark:text-gray-300" }, " Get access to government contract pricing and exclusive discounts when you create an account. ")
                    ]),
                    createVNode("div", { class: "bg-white dark:bg-gray-700 rounded-lg shadow-md p-6" }, [
                      createVNode("div", { class: "text-blue-600 dark:text-blue-400 mb-4" }, [
                        (openBlock(), createBlock("svg", {
                          class: "w-12 h-12",
                          fill: "none",
                          stroke: "currentColor",
                          viewBox: "0 0 24 24"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            "stroke-width": "2",
                            d: "M13 10V3L4 14h7v7l9-11h-7z"
                          })
                        ]))
                      ]),
                      createVNode("h3", { class: "text-xl font-bold mb-2 text-gray-900 dark:text-white" }, "Fast Delivery"),
                      createVNode("p", { class: "text-gray-600 dark:text-gray-300" }, " Quick turnaround times and reliable delivery to meet your project deadlines. ")
                    ])
                  ]),
                  createVNode("div", { class: "mt-12 text-center" }, [
                    createVNode("div", { class: "bg-blue-50 dark:bg-gray-700 rounded-lg p-8" }, [
                      createVNode("h2", { class: "text-2xl font-bold mb-4 text-gray-900 dark:text-white" }, " Ready to Get Started? "),
                      createVNode("p", { class: "text-gray-600 dark:text-gray-300 mb-6" }, " Create an account to browse our full catalog and access exclusive pricing "),
                      createVNode("div", { class: "flex flex-col sm:flex-row gap-4 justify-center" }, [
                        createVNode("a", {
                          href: "/register",
                          class: "inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition"
                        }, " Create Account "),
                        createVNode("a", {
                          href: "/login",
                          class: "inline-flex items-center justify-center px-6 py-3 border border-blue-600 text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 dark:bg-gray-800 dark:text-blue-400 dark:hover:bg-gray-700 transition"
                        }, " Sign In ")
                      ])
                    ])
                  ])
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Store/Public.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
