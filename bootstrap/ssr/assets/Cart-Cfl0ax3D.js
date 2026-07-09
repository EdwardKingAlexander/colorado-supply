import { computed, unref, withCtx, createTextVNode, createVNode, createBlock, toDisplayString, openBlock, Fragment, renderList, createCommentVNode, withDirectives, vModelSelect, useSSRContext } from "vue";
import { ssrRenderComponent, ssrIncludeBooleanAttr, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AuthenticatedLayout-BaKoCKvn.js";
import { Head, Link } from "@inertiajs/vue3";
import { u as useCartStore } from "./useCartStore-OHIRORWN.js";
import "./ApplicationLogo-B2173abF.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "Cart",
  __ssrInlineRender: true,
  props: {
    locations: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const cartStore = useCartStore();
    const props = __props;
    const locations = computed(() => props.locations ?? []);
    const hasItems = computed(() => cartStore.items.length > 0);
    const itemCount = computed(() => cartStore.itemCount.value);
    const currencyFormatter = new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD"
    });
    const formatCurrency = (amount) => currencyFormatter.format(Number(amount) || 0);
    const totalDisplay = computed(() => formatCurrency(cartStore.total.value));
    const locationNameById = computed(
      () => locations.value.reduce(
        (acc, location) => {
          acc[location.id] = location.name;
          return acc;
        },
        { 0: "Main Store" }
      )
    );
    const groupedItems = computed(() => {
      const groups = {};
      cartStore.items.forEach((item) => {
        const locationId = item.location_id || 0;
        if (!groups[locationId]) {
          groups[locationId] = [];
        }
        groups[locationId].push(item);
      });
      return groups;
    });
    const handleRemove = (id) => cartStore.removeItem(id);
    const handleClear = () => cartStore.clearCart();
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Cart" }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-gray-50 min-h-screen"${_scopeId}><div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"${_scopeId}><div class="flex items-center justify-between"${_scopeId}><div${_scopeId}><p class="text-sm text-gray-500 uppercase tracking-wide"${_scopeId}>Cart Summary</p><h1 class="text-2xl font-semibold text-gray-900"${_scopeId}>Your Cart</h1></div><div class="flex items-center gap-3"${_scopeId}><button type="button" class="px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-60"${ssrIncludeBooleanAttr(!hasItems.value) ? " disabled" : ""}${_scopeId}> Clear All </button>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: "/store/checkout",
              class: ["px-4 py-2 text-sm font-semibold text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors disabled:opacity-60 disabled:cursor-not-allowed", { "pointer-events-none opacity-60": !hasItems.value }],
              tabindex: !hasItems.value ? -1 : void 0,
              "aria-disabled": !hasItems.value
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Proceed to Checkout `);
                } else {
                  return [
                    createTextVNode(" Proceed to Checkout ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div><div class="bg-white rounded-lg shadow-sm border border-gray-200"${_scopeId}><div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between"${_scopeId}><div${_scopeId}><p class="text-sm text-gray-500"${_scopeId}>Items</p><p class="text-lg font-semibold text-gray-900"${_scopeId}>${ssrInterpolate(itemCount.value)} total</p></div><div class="text-right"${_scopeId}><p class="text-sm text-gray-500"${_scopeId}>Cart Total</p><p class="text-2xl font-semibold text-gray-900"${_scopeId}>${ssrInterpolate(totalDisplay.value)}</p></div></div>`);
            if (hasItems.value) {
              _push2(`<div class="divide-y divide-gray-100"${_scopeId}><div class="hidden md:grid md:grid-cols-12 px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider"${_scopeId}><div class="col-span-4"${_scopeId}>Item</div><div class="col-span-2 text-right"${_scopeId}>Unit Price</div><div class="col-span-2 text-right"${_scopeId}>Quantity</div><div class="col-span-2 text-right"${_scopeId}>Subtotal</div><div class="col-span-1 text-right"${_scopeId}>Location</div><div class="col-span-1 text-right"${_scopeId}></div></div><!--[-->`);
              ssrRenderList(groupedItems.value, (group, locationId) => {
                _push2(`<!--[--><div class="px-6 py-2 bg-gray-50 text-sm font-semibold text-gray-700 col-span-full"${_scopeId}>${ssrInterpolate(locationNameById.value[locationId])}</div><!--[-->`);
                ssrRenderList(group, (item) => {
                  _push2(`<div class="px-6 py-4 grid grid-cols-1 gap-3 md:grid-cols-12 md:items-center text-sm text-gray-800"${_scopeId}><div class="md:col-span-4"${_scopeId}><p class="font-medium text-gray-900"${_scopeId}>${ssrInterpolate(item.name)}</p>`);
                  if (item.slug) {
                    _push2(`<p class="text-xs text-gray-500 mt-1"${_scopeId}> SKU: ${ssrInterpolate(item.slug)}</p>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div><div class="md:col-span-2 text-gray-600 md:text-right"${_scopeId}>${ssrInterpolate(formatCurrency(item.price))}</div><div class="md:col-span-2 md:text-right"${_scopeId}><div class="flex items-center gap-2 md:justify-end"${_scopeId}><button type="button" class="px-2 py-1 border border-gray-300 rounded text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"${ssrIncludeBooleanAttr(item.quantity <= 1) ? " disabled" : ""}${_scopeId}> − </button><span class="w-8 text-center font-semibold text-gray-900"${_scopeId}>${ssrInterpolate(item.quantity)}</span><button type="button" class="px-2 py-1 border border-gray-300 rounded text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"${_scopeId}> + </button></div></div><div class="md:col-span-2 font-semibold text-gray-900 md:text-right"${_scopeId}>${ssrInterpolate(formatCurrency(item.price * item.quantity))}</div><div class="md:col-span-1 md:text-right"${_scopeId}><select class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"${_scopeId}><option${ssrRenderAttr("value", null)}${ssrIncludeBooleanAttr(Array.isArray(item.location_id) ? ssrLooseContain(item.location_id, null) : ssrLooseEqual(item.location_id, null)) ? " selected" : ""}${_scopeId}>Main Store</option><!--[-->`);
                  ssrRenderList(locations.value, (loc) => {
                    _push2(`<option${ssrRenderAttr("value", loc.id)}${ssrIncludeBooleanAttr(Array.isArray(item.location_id) ? ssrLooseContain(item.location_id, loc.id) : ssrLooseEqual(item.location_id, loc.id)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(loc.name)}</option>`);
                  });
                  _push2(`<!--]--></select></div><div class="md:col-span-1 md:text-right"${_scopeId}><button type="button" class="text-xs font-semibold text-gray-500 hover:text-gray-900"${_scopeId}> Remove </button></div></div>`);
                });
                _push2(`<!--]--><!--]-->`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<div class="px-6 py-16 text-center text-sm text-gray-500"${_scopeId}><p class="font-medium text-gray-700"${_scopeId}>Your cart is empty.</p><p class="mt-2"${_scopeId}>Browse the catalog and add items to your cart.</p>`);
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("store.index"),
                class: "inline-flex items-center justify-center mt-6 px-5 py-2 text-sm font-semibold text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
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
              _push2(`</div>`);
            }
            _push2(`</div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "bg-gray-50 min-h-screen" }, [
                createVNode("div", { class: "max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" }, [
                  createVNode("div", { class: "flex items-center justify-between" }, [
                    createVNode("div", null, [
                      createVNode("p", { class: "text-sm text-gray-500 uppercase tracking-wide" }, "Cart Summary"),
                      createVNode("h1", { class: "text-2xl font-semibold text-gray-900" }, "Your Cart")
                    ]),
                    createVNode("div", { class: "flex items-center gap-3" }, [
                      createVNode("button", {
                        type: "button",
                        class: "px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-60",
                        disabled: !hasItems.value,
                        onClick: handleClear
                      }, " Clear All ", 8, ["disabled"]),
                      createVNode(unref(Link), {
                        href: "/store/checkout",
                        class: ["px-4 py-2 text-sm font-semibold text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors disabled:opacity-60 disabled:cursor-not-allowed", { "pointer-events-none opacity-60": !hasItems.value }],
                        tabindex: !hasItems.value ? -1 : void 0,
                        "aria-disabled": !hasItems.value
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Proceed to Checkout ")
                        ]),
                        _: 1
                      }, 8, ["class", "tabindex", "aria-disabled"])
                    ])
                  ]),
                  createVNode("div", { class: "bg-white rounded-lg shadow-sm border border-gray-200" }, [
                    createVNode("div", { class: "border-b border-gray-200 px-6 py-4 flex items-center justify-between" }, [
                      createVNode("div", null, [
                        createVNode("p", { class: "text-sm text-gray-500" }, "Items"),
                        createVNode("p", { class: "text-lg font-semibold text-gray-900" }, toDisplayString(itemCount.value) + " total", 1)
                      ]),
                      createVNode("div", { class: "text-right" }, [
                        createVNode("p", { class: "text-sm text-gray-500" }, "Cart Total"),
                        createVNode("p", { class: "text-2xl font-semibold text-gray-900" }, toDisplayString(totalDisplay.value), 1)
                      ])
                    ]),
                    hasItems.value ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "divide-y divide-gray-100"
                    }, [
                      createVNode("div", { class: "hidden md:grid md:grid-cols-12 px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider" }, [
                        createVNode("div", { class: "col-span-4" }, "Item"),
                        createVNode("div", { class: "col-span-2 text-right" }, "Unit Price"),
                        createVNode("div", { class: "col-span-2 text-right" }, "Quantity"),
                        createVNode("div", { class: "col-span-2 text-right" }, "Subtotal"),
                        createVNode("div", { class: "col-span-1 text-right" }, "Location"),
                        createVNode("div", { class: "col-span-1 text-right" })
                      ]),
                      (openBlock(true), createBlock(Fragment, null, renderList(groupedItems.value, (group, locationId) => {
                        return openBlock(), createBlock(Fragment, { key: locationId }, [
                          createVNode("div", { class: "px-6 py-2 bg-gray-50 text-sm font-semibold text-gray-700 col-span-full" }, toDisplayString(locationNameById.value[locationId]), 1),
                          (openBlock(true), createBlock(Fragment, null, renderList(group, (item) => {
                            return openBlock(), createBlock("div", {
                              key: item.id,
                              class: "px-6 py-4 grid grid-cols-1 gap-3 md:grid-cols-12 md:items-center text-sm text-gray-800"
                            }, [
                              createVNode("div", { class: "md:col-span-4" }, [
                                createVNode("p", { class: "font-medium text-gray-900" }, toDisplayString(item.name), 1),
                                item.slug ? (openBlock(), createBlock("p", {
                                  key: 0,
                                  class: "text-xs text-gray-500 mt-1"
                                }, " SKU: " + toDisplayString(item.slug), 1)) : createCommentVNode("", true)
                              ]),
                              createVNode("div", { class: "md:col-span-2 text-gray-600 md:text-right" }, toDisplayString(formatCurrency(item.price)), 1),
                              createVNode("div", { class: "md:col-span-2 md:text-right" }, [
                                createVNode("div", { class: "flex items-center gap-2 md:justify-end" }, [
                                  createVNode("button", {
                                    type: "button",
                                    class: "px-2 py-1 border border-gray-300 rounded text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed",
                                    disabled: item.quantity <= 1,
                                    onClick: ($event) => unref(cartStore).decrementQuantity(item.id)
                                  }, " − ", 8, ["disabled", "onClick"]),
                                  createVNode("span", { class: "w-8 text-center font-semibold text-gray-900" }, toDisplayString(item.quantity), 1),
                                  createVNode("button", {
                                    type: "button",
                                    class: "px-2 py-1 border border-gray-300 rounded text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors",
                                    onClick: ($event) => unref(cartStore).incrementQuantity(item.id)
                                  }, " + ", 8, ["onClick"])
                                ])
                              ]),
                              createVNode("div", { class: "md:col-span-2 font-semibold text-gray-900 md:text-right" }, toDisplayString(formatCurrency(item.price * item.quantity)), 1),
                              createVNode("div", { class: "md:col-span-1 md:text-right" }, [
                                withDirectives(createVNode("select", {
                                  "onUpdate:modelValue": ($event) => item.location_id = $event,
                                  onChange: ($event) => unref(cartStore).updateItemLocation(item.id, item.location_id),
                                  class: "block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                }, [
                                  createVNode("option", { value: null }, "Main Store"),
                                  (openBlock(true), createBlock(Fragment, null, renderList(locations.value, (loc) => {
                                    return openBlock(), createBlock("option", {
                                      key: loc.id,
                                      value: loc.id
                                    }, toDisplayString(loc.name), 9, ["value"]);
                                  }), 128))
                                ], 40, ["onUpdate:modelValue", "onChange"]), [
                                  [vModelSelect, item.location_id]
                                ])
                              ]),
                              createVNode("div", { class: "md:col-span-1 md:text-right" }, [
                                createVNode("button", {
                                  type: "button",
                                  class: "text-xs font-semibold text-gray-500 hover:text-gray-900",
                                  onClick: ($event) => handleRemove(item.id)
                                }, " Remove ", 8, ["onClick"])
                              ])
                            ]);
                          }), 128))
                        ], 64);
                      }), 128))
                    ])) : (openBlock(), createBlock("div", {
                      key: 1,
                      class: "px-6 py-16 text-center text-sm text-gray-500"
                    }, [
                      createVNode("p", { class: "font-medium text-gray-700" }, "Your cart is empty."),
                      createVNode("p", { class: "mt-2" }, "Browse the catalog and add items to your cart."),
                      createVNode(unref(Link), {
                        href: _ctx.route("store.index"),
                        class: "inline-flex items-center justify-center mt-6 px-5 py-2 text-sm font-semibold text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Return to Store ")
                        ]),
                        _: 1
                      }, 8, ["href"])
                    ]))
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Store/Cart.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
