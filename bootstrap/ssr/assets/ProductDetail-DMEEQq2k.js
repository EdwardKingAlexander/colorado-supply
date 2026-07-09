import { ref, computed, onMounted, onBeforeUnmount, unref, withCtx, createBlock, createTextVNode, openBlock, createVNode, createCommentVNode, toDisplayString, Fragment, renderList, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderStyle, ssrRenderAttr, ssrRenderList } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AuthenticatedLayout-BaKoCKvn.js";
import { Head, Link } from "@inertiajs/vue3";
import { u as useCartStore } from "./useCartStore-OHIRORWN.js";
import "./ApplicationLogo-B2173abF.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "ProductDetail",
  __ssrInlineRender: true,
  props: {
    slug: {
      type: String,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const product = ref(null);
    const loading = ref(true);
    const error = ref(null);
    const currencyFormatter = new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD"
    });
    const priceDisplay = computed(() => {
      if (!product.value?.price) {
        return "Call for pricing";
      }
      const price = Number(product.value.price);
      if (Number.isFinite(price)) {
        return currencyFormatter.format(price);
      }
      return "Call for pricing";
    });
    const partNumber = computed(() => {
      if (!product.value) {
        return "";
      }
      return product.value.sku ?? product.value.slug ?? `#${product.value.id}`;
    });
    const unitLabel = computed(() => product.value?.unit ?? "EA");
    const specifications = computed(() => {
      if (!product.value?.specifications || !Array.isArray(product.value.specifications)) {
        return [];
      }
      return product.value.specifications;
    });
    const buildPlaceholderImage = (seed, width = 800, height = 800) => {
      const safeSeed = encodeURIComponent(seed ?? "product-detail");
      return `https://picsum.photos/seed/${safeSeed}/${width}/${height}`;
    };
    const resolveImagePath = (image) => {
      if (typeof image !== "string" || image.length === 0) {
        return null;
      }
      if (image.startsWith("http://") || image.startsWith("https://")) {
        return image;
      }
      return `/storage/${image.replace(/^\/+/, "")}`;
    };
    const productImage = computed(() => {
      const resolved = resolveImagePath(product.value?.image ?? "");
      if (resolved) {
        return resolved;
      }
      const seed = product.value?.slug ?? product.value?.id ?? props.slug;
      return buildPlaceholderImage(seed, 800, 800);
    });
    const stockStatus = computed(() => {
      if (!product.value) {
        return null;
      }
      if (!product.value.in_stock) {
        return {
          label: product.value.lead_time_days ? `Ships in ${product.value.lead_time_days} days` : "Out of Stock",
          class: "bg-yellow-100 text-yellow-800",
          icon: "⏱"
        };
      }
      return {
        label: "In Stock",
        class: "bg-green-100 text-green-800",
        icon: "✓"
      };
    });
    const hasDimensions = computed(() => {
      if (!product.value?.dimensions) {
        return false;
      }
      const dims = product.value.dimensions;
      return dims.length_mm || dims.width_mm || dims.height_mm || dims.weight_g;
    });
    const cartStore = useCartStore();
    const justAdded = ref(false);
    let feedbackTimer;
    const fetchProduct = async () => {
      loading.value = true;
      error.value = null;
      try {
        const response = await fetch(`/api/v1/store/products/${props.slug}`);
        if (!response.ok) {
          throw new Error("Product not found");
        }
        const data = await response.json();
        product.value = data.data;
      } catch (err) {
        error.value = err.message;
        console.error("Failed to fetch product:", err);
      } finally {
        loading.value = false;
      }
    };
    onMounted(() => {
      fetchProduct();
    });
    const handleAddToCart = () => {
      if (!product.value) {
        return;
      }
      cartStore.addItem(product.value);
      justAdded.value = true;
      if (feedbackTimer) {
        clearTimeout(feedbackTimer);
      }
      feedbackTimer = setTimeout(() => {
        justAdded.value = false;
        feedbackTimer = null;
      }, 1500);
    };
    onBeforeUnmount(() => {
      if (feedbackTimer) {
        clearTimeout(feedbackTimer);
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: product.value?.name ?? "Product Details"
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-gray-50 min-h-screen"${_scopeId}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"${_scopeId}><div class="mb-6"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("store.index"),
              class: "inline-flex items-center text-sm text-gray-600 hover:text-gray-900 transition-colors"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId2}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"${_scopeId2}></path></svg> Back to Catalog `);
                } else {
                  return [
                    (openBlock(), createBlock("svg", {
                      class: "w-4 h-4 mr-2",
                      fill: "none",
                      stroke: "currentColor",
                      viewBox: "0 0 24 24"
                    }, [
                      createVNode("path", {
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        "stroke-width": "2",
                        d: "M15 19l-7-7 7-7"
                      })
                    ])),
                    createTextVNode(" Back to Catalog ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
            if (loading.value) {
              _push2(`<div class="bg-white rounded-lg shadow-sm p-8"${_scopeId}><div class="animate-pulse space-y-6"${_scopeId}><div class="h-8 bg-gray-200 rounded w-1/3"${_scopeId}></div><div class="grid grid-cols-2 gap-8"${_scopeId}><div class="space-y-4"${_scopeId}><div class="h-64 bg-gray-200 rounded"${_scopeId}></div></div><div class="space-y-4"${_scopeId}><div class="h-4 bg-gray-200 rounded w-2/3"${_scopeId}></div><div class="h-4 bg-gray-200 rounded w-1/2"${_scopeId}></div><div class="h-32 bg-gray-200 rounded"${_scopeId}></div></div></div></div></div>`);
            } else if (error.value) {
              _push2(`<div class="bg-white rounded-lg shadow-sm p-8"${_scopeId}><div class="text-center"${_scopeId}><p class="text-red-600 font-medium"${_scopeId}>${ssrInterpolate(error.value)}</p>`);
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("store.index"),
                class: "mt-4 inline-block text-sm text-gray-600 hover:text-gray-900"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` Return to catalog `);
                  } else {
                    return [
                      createTextVNode(" Return to catalog ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div></div>`);
            } else if (product.value) {
              _push2(`<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm"${_scopeId}><div class="border-b border-gray-200 dark:border-gray-700 px-8 py-6"${_scopeId}><div class="flex justify-between items-start gap-4"${_scopeId}><div class="flex-1"${_scopeId}><h1 class="text-2xl font-semibold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(product.value.name)}</h1><div class="flex flex-wrap items-center gap-3 mt-3"${_scopeId}><p class="text-sm text-gray-500 dark:text-gray-400 font-mono"${_scopeId}> Part #: ${ssrInterpolate(partNumber.value)}</p>`);
              if (stockStatus.value) {
                _push2(`<span class="${ssrRenderClass([stockStatus.value.class, "inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full"])}"${_scopeId}><span${_scopeId}>${ssrInterpolate(stockStatus.value.icon)}</span> ${ssrInterpolate(stockStatus.value.label)}</span>`);
              } else {
                _push2(`<!---->`);
              }
              if (product.value.vendor) {
                _push2(`<p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}> Vendor: ${ssrInterpolate(product.value.vendor.name)}</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
              if (product.value.category) {
                _push2(`<p class="text-sm text-gray-500 dark:text-gray-400 mt-2"${_scopeId}> Category: ${ssrInterpolate(product.value.category.name)}</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><div class="text-right flex-shrink-0"${_scopeId}><p class="text-3xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(priceDisplay.value)}</p><p class="text-sm text-gray-500 dark:text-gray-400 mt-1"${_scopeId}> per ${ssrInterpolate(unitLabel.value)}</p>`);
              if (product.value.list_price && product.value.list_price > product.value.price) {
                _push2(`<p class="text-sm text-gray-500 dark:text-gray-400 line-through mt-1"${_scopeId}> List: ${ssrInterpolate(unref(currencyFormatter).format(product.value.list_price))}</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div></div></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-8"${_scopeId}><div class="space-y-4"${_scopeId}><div class="bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden" style="${ssrRenderStyle({ "aspect-ratio": "1 / 1" })}"${_scopeId}><img${ssrRenderAttr("src", productImage.value)}${ssrRenderAttr("alt", product.value.name)} class="w-full h-full object-contain"${_scopeId}></div>`);
              if (product.value.mpn || product.value.gtin || product.value.country_of_origin) {
                _push2(`<div class="bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4"${_scopeId}><h3 class="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-2"${_scopeId}> Product Information </h3><dl class="space-y-1 text-sm"${_scopeId}>`);
                if (product.value.mpn) {
                  _push2(`<div class="flex justify-between"${_scopeId}><dt class="text-gray-600 dark:text-gray-400"${_scopeId}>MPN:</dt><dd class="font-mono text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(product.value.mpn)}</dd></div>`);
                } else {
                  _push2(`<!---->`);
                }
                if (product.value.gtin) {
                  _push2(`<div class="flex justify-between"${_scopeId}><dt class="text-gray-600 dark:text-gray-400"${_scopeId}>GTIN:</dt><dd class="font-mono text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(product.value.gtin)}</dd></div>`);
                } else {
                  _push2(`<!---->`);
                }
                if (product.value.country_of_origin) {
                  _push2(`<div class="flex justify-between"${_scopeId}><dt class="text-gray-600 dark:text-gray-400"${_scopeId}>Country of Origin:</dt><dd class="text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(product.value.country_of_origin)}</dd></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</dl></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><div class="space-y-6"${_scopeId}><div${_scopeId}><h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-2"${_scopeId}> Description </h3><p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed"${_scopeId}>${ssrInterpolate(product.value.description ?? "No description available.")}</p></div>`);
              if (hasDimensions.value) {
                _push2(`<div${_scopeId}><h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-3"${_scopeId}> Physical Dimensions </h3><div class="bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 divide-y divide-gray-200 dark:divide-gray-600"${_scopeId}>`);
                if (product.value.dimensions.length_mm) {
                  _push2(`<div class="px-4 py-2.5 grid grid-cols-2 gap-4"${_scopeId}><dt class="text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>Length</dt><dd class="text-sm text-gray-900 dark:text-white font-mono"${_scopeId}>${ssrInterpolate(product.value.dimensions.length_mm)} mm</dd></div>`);
                } else {
                  _push2(`<!---->`);
                }
                if (product.value.dimensions.width_mm) {
                  _push2(`<div class="px-4 py-2.5 grid grid-cols-2 gap-4"${_scopeId}><dt class="text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>Width</dt><dd class="text-sm text-gray-900 dark:text-white font-mono"${_scopeId}>${ssrInterpolate(product.value.dimensions.width_mm)} mm</dd></div>`);
                } else {
                  _push2(`<!---->`);
                }
                if (product.value.dimensions.height_mm) {
                  _push2(`<div class="px-4 py-2.5 grid grid-cols-2 gap-4"${_scopeId}><dt class="text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>Height</dt><dd class="text-sm text-gray-900 dark:text-white font-mono"${_scopeId}>${ssrInterpolate(product.value.dimensions.height_mm)} mm</dd></div>`);
                } else {
                  _push2(`<!---->`);
                }
                if (product.value.dimensions.weight_g) {
                  _push2(`<div class="px-4 py-2.5 grid grid-cols-2 gap-4"${_scopeId}><dt class="text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>Weight</dt><dd class="text-sm text-gray-900 dark:text-white font-mono"${_scopeId}>${ssrInterpolate(product.value.dimensions.weight_g)} g</dd></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div></div>`);
              } else {
                _push2(`<!---->`);
              }
              if (specifications.value.length > 0) {
                _push2(`<div${_scopeId}><h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-3"${_scopeId}> Technical Specifications </h3><div class="bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 divide-y divide-gray-200 dark:divide-gray-600"${_scopeId}><!--[-->`);
                ssrRenderList(specifications.value, (spec) => {
                  _push2(`<div class="px-4 py-2.5 grid grid-cols-2 gap-4"${_scopeId}><dt class="text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>${ssrInterpolate(spec.name)}</dt><dd class="text-sm text-gray-900 dark:text-white font-mono"${_scopeId}>${ssrInterpolate(spec.value ?? "—")}</dd></div>`);
                });
                _push2(`<!--]--></div></div>`);
              } else {
                _push2(`<div class="bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-6 text-center"${_scopeId}><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}> No technical specifications available for this product. </p></div>`);
              }
              _push2(`<div class="pt-4"${_scopeId}><button type="button" class="w-full px-6 py-3 text-base font-semibold text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed"${_scopeId}>`);
              if (justAdded.value) {
                _push2(`<span class="inline-flex items-center gap-1"${_scopeId}> Added <span aria-hidden="true"${_scopeId}>✓</span></span>`);
              } else {
                _push2(`<span${_scopeId}>Add to Cart</span>`);
              }
              _push2(`</button><p class="text-xs text-gray-500 text-center mt-3"${_scopeId}> Questions? Contact our sales team for assistance. </p></div></div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", { class: "bg-gray-50 min-h-screen" }, [
                createVNode("div", { class: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" }, [
                  createVNode("div", { class: "mb-6" }, [
                    createVNode(unref(Link), {
                      href: _ctx.route("store.index"),
                      class: "inline-flex items-center text-sm text-gray-600 hover:text-gray-900 transition-colors"
                    }, {
                      default: withCtx(() => [
                        (openBlock(), createBlock("svg", {
                          class: "w-4 h-4 mr-2",
                          fill: "none",
                          stroke: "currentColor",
                          viewBox: "0 0 24 24"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            "stroke-width": "2",
                            d: "M15 19l-7-7 7-7"
                          })
                        ])),
                        createTextVNode(" Back to Catalog ")
                      ]),
                      _: 1
                    }, 8, ["href"])
                  ]),
                  loading.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "bg-white rounded-lg shadow-sm p-8"
                  }, [
                    createVNode("div", { class: "animate-pulse space-y-6" }, [
                      createVNode("div", { class: "h-8 bg-gray-200 rounded w-1/3" }),
                      createVNode("div", { class: "grid grid-cols-2 gap-8" }, [
                        createVNode("div", { class: "space-y-4" }, [
                          createVNode("div", { class: "h-64 bg-gray-200 rounded" })
                        ]),
                        createVNode("div", { class: "space-y-4" }, [
                          createVNode("div", { class: "h-4 bg-gray-200 rounded w-2/3" }),
                          createVNode("div", { class: "h-4 bg-gray-200 rounded w-1/2" }),
                          createVNode("div", { class: "h-32 bg-gray-200 rounded" })
                        ])
                      ])
                    ])
                  ])) : error.value ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "bg-white rounded-lg shadow-sm p-8"
                  }, [
                    createVNode("div", { class: "text-center" }, [
                      createVNode("p", { class: "text-red-600 font-medium" }, toDisplayString(error.value), 1),
                      createVNode(unref(Link), {
                        href: _ctx.route("store.index"),
                        class: "mt-4 inline-block text-sm text-gray-600 hover:text-gray-900"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Return to catalog ")
                        ]),
                        _: 1
                      }, 8, ["href"])
                    ])
                  ])) : product.value ? (openBlock(), createBlock("div", {
                    key: 2,
                    class: "bg-white dark:bg-gray-800 rounded-lg shadow-sm"
                  }, [
                    createVNode("div", { class: "border-b border-gray-200 dark:border-gray-700 px-8 py-6" }, [
                      createVNode("div", { class: "flex justify-between items-start gap-4" }, [
                        createVNode("div", { class: "flex-1" }, [
                          createVNode("h1", { class: "text-2xl font-semibold text-gray-900 dark:text-white" }, toDisplayString(product.value.name), 1),
                          createVNode("div", { class: "flex flex-wrap items-center gap-3 mt-3" }, [
                            createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400 font-mono" }, " Part #: " + toDisplayString(partNumber.value), 1),
                            stockStatus.value ? (openBlock(), createBlock("span", {
                              key: 0,
                              class: [stockStatus.value.class, "inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full"]
                            }, [
                              createVNode("span", null, toDisplayString(stockStatus.value.icon), 1),
                              createTextVNode(" " + toDisplayString(stockStatus.value.label), 1)
                            ], 2)) : createCommentVNode("", true),
                            product.value.vendor ? (openBlock(), createBlock("p", {
                              key: 1,
                              class: "text-sm text-gray-500 dark:text-gray-400"
                            }, " Vendor: " + toDisplayString(product.value.vendor.name), 1)) : createCommentVNode("", true)
                          ]),
                          product.value.category ? (openBlock(), createBlock("p", {
                            key: 0,
                            class: "text-sm text-gray-500 dark:text-gray-400 mt-2"
                          }, " Category: " + toDisplayString(product.value.category.name), 1)) : createCommentVNode("", true)
                        ]),
                        createVNode("div", { class: "text-right flex-shrink-0" }, [
                          createVNode("p", { class: "text-3xl font-bold text-gray-900 dark:text-white" }, toDisplayString(priceDisplay.value), 1),
                          createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400 mt-1" }, " per " + toDisplayString(unitLabel.value), 1),
                          product.value.list_price && product.value.list_price > product.value.price ? (openBlock(), createBlock("p", {
                            key: 0,
                            class: "text-sm text-gray-500 dark:text-gray-400 line-through mt-1"
                          }, " List: " + toDisplayString(unref(currencyFormatter).format(product.value.list_price)), 1)) : createCommentVNode("", true)
                        ])
                      ])
                    ]),
                    createVNode("div", { class: "grid grid-cols-1 lg:grid-cols-2 gap-8 p-8" }, [
                      createVNode("div", { class: "space-y-4" }, [
                        createVNode("div", {
                          class: "bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden",
                          style: { "aspect-ratio": "1 / 1" }
                        }, [
                          createVNode("img", {
                            src: productImage.value,
                            alt: product.value.name,
                            class: "w-full h-full object-contain"
                          }, null, 8, ["src", "alt"])
                        ]),
                        product.value.mpn || product.value.gtin || product.value.country_of_origin ? (openBlock(), createBlock("div", {
                          key: 0,
                          class: "bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4"
                        }, [
                          createVNode("h3", { class: "text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-2" }, " Product Information "),
                          createVNode("dl", { class: "space-y-1 text-sm" }, [
                            product.value.mpn ? (openBlock(), createBlock("div", {
                              key: 0,
                              class: "flex justify-between"
                            }, [
                              createVNode("dt", { class: "text-gray-600 dark:text-gray-400" }, "MPN:"),
                              createVNode("dd", { class: "font-mono text-gray-900 dark:text-white" }, toDisplayString(product.value.mpn), 1)
                            ])) : createCommentVNode("", true),
                            product.value.gtin ? (openBlock(), createBlock("div", {
                              key: 1,
                              class: "flex justify-between"
                            }, [
                              createVNode("dt", { class: "text-gray-600 dark:text-gray-400" }, "GTIN:"),
                              createVNode("dd", { class: "font-mono text-gray-900 dark:text-white" }, toDisplayString(product.value.gtin), 1)
                            ])) : createCommentVNode("", true),
                            product.value.country_of_origin ? (openBlock(), createBlock("div", {
                              key: 2,
                              class: "flex justify-between"
                            }, [
                              createVNode("dt", { class: "text-gray-600 dark:text-gray-400" }, "Country of Origin:"),
                              createVNode("dd", { class: "text-gray-900 dark:text-white" }, toDisplayString(product.value.country_of_origin), 1)
                            ])) : createCommentVNode("", true)
                          ])
                        ])) : createCommentVNode("", true)
                      ]),
                      createVNode("div", { class: "space-y-6" }, [
                        createVNode("div", null, [
                          createVNode("h3", { class: "text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-2" }, " Description "),
                          createVNode("p", { class: "text-sm text-gray-700 dark:text-gray-300 leading-relaxed" }, toDisplayString(product.value.description ?? "No description available."), 1)
                        ]),
                        hasDimensions.value ? (openBlock(), createBlock("div", { key: 0 }, [
                          createVNode("h3", { class: "text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-3" }, " Physical Dimensions "),
                          createVNode("div", { class: "bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 divide-y divide-gray-200 dark:divide-gray-600" }, [
                            product.value.dimensions.length_mm ? (openBlock(), createBlock("div", {
                              key: 0,
                              class: "px-4 py-2.5 grid grid-cols-2 gap-4"
                            }, [
                              createVNode("dt", { class: "text-sm font-medium text-gray-700 dark:text-gray-300" }, "Length"),
                              createVNode("dd", { class: "text-sm text-gray-900 dark:text-white font-mono" }, toDisplayString(product.value.dimensions.length_mm) + " mm", 1)
                            ])) : createCommentVNode("", true),
                            product.value.dimensions.width_mm ? (openBlock(), createBlock("div", {
                              key: 1,
                              class: "px-4 py-2.5 grid grid-cols-2 gap-4"
                            }, [
                              createVNode("dt", { class: "text-sm font-medium text-gray-700 dark:text-gray-300" }, "Width"),
                              createVNode("dd", { class: "text-sm text-gray-900 dark:text-white font-mono" }, toDisplayString(product.value.dimensions.width_mm) + " mm", 1)
                            ])) : createCommentVNode("", true),
                            product.value.dimensions.height_mm ? (openBlock(), createBlock("div", {
                              key: 2,
                              class: "px-4 py-2.5 grid grid-cols-2 gap-4"
                            }, [
                              createVNode("dt", { class: "text-sm font-medium text-gray-700 dark:text-gray-300" }, "Height"),
                              createVNode("dd", { class: "text-sm text-gray-900 dark:text-white font-mono" }, toDisplayString(product.value.dimensions.height_mm) + " mm", 1)
                            ])) : createCommentVNode("", true),
                            product.value.dimensions.weight_g ? (openBlock(), createBlock("div", {
                              key: 3,
                              class: "px-4 py-2.5 grid grid-cols-2 gap-4"
                            }, [
                              createVNode("dt", { class: "text-sm font-medium text-gray-700 dark:text-gray-300" }, "Weight"),
                              createVNode("dd", { class: "text-sm text-gray-900 dark:text-white font-mono" }, toDisplayString(product.value.dimensions.weight_g) + " g", 1)
                            ])) : createCommentVNode("", true)
                          ])
                        ])) : createCommentVNode("", true),
                        specifications.value.length > 0 ? (openBlock(), createBlock("div", { key: 1 }, [
                          createVNode("h3", { class: "text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-3" }, " Technical Specifications "),
                          createVNode("div", { class: "bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 divide-y divide-gray-200 dark:divide-gray-600" }, [
                            (openBlock(true), createBlock(Fragment, null, renderList(specifications.value, (spec) => {
                              return openBlock(), createBlock("div", {
                                key: spec.name,
                                class: "px-4 py-2.5 grid grid-cols-2 gap-4"
                              }, [
                                createVNode("dt", { class: "text-sm font-medium text-gray-700 dark:text-gray-300" }, toDisplayString(spec.name), 1),
                                createVNode("dd", { class: "text-sm text-gray-900 dark:text-white font-mono" }, toDisplayString(spec.value ?? "—"), 1)
                              ]);
                            }), 128))
                          ])
                        ])) : (openBlock(), createBlock("div", {
                          key: 2,
                          class: "bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-6 text-center"
                        }, [
                          createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, " No technical specifications available for this product. ")
                        ])),
                        createVNode("div", { class: "pt-4" }, [
                          createVNode("button", {
                            type: "button",
                            class: "w-full px-6 py-3 text-base font-semibold text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed",
                            onClick: handleAddToCart
                          }, [
                            justAdded.value ? (openBlock(), createBlock("span", {
                              key: 0,
                              class: "inline-flex items-center gap-1"
                            }, [
                              createTextVNode(" Added "),
                              createVNode("span", { "aria-hidden": "true" }, "✓")
                            ])) : (openBlock(), createBlock("span", { key: 1 }, "Add to Cart"))
                          ]),
                          createVNode("p", { class: "text-xs text-gray-500 text-center mt-3" }, " Questions? Contact our sales team for assistance. ")
                        ])
                      ])
                    ])
                  ])) : createCommentVNode("", true)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Store/ProductDetail.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
