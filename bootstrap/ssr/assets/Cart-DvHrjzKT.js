import { computed, unref, withCtx, createTextVNode, toDisplayString, createVNode, createBlock, openBlock, Fragment, renderList, createCommentVNode, withDirectives, vModelSelect, useSSRContext } from "vue";
import { ssrRenderComponent, ssrIncludeBooleanAttr, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { u as useCartStore, _ as _sfc_main$1 } from "./StoreLayout-UuGiv97H.js";
import { Head, Link } from "@inertiajs/vue3";
import "@headlessui/vue";
import "./CookieConsentBanner-ByAlkSbo.js";
import "axios";
import "./logo-cleansed-light-B5mBHTsK.js";
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
    const productUrl = (item) => item.url ?? route("store.product", { product: item.productId ?? item.id, slug: item.slug ?? void 0 });
    const handleRemove = (id) => cartStore.removeItem(id);
    const handleClear = () => cartStore.clearCart();
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Cart" }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-st-bg min-h-screen"${_scopeId}><div class="mobile-page-gutter mx-auto max-w-5xl space-y-6 py-6 sm:py-8 lg:px-8"${_scopeId}><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"${_scopeId}><div${_scopeId}><h1 class="st-display text-2xl font-extrabold text-st-ink sm:text-3xl"${_scopeId}>Your cart</h1></div><div class="grid grid-cols-1 gap-2 xs:grid-cols-2 sm:flex sm:items-center sm:gap-3"${_scopeId}><button type="button" class="inline-flex min-h-12 items-center justify-center rounded-md border border-st-line px-4 py-3 text-base font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action disabled:opacity-60"${ssrIncludeBooleanAttr(!hasItems.value) ? " disabled" : ""}${_scopeId}> Clear All </button>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: "/store/checkout",
              class: ["inline-flex min-h-12 items-center justify-center rounded-md bg-st-fill px-4 py-3 text-base font-semibold text-white transition-colors hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60", { "pointer-events-none opacity-60": !hasItems.value }],
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
            _push2(`</div></div><div class="bg-st-panel rounded-lg shadow-sm border border-st-line"${_scopeId}><div class="flex items-center justify-between border-b border-st-line px-4 py-4 sm:px-6"${_scopeId}><div${_scopeId}><p class="text-sm text-st-muted"${_scopeId}>Items</p><p class="text-lg font-semibold text-st-ink"${_scopeId}>${ssrInterpolate(itemCount.value)} total</p></div><div class="text-right"${_scopeId}><p class="text-sm text-st-muted"${_scopeId}>Cart Total</p><p class="text-2xl font-semibold text-st-ink"${_scopeId}>${ssrInterpolate(totalDisplay.value)}</p></div></div>`);
            if (hasItems.value) {
              _push2(`<div class="divide-y divide-st-line"${_scopeId}><div class="hidden md:grid md:grid-cols-12 px-6 py-3 text-sm font-medium text-st-muted"${_scopeId}><div class="col-span-4"${_scopeId}>Item</div><div class="col-span-2 text-right"${_scopeId}>Unit Price</div><div class="col-span-2 text-right"${_scopeId}>Quantity</div><div class="col-span-2 text-right"${_scopeId}>Subtotal</div><div class="col-span-1 text-right"${_scopeId}>Location</div><div class="col-span-1 text-right"${_scopeId}></div></div><!--[-->`);
              ssrRenderList(groupedItems.value, (group, locationId) => {
                _push2(`<!--[--><div class="col-span-full bg-st-bg px-4 py-3 text-base font-semibold text-st-ink/85 sm:px-6"${_scopeId}>${ssrInterpolate(locationNameById.value[locationId])}</div><!--[-->`);
                ssrRenderList(group, (item) => {
                  _push2(`<div class="grid grid-cols-1 gap-4 px-4 py-5 text-base text-st-ink sm:px-6 md:grid-cols-12 md:items-center md:text-sm"${_scopeId}><div class="md:col-span-4"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Link), {
                    href: productUrl(item),
                    class: "font-medium text-st-ink hover:text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(item.name)}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(item.name), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  if (item.sku) {
                    _push2(`<p class="text-xs text-st-muted mt-1"${_scopeId}> SKU: ${ssrInterpolate(item.sku)}</p>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  if (item.minQuantity > 1) {
                    _push2(`<p class="text-xs text-st-muted mt-1"${_scopeId}> Minimum order: ${ssrInterpolate(item.minQuantity)}</p>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div><div class="md:col-span-2 text-st-muted md:text-right"${_scopeId}>${ssrInterpolate(formatCurrency(item.price))}</div><div class="md:col-span-2 md:text-right"${_scopeId}><div class="flex items-center gap-2 md:justify-end"${_scopeId}><button type="button" class="inline-flex h-12 w-12 items-center justify-center rounded-md border border-st-line text-xl font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action disabled:cursor-not-allowed disabled:opacity-50"${ssrIncludeBooleanAttr(item.quantity <= (item.minQuantity ?? 1)) ? " disabled" : ""}${ssrRenderAttr("aria-label", `Decrease quantity of ${item.name}`)}${_scopeId}> − </button><label${ssrRenderAttr("for", `cart-qty-${item.id}`)} class="sr-only"${_scopeId}>Quantity of ${ssrInterpolate(item.name)}</label><input${ssrRenderAttr("id", `cart-qty-${item.id}`)}${ssrRenderAttr("value", item.quantity)} type="number"${ssrRenderAttr("min", item.minQuantity ?? 1)} inputmode="numeric" class="st-num h-12 w-16 rounded-md border border-st-line bg-st-sunk px-1 text-center text-base font-semibold text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40"${_scopeId}><button type="button"${ssrRenderAttr("aria-label", `Increase quantity of ${item.name}`)} class="inline-flex h-12 w-12 items-center justify-center rounded-md border border-st-line text-xl font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action"${_scopeId}> + </button></div></div><div class="md:col-span-2 font-semibold text-st-ink md:text-right"${_scopeId}>${ssrInterpolate(formatCurrency(item.price * item.quantity))}</div><div class="md:col-span-1 md:text-right"${_scopeId}><select class="block min-h-12 w-full rounded-md border-st-line text-base shadow-sm focus:border-st-action focus:ring-st-action"${_scopeId}><option${ssrRenderAttr("value", null)}${ssrIncludeBooleanAttr(Array.isArray(item.location_id) ? ssrLooseContain(item.location_id, null) : ssrLooseEqual(item.location_id, null)) ? " selected" : ""}${_scopeId}>Main Store</option><!--[-->`);
                  ssrRenderList(locations.value, (loc) => {
                    _push2(`<option${ssrRenderAttr("value", loc.id)}${ssrIncludeBooleanAttr(Array.isArray(item.location_id) ? ssrLooseContain(item.location_id, loc.id) : ssrLooseEqual(item.location_id, loc.id)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(loc.name)}</option>`);
                  });
                  _push2(`<!--]--></select></div><div class="md:col-span-1 md:text-right"${_scopeId}><button type="button" class="inline-flex min-h-12 w-full items-center justify-center rounded-md px-3 py-2 text-base font-semibold text-red-400 hover:bg-red-500/10 focus:outline-none focus:ring-2 focus:ring-red-600 md:w-auto"${_scopeId}> Remove </button></div></div>`);
                });
                _push2(`<!--]--><!--]-->`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<div class="px-6 py-16 text-center text-sm text-st-muted"${_scopeId}><p class="font-medium text-st-ink/85"${_scopeId}>Your cart is empty.</p><p class="mt-2"${_scopeId}>Browse the catalog and add items to your cart.</p>`);
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("store.index"),
                class: "mt-6 inline-flex min-h-12 items-center justify-center rounded-md border border-st-line px-5 py-3 text-base font-semibold text-st-ink transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action"
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
              createVNode("div", { class: "bg-st-bg min-h-screen" }, [
                createVNode("div", { class: "mobile-page-gutter mx-auto max-w-5xl space-y-6 py-6 sm:py-8 lg:px-8" }, [
                  createVNode("div", { class: "flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between" }, [
                    createVNode("div", null, [
                      createVNode("h1", { class: "st-display text-2xl font-extrabold text-st-ink sm:text-3xl" }, "Your cart")
                    ]),
                    createVNode("div", { class: "grid grid-cols-1 gap-2 xs:grid-cols-2 sm:flex sm:items-center sm:gap-3" }, [
                      createVNode("button", {
                        type: "button",
                        class: "inline-flex min-h-12 items-center justify-center rounded-md border border-st-line px-4 py-3 text-base font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action disabled:opacity-60",
                        disabled: !hasItems.value,
                        onClick: handleClear
                      }, " Clear All ", 8, ["disabled"]),
                      createVNode(unref(Link), {
                        href: "/store/checkout",
                        class: ["inline-flex min-h-12 items-center justify-center rounded-md bg-st-fill px-4 py-3 text-base font-semibold text-white transition-colors hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60", { "pointer-events-none opacity-60": !hasItems.value }],
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
                  createVNode("div", { class: "bg-st-panel rounded-lg shadow-sm border border-st-line" }, [
                    createVNode("div", { class: "flex items-center justify-between border-b border-st-line px-4 py-4 sm:px-6" }, [
                      createVNode("div", null, [
                        createVNode("p", { class: "text-sm text-st-muted" }, "Items"),
                        createVNode("p", { class: "text-lg font-semibold text-st-ink" }, toDisplayString(itemCount.value) + " total", 1)
                      ]),
                      createVNode("div", { class: "text-right" }, [
                        createVNode("p", { class: "text-sm text-st-muted" }, "Cart Total"),
                        createVNode("p", { class: "text-2xl font-semibold text-st-ink" }, toDisplayString(totalDisplay.value), 1)
                      ])
                    ]),
                    hasItems.value ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "divide-y divide-st-line"
                    }, [
                      createVNode("div", { class: "hidden md:grid md:grid-cols-12 px-6 py-3 text-sm font-medium text-st-muted" }, [
                        createVNode("div", { class: "col-span-4" }, "Item"),
                        createVNode("div", { class: "col-span-2 text-right" }, "Unit Price"),
                        createVNode("div", { class: "col-span-2 text-right" }, "Quantity"),
                        createVNode("div", { class: "col-span-2 text-right" }, "Subtotal"),
                        createVNode("div", { class: "col-span-1 text-right" }, "Location"),
                        createVNode("div", { class: "col-span-1 text-right" })
                      ]),
                      (openBlock(true), createBlock(Fragment, null, renderList(groupedItems.value, (group, locationId) => {
                        return openBlock(), createBlock(Fragment, { key: locationId }, [
                          createVNode("div", { class: "col-span-full bg-st-bg px-4 py-3 text-base font-semibold text-st-ink/85 sm:px-6" }, toDisplayString(locationNameById.value[locationId]), 1),
                          (openBlock(true), createBlock(Fragment, null, renderList(group, (item) => {
                            return openBlock(), createBlock("div", {
                              key: item.id,
                              class: "grid grid-cols-1 gap-4 px-4 py-5 text-base text-st-ink sm:px-6 md:grid-cols-12 md:items-center md:text-sm"
                            }, [
                              createVNode("div", { class: "md:col-span-4" }, [
                                createVNode(unref(Link), {
                                  href: productUrl(item),
                                  class: "font-medium text-st-ink hover:text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                                }, {
                                  default: withCtx(() => [
                                    createTextVNode(toDisplayString(item.name), 1)
                                  ]),
                                  _: 2
                                }, 1032, ["href"]),
                                item.sku ? (openBlock(), createBlock("p", {
                                  key: 0,
                                  class: "text-xs text-st-muted mt-1"
                                }, " SKU: " + toDisplayString(item.sku), 1)) : createCommentVNode("", true),
                                item.minQuantity > 1 ? (openBlock(), createBlock("p", {
                                  key: 1,
                                  class: "text-xs text-st-muted mt-1"
                                }, " Minimum order: " + toDisplayString(item.minQuantity), 1)) : createCommentVNode("", true)
                              ]),
                              createVNode("div", { class: "md:col-span-2 text-st-muted md:text-right" }, toDisplayString(formatCurrency(item.price)), 1),
                              createVNode("div", { class: "md:col-span-2 md:text-right" }, [
                                createVNode("div", { class: "flex items-center gap-2 md:justify-end" }, [
                                  createVNode("button", {
                                    type: "button",
                                    class: "inline-flex h-12 w-12 items-center justify-center rounded-md border border-st-line text-xl font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action disabled:cursor-not-allowed disabled:opacity-50",
                                    disabled: item.quantity <= (item.minQuantity ?? 1),
                                    "aria-label": `Decrease quantity of ${item.name}`,
                                    onClick: ($event) => unref(cartStore).decrementQuantity(item.id)
                                  }, " − ", 8, ["disabled", "aria-label", "onClick"]),
                                  createVNode("label", {
                                    for: `cart-qty-${item.id}`,
                                    class: "sr-only"
                                  }, "Quantity of " + toDisplayString(item.name), 9, ["for"]),
                                  createVNode("input", {
                                    id: `cart-qty-${item.id}`,
                                    value: item.quantity,
                                    type: "number",
                                    min: item.minQuantity ?? 1,
                                    inputmode: "numeric",
                                    class: "st-num h-12 w-16 rounded-md border border-st-line bg-st-sunk px-1 text-center text-base font-semibold text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40",
                                    onInput: ($event) => unref(cartStore).previewQuantity(item.id, $event.target.value),
                                    onChange: ($event) => {
                                      unref(cartStore).setQuantity(item.id, $event.target.value);
                                      $event.target.value = item.quantity;
                                    }
                                  }, null, 40, ["id", "value", "min", "onInput", "onChange"]),
                                  createVNode("button", {
                                    type: "button",
                                    "aria-label": `Increase quantity of ${item.name}`,
                                    class: "inline-flex h-12 w-12 items-center justify-center rounded-md border border-st-line text-xl font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action",
                                    onClick: ($event) => unref(cartStore).incrementQuantity(item.id)
                                  }, " + ", 8, ["aria-label", "onClick"])
                                ])
                              ]),
                              createVNode("div", { class: "md:col-span-2 font-semibold text-st-ink md:text-right" }, toDisplayString(formatCurrency(item.price * item.quantity)), 1),
                              createVNode("div", { class: "md:col-span-1 md:text-right" }, [
                                withDirectives(createVNode("select", {
                                  "onUpdate:modelValue": ($event) => item.location_id = $event,
                                  onChange: ($event) => unref(cartStore).updateItemLocation(item.id, item.location_id),
                                  class: "block min-h-12 w-full rounded-md border-st-line text-base shadow-sm focus:border-st-action focus:ring-st-action"
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
                                  class: "inline-flex min-h-12 w-full items-center justify-center rounded-md px-3 py-2 text-base font-semibold text-red-400 hover:bg-red-500/10 focus:outline-none focus:ring-2 focus:ring-red-600 md:w-auto",
                                  onClick: ($event) => handleRemove(item.id)
                                }, " Remove ", 8, ["onClick"])
                              ])
                            ]);
                          }), 128))
                        ], 64);
                      }), 128))
                    ])) : (openBlock(), createBlock("div", {
                      key: 1,
                      class: "px-6 py-16 text-center text-sm text-st-muted"
                    }, [
                      createVNode("p", { class: "font-medium text-st-ink/85" }, "Your cart is empty."),
                      createVNode("p", { class: "mt-2" }, "Browse the catalog and add items to your cart."),
                      createVNode(unref(Link), {
                        href: _ctx.route("store.index"),
                        class: "mt-6 inline-flex min-h-12 items-center justify-center rounded-md border border-st-line px-5 py-3 text-base font-semibold text-st-ink transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action"
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
