import { computed, ref, unref, withCtx, createTextVNode, createBlock, openBlock, createVNode, createCommentVNode, toDisplayString, Fragment, renderList, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderList } from "vue/server-renderer";
import axios from "axios";
import { _ as _sfc_main$1 } from "./AuthenticatedLayout-DnHFEBKh.js";
import { P as PrimaryButton } from "./PrimaryButton-CIooT64n.js";
import { _ as _sfc_main$2 } from "./InputError-fLcttu_2.js";
import { Head, Link } from "@inertiajs/vue3";
import "./ApplicationLogo-B2173abF.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "CheckoutPay",
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
    const formatCurrency = (amount) => currencyFormatter.format(Number(amount) || 0);
    const isPaid = computed(() => props.order.payment_status === "paid");
    const formatAddress = (address) => {
      if (!address) {
        return "";
      }
      return [address.line1, address.line2, [address.city, address.state, address.postal_code].filter(Boolean).join(", "), address.country].filter(Boolean).join("\n");
    };
    const redirecting = ref(false);
    const cardError = ref(null);
    const payWithCard = async () => {
      if (redirecting.value) {
        return;
      }
      redirecting.value = true;
      cardError.value = null;
      try {
        const response = await axios.post(`/api/v1/orders/${props.order.id}/checkout`);
        window.location.href = response.data.checkout_url;
      } catch (error) {
        if (error.response?.status === 422) {
          cardError.value = error.response.data.errors?.order?.[0] ?? "Unable to start checkout. Please try again.";
        } else {
          cardError.value = "Unable to start checkout. Please try again.";
        }
        redirecting.value = false;
      }
    };
    const redirectingPaypal = ref(false);
    const paypalError = ref(null);
    const payWithPaypal = async () => {
      if (redirectingPaypal.value) {
        return;
      }
      redirectingPaypal.value = true;
      paypalError.value = null;
      try {
        const response = await axios.post(`/api/v1/orders/${props.order.id}/checkout/paypal`);
        window.location.href = response.data.approve_url;
      } catch (error) {
        if (error.response?.status === 422) {
          paypalError.value = error.response.data.errors?.order?.[0] ?? "Unable to start PayPal checkout. Please try again.";
        } else {
          paypalError.value = "Unable to start PayPal checkout. Please try again.";
        }
        redirectingPaypal.value = false;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Checkout" }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-gray-50 min-h-screen"${_scopeId}><div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"${_scopeId}><div${_scopeId}><p class="text-sm text-gray-500 uppercase tracking-wide"${_scopeId}>Checkout</p><h1 class="text-2xl font-semibold text-gray-900"${_scopeId}>Order #${ssrInterpolate(__props.order.order_number)}</h1></div>`);
            if (isPaid.value) {
              _push2(`<div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800"${_scopeId}> This order has already been paid. `);
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("store.checkout.success", __props.order.id),
                class: "font-semibold underline"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` View confirmation `);
                  } else {
                    return [
                      createTextVNode(" View confirmation ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"${_scopeId}><div class="lg:col-span-2 space-y-6"${_scopeId}><div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4"${_scopeId}><h2 class="text-lg font-semibold text-gray-900"${_scopeId}>Order Items</h2><div class="divide-y divide-gray-100"${_scopeId}><!--[-->`);
            ssrRenderList(__props.order.items, (item) => {
              _push2(`<div class="flex items-center justify-between py-2 text-sm"${_scopeId}><div${_scopeId}><p class="font-medium text-gray-900"${_scopeId}>${ssrInterpolate(item.name)}</p><p class="text-xs text-gray-500"${_scopeId}>Qty ${ssrInterpolate(item.quantity)} × ${ssrInterpolate(formatCurrency(item.unit_price))}</p></div><p class="font-semibold text-gray-900"${_scopeId}>${ssrInterpolate(formatCurrency(item.line_total))}</p></div>`);
            });
            _push2(`<!--]--></div><div class="border-t border-gray-200 pt-4 flex items-center justify-between"${_scopeId}><p class="text-sm font-semibold text-gray-700"${_scopeId}>Order Total</p><p class="text-xl font-bold text-gray-900"${_scopeId}>${ssrInterpolate(formatCurrency(__props.order.grand_total))}</p></div></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-6"${_scopeId}><div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"${_scopeId}><h3 class="text-sm font-semibold text-gray-900 mb-2"${_scopeId}>Billing Address</h3><p class="text-sm text-gray-600 whitespace-pre-line"${_scopeId}>${ssrInterpolate(formatAddress(__props.order.billing_address))}</p></div><div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"${_scopeId}><h3 class="text-sm font-semibold text-gray-900 mb-2"${_scopeId}>Shipping Address</h3><p class="text-sm text-gray-600 whitespace-pre-line"${_scopeId}>${ssrInterpolate(formatAddress(__props.order.shipping_address))}</p></div></div></div><div class="lg:col-span-1"${_scopeId}><div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4 sticky top-24"${_scopeId}><h2 class="text-lg font-semibold text-gray-900"${_scopeId}>Choose a Payment Method</h2><div id="payment-methods" class="space-y-3"${_scopeId}>`);
            _push2(ssrRenderComponent(PrimaryButton, {
              type: "button",
              class: "w-full justify-center",
              disabled: redirecting.value || isPaid.value,
              onClick: payWithCard
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  if (redirecting.value) {
                    _push3(`<span${_scopeId2}>Redirecting to Stripe…</span>`);
                  } else {
                    _push3(`<span${_scopeId2}>Pay with Card</span>`);
                  }
                } else {
                  return [
                    redirecting.value ? (openBlock(), createBlock("span", { key: 0 }, "Redirecting to Stripe…")) : (openBlock(), createBlock("span", { key: 1 }, "Pay with Card"))
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$2, { message: cardError.value }, null, _parent2, _scopeId));
            _push2(`<p class="text-xs text-gray-500"${_scopeId}> Google Pay and Apple Pay are available automatically on the Stripe payment page when supported by your browser and device. </p>`);
            _push2(ssrRenderComponent(PrimaryButton, {
              type: "button",
              class: "w-full justify-center bg-[#ffc439] hover:bg-[#f0b932] text-gray-900 focus:bg-[#f0b932] focus:ring-[#ffc439] active:bg-[#f0b932]",
              disabled: redirectingPaypal.value || isPaid.value,
              onClick: payWithPaypal
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  if (redirectingPaypal.value) {
                    _push3(`<span${_scopeId2}>Redirecting to PayPal…</span>`);
                  } else {
                    _push3(`<span${_scopeId2}>Pay with PayPal</span>`);
                  }
                } else {
                  return [
                    redirectingPaypal.value ? (openBlock(), createBlock("span", { key: 0 }, "Redirecting to PayPal…")) : (openBlock(), createBlock("span", { key: 1 }, "Pay with PayPal"))
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$2, { message: paypalError.value }, null, _parent2, _scopeId));
            _push2(`</div></div></div></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "bg-gray-50 min-h-screen" }, [
                createVNode("div", { class: "max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" }, [
                  createVNode("div", null, [
                    createVNode("p", { class: "text-sm text-gray-500 uppercase tracking-wide" }, "Checkout"),
                    createVNode("h1", { class: "text-2xl font-semibold text-gray-900" }, "Order #" + toDisplayString(__props.order.order_number), 1)
                  ]),
                  isPaid.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800"
                  }, [
                    createTextVNode(" This order has already been paid. "),
                    createVNode(unref(Link), {
                      href: _ctx.route("store.checkout.success", __props.order.id),
                      class: "font-semibold underline"
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" View confirmation ")
                      ]),
                      _: 1
                    }, 8, ["href"])
                  ])) : createCommentVNode("", true),
                  createVNode("div", { class: "grid grid-cols-1 lg:grid-cols-3 gap-6" }, [
                    createVNode("div", { class: "lg:col-span-2 space-y-6" }, [
                      createVNode("div", { class: "bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4" }, [
                        createVNode("h2", { class: "text-lg font-semibold text-gray-900" }, "Order Items"),
                        createVNode("div", { class: "divide-y divide-gray-100" }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(__props.order.items, (item) => {
                            return openBlock(), createBlock("div", {
                              key: item.id,
                              class: "flex items-center justify-between py-2 text-sm"
                            }, [
                              createVNode("div", null, [
                                createVNode("p", { class: "font-medium text-gray-900" }, toDisplayString(item.name), 1),
                                createVNode("p", { class: "text-xs text-gray-500" }, "Qty " + toDisplayString(item.quantity) + " × " + toDisplayString(formatCurrency(item.unit_price)), 1)
                              ]),
                              createVNode("p", { class: "font-semibold text-gray-900" }, toDisplayString(formatCurrency(item.line_total)), 1)
                            ]);
                          }), 128))
                        ]),
                        createVNode("div", { class: "border-t border-gray-200 pt-4 flex items-center justify-between" }, [
                          createVNode("p", { class: "text-sm font-semibold text-gray-700" }, "Order Total"),
                          createVNode("p", { class: "text-xl font-bold text-gray-900" }, toDisplayString(formatCurrency(__props.order.grand_total)), 1)
                        ])
                      ]),
                      createVNode("div", { class: "grid grid-cols-1 sm:grid-cols-2 gap-6" }, [
                        createVNode("div", { class: "bg-white rounded-lg shadow-sm border border-gray-200 p-6" }, [
                          createVNode("h3", { class: "text-sm font-semibold text-gray-900 mb-2" }, "Billing Address"),
                          createVNode("p", { class: "text-sm text-gray-600 whitespace-pre-line" }, toDisplayString(formatAddress(__props.order.billing_address)), 1)
                        ]),
                        createVNode("div", { class: "bg-white rounded-lg shadow-sm border border-gray-200 p-6" }, [
                          createVNode("h3", { class: "text-sm font-semibold text-gray-900 mb-2" }, "Shipping Address"),
                          createVNode("p", { class: "text-sm text-gray-600 whitespace-pre-line" }, toDisplayString(formatAddress(__props.order.shipping_address)), 1)
                        ])
                      ])
                    ]),
                    createVNode("div", { class: "lg:col-span-1" }, [
                      createVNode("div", { class: "bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4 sticky top-24" }, [
                        createVNode("h2", { class: "text-lg font-semibold text-gray-900" }, "Choose a Payment Method"),
                        createVNode("div", {
                          id: "payment-methods",
                          class: "space-y-3"
                        }, [
                          createVNode(PrimaryButton, {
                            type: "button",
                            class: "w-full justify-center",
                            disabled: redirecting.value || isPaid.value,
                            onClick: payWithCard
                          }, {
                            default: withCtx(() => [
                              redirecting.value ? (openBlock(), createBlock("span", { key: 0 }, "Redirecting to Stripe…")) : (openBlock(), createBlock("span", { key: 1 }, "Pay with Card"))
                            ]),
                            _: 1
                          }, 8, ["disabled"]),
                          createVNode(_sfc_main$2, { message: cardError.value }, null, 8, ["message"]),
                          createVNode("p", { class: "text-xs text-gray-500" }, " Google Pay and Apple Pay are available automatically on the Stripe payment page when supported by your browser and device. "),
                          createVNode(PrimaryButton, {
                            type: "button",
                            class: "w-full justify-center bg-[#ffc439] hover:bg-[#f0b932] text-gray-900 focus:bg-[#f0b932] focus:ring-[#ffc439] active:bg-[#f0b932]",
                            disabled: redirectingPaypal.value || isPaid.value,
                            onClick: payWithPaypal
                          }, {
                            default: withCtx(() => [
                              redirectingPaypal.value ? (openBlock(), createBlock("span", { key: 0 }, "Redirecting to PayPal…")) : (openBlock(), createBlock("span", { key: 1 }, "Pay with PayPal"))
                            ]),
                            _: 1
                          }, 8, ["disabled"]),
                          createVNode(_sfc_main$2, { message: paypalError.value }, null, 8, ["message"])
                        ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Store/CheckoutPay.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
