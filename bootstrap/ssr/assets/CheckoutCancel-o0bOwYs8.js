import { mergeProps, withCtx, unref, createVNode, createTextVNode, toDisplayString, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-DAY7LMhN.js";
import { Head, Link } from "@inertiajs/vue3";
import "@headlessui/vue";
import "@heroicons/vue/24/outline";
const _sfc_main = {
  __name: "CheckoutCancel",
  __ssrInlineRender: true,
  props: {
    order: {
      type: Object,
      required: true
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
                  _push3(`<title${_scopeId2}>Checkout Cancelled | Colorado Supply &amp; Procurement</title>`);
                } else {
                  return [
                    createVNode("title", null, "Checkout Cancelled | Colorado Supply & Procurement")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="min-h-screen bg-gray-50 dark:bg-gray-800 flex items-center justify-center px-4"${_scopeId}><div class="max-w-md w-full bg-white dark:bg-gray-700 rounded-lg shadow-md p-8 text-center"${_scopeId}><h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"${_scopeId}> Checkout Cancelled </h1><p class="text-gray-600 dark:text-gray-300 mb-6"${_scopeId}> Order <span class="font-semibold"${_scopeId}>${ssrInterpolate(__props.order.order_number)}</span> has not been charged. You can try again whenever you&#39;re ready. </p>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: "/store",
              class: "inline-block px-6 py-2 bg-blue-700 text-white rounded-md hover:bg-blue-800"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Return to Store `);
                } else {
                  return [
                    createTextVNode(" Return to Store ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div>`);
          } else {
            return [
              createVNode(unref(Head), null, {
                default: withCtx(() => [
                  createVNode("title", null, "Checkout Cancelled | Colorado Supply & Procurement")
                ]),
                _: 1
              }),
              createVNode("div", { class: "min-h-screen bg-gray-50 dark:bg-gray-800 flex items-center justify-center px-4" }, [
                createVNode("div", { class: "max-w-md w-full bg-white dark:bg-gray-700 rounded-lg shadow-md p-8 text-center" }, [
                  createVNode("h1", { class: "text-2xl font-bold text-gray-900 dark:text-white mb-2" }, " Checkout Cancelled "),
                  createVNode("p", { class: "text-gray-600 dark:text-gray-300 mb-6" }, [
                    createTextVNode(" Order "),
                    createVNode("span", { class: "font-semibold" }, toDisplayString(__props.order.order_number), 1),
                    createTextVNode(" has not been charged. You can try again whenever you're ready. ")
                  ]),
                  createVNode(unref(Link), {
                    href: "/store",
                    class: "inline-block px-6 py-2 bg-blue-700 text-white rounded-md hover:bg-blue-800"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" Return to Store ")
                    ]),
                    _: 1
                  })
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Store/CheckoutCancel.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
