import { computed, mergeProps, unref, withCtx, createVNode, toDisplayString, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderList } from "vue/server-renderer";
import { Head } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "OrderTracker",
  __ssrInlineRender: true,
  props: {
    order: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const currency = new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" });
    const steps = computed(() => {
      const paid = props.order.payment_status.value === "paid";
      const shipped = ["partially_fulfilled", "fulfilled"].includes(props.order.fulfillment_status.value) || props.order.shipments.length > 0;
      const fulfilled = props.order.fulfillment_status.value === "fulfilled";
      return [
        { label: "Order placed", detail: props.order.placed_at, done: true },
        { label: "Payment received", detail: props.order.payment_status.label, done: paid },
        { label: "Shipped", detail: props.order.shipments[0]?.carrier ?? null, done: shipped },
        { label: "Order complete", detail: null, done: fulfilled }
      ];
    });
    const isCancelled = computed(() => props.order.status.value === "cancelled");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 dark:bg-gray-900" }, _attrs))}>`);
      _push(ssrRenderComponent(unref(Head), null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<title${_scopeId}>Track Order ${ssrInterpolate(__props.order.order_number)} | Colorado Supply &amp; Procurement</title>`);
          } else {
            return [
              createVNode("title", null, "Track Order " + toDisplayString(__props.order.order_number) + " | Colorado Supply & Procurement", 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<header class="bg-gray-900 py-5"><div class="mx-auto flex max-w-3xl items-center justify-between px-4 sm:px-6"><span class="text-base font-bold tracking-wide text-gray-100"> COLORADO <span class="text-amber-400">SUPPLY &amp; PROCUREMENT</span></span><a href="/" class="text-sm font-semibold text-gray-200 hover:text-amber-300">Home</a></div></header><main class="mx-auto max-w-3xl px-4 py-10 sm:px-6"><div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800 sm:p-8"><div class="flex flex-wrap items-start justify-between gap-3"><div><h1 class="text-2xl font-bold text-gray-900 dark:text-white"> Order ${ssrInterpolate(__props.order.order_number)}</h1><p class="mt-1 text-base text-gray-600 dark:text-gray-300"> Placed ${ssrInterpolate(__props.order.placed_at)}</p></div><span class="${ssrRenderClass([isCancelled.value ? "bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300" : "bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300", "rounded-full px-3 py-1 text-sm font-semibold"])}">${ssrInterpolate(__props.order.status.label)}</span></div>`);
      if (!isCancelled.value) {
        _push(`<ol class="mt-8 space-y-4" aria-label="Order progress"><!--[-->`);
        ssrRenderList(steps.value, (step, index) => {
          _push(`<li class="flex items-start gap-3"><span class="${ssrRenderClass([step.done ? "bg-green-600 text-white" : "border-2 border-gray-300 bg-white text-gray-400 dark:border-gray-600 dark:bg-gray-800", "mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"])}">`);
          if (step.done) {
            _push(`<!--[-->✓<!--]-->`);
          } else {
            _push(`<!--[-->${ssrInterpolate(index + 1)}<!--]-->`);
          }
          _push(`</span><div><p class="${ssrRenderClass([step.done ? "text-gray-900 dark:text-white" : "text-gray-500 dark:text-gray-400", "text-base font-semibold"])}">${ssrInterpolate(step.label)}</p>`);
          if (step.detail && step.done) {
            _push(`<p class="text-sm text-gray-500 dark:text-gray-400">${ssrInterpolate(step.detail)}</p>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></li>`);
        });
        _push(`<!--]--></ol>`);
      } else {
        _push(`<p class="mt-6 text-base text-gray-600 dark:text-gray-300"> This order has been cancelled. If you have questions, reply to your order email and our team will assist. </p>`);
      }
      if (__props.order.shipments.length) {
        _push(`<div class="mt-8"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Shipments</h2><ul class="mt-2 divide-y divide-gray-100 dark:divide-gray-700"><!--[-->`);
        ssrRenderList(__props.order.shipments, (shipment, index) => {
          _push(`<li class="flex flex-wrap items-center justify-between gap-2 py-3 text-base"><span class="text-gray-700 dark:text-gray-200">${ssrInterpolate(shipment.carrier || "Carrier pending")} `);
          if (shipment.shipped_at) {
            _push(`<span class="text-sm text-gray-500 dark:text-gray-400"> — ${ssrInterpolate(shipment.shipped_at)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</span>`);
          if (shipment.tracking_number) {
            _push(`<span class="font-mono text-sm text-gray-900 dark:text-gray-100">${ssrInterpolate(shipment.tracking_number)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</li>`);
        });
        _push(`<!--]--></ul></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="mt-8"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Items</h2><ul class="mt-2 divide-y divide-gray-100 dark:divide-gray-700"><!--[-->`);
      ssrRenderList(__props.order.items, (item, index) => {
        _push(`<li class="flex items-center justify-between gap-4 py-3 text-base"><span class="text-gray-700 dark:text-gray-200">${ssrInterpolate(item.name)} <span class="text-sm text-gray-500 dark:text-gray-400">× ${ssrInterpolate(item.quantity)}</span></span><span class="shrink-0 text-gray-900 dark:text-gray-100">${ssrInterpolate(unref(currency).format(item.line_total))}</span></li>`);
      });
      _push(`<!--]--></ul><dl class="mt-4 space-y-1 border-t border-gray-200 pt-4 text-base dark:border-gray-700"><div class="flex justify-between text-gray-600 dark:text-gray-300"><dt>Subtotal</dt><dd>${ssrInterpolate(unref(currency).format(__props.order.subtotal))}</dd></div>`);
      if (__props.order.shipping_total) {
        _push(`<div class="flex justify-between text-gray-600 dark:text-gray-300"><dt>Shipping</dt><dd>${ssrInterpolate(unref(currency).format(__props.order.shipping_total))}</dd></div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.order.tax_total) {
        _push(`<div class="flex justify-between text-gray-600 dark:text-gray-300"><dt>Tax</dt><dd>${ssrInterpolate(unref(currency).format(__props.order.tax_total))}</dd></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="flex justify-between font-bold text-gray-900 dark:text-white"><dt>Total</dt><dd>${ssrInterpolate(unref(currency).format(__props.order.grand_total))}</dd></div></dl></div></div><p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400"> Questions about this order? Reply to your order confirmation email and our team will assist. </p></main></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Store/OrderTracker.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
