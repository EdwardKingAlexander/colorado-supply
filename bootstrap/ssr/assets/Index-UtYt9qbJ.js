import { unref, withCtx, createTextVNode, toDisplayString, createVNode, createBlock, openBlock, Fragment, renderList, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderList, ssrInterpolate } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AuthenticatedLayout-BXbec8wQ.js";
import { Head, Link } from "@inertiajs/vue3";
import "./ApplicationLogo-B2173abF.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
import "@headlessui/vue";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: {
    company: Object,
    locations: Array
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Company Home" }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        header: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h2 class="font-semibold text-xl text-gray-800 leading-tight"${_scopeId}>Company: ${ssrInterpolate(__props.company.name)}</h2>`);
          } else {
            return [
              createVNode("h2", { class: "font-semibold text-xl text-gray-800 leading-tight" }, "Company: " + toDisplayString(__props.company.name), 1)
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="py-8 sm:py-12"${_scopeId}><div class="mobile-page-gutter mx-auto max-w-7xl lg:px-8"${_scopeId}><div class="overflow-hidden rounded-lg bg-white shadow-sm"${_scopeId}><div class="p-4 text-gray-900 sm:p-6"${_scopeId}><h3 class="text-xl font-semibold"${_scopeId}>Your Locations</h3><ul class="mt-4 divide-y divide-gray-200"${_scopeId}><!--[-->`);
            ssrRenderList(__props.locations, (location) => {
              _push2(`<li${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("store.location.index", { location: location.slug }),
                class: "flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-medium text-indigo-700 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`${ssrInterpolate(location.name)}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(location.name), 1)
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
              _push2(`</li>`);
            });
            _push2(`<!--]--></ul></div></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "py-8 sm:py-12" }, [
                createVNode("div", { class: "mobile-page-gutter mx-auto max-w-7xl lg:px-8" }, [
                  createVNode("div", { class: "overflow-hidden rounded-lg bg-white shadow-sm" }, [
                    createVNode("div", { class: "p-4 text-gray-900 sm:p-6" }, [
                      createVNode("h3", { class: "text-xl font-semibold" }, "Your Locations"),
                      createVNode("ul", { class: "mt-4 divide-y divide-gray-200" }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(__props.locations, (location) => {
                          return openBlock(), createBlock("li", {
                            key: location.id
                          }, [
                            createVNode(unref(Link), {
                              href: _ctx.route("store.location.index", { location: location.slug }),
                              class: "flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-medium text-indigo-700 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                            }, {
                              default: withCtx(() => [
                                createTextVNode(toDisplayString(location.name), 1)
                              ]),
                              _: 2
                            }, 1032, ["href"])
                          ]);
                        }), 128))
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
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Company/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
