import { computed, mergeProps, withCtx, unref, createVNode, createTextVNode, createBlock, toDisplayString, openBlock, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BeNNp45Q.js";
import { Head, Link } from "@inertiajs/vue3";
import "./CookieConsentBanner-ByAlkSbo.js";
import "@headlessui/vue";
import "axios";
import "@heroicons/vue/24/outline";
import "./logo-cleansed-light-B5mBHTsK.js";
const _sfc_main = {
  __name: "CheckoutSuccess",
  __ssrInlineRender: true,
  props: {
    order: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const currencyFormatter = new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD"
    });
    const totalDisplay = computed(() => currencyFormatter.format(Number(props.order.grand_total) || 0));
    const isPaid = computed(() => props.order.payment_status === "paid");
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, mergeProps({
        appName: _ctx.$page.props.appName
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Head), null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<title${_scopeId2}>Payment | Colorado Supply &amp; Procurement</title>`);
                } else {
                  return [
                    createVNode("title", null, "Payment | Colorado Supply & Procurement")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 pb-12 pt-28 dark:bg-gray-800 sm:px-6"${_scopeId}><div class="w-full max-w-md rounded-lg bg-white p-6 text-center shadow-md dark:bg-gray-700 sm:p-8"${_scopeId}><h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"${_scopeId}> Thank you for your order </h1><p class="mb-6 text-base leading-6 text-gray-600 dark:text-gray-300"${_scopeId}> Order <span class="font-semibold"${_scopeId}>${ssrInterpolate(__props.order.order_number)}</span> — ${ssrInterpolate(totalDisplay.value)}</p>`);
            if (isPaid.value) {
              _push2(`<p class="text-green-600 dark:text-green-400 font-medium mb-6"${_scopeId}> Payment confirmed. </p>`);
            } else {
              _push2(`<p class="text-amber-600 dark:text-amber-400 font-medium mb-6"${_scopeId}> We&#39;re confirming your payment now. You&#39;ll receive an email once it&#39;s complete. </p>`);
            }
            _push2(ssrRenderComponent(unref(Link), {
              href: "/store",
              class: "inline-flex min-h-12 w-full items-center justify-center rounded-md bg-blue-700 px-6 py-3 text-base font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Continue Shopping `);
                } else {
                  return [
                    createTextVNode(" Continue Shopping ")
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
                  createVNode("title", null, "Payment | Colorado Supply & Procurement")
                ]),
                _: 1
              }),
              createVNode("div", { class: "flex min-h-screen items-center justify-center bg-gray-50 px-4 pb-12 pt-28 dark:bg-gray-800 sm:px-6" }, [
                createVNode("div", { class: "w-full max-w-md rounded-lg bg-white p-6 text-center shadow-md dark:bg-gray-700 sm:p-8" }, [
                  createVNode("h1", { class: "text-2xl font-bold text-gray-900 dark:text-white mb-2" }, " Thank you for your order "),
                  createVNode("p", { class: "mb-6 text-base leading-6 text-gray-600 dark:text-gray-300" }, [
                    createTextVNode(" Order "),
                    createVNode("span", { class: "font-semibold" }, toDisplayString(__props.order.order_number), 1),
                    createTextVNode(" — " + toDisplayString(totalDisplay.value), 1)
                  ]),
                  isPaid.value ? (openBlock(), createBlock("p", {
                    key: 0,
                    class: "text-green-600 dark:text-green-400 font-medium mb-6"
                  }, " Payment confirmed. ")) : (openBlock(), createBlock("p", {
                    key: 1,
                    class: "text-amber-600 dark:text-amber-400 font-medium mb-6"
                  }, " We're confirming your payment now. You'll receive an email once it's complete. ")),
                  createVNode(unref(Link), {
                    href: "/store",
                    class: "inline-flex min-h-12 w-full items-center justify-center rounded-md bg-blue-700 px-6 py-3 text-base font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" Continue Shopping ")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Store/CheckoutSuccess.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
