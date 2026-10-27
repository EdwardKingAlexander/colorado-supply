import { computed, reactive, watch, ref, onMounted, onBeforeUnmount, mergeProps, unref, withCtx, createVNode, createTextVNode, createBlock, toDisplayString, openBlock, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderClass, ssrRenderSlot } from "vue/server-renderer";
import { usePage, Link } from "@inertiajs/vue3";
import { Menu, MenuButton, MenuItems, MenuItem, Dialog, DialogPanel } from "@headlessui/vue";
import { _ as _sfc_main$1 } from "./CookieConsentBanner-ByAlkSbo.js";
import { l as logo, a as logoLight } from "./logo-cleansed-light-B5mBHTsK.js";
function unitPriceFor(pricing, quantity) {
  if (!pricing) {
    return null;
  }
  if (typeof pricing.company_price === "number") {
    return pricing.company_price;
  }
  const tier = (pricing.tiers ?? []).filter((candidate) => candidate.min_quantity <= quantity).at(-1);
  if (tier) {
    return tier.unit_price;
  }
  return typeof pricing.base_price === "number" ? pricing.base_price : null;
}
function normalizeQuantity(value, minimum = 1) {
  const parsed = Math.floor(Number(value));
  return Number.isFinite(parsed) && parsed >= minimum ? parsed : minimum;
}
const currency = new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" });
const STORAGE_KEY = "colorado-supply.cart";
const linePricing = (pricing) => {
  if (!pricing || typeof pricing !== "object") {
    return null;
  }
  return {
    company_price: typeof pricing.company_price === "number" ? pricing.company_price : void 0,
    base_price: typeof pricing.base_price === "number" ? pricing.base_price : void 0,
    tiers: Array.isArray(pricing.tiers) ? pricing.tiers : []
  };
};
const normalizeItem = (item) => {
  const minQuantity = Math.max(1, Number(item.minQuantity) || 1);
  const quantity = normalizeQuantity(item.quantity, minQuantity);
  const pricing = linePricing(item.pricing);
  const pricedFromTiers = pricing ? unitPriceFor(pricing, quantity) : null;
  return {
    id: item.id,
    productId: item.productId ?? item.id,
    name: item.name ?? "Unnamed Item",
    sku: item.sku ?? null,
    slug: item.slug ?? null,
    url: item.url ?? null,
    unit: item.unit ?? null,
    minQuantity,
    pricing,
    price: pricedFromTiers ?? (Number(item.price) || 0),
    quantity,
    location_id: item.location_id ?? null
  };
};
const loadInitialItems = () => {
  if (typeof window === "undefined") {
    return [];
  }
  try {
    const stored = window.localStorage.getItem(STORAGE_KEY);
    if (!stored) {
      return [];
    }
    const parsed = JSON.parse(stored);
    if (!Array.isArray(parsed)) {
      return [];
    }
    return parsed.filter((item) => item && typeof item.id !== "undefined").map(normalizeItem);
  } catch (error) {
    console.warn("Failed to parse cart storage", error);
    return [];
  }
};
const state = reactive({
  items: loadInitialItems()
});
const persistItems = (items) => {
  if (typeof window === "undefined") {
    return;
  }
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
  } catch (error) {
    console.warn("Failed to persist cart storage", error);
  }
};
if (typeof window !== "undefined") {
  watch(
    () => state.items,
    (items) => {
      persistItems(items);
    },
    { deep: true }
  );
}
const findItem = (id) => state.items.find((cartItem) => cartItem.id === id);
const applyQuantity = (item, quantity, clamp = true) => {
  item.quantity = clamp ? normalizeQuantity(quantity, item.minQuantity ?? 1) : quantity;
  if (item.pricing) {
    item.price = unitPriceFor(item.pricing, item.quantity) ?? item.price;
  }
};
function useCartStore() {
  const addItem = (product, quantity = 1, location_id = null) => {
    if (!product || typeof product.id === "undefined") {
      return;
    }
    const minQuantity = Math.max(1, Number(product.minimum_order_quantity) || 1);
    const existing = findItem(product.id);
    if (existing) {
      applyQuantity(existing, existing.quantity + normalizeQuantity(quantity, 1));
      if (!existing.location_id && location_id) {
        existing.location_id = location_id;
      }
      return;
    }
    state.items.push(normalizeItem({
      id: product.id,
      productId: product.id,
      name: product.name,
      sku: product.sku,
      slug: product.slug,
      url: product.url,
      unit: product.unit_of_measure ?? product.unit,
      minQuantity,
      pricing: product.pricing,
      price: product.price,
      quantity: normalizeQuantity(quantity, minQuantity),
      location_id
    }));
  };
  const removeItem = (id) => {
    const index = state.items.findIndex((item) => item.id === id);
    if (index === -1) {
      return;
    }
    state.items.splice(index, 1);
  };
  const clearCart = () => {
    if (!state.items.length) {
      return;
    }
    state.items.splice(0, state.items.length);
  };
  const setQuantity = (id, quantity) => {
    const item = findItem(id);
    if (item) {
      applyQuantity(item, quantity);
    }
  };
  const previewQuantity = (id, value) => {
    const item = findItem(id);
    const typed = Math.floor(Number(value));
    if (item && value !== "" && Number.isFinite(typed) && typed >= 1) {
      applyQuantity(item, typed, false);
    }
  };
  const incrementQuantity = (id) => {
    const item = findItem(id);
    if (item) {
      applyQuantity(item, item.quantity + 1);
    }
  };
  const decrementQuantity = (id) => {
    const item = findItem(id);
    if (item && item.quantity > (item.minQuantity ?? 1)) {
      applyQuantity(item, item.quantity - 1);
    }
  };
  const updateItemLocation = (id, newLocationId) => {
    const item = findItem(id);
    if (!item) {
      return;
    }
    item.location_id = newLocationId;
  };
  const itemCount = computed(() => state.items.reduce((total2, item) => total2 + item.quantity, 0));
  const total = computed(() => state.items.reduce((sum, item) => sum + item.price * item.quantity, 0));
  return {
    items: state.items,
    itemCount,
    total,
    totalCost: total,
    addItem,
    removeItem,
    clearCart,
    setQuantity,
    previewQuantity,
    incrementQuantity,
    decrementQuantity,
    updateItemLocation
  };
}
const _sfc_main = {
  __name: "StoreLayout",
  __ssrInlineRender: true,
  props: {
    // Current catalog search term (v-model:search).
    search: {
      type: String,
      default: ""
    },
    // When true the page handles searching itself (debounced `search` events).
    // Otherwise submitting the header search opens the catalog with ?q=.
    liveSearch: {
      type: Boolean,
      default: false
    },
    searching: {
      type: Boolean,
      default: false
    }
  },
  emits: ["update:search", "search"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const page = usePage();
    const cartStore = useCartStore();
    const cartCount = computed(() => cartStore.itemCount.value);
    const accountName = computed(() => page.props.auth?.user?.name ?? page.props.auth?.admin?.name ?? "Account");
    const accountEmail = computed(() => page.props.auth?.user?.email ?? page.props.auth?.admin?.email ?? null);
    const isCustomer = computed(() => Boolean(page.props.auth?.user));
    const isAdmin = computed(() => Boolean(page.props.auth?.admin));
    const query = ref(props.search ?? "");
    const searchInput = ref(null);
    const mobileMenuOpen = ref(false);
    let debounceTimer = null;
    watch(() => props.search, (value) => {
      if ((value ?? "") !== query.value) {
        query.value = value ?? "";
      }
    });
    const onKeydown = (event) => {
      if (event.key !== "/" || event.metaKey || event.ctrlKey || event.altKey) {
        return;
      }
      const target = event.target;
      const typing = target instanceof HTMLElement && (target.isContentEditable || ["INPUT", "TEXTAREA", "SELECT"].includes(target.tagName));
      if (!typing) {
        event.preventDefault();
        searchInput.value?.focus();
        searchInput.value?.select();
      }
    };
    onMounted(() => document.addEventListener("keydown", onKeydown));
    onBeforeUnmount(() => {
      document.removeEventListener("keydown", onKeydown);
      clearTimeout(debounceTimer);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "store-shell min-h-screen bg-st-bg text-st-ink" }, _attrs))}><header class="safe-top sticky top-0 z-40 border-b border-st-line bg-st-panel/95 backdrop-blur"><div class="mx-auto flex max-w-screen-2xl flex-wrap items-center gap-x-4 gap-y-3 px-4 py-3 sm:px-6 lg:flex-nowrap lg:gap-x-8 lg:px-8">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("store.index"),
        class: "flex shrink-0 items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action",
        "aria-label": "Colorado Supply store home"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img${ssrRenderAttr("src", unref(logo))} alt="" class="h-9 w-auto dark:hidden"${_scopeId}><img${ssrRenderAttr("src", unref(logoLight))} alt="" class="hidden h-9 w-auto dark:block"${_scopeId}>`);
          } else {
            return [
              createVNode("img", {
                src: unref(logo),
                alt: "",
                class: "h-9 w-auto dark:hidden"
              }, null, 8, ["src"]),
              createVNode("img", {
                src: unref(logoLight),
                alt: "",
                class: "hidden h-9 w-auto dark:block"
              }, null, 8, ["src"])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="order-2 ml-auto flex items-center gap-1 lg:order-3 lg:ml-0">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("dashboard"),
        class: "hidden min-h-11 items-center rounded-md px-3 text-sm font-medium text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:inline-flex"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Dashboard `);
          } else {
            return [
              createTextVNode(" Dashboard ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Menu), {
        as: "div",
        class: "relative hidden lg:block"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(MenuButton), { class: "inline-flex min-h-11 items-center gap-1.5 rounded-md px-3 text-sm font-medium text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<span class="max-w-[10rem] truncate"${_scopeId2}>${ssrInterpolate(accountName.value)}</span><svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"${_scopeId2}><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"${_scopeId2}></path></svg>`);
                } else {
                  return [
                    createVNode("span", { class: "max-w-[10rem] truncate" }, toDisplayString(accountName.value), 1),
                    (openBlock(), createBlock("svg", {
                      class: "h-4 w-4",
                      viewBox: "0 0 20 20",
                      fill: "currentColor",
                      "aria-hidden": "true"
                    }, [
                      createVNode("path", {
                        "fill-rule": "evenodd",
                        d: "M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z",
                        "clip-rule": "evenodd"
                      })
                    ]))
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(MenuItems), { class: "absolute right-0 z-50 mt-2 w-60 overflow-hidden rounded-lg border border-st-line bg-st-panel py-1 shadow-2xl focus:outline-none" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="border-b border-st-line px-4 py-3"${_scopeId2}><p class="truncate text-sm font-semibold text-st-ink"${_scopeId2}>${ssrInterpolate(accountName.value)}</p>`);
                  if (accountEmail.value) {
                    _push3(`<p class="truncate text-sm text-st-muted"${_scopeId2}>${ssrInterpolate(accountEmail.value)}</p>`);
                  } else {
                    _push3(`<!---->`);
                  }
                  _push3(`</div>`);
                  if (isCustomer.value) {
                    _push3(ssrRenderComponent(unref(MenuItem), null, {
                      default: withCtx(({ active }, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(ssrRenderComponent(unref(Link), {
                            href: _ctx.route("dashboard.reports"),
                            class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"]
                          }, {
                            default: withCtx((_3, _push5, _parent5, _scopeId4) => {
                              if (_push5) {
                                _push5(`Order reports`);
                              } else {
                                return [
                                  createTextVNode("Order reports")
                                ];
                              }
                            }),
                            _: 2
                          }, _parent4, _scopeId3));
                        } else {
                          return [
                            createVNode(unref(Link), {
                              href: _ctx.route("dashboard.reports"),
                              class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"]
                            }, {
                              default: withCtx(() => [
                                createTextVNode("Order reports")
                              ]),
                              _: 1
                            }, 8, ["href", "class"])
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  if (isCustomer.value) {
                    _push3(ssrRenderComponent(unref(MenuItem), null, {
                      default: withCtx(({ active }, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(ssrRenderComponent(unref(Link), {
                            href: _ctx.route("profile.edit"),
                            class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"]
                          }, {
                            default: withCtx((_3, _push5, _parent5, _scopeId4) => {
                              if (_push5) {
                                _push5(`Profile`);
                              } else {
                                return [
                                  createTextVNode("Profile")
                                ];
                              }
                            }),
                            _: 2
                          }, _parent4, _scopeId3));
                        } else {
                          return [
                            createVNode(unref(Link), {
                              href: _ctx.route("profile.edit"),
                              class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"]
                            }, {
                              default: withCtx(() => [
                                createTextVNode("Profile")
                              ]),
                              _: 1
                            }, 8, ["href", "class"])
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  if (isAdmin.value) {
                    _push3(ssrRenderComponent(unref(MenuItem), null, {
                      default: withCtx(({ active }, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(`<a${ssrRenderAttr("href", _ctx.route("filament.admin.pages.dashboard"))} class="${ssrRenderClass([active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"])}"${_scopeId3}>Admin panel</a>`);
                        } else {
                          return [
                            createVNode("a", {
                              href: _ctx.route("filament.admin.pages.dashboard"),
                              class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"]
                            }, "Admin panel", 10, ["href"])
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  _push3(ssrRenderComponent(unref(MenuItem), null, {
                    default: withCtx(({ active }, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(ssrRenderComponent(unref(Link), {
                          href: _ctx.route("logout"),
                          method: "post",
                          as: "button",
                          class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block w-full px-4 py-2.5 text-left text-sm"]
                        }, {
                          default: withCtx((_3, _push5, _parent5, _scopeId4) => {
                            if (_push5) {
                              _push5(`Log out`);
                            } else {
                              return [
                                createTextVNode("Log out")
                              ];
                            }
                          }),
                          _: 2
                        }, _parent4, _scopeId3));
                      } else {
                        return [
                          createVNode(unref(Link), {
                            href: _ctx.route("logout"),
                            method: "post",
                            as: "button",
                            class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block w-full px-4 py-2.5 text-left text-sm"]
                          }, {
                            default: withCtx(() => [
                              createTextVNode("Log out")
                            ]),
                            _: 1
                          }, 8, ["href", "class"])
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode("div", { class: "border-b border-st-line px-4 py-3" }, [
                      createVNode("p", { class: "truncate text-sm font-semibold text-st-ink" }, toDisplayString(accountName.value), 1),
                      accountEmail.value ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "truncate text-sm text-st-muted"
                      }, toDisplayString(accountEmail.value), 1)) : createCommentVNode("", true)
                    ]),
                    isCustomer.value ? (openBlock(), createBlock(unref(MenuItem), { key: 0 }, {
                      default: withCtx(({ active }) => [
                        createVNode(unref(Link), {
                          href: _ctx.route("dashboard.reports"),
                          class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"]
                        }, {
                          default: withCtx(() => [
                            createTextVNode("Order reports")
                          ]),
                          _: 1
                        }, 8, ["href", "class"])
                      ]),
                      _: 1
                    })) : createCommentVNode("", true),
                    isCustomer.value ? (openBlock(), createBlock(unref(MenuItem), { key: 1 }, {
                      default: withCtx(({ active }) => [
                        createVNode(unref(Link), {
                          href: _ctx.route("profile.edit"),
                          class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"]
                        }, {
                          default: withCtx(() => [
                            createTextVNode("Profile")
                          ]),
                          _: 1
                        }, 8, ["href", "class"])
                      ]),
                      _: 1
                    })) : createCommentVNode("", true),
                    isAdmin.value ? (openBlock(), createBlock(unref(MenuItem), { key: 2 }, {
                      default: withCtx(({ active }) => [
                        createVNode("a", {
                          href: _ctx.route("filament.admin.pages.dashboard"),
                          class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"]
                        }, "Admin panel", 10, ["href"])
                      ]),
                      _: 1
                    })) : createCommentVNode("", true),
                    createVNode(unref(MenuItem), null, {
                      default: withCtx(({ active }) => [
                        createVNode(unref(Link), {
                          href: _ctx.route("logout"),
                          method: "post",
                          as: "button",
                          class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block w-full px-4 py-2.5 text-left text-sm"]
                        }, {
                          default: withCtx(() => [
                            createTextVNode("Log out")
                          ]),
                          _: 1
                        }, 8, ["href", "class"])
                      ]),
                      _: 1
                    })
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(unref(MenuButton), { class: "inline-flex min-h-11 items-center gap-1.5 rounded-md px-3 text-sm font-medium text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action" }, {
                default: withCtx(() => [
                  createVNode("span", { class: "max-w-[10rem] truncate" }, toDisplayString(accountName.value), 1),
                  (openBlock(), createBlock("svg", {
                    class: "h-4 w-4",
                    viewBox: "0 0 20 20",
                    fill: "currentColor",
                    "aria-hidden": "true"
                  }, [
                    createVNode("path", {
                      "fill-rule": "evenodd",
                      d: "M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z",
                      "clip-rule": "evenodd"
                    })
                  ]))
                ]),
                _: 1
              }),
              createVNode(unref(MenuItems), { class: "absolute right-0 z-50 mt-2 w-60 overflow-hidden rounded-lg border border-st-line bg-st-panel py-1 shadow-2xl focus:outline-none" }, {
                default: withCtx(() => [
                  createVNode("div", { class: "border-b border-st-line px-4 py-3" }, [
                    createVNode("p", { class: "truncate text-sm font-semibold text-st-ink" }, toDisplayString(accountName.value), 1),
                    accountEmail.value ? (openBlock(), createBlock("p", {
                      key: 0,
                      class: "truncate text-sm text-st-muted"
                    }, toDisplayString(accountEmail.value), 1)) : createCommentVNode("", true)
                  ]),
                  isCustomer.value ? (openBlock(), createBlock(unref(MenuItem), { key: 0 }, {
                    default: withCtx(({ active }) => [
                      createVNode(unref(Link), {
                        href: _ctx.route("dashboard.reports"),
                        class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"]
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Order reports")
                        ]),
                        _: 1
                      }, 8, ["href", "class"])
                    ]),
                    _: 1
                  })) : createCommentVNode("", true),
                  isCustomer.value ? (openBlock(), createBlock(unref(MenuItem), { key: 1 }, {
                    default: withCtx(({ active }) => [
                      createVNode(unref(Link), {
                        href: _ctx.route("profile.edit"),
                        class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"]
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Profile")
                        ]),
                        _: 1
                      }, 8, ["href", "class"])
                    ]),
                    _: 1
                  })) : createCommentVNode("", true),
                  isAdmin.value ? (openBlock(), createBlock(unref(MenuItem), { key: 2 }, {
                    default: withCtx(({ active }) => [
                      createVNode("a", {
                        href: _ctx.route("filament.admin.pages.dashboard"),
                        class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block px-4 py-2.5 text-sm"]
                      }, "Admin panel", 10, ["href"])
                    ]),
                    _: 1
                  })) : createCommentVNode("", true),
                  createVNode(unref(MenuItem), null, {
                    default: withCtx(({ active }) => [
                      createVNode(unref(Link), {
                        href: _ctx.route("logout"),
                        method: "post",
                        as: "button",
                        class: [active ? "bg-st-sunk text-st-ink" : "text-st-muted", "block w-full px-4 py-2.5 text-left text-sm"]
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Log out")
                        ]),
                        _: 1
                      }, 8, ["href", "class"])
                    ]),
                    _: 1
                  })
                ]),
                _: 1
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("store.cart"),
        class: "relative inline-flex min-h-11 items-center gap-2 rounded-md border border-st-line px-3 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action",
        "aria-label": cartCount.value ? `Cart, ${cartCount.value} items` : "Cart"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.39c.51 0 .96.34 1.09.84l.38 1.44m0 0L6.86 12h11.07l2.07-6.72H5.11zM6.86 12 6 15.75h12.75M8.25 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm10.5 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"${_scopeId}></path></svg><span class="hidden sm:inline"${_scopeId}>Cart</span>`);
            if (cartCount.value) {
              _push2(`<span class="st-num inline-flex min-w-6 items-center justify-center rounded bg-st-signal px-1.5 py-0.5 text-xs font-bold text-[#1a1300]"${_scopeId}>${ssrInterpolate(cartCount.value)}</span>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              (openBlock(), createBlock("svg", {
                class: "h-5 w-5",
                viewBox: "0 0 24 24",
                fill: "none",
                stroke: "currentColor",
                "stroke-width": "1.7",
                "aria-hidden": "true"
              }, [
                createVNode("path", {
                  "stroke-linecap": "round",
                  "stroke-linejoin": "round",
                  d: "M2.25 3h1.39c.51 0 .96.34 1.09.84l.38 1.44m0 0L6.86 12h11.07l2.07-6.72H5.11zM6.86 12 6 15.75h12.75M8.25 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm10.5 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"
                })
              ])),
              createVNode("span", { class: "hidden sm:inline" }, "Cart"),
              cartCount.value ? (openBlock(), createBlock("span", {
                key: 0,
                class: "st-num inline-flex min-w-6 items-center justify-center rounded bg-st-signal px-1.5 py-0.5 text-xs font-bold text-[#1a1300]"
              }, toDisplayString(cartCount.value), 1)) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:hidden" aria-label="Open account menu"${ssrRenderAttr("aria-expanded", mobileMenuOpen.value)}><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"></path></svg></button></div><form role="search" class="order-3 w-full lg:order-2 lg:max-w-3xl lg:flex-1"><label for="store-search" class="sr-only">Search the catalog</label><div class="group relative flex items-center"><svg class="pointer-events-none absolute left-3.5 h-5 w-5 text-st-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"></path></svg><input id="store-search"${ssrRenderAttr("value", query.value)} type="search" name="q" autocomplete="off" enterkeyhint="search" placeholder="Search by part number, product name, or spec" class="h-12 w-full rounded-lg border border-st-line bg-st-sunk pl-11 pr-24 text-base text-st-ink placeholder:text-st-muted focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40 [&amp;::-webkit-search-cancel-button]:hidden"><div class="absolute right-2 flex items-center gap-1">`);
      if (__props.searching) {
        _push(`<span class="mr-1 h-4 w-4 animate-spin rounded-full border-2 border-st-line border-t-st-action" aria-hidden="true"></span>`);
      } else {
        _push(`<!---->`);
      }
      if (query.value) {
        _push(`<button type="button" class="inline-flex h-9 items-center rounded px-2 text-sm text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"> Clear </button>`);
      } else {
        _push(`<kbd class="hidden rounded border border-st-line px-1.5 py-0.5 text-xs text-st-muted sm:inline-block" title="Press / to search">/</kbd>`);
      }
      _push(`</div></div></form></div></header><main>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</main>`);
      _push(ssrRenderComponent(unref(Dialog), {
        class: "lg:hidden",
        open: mobileMenuOpen.value,
        onClose: ($event) => mobileMenuOpen.value = false
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="fixed inset-0 z-40 bg-black/60" aria-hidden="true"${_scopeId}></div>`);
            _push2(ssrRenderComponent(unref(DialogPanel), { class: "store-shell safe-y fixed inset-y-0 right-0 z-50 flex w-full max-w-drawer flex-col bg-st-panel text-st-ink shadow-2xl" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="flex h-16 items-center justify-between border-b border-st-line px-4"${_scopeId2}><div class="min-w-0"${_scopeId2}><p class="truncate text-base font-semibold"${_scopeId2}>${ssrInterpolate(accountName.value)}</p>`);
                  if (accountEmail.value) {
                    _push3(`<p class="truncate text-sm text-st-muted"${_scopeId2}>${ssrInterpolate(accountEmail.value)}</p>`);
                  } else {
                    _push3(`<!---->`);
                  }
                  _push3(`</div><button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink" aria-label="Close account menu"${_scopeId2}><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"${_scopeId2}><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"${_scopeId2}></path></svg></button></div><nav class="flex flex-col p-2 text-base"${_scopeId2}>`);
                  _push3(ssrRenderComponent(unref(Link), {
                    href: _ctx.route("store.index"),
                    class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                    onClick: ($event) => mobileMenuOpen.value = false
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`Catalog`);
                      } else {
                        return [
                          createTextVNode("Catalog")
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(unref(Link), {
                    href: _ctx.route("store.cart"),
                    class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                    onClick: ($event) => mobileMenuOpen.value = false
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`Cart`);
                      } else {
                        return [
                          createTextVNode("Cart")
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(unref(Link), {
                    href: _ctx.route("dashboard"),
                    class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                    onClick: ($event) => mobileMenuOpen.value = false
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`Dashboard`);
                      } else {
                        return [
                          createTextVNode("Dashboard")
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                  if (isCustomer.value) {
                    _push3(ssrRenderComponent(unref(Link), {
                      href: _ctx.route("dashboard.reports"),
                      class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                      onClick: ($event) => mobileMenuOpen.value = false
                    }, {
                      default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(`Order reports`);
                        } else {
                          return [
                            createTextVNode("Order reports")
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  if (isCustomer.value) {
                    _push3(ssrRenderComponent(unref(Link), {
                      href: _ctx.route("profile.edit"),
                      class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                      onClick: ($event) => mobileMenuOpen.value = false
                    }, {
                      default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(`Profile`);
                        } else {
                          return [
                            createTextVNode("Profile")
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  if (isAdmin.value) {
                    _push3(`<a${ssrRenderAttr("href", _ctx.route("filament.admin.pages.dashboard"))} class="rounded-md px-3 py-3 hover:bg-st-sunk"${_scopeId2}>Admin panel</a>`);
                  } else {
                    _push3(`<!---->`);
                  }
                  _push3(ssrRenderComponent(unref(Link), {
                    href: _ctx.route("logout"),
                    method: "post",
                    as: "button",
                    class: "rounded-md px-3 py-3 text-left hover:bg-st-sunk",
                    onClick: ($event) => mobileMenuOpen.value = false
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`Log out`);
                      } else {
                        return [
                          createTextVNode("Log out")
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                  _push3(`</nav>`);
                } else {
                  return [
                    createVNode("div", { class: "flex h-16 items-center justify-between border-b border-st-line px-4" }, [
                      createVNode("div", { class: "min-w-0" }, [
                        createVNode("p", { class: "truncate text-base font-semibold" }, toDisplayString(accountName.value), 1),
                        accountEmail.value ? (openBlock(), createBlock("p", {
                          key: 0,
                          class: "truncate text-sm text-st-muted"
                        }, toDisplayString(accountEmail.value), 1)) : createCommentVNode("", true)
                      ]),
                      createVNode("button", {
                        type: "button",
                        class: "inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink",
                        "aria-label": "Close account menu",
                        onClick: ($event) => mobileMenuOpen.value = false
                      }, [
                        (openBlock(), createBlock("svg", {
                          class: "h-6 w-6",
                          viewBox: "0 0 24 24",
                          fill: "none",
                          stroke: "currentColor",
                          "stroke-width": "2",
                          "aria-hidden": "true"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            d: "M6 6l12 12M18 6L6 18"
                          })
                        ]))
                      ], 8, ["onClick"])
                    ]),
                    createVNode("nav", { class: "flex flex-col p-2 text-base" }, [
                      createVNode(unref(Link), {
                        href: _ctx.route("store.index"),
                        class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                        onClick: ($event) => mobileMenuOpen.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Catalog")
                        ]),
                        _: 1
                      }, 8, ["href", "onClick"]),
                      createVNode(unref(Link), {
                        href: _ctx.route("store.cart"),
                        class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                        onClick: ($event) => mobileMenuOpen.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Cart")
                        ]),
                        _: 1
                      }, 8, ["href", "onClick"]),
                      createVNode(unref(Link), {
                        href: _ctx.route("dashboard"),
                        class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                        onClick: ($event) => mobileMenuOpen.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Dashboard")
                        ]),
                        _: 1
                      }, 8, ["href", "onClick"]),
                      isCustomer.value ? (openBlock(), createBlock(unref(Link), {
                        key: 0,
                        href: _ctx.route("dashboard.reports"),
                        class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                        onClick: ($event) => mobileMenuOpen.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Order reports")
                        ]),
                        _: 1
                      }, 8, ["href", "onClick"])) : createCommentVNode("", true),
                      isCustomer.value ? (openBlock(), createBlock(unref(Link), {
                        key: 1,
                        href: _ctx.route("profile.edit"),
                        class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                        onClick: ($event) => mobileMenuOpen.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Profile")
                        ]),
                        _: 1
                      }, 8, ["href", "onClick"])) : createCommentVNode("", true),
                      isAdmin.value ? (openBlock(), createBlock("a", {
                        key: 2,
                        href: _ctx.route("filament.admin.pages.dashboard"),
                        class: "rounded-md px-3 py-3 hover:bg-st-sunk"
                      }, "Admin panel", 8, ["href"])) : createCommentVNode("", true),
                      createVNode(unref(Link), {
                        href: _ctx.route("logout"),
                        method: "post",
                        as: "button",
                        class: "rounded-md px-3 py-3 text-left hover:bg-st-sunk",
                        onClick: ($event) => mobileMenuOpen.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Log out")
                        ]),
                        _: 1
                      }, 8, ["href", "onClick"])
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode("div", {
                class: "fixed inset-0 z-40 bg-black/60",
                "aria-hidden": "true"
              }),
              createVNode(unref(DialogPanel), { class: "store-shell safe-y fixed inset-y-0 right-0 z-50 flex w-full max-w-drawer flex-col bg-st-panel text-st-ink shadow-2xl" }, {
                default: withCtx(() => [
                  createVNode("div", { class: "flex h-16 items-center justify-between border-b border-st-line px-4" }, [
                    createVNode("div", { class: "min-w-0" }, [
                      createVNode("p", { class: "truncate text-base font-semibold" }, toDisplayString(accountName.value), 1),
                      accountEmail.value ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "truncate text-sm text-st-muted"
                      }, toDisplayString(accountEmail.value), 1)) : createCommentVNode("", true)
                    ]),
                    createVNode("button", {
                      type: "button",
                      class: "inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink",
                      "aria-label": "Close account menu",
                      onClick: ($event) => mobileMenuOpen.value = false
                    }, [
                      (openBlock(), createBlock("svg", {
                        class: "h-6 w-6",
                        viewBox: "0 0 24 24",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "2",
                        "aria-hidden": "true"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          d: "M6 6l12 12M18 6L6 18"
                        })
                      ]))
                    ], 8, ["onClick"])
                  ]),
                  createVNode("nav", { class: "flex flex-col p-2 text-base" }, [
                    createVNode(unref(Link), {
                      href: _ctx.route("store.index"),
                      class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                      onClick: ($event) => mobileMenuOpen.value = false
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Catalog")
                      ]),
                      _: 1
                    }, 8, ["href", "onClick"]),
                    createVNode(unref(Link), {
                      href: _ctx.route("store.cart"),
                      class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                      onClick: ($event) => mobileMenuOpen.value = false
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Cart")
                      ]),
                      _: 1
                    }, 8, ["href", "onClick"]),
                    createVNode(unref(Link), {
                      href: _ctx.route("dashboard"),
                      class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                      onClick: ($event) => mobileMenuOpen.value = false
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Dashboard")
                      ]),
                      _: 1
                    }, 8, ["href", "onClick"]),
                    isCustomer.value ? (openBlock(), createBlock(unref(Link), {
                      key: 0,
                      href: _ctx.route("dashboard.reports"),
                      class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                      onClick: ($event) => mobileMenuOpen.value = false
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Order reports")
                      ]),
                      _: 1
                    }, 8, ["href", "onClick"])) : createCommentVNode("", true),
                    isCustomer.value ? (openBlock(), createBlock(unref(Link), {
                      key: 1,
                      href: _ctx.route("profile.edit"),
                      class: "rounded-md px-3 py-3 hover:bg-st-sunk",
                      onClick: ($event) => mobileMenuOpen.value = false
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Profile")
                      ]),
                      _: 1
                    }, 8, ["href", "onClick"])) : createCommentVNode("", true),
                    isAdmin.value ? (openBlock(), createBlock("a", {
                      key: 2,
                      href: _ctx.route("filament.admin.pages.dashboard"),
                      class: "rounded-md px-3 py-3 hover:bg-st-sunk"
                    }, "Admin panel", 8, ["href"])) : createCommentVNode("", true),
                    createVNode(unref(Link), {
                      href: _ctx.route("logout"),
                      method: "post",
                      as: "button",
                      class: "rounded-md px-3 py-3 text-left hover:bg-st-sunk",
                      onClick: ($event) => mobileMenuOpen.value = false
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Log out")
                      ]),
                      _: 1
                    }, 8, ["href", "onClick"])
                  ])
                ]),
                _: 1
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, null, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Layouts/StoreLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as _,
  unitPriceFor as a,
  currency as c,
  normalizeQuantity as n,
  useCartStore as u
};
