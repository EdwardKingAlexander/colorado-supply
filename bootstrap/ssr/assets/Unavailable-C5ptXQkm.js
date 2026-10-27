import { mergeProps, withCtx, unref, createVNode, createTextVNode, createBlock, openBlock, toDisplayString, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderStyle, ssrRenderAttr, ssrInterpolate } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-CyuId9rx.js";
import { Head, Link } from "@inertiajs/vue3";
import "./CookieConsentBanner-ByAlkSbo.js";
import "@headlessui/vue";
import "axios";
import "@heroicons/vue/24/outline";
import "./logo-cleansed-light-B5mBHTsK.js";
const _sfc_main = {
  __name: "Unavailable",
  __ssrInlineRender: true,
  props: {
    contactEmail: {
      type: String,
      default: "Edward@cogovsupply.com"
    }
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
                  _push3(`<title${_scopeId2}>Store Under Maintenance | Colorado Supply &amp; Procurement</title>`);
                } else {
                  return [
                    createVNode("title", null, "Store Under Maintenance | Colorado Supply & Procurement")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 pb-16 pt-28 dark:bg-gray-900 sm:px-6"${_scopeId}><div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-white/10"${_scopeId}><div class="flex flex-col items-center px-6 py-8 text-center" style="${ssrRenderStyle({ "background-color": "#16334c" })}"${_scopeId}><div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full" style="${ssrRenderStyle({ "background-color": "rgba(233, 194, 81, 0.15)" })}"${_scopeId}><svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="#e9c251" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"${_scopeId}><path d="M3 9l1.5-5h15L21 9"${_scopeId}></path><path d="M4 9h16v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9z"${_scopeId}></path><path d="M9 20v-5h6v5"${_scopeId}></path></svg></div><h1 class="text-2xl font-bold text-white"${_scopeId}> Store Under Maintenance </h1><p class="mt-1 text-sm font-medium" style="${ssrRenderStyle({ "color": "#e9c251" })}"${_scopeId}> We’ll be back online soon </p></div><div class="px-6 py-8 text-center sm:px-8"${_scopeId}><p class="text-base leading-7 text-gray-600 dark:text-gray-300"${_scopeId}> We’re currently performing scheduled maintenance to serve you better and expect to be back shortly. Thank you for your patience — your account and order history are safe and will be waiting for you when we return. </p><div class="my-7 border-t border-gray-200 dark:border-white/10"${_scopeId}></div><p class="text-base leading-7 text-gray-700 dark:text-gray-200"${_scopeId}> Have an order to place or an urgent request in the meantime? Our team is happy to help you directly. </p><a${ssrRenderAttr("href", `mailto:${__props.contactEmail}`)} class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-md px-6 py-3 text-base font-semibold text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto" style="${ssrRenderStyle({ "background-color": "#16334c" })}"${_scopeId}> Email ${ssrInterpolate(__props.contactEmail)}</a><div class="mt-6"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("dashboard"),
              class: "text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` ← Return to your dashboard `);
                } else {
                  return [
                    createTextVNode(" ← Return to your dashboard ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div></div></div>`);
          } else {
            return [
              createVNode(unref(Head), null, {
                default: withCtx(() => [
                  createVNode("title", null, "Store Under Maintenance | Colorado Supply & Procurement")
                ]),
                _: 1
              }),
              createVNode("div", { class: "flex min-h-screen items-center justify-center bg-gray-50 px-4 pb-16 pt-28 dark:bg-gray-900 sm:px-6" }, [
                createVNode("div", { class: "w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-white/10" }, [
                  createVNode("div", {
                    class: "flex flex-col items-center px-6 py-8 text-center",
                    style: { "background-color": "#16334c" }
                  }, [
                    createVNode("div", {
                      class: "mb-4 flex h-16 w-16 items-center justify-center rounded-full",
                      style: { "background-color": "rgba(233, 194, 81, 0.15)" }
                    }, [
                      (openBlock(), createBlock("svg", {
                        class: "h-8 w-8",
                        viewBox: "0 0 24 24",
                        fill: "none",
                        stroke: "#e9c251",
                        "stroke-width": "1.75",
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        "aria-hidden": "true"
                      }, [
                        createVNode("path", { d: "M3 9l1.5-5h15L21 9" }),
                        createVNode("path", { d: "M4 9h16v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9z" }),
                        createVNode("path", { d: "M9 20v-5h6v5" })
                      ]))
                    ]),
                    createVNode("h1", { class: "text-2xl font-bold text-white" }, " Store Under Maintenance "),
                    createVNode("p", {
                      class: "mt-1 text-sm font-medium",
                      style: { "color": "#e9c251" }
                    }, " We’ll be back online soon ")
                  ]),
                  createVNode("div", { class: "px-6 py-8 text-center sm:px-8" }, [
                    createVNode("p", { class: "text-base leading-7 text-gray-600 dark:text-gray-300" }, " We’re currently performing scheduled maintenance to serve you better and expect to be back shortly. Thank you for your patience — your account and order history are safe and will be waiting for you when we return. "),
                    createVNode("div", { class: "my-7 border-t border-gray-200 dark:border-white/10" }),
                    createVNode("p", { class: "text-base leading-7 text-gray-700 dark:text-gray-200" }, " Have an order to place or an urgent request in the meantime? Our team is happy to help you directly. "),
                    createVNode("a", {
                      href: `mailto:${__props.contactEmail}`,
                      class: "mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-md px-6 py-3 text-base font-semibold text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto",
                      style: { "background-color": "#16334c" }
                    }, " Email " + toDisplayString(__props.contactEmail), 9, ["href"]),
                    createVNode("div", { class: "mt-6" }, [
                      createVNode(unref(Link), {
                        href: _ctx.route("dashboard"),
                        class: "text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" ← Return to your dashboard ")
                        ]),
                        _: 1
                      }, 8, ["href"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Store/Unavailable.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
