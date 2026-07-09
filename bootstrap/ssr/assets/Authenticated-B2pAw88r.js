import { mergeProps, withCtx, unref, createVNode, createBlock, createCommentVNode, openBlock, Fragment, renderList, toDisplayString, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderList, ssrRenderAttr, ssrInterpolate } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-DAY7LMhN.js";
import { Head } from "@inertiajs/vue3";
import "@headlessui/vue";
import "@heroicons/vue/24/outline";
const _sfc_main = {
  __name: "Authenticated",
  __ssrInlineRender: true,
  props: {
    products: Array
  },
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
                  _push3(`<title${_scopeId2}>Store | Colorado Supply &amp; Procurement</title><meta name="description" content="Browse our catalog of industrial supplies and equipment with exclusive pricing."${_scopeId2}>`);
                } else {
                  return [
                    createVNode("title", null, "Store | Colorado Supply & Procurement"),
                    createVNode("meta", {
                      name: "description",
                      content: "Browse our catalog of industrial supplies and equipment with exclusive pricing."
                    })
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="min-h-screen bg-gray-50 dark:bg-gray-800"${_scopeId}><div class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-12"${_scopeId}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"${_scopeId}><h1 class="text-4xl font-bold mb-2"${_scopeId}>Welcome Back!</h1><p class="text-xl text-blue-100"${_scopeId}> Browse our personalized product recommendations </p></div></div><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12"${_scopeId}><div class="mb-8"${_scopeId}><h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"${_scopeId}> Featured Products for You </h2><p class="text-gray-600 dark:text-gray-300"${_scopeId}> Based on your previous orders and preferences </p></div><div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"${_scopeId}><!--[-->`);
            ssrRenderList(__props.products, (product) => {
              _push2(`<div class="bg-white dark:bg-gray-700 rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300"${_scopeId}><div class="aspect-w-16 aspect-h-9 bg-gray-200 dark:bg-gray-600"${_scopeId}><img${ssrRenderAttr("src", product.image)}${ssrRenderAttr("alt", product.name)} class="w-full h-48 object-cover"${_scopeId}></div><div class="p-6"${_scopeId}><h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2"${_scopeId}>${ssrInterpolate(product.name)}</h3><p class="text-gray-600 dark:text-gray-300 text-sm mb-4"${_scopeId}>${ssrInterpolate(product.description)}</p><div class="flex items-center justify-between"${_scopeId}><span class="text-2xl font-bold text-blue-600 dark:text-blue-400"${_scopeId}> $${ssrInterpolate(product.price.toFixed(2))}</span><button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors duration-200 text-sm font-medium"${_scopeId}> Add to Cart </button></div></div></div>`);
            });
            _push2(`<!--]--></div>`);
            if (!__props.products || __props.products.length === 0) {
              _push2(`<div class="text-center py-12"${_scopeId}><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"${_scopeId}></path></svg><h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white"${_scopeId}> No products available </h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400"${_scopeId}> Check back soon for new products. </p></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="mt-12 bg-blue-50 dark:bg-gray-700 rounded-lg p-6"${_scopeId}><div class="flex flex-col md:flex-row items-center justify-between gap-4"${_scopeId}><div${_scopeId}><h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1"${_scopeId}> Need something specific? </h3><p class="text-gray-600 dark:text-gray-300"${_scopeId}> Contact us for custom quotes and bulk ordering options. </p></div><a href="/contact" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition whitespace-nowrap"${_scopeId}> Contact Us </a></div></div></div></div>`);
          } else {
            return [
              createVNode(unref(Head), null, {
                default: withCtx(() => [
                  createVNode("title", null, "Store | Colorado Supply & Procurement"),
                  createVNode("meta", {
                    name: "description",
                    content: "Browse our catalog of industrial supplies and equipment with exclusive pricing."
                  })
                ]),
                _: 1
              }),
              createVNode("div", { class: "min-h-screen bg-gray-50 dark:bg-gray-800" }, [
                createVNode("div", { class: "bg-gradient-to-br from-blue-600 to-blue-800 text-white py-12" }, [
                  createVNode("div", { class: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" }, [
                    createVNode("h1", { class: "text-4xl font-bold mb-2" }, "Welcome Back!"),
                    createVNode("p", { class: "text-xl text-blue-100" }, " Browse our personalized product recommendations ")
                  ])
                ]),
                createVNode("div", { class: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" }, [
                  createVNode("div", { class: "mb-8" }, [
                    createVNode("h2", { class: "text-2xl font-bold text-gray-900 dark:text-white mb-2" }, " Featured Products for You "),
                    createVNode("p", { class: "text-gray-600 dark:text-gray-300" }, " Based on your previous orders and preferences ")
                  ]),
                  createVNode("div", { class: "grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.products, (product) => {
                      return openBlock(), createBlock("div", {
                        key: product.id,
                        class: "bg-white dark:bg-gray-700 rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300"
                      }, [
                        createVNode("div", { class: "aspect-w-16 aspect-h-9 bg-gray-200 dark:bg-gray-600" }, [
                          createVNode("img", {
                            src: product.image,
                            alt: product.name,
                            class: "w-full h-48 object-cover"
                          }, null, 8, ["src", "alt"])
                        ]),
                        createVNode("div", { class: "p-6" }, [
                          createVNode("h3", { class: "text-lg font-semibold text-gray-900 dark:text-white mb-2" }, toDisplayString(product.name), 1),
                          createVNode("p", { class: "text-gray-600 dark:text-gray-300 text-sm mb-4" }, toDisplayString(product.description), 1),
                          createVNode("div", { class: "flex items-center justify-between" }, [
                            createVNode("span", { class: "text-2xl font-bold text-blue-600 dark:text-blue-400" }, " $" + toDisplayString(product.price.toFixed(2)), 1),
                            createVNode("button", { class: "px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors duration-200 text-sm font-medium" }, " Add to Cart ")
                          ])
                        ])
                      ]);
                    }), 128))
                  ]),
                  !__props.products || __props.products.length === 0 ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "text-center py-12"
                  }, [
                    (openBlock(), createBlock("svg", {
                      class: "mx-auto h-12 w-12 text-gray-400",
                      fill: "none",
                      stroke: "currentColor",
                      viewBox: "0 0 24 24"
                    }, [
                      createVNode("path", {
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        "stroke-width": "2",
                        d: "M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                      })
                    ])),
                    createVNode("h3", { class: "mt-2 text-sm font-medium text-gray-900 dark:text-white" }, " No products available "),
                    createVNode("p", { class: "mt-1 text-sm text-gray-500 dark:text-gray-400" }, " Check back soon for new products. ")
                  ])) : createCommentVNode("", true),
                  createVNode("div", { class: "mt-12 bg-blue-50 dark:bg-gray-700 rounded-lg p-6" }, [
                    createVNode("div", { class: "flex flex-col md:flex-row items-center justify-between gap-4" }, [
                      createVNode("div", null, [
                        createVNode("h3", { class: "text-lg font-semibold text-gray-900 dark:text-white mb-1" }, " Need something specific? "),
                        createVNode("p", { class: "text-gray-600 dark:text-gray-300" }, " Contact us for custom quotes and bulk ordering options. ")
                      ]),
                      createVNode("a", {
                        href: "/contact",
                        class: "inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition whitespace-nowrap"
                      }, " Contact Us ")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Store/Authenticated.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
