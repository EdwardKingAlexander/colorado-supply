import { ref, computed, watch, mergeProps, useSSRContext, onBeforeUnmount, unref, withCtx, createVNode, createTextVNode, toDisplayString, onMounted, createBlock, createCommentVNode, openBlock, Fragment, renderList } from "vue";
import { ssrRenderAttrs, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr, ssrInterpolate, ssrRenderList, ssrRenderComponent } from "vue/server-renderer";
import axios from "axios";
import { _ as _sfc_main$5 } from "./AuthenticatedLayout-DnHFEBKh.js";
import { Link, Head } from "@inertiajs/vue3";
import { u as useCartStore } from "./useCartStore-OHIRORWN.js";
import "./ApplicationLogo-B2173abF.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main$4 = {
  __name: "SearchBar",
  __ssrInlineRender: true,
  props: {
    modelValue: {
      type: String,
      default: ""
    },
    loading: {
      type: Boolean,
      default: false
    },
    placeholder: {
      type: String,
      default: "Search catalog..."
    },
    debounce: {
      type: Number,
      default: 350
    },
    variant: {
      type: String,
      default: "card",
      validator: (value) => ["card", "inline"].includes(value)
    }
  },
  emits: ["update:modelValue", "search"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const localQuery = ref(props.modelValue ?? "");
    const wrapperClasses = computed(() => {
      if (props.variant === "inline") {
        return "w-full";
      }
      return "bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4";
    });
    const innerClasses = computed(() => {
      if (props.variant === "inline") {
        return "w-full flex flex-col gap-2 sm:flex-row sm:items-center";
      }
      return "max-w-3xl mx-auto flex gap-3";
    });
    const inputClasses = computed(() => {
      const base = "w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 bg-white dark:bg-gray-900";
      if (props.variant === "inline") {
        return base + " shadow-sm";
      }
      return base;
    });
    const buttonClasses = computed(() => {
      const base = "text-sm font-semibold text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed";
      if (props.variant === "inline") {
        return base + " px-4 py-2 bg-gray-900 hover:bg-gray-800 flex-shrink-0";
      }
      return base + " px-5 py-3 bg-gray-900 hover:bg-gray-800";
    });
    const watchDebounced = (source, callback, delay = 300) => {
      let timeoutId;
      return watch(
        source,
        (value, oldValue, onCleanup) => {
          if (timeoutId) {
            clearTimeout(timeoutId);
          }
          timeoutId = setTimeout(() => callback(value, oldValue), delay);
          onCleanup(() => clearTimeout(timeoutId));
        },
        { flush: "post" }
      );
    };
    watch(
      () => props.modelValue,
      (value) => {
        if (value === localQuery.value) {
          return;
        }
        localQuery.value = value ?? "";
      }
    );
    watchDebounced(
      () => localQuery.value,
      (value) => {
        emit("search", value.trim());
      },
      props.debounce
    );
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<form${ssrRenderAttrs(mergeProps({ class: wrapperClasses.value }, _attrs))}><div class="${ssrRenderClass(innerClasses.value)}"><input${ssrRenderAttr("value", localQuery.value)}${ssrRenderAttr("placeholder", __props.placeholder)} class="${ssrRenderClass(inputClasses.value)}" type="text" name="store-search" autocomplete="off"><button type="submit" class="${ssrRenderClass(buttonClasses.value)}"${ssrIncludeBooleanAttr(__props.loading) ? " disabled" : ""}>`);
      if (__props.loading) {
        _push(`<span class="animate-pulse">Searching...</span>`);
      } else {
        _push(`<span>Search</span>`);
      }
      _push(`</button></div></form>`);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Store/SearchBar.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const PRODUCT_TYPE_FILTER = "Product Type";
const _sfc_main$3 = {
  __name: "ParametricFilters",
  __ssrInlineRender: true,
  props: {
    filters: {
      type: Array,
      default: () => []
    },
    activeFilters: {
      type: Object,
      default: () => ({})
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ["update:activeFilters", "clear"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const orderedFilters = computed(() => {
      if (!Array.isArray(props.filters)) {
        return [];
      }
      const productTypeFilter = props.filters.find((filter) => filter.name === PRODUCT_TYPE_FILTER);
      const otherFilters = props.filters.filter((filter) => filter.name !== PRODUCT_TYPE_FILTER);
      return productTypeFilter ? [productTypeFilter, ...otherFilters] : otherFilters;
    });
    const isValueSelected = (attributeName, value) => {
      const filterValue = props.activeFilters[attributeName];
      if (Array.isArray(filterValue)) {
        return filterValue.includes(value);
      }
      return filterValue === value;
    };
    const isProductTypeFilter = (filter) => filter.name === PRODUCT_TYPE_FILTER;
    const hasActiveFilters = computed(() => {
      return Object.values(props.activeFilters).some((value) => {
        if (Array.isArray(value)) {
          return value.length > 0;
        }
        return value !== void 0 && value !== null;
      });
    });
    const activeFilterCount = computed(() => {
      return Object.values(props.activeFilters).reduce((count, value) => {
        if (Array.isArray(value)) {
          return count + value.length;
        }
        return count + 1;
      }, 0);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white/95 dark:bg-gray-900/90 rounded-2xl shadow-lg ring-1 ring-gray-100 dark:ring-gray-800 p-4" }, _attrs))}><div class="flex items-center justify-between mb-4"><h2 class="text-base font-semibold tracking-tight text-gray-900 dark:text-white"> Filter by Specs </h2>`);
      if (hasActiveFilters.value) {
        _push(`<button type="button" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"> Clear All (${ssrInterpolate(activeFilterCount.value)}) </button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (__props.loading) {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(4, (i) => {
          _push(`<div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-3"><div class="flex items-center justify-between mb-3"><div class="h-3 w-24 bg-gray-100 dark:bg-gray-700 rounded animate-pulse"></div><div class="h-3 w-10 bg-gray-100 dark:bg-gray-700 rounded animate-pulse"></div></div><div class="space-y-2"><!--[-->`);
          ssrRenderList(3, (j) => {
            _push(`<div class="h-4 bg-gray-50 dark:bg-gray-600/70 rounded animate-pulse"></div>`);
          });
          _push(`<!--]--></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (__props.filters.length === 0) {
        _push(`<div class="text-center py-6"><p class="text-sm text-gray-500 dark:text-gray-400"> No filters available for this category yet. Try selecting a different category or broadening your search. </p></div>`);
      } else {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(orderedFilters.value, (filter) => {
          _push(`<div class="pb-4 border-b border-gray-100 dark:border-gray-800 last:border-0"><h3 class="text-sm font-semibold tracking-tight text-gray-900 dark:text-white mb-2">${ssrInterpolate(filter.name)}</h3>`);
          if (isProductTypeFilter(filter)) {
            _push(`<div class="space-y-1"><!--[-->`);
            ssrRenderList(filter.values, (value) => {
              _push(`<label class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition"><input type="checkbox"${ssrRenderAttr("name", `filter-${filter.name}-${value}`)}${ssrRenderAttr("value", value)}${ssrIncludeBooleanAttr(isValueSelected(filter.name, value)) ? " checked" : ""} class="w-4 h-4 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800"><span class="ml-2 text-sm font-mono text-gray-700 dark:text-gray-300">${ssrInterpolate(value)}</span></label>`);
            });
            _push(`<!--]--></div>`);
          } else if (filter.type === "string" || filter.type === "select") {
            _push(`<div class="space-y-1"><!--[-->`);
            ssrRenderList(filter.values, (value) => {
              _push(`<label class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition"><input type="radio"${ssrRenderAttr("name", `filter-${filter.name}`)}${ssrRenderAttr("value", value)}${ssrIncludeBooleanAttr(isValueSelected(filter.name, value)) ? " checked" : ""} class="w-4 h-4 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800"><span class="ml-2 text-sm font-mono text-gray-700 dark:text-gray-300">${ssrInterpolate(value)}</span></label>`);
            });
            _push(`<!--]--></div>`);
          } else if (filter.type === "boolean") {
            _push(`<div class="space-y-1"><!--[-->`);
            ssrRenderList(["true", "false"], (value) => {
              _push(`<label class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition"><input type="radio"${ssrRenderAttr("name", `filter-${filter.name}`)}${ssrRenderAttr("value", value)}${ssrIncludeBooleanAttr(isValueSelected(filter.name, value)) ? " checked" : ""} class="w-4 h-4 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800"><span class="ml-2 text-sm font-mono text-gray-700 dark:text-gray-300">${ssrInterpolate(value === "true" ? "Yes" : "No")}</span></label>`);
            });
            _push(`<!--]--></div>`);
          } else if (filter.type === "integer" || filter.type === "float") {
            _push(`<div class="space-y-1"><!--[-->`);
            ssrRenderList(filter.values, (value) => {
              _push(`<label class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition"><input type="radio"${ssrRenderAttr("name", `filter-${filter.name}`)}${ssrRenderAttr("value", value)}${ssrIncludeBooleanAttr(isValueSelected(filter.name, value)) ? " checked" : ""} class="w-4 h-4 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800"><span class="ml-2 text-sm font-mono text-gray-700 dark:text-gray-300">${ssrInterpolate(value)}</span></label>`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Store/ParametricFilters.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = {
  __name: "ProductRow",
  __ssrInlineRender: true,
  props: {
    product: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    const currencyFormatter = new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD"
    });
    const props = __props;
    const partNumber = computed(() => props.product.sku ?? props.product.slug ?? `#${props.product.id}`);
    const priceDisplay = computed(() => {
      const price = Number(props.product.price);
      if (Number.isFinite(price)) {
        return currencyFormatter.format(price);
      }
      return "Call for pricing";
    });
    const unitLabel = computed(() => props.product.unit ?? "EA");
    const specPreview = computed(() => {
      if (!Array.isArray(props.product.specifications) || props.product.specifications.length === 0) {
        return null;
      }
      return props.product.specifications.slice(0, 2).map((spec) => `${spec.name}: ${spec.value}`).join(" | ");
    });
    const productSlug = computed(() => props.product.slug ?? props.product.id);
    const buildPlaceholderImage = (seed, width = 400, height = 400) => {
      const safeSeed = encodeURIComponent(seed ?? "product");
      return `https://picsum.photos/seed/${safeSeed}/${width}/${height}`;
    };
    const productImage = computed(() => {
      const image = props.product.image;
      if (typeof image === "string" && image.length > 0) {
        if (image.startsWith("http://") || image.startsWith("https://")) {
          return image;
        }
        return `/storage/${image.replace(/^\/+/, "")}`;
      }
      const seed = props.product.slug ?? props.product.id ?? "catalog-item";
      return buildPlaceholderImage(seed, 400, 400);
    });
    const stockStatus = computed(() => {
      if (!props.product.in_stock) {
        return {
          label: props.product.lead_time_days ? `Ships in ${props.product.lead_time_days} days` : "Out of Stock",
          class: "bg-yellow-100 text-yellow-800"
        };
      }
      return {
        label: "In Stock",
        class: "bg-green-100 text-green-800"
      };
    });
    useCartStore();
    const justAdded = ref(false);
    const quantity = ref(1);
    onBeforeUnmount(() => {
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "px-4 py-4 hover:bg-gray-50/70 dark:hover:bg-gray-800 transition-all" }, _attrs))}><div class="hidden lg:grid lg:grid-cols-12 gap-4 items-center"><div class="col-span-1">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("store.show", productSlug.value)
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img${ssrRenderAttr("src", productImage.value)}${ssrRenderAttr("alt", __props.product.name)} class="w-16 h-16 object-cover rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"${_scopeId}>`);
          } else {
            return [
              createVNode("img", {
                src: productImage.value,
                alt: __props.product.name,
                class: "w-16 h-16 object-cover rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
              }, null, 8, ["src", "alt"])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="col-span-2"><p class="text-sm font-mono text-gray-900 dark:text-white tracking-tight">${ssrInterpolate(partNumber.value)}</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase">${ssrInterpolate(unitLabel.value)}</p><span class="${ssrRenderClass([stockStatus.value.class, "inline-flex items-center gap-1 mt-1 px-2 py-0.5 text-[11px] font-semibold rounded-full"])}">${ssrInterpolate(stockStatus.value.label)}</span></div><div class="col-span-3">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("store.show", productSlug.value),
        class: "text-sm font-medium text-gray-900 dark:text-white hover:text-gray-700 dark:hover:text-gray-300 hover:underline"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(__props.product.name)}`);
          } else {
            return [
              createTextVNode(toDisplayString(__props.product.name), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      if (specPreview.value) {
        _push(`<p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1">${ssrInterpolate(specPreview.value)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="col-span-2"><p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">${ssrInterpolate(__props.product.description ?? "No description available.")}</p></div><div class="col-span-2"><p class="text-sm font-semibold text-gray-900 dark:text-white">${ssrInterpolate(priceDisplay.value)}</p>`);
      if (__props.product.vendor) {
        _push(`<p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-mono">${ssrInterpolate(__props.product.vendor.name)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="col-span-2"><div class="flex items-center justify-between gap-2 mb-2"><button type="button" class="inline-flex h-8 w-8 items-center justify-center border border-gray-300 rounded text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"${ssrIncludeBooleanAttr(quantity.value <= 1) ? " disabled" : ""}> − </button><span class="w-10 text-center text-sm font-semibold text-gray-900">${ssrInterpolate(quantity.value)}</span><button type="button" class="inline-flex h-8 w-8 items-center justify-center border border-gray-300 rounded text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors"> + </button></div><button type="button" class="w-full px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed">`);
      if (justAdded.value) {
        _push(`<span class="inline-flex items-center gap-1"> Added <span aria-hidden="true">✓</span></span>`);
      } else {
        _push(`<span>Add to Cart</span>`);
      }
      _push(`</button></div></div><div class="lg:hidden space-y-3"><div class="flex gap-3">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("store.show", productSlug.value),
        class: "flex-shrink-0"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img${ssrRenderAttr("src", productImage.value)}${ssrRenderAttr("alt", __props.product.name)} class="w-20 h-20 object-cover rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"${_scopeId}>`);
          } else {
            return [
              createVNode("img", {
                src: productImage.value,
                alt: __props.product.name,
                class: "w-20 h-20 object-cover rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
              }, null, 8, ["src", "alt"])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="flex-1 min-w-0">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("store.show", productSlug.value),
        class: "text-sm font-medium text-gray-900 dark:text-white hover:text-gray-700 dark:hover:text-gray-300 hover:underline"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(__props.product.name)}`);
          } else {
            return [
              createTextVNode(toDisplayString(__props.product.name), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1">${ssrInterpolate(partNumber.value)}</p><div class="flex items-center gap-2 mt-1"><span class="${ssrRenderClass([stockStatus.value.class, "inline-block px-2 py-0.5 text-xs font-medium rounded"])}">${ssrInterpolate(stockStatus.value.label)}</span><p class="text-xs text-gray-500 dark:text-gray-400">${ssrInterpolate(unitLabel.value)}</p></div>`);
      if (specPreview.value) {
        _push(`<p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1">${ssrInterpolate(specPreview.value)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="text-right flex-shrink-0"><p class="text-sm font-semibold text-gray-900 dark:text-white">${ssrInterpolate(priceDisplay.value)}</p>`);
      if (__props.product.vendor) {
        _push(`<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${ssrInterpolate(__props.product.vendor.name)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">${ssrInterpolate(__props.product.description ?? "No description available.")}</p><div class="flex items-center gap-2"><button type="button" class="px-3 py-1 border border-gray-300 rounded text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"${ssrIncludeBooleanAttr(quantity.value <= 1) ? " disabled" : ""}> − </button><span class="w-10 text-center text-sm font-semibold text-gray-900">${ssrInterpolate(quantity.value)}</span><button type="button" class="px-3 py-1 border border-gray-300 rounded text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors"> + </button><button type="button" class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed">`);
      if (justAdded.value) {
        _push(`<span class="inline-flex items-center gap-1"> Added <span aria-hidden="true">✓</span></span>`);
      } else {
        _push(`<span>Add to Cart</span>`);
      }
      _push(`</button></div></div></div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Store/ProductRow.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "ProductList",
  __ssrInlineRender: true,
  props: {
    products: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    meta: {
      type: Object,
      default: null
    },
    isSearch: {
      type: Boolean,
      default: false
    },
    currentPage: {
      type: Number,
      default: 1
    },
    totalPages: {
      type: Number,
      default: 1
    }
  },
  emits: ["previous", "next", "clear-filters", "reset-navigation"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const headerSubtitle = computed(() => {
      if (props.loading) {
        return "Loading catalog data...";
      }
      if (!props.products.length) {
        return props.isSearch ? "No products found. Adjust your filters or search term." : "Products will appear here once available.";
      }
      const from = props.meta?.from ?? 1;
      const to = props.meta?.to ?? props.products.length;
      const total = props.meta?.total ?? props.products.length;
      return `Showing ${from}-${to} of ${total} ${props.isSearch ? "results" : "items"}`;
    });
    const canGoPrevious = computed(() => props.currentPage > 1);
    const canGoNext = computed(() => props.currentPage < props.totalPages);
    const showPagination = computed(() => props.totalPages > 1);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white/95 dark:bg-gray-900/90 rounded-2xl shadow-xl ring-1 ring-gray-100 dark:ring-gray-800 overflow-hidden" }, _attrs))}><div class="p-5 border-b border-gray-100 dark:border-gray-800"><h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">Products</h1><p class="text-sm font-mono text-gray-500 dark:text-gray-400 mt-1">${ssrInterpolate(headerSubtitle.value)}</p></div><div class="hidden lg:grid lg:grid-cols-12 gap-4 px-5 py-3 bg-gray-50/90 dark:bg-gray-800/90 border-b border-gray-100 dark:border-gray-800 text-[11px] font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-[0.2em]"><div class="col-span-1">Image</div><div class="col-span-2">Part Number</div><div class="col-span-3">Name</div><div class="col-span-2">Description</div><div class="col-span-2">Price</div><div class="col-span-2 text-right">Cart</div></div>`);
      if (__props.loading) {
        _push(`<div class="grid grid-cols-1 gap-4 px-4 py-6 sm:grid-cols-2"><!--[-->`);
        ssrRenderList(4, (row) => {
          _push(`<div class="border border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-4 space-y-4 animate-pulse bg-gray-50/60 dark:bg-gray-700/40"><div class="flex items-center gap-3"><div class="w-16 h-16 rounded bg-gray-200 dark:bg-gray-600"></div><div class="flex-1 space-y-2"><div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-3/4"></div><div class="h-3 bg-gray-100 dark:bg-gray-500 rounded w-1/2"></div></div></div><div class="h-3 bg-gray-100 dark:bg-gray-500 rounded w-full"></div><div class="flex items-center justify-between"><div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-20"></div><div class="h-8 bg-gray-200 dark:bg-gray-600 rounded w-24"></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!--[-->`);
        if (__props.products.length) {
          _push(`<div class="divide-y divide-gray-100 dark:divide-gray-800"><!--[-->`);
          ssrRenderList(__props.products, (product) => {
            _push(ssrRenderComponent(_sfc_main$2, {
              key: product.id,
              product
            }, null, _parent));
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<div class="p-10 text-center text-sm text-gray-500"><div class="inline-flex flex-col items-center gap-2 rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 px-8 py-6 shadow-sm"><p class="font-semibold text-lg text-gray-800 dark:text-gray-100">No products match your filters</p><p class="text-sm text-gray-500 dark:text-gray-400"> Try removing a filter, widening the search term, or browsing categories. </p><div class="mt-4 flex flex-wrap justify-center gap-2"><button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition"> Clear filters </button><button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition"> Browse categories </button></div></div></div>`);
        }
        _push(`<!--]-->`);
      }
      if (showPagination.value) {
        _push(`<div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 text-sm bg-gray-50/60 dark:bg-gray-900/60"><button type="button" class="${ssrRenderClass([canGoPrevious.value ? "border-gray-300 text-gray-700 hover:bg-gray-50" : "border-gray-200 text-gray-400 cursor-not-allowed", "px-4 py-2 border rounded-lg font-medium transition"])}"${ssrIncludeBooleanAttr(!canGoPrevious.value) ? " disabled" : ""}> Previous </button><p class="text-xs text-gray-500"> Page ${ssrInterpolate(__props.currentPage)} of ${ssrInterpolate(__props.totalPages)}</p><button type="button" class="${ssrRenderClass([canGoNext.value ? "border-gray-300 text-gray-700 hover:bg-gray-50" : "border-gray-200 text-gray-400 cursor-not-allowed", "px-4 py-2 border rounded-lg font-medium transition"])}"${ssrIncludeBooleanAttr(!canGoNext.value) ? " disabled" : ""}> Next </button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Store/ProductList.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "StoreIndex",
  __ssrInlineRender: true,
  setup(__props) {
    const allCategories = ref([]);
    const categoriesLoading = ref(true);
    const selectedParentCategory = ref(null);
    const selectedSubcategory = ref(null);
    const navigationLevel = ref("categories");
    const products = ref([]);
    const productsLoading = ref(true);
    const pagination = ref(null);
    const searchTerm = ref("");
    const currentPage = ref(1);
    const totalPages = ref(1);
    const availableFilters = ref([]);
    const activeFilters = ref({});
    const filtersLoading = ref(false);
    const isSearching = computed(() => searchTerm.value.trim().length > 0);
    const showFilters = computed(() => navigationLevel.value === "products" && Boolean(selectedSubcategory.value));
    const showingProductView = computed(() => navigationLevel.value === "products" && (selectedSubcategory.value || isSearching.value));
    const isGlobalSearch = computed(() => navigationLevel.value === "products" && !selectedSubcategory.value && isSearching.value);
    const cartStore = useCartStore();
    const cartItemCount = computed(() => cartStore.itemCount.value);
    const parentCategories = computed(() => {
      return allCategories.value.filter((cat) => !cat.parent_id);
    });
    const subcategories = computed(() => {
      if (!selectedParentCategory.value) return [];
      return allCategories.value.filter((cat) => cat.parent_id === selectedParentCategory.value.id);
    });
    const activeFilterPills = computed(() => {
      const pills = [];
      const trimmedSearch = searchTerm.value.trim();
      if (trimmedSearch.length > 0) {
        pills.push({
          id: `search-${trimmedSearch}`,
          type: "search",
          label: `Search: ${trimmedSearch}`
        });
      }
      Object.entries(activeFilters.value).forEach(([attribute, value]) => {
        if (Array.isArray(value)) {
          value.forEach((option, index) => {
            pills.push({
              id: `${attribute}-${option}-${index}`,
              type: "filter",
              attribute,
              value: option,
              label: `${attribute}: ${option}`
            });
          });
        } else if (value !== void 0 && value !== null && value !== "") {
          pills.push({
            id: `${attribute}-${value}`,
            type: "filter",
            attribute,
            value,
            label: `${attribute}: ${value}`
          });
        }
      });
      return pills;
    });
    const hasRemovableFilters = computed(() => activeFilterPills.value.some((pill) => pill.type === "filter"));
    const breadcrumbTrail = computed(() => {
      const crumbs = [{
        key: "catalog",
        label: "Browse Catalog",
        target: "catalog",
        clickable: navigationLevel.value !== "categories" || Boolean(selectedParentCategory.value) || Boolean(selectedSubcategory.value) || isSearching.value
      }];
      if (selectedParentCategory.value) {
        crumbs.push({
          key: `parent-${selectedParentCategory.value.id}`,
          label: selectedParentCategory.value.name,
          target: "parent",
          clickable: navigationLevel.value === "products" && Boolean(selectedSubcategory.value)
        });
      }
      if (selectedSubcategory.value) {
        crumbs.push({
          key: `sub-${selectedSubcategory.value.id}`,
          label: selectedSubcategory.value.name,
          target: null,
          clickable: false
        });
      } else if (isGlobalSearch.value) {
        crumbs.push({
          key: "search",
          label: `Search: "${searchTerm.value.trim()}"`,
          target: null,
          clickable: false
        });
      }
      return crumbs;
    });
    const fetchCategories = async () => {
      categoriesLoading.value = true;
      try {
        const { data } = await axios.get("/api/v1/store/categories");
        allCategories.value = data.data ?? [];
      } catch (error) {
        console.error("Failed to load categories", error);
        allCategories.value = [];
      } finally {
        categoriesLoading.value = false;
      }
    };
    const resetToCategories = () => {
      selectedParentCategory.value = null;
      selectedSubcategory.value = null;
      navigationLevel.value = "categories";
      searchTerm.value = "";
      products.value = [];
      availableFilters.value = [];
      activeFilters.value = {};
      pagination.value = null;
      currentPage.value = 1;
      totalPages.value = 1;
      productsLoading.value = false;
    };
    const showParentCategoryList = () => {
      if (!selectedParentCategory.value) {
        return;
      }
      selectedSubcategory.value = null;
      navigationLevel.value = "subcategories";
      products.value = [];
      availableFilters.value = [];
      activeFilters.value = {};
      pagination.value = null;
      currentPage.value = 1;
      totalPages.value = 1;
      productsLoading.value = false;
    };
    const selectParentCategory = (category) => {
      selectedParentCategory.value = category;
      selectedSubcategory.value = null;
      navigationLevel.value = "subcategories";
      products.value = [];
      availableFilters.value = [];
      activeFilters.value = {};
    };
    const selectSubcategory = async (subcategory) => {
      selectedSubcategory.value = subcategory;
      navigationLevel.value = "products";
      activeFilters.value = {};
      currentPage.value = 1;
      await Promise.all([
        fetchFilters(),
        fetchProducts()
      ]);
    };
    const fetchFilters = async () => {
      if (!selectedSubcategory.value) return;
      filtersLoading.value = true;
      const params = {
        category_id: selectedSubcategory.value.id,
        ...Object.keys(activeFilters.value).length > 0 ? { filters: activeFilters.value } : {}
      };
      try {
        const response = await axios.get("/api/v1/store/filters", { params });
        availableFilters.value = response.data.data ?? [];
      } catch (error) {
        console.error("Failed to load filters", error);
        availableFilters.value = [];
      } finally {
        filtersLoading.value = false;
      }
    };
    const fetchProducts = async () => {
      const trimmedQuery = searchTerm.value.trim();
      if (!selectedSubcategory.value && trimmedQuery === "") {
        return;
      }
      productsLoading.value = true;
      const endpoint = trimmedQuery.length ? "/api/v1/store/search" : "/api/v1/store/products";
      const params = {
        page: currentPage.value
      };
      if (selectedSubcategory.value) {
        params.category_id = selectedSubcategory.value.id;
      }
      if (trimmedQuery.length) {
        params.query = trimmedQuery;
      }
      if (Object.keys(activeFilters.value).length > 0) {
        params.filters = activeFilters.value;
      }
      try {
        const response = await axios.get(endpoint, { params });
        products.value = response.data.data ?? [];
        pagination.value = response.data.meta ?? null;
        currentPage.value = response.data.meta?.current_page ?? 1;
        totalPages.value = response.data.meta?.last_page ?? 1;
      } catch (error) {
        console.error("Failed to load products", error);
        products.value = [];
        pagination.value = null;
        currentPage.value = 1;
        totalPages.value = 1;
      } finally {
        productsLoading.value = false;
      }
    };
    const handleSearch = async (term) => {
      searchTerm.value = term;
      currentPage.value = 1;
      const trimmedQuery = term.trim();
      if (trimmedQuery === "") {
        if (!selectedSubcategory.value) {
          navigationLevel.value = "categories";
          products.value = [];
          pagination.value = null;
          totalPages.value = 1;
          productsLoading.value = false;
          return;
        }
        await fetchProducts();
        return;
      }
      if (navigationLevel.value !== "products") {
        navigationLevel.value = "products";
      }
      await fetchProducts();
    };
    const handleFiltersUpdate = async (newFilters) => {
      activeFilters.value = newFilters;
      currentPage.value = 1;
      await Promise.all([
        fetchFilters(),
        fetchProducts()
      ]);
    };
    const removeFilterPill = async (pill) => {
      if (pill.type === "search") {
        await handleSearch("");
        return;
      }
      const updatedFilters = { ...activeFilters.value };
      const currentValue = updatedFilters[pill.attribute];
      if (Array.isArray(currentValue)) {
        const nextValues = currentValue.filter((value) => value !== pill.value);
        if (nextValues.length > 0) {
          updatedFilters[pill.attribute] = nextValues;
        } else {
          delete updatedFilters[pill.attribute];
        }
      } else {
        delete updatedFilters[pill.attribute];
      }
      activeFilters.value = updatedFilters;
      currentPage.value = 1;
      await Promise.all([
        fetchFilters(),
        fetchProducts()
      ]);
    };
    const handleClearFilters = async () => {
      activeFilters.value = {};
      currentPage.value = 1;
      await Promise.all([
        fetchFilters(),
        fetchProducts()
      ]);
    };
    const goToPreviousPage = () => {
      if (currentPage.value <= 1) return;
      currentPage.value -= 1;
      fetchProducts();
    };
    const goToNextPage = () => {
      if (currentPage.value >= totalPages.value) return;
      currentPage.value += 1;
      fetchProducts();
    };
    const handleBreadcrumbClick = (crumb) => {
      if (!crumb.clickable || !crumb.target) {
        return;
      }
      if (crumb.target === "catalog") {
        resetToCategories();
        return;
      }
      if (crumb.target === "parent") {
        showParentCategoryList();
      }
    };
    onMounted(() => {
      fetchCategories();
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Store" }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$5, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-gray-50 dark:bg-gray-900 min-h-screen"${_scopeId}><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"${_scopeId}><div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"${_scopeId}><div class="space-y-2"${_scopeId}><p class="text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase"${_scopeId}>Industrial Supply Catalog</p><h1 class="text-3xl font-bold text-gray-900 dark:text-white"${_scopeId}>Browse products &amp; build your cart</h1><p class="text-sm text-gray-600 dark:text-gray-400"${_scopeId}> Use the search bar and filters below to zero in on the exact specs you need. </p></div>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("store.cart"),
              class: "inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors whitespace-nowrap"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"${_scopeId2}><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75h-9A2.25 2.25 0 005.25 6v12a2.25 2.25 0 002.25 2.25h9A2.25 2.25 0 0018.75 18V6a2.25 2.25 0 00-2.25-2.25z"${_scopeId2}></path><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5h7.5M8.25 12h7.5M8.25 16.5h4.5"${_scopeId2}></path></svg><span${_scopeId2}>View Cart</span>`);
                  if (cartItemCount.value) {
                    _push3(`<span class="ml-1 inline-flex items-center justify-center rounded-full bg-gray-900 dark:bg-blue-600 px-2 py-0.5 text-xs font-bold text-white"${_scopeId2}>${ssrInterpolate(cartItemCount.value)}</span>`);
                  } else {
                    _push3(`<!---->`);
                  }
                } else {
                  return [
                    (openBlock(), createBlock("svg", {
                      class: "h-4 w-4",
                      fill: "none",
                      stroke: "currentColor",
                      "stroke-width": "1.5",
                      viewBox: "0 0 24 24"
                    }, [
                      createVNode("path", {
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        d: "M16.5 3.75h-9A2.25 2.25 0 005.25 6v12a2.25 2.25 0 002.25 2.25h9A2.25 2.25 0 0018.75 18V6a2.25 2.25 0 00-2.25-2.25z"
                      }),
                      createVNode("path", {
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        d: "M8.25 7.5h7.5M8.25 12h7.5M8.25 16.5h4.5"
                      })
                    ])),
                    createVNode("span", null, "View Cart"),
                    cartItemCount.value ? (openBlock(), createBlock("span", {
                      key: 0,
                      class: "ml-1 inline-flex items-center justify-center rounded-full bg-gray-900 dark:bg-blue-600 px-2 py-0.5 text-xs font-bold text-white"
                    }, toDisplayString(cartItemCount.value), 1)) : createCommentVNode("", true)
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div><div class="sticky top-24 z-0 -mx-4 sm:-mx-6 lg:-mx-8 mb-8"${_scopeId}><div class="bg-gray-50/95 dark:bg-gray-900/95 border border-gray-200 dark:border-gray-700 shadow-sm backdrop-blur rounded-b-2xl px-4 sm:px-6 lg:px-8 py-3 space-y-3"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$4, {
              modelValue: searchTerm.value,
              "onUpdate:modelValue": ($event) => searchTerm.value = $event,
              variant: "inline",
              loading: navigationLevel.value === "products" ? productsLoading.value : false,
              placeholder: "Search by product, SKU, or spec",
              onSearch: handleSearch
            }, null, _parent2, _scopeId));
            if (breadcrumbTrail.value.length) {
              _push2(`<div class="flex flex-wrap items-center gap-1 text-xs font-medium text-gray-600 dark:text-gray-300"${_scopeId}><!--[-->`);
              ssrRenderList(breadcrumbTrail.value, (crumb, index) => {
                _push2(`<!--[-->`);
                if (crumb.clickable) {
                  _push2(`<button type="button" class="text-blue-600 dark:text-blue-400 hover:underline"${_scopeId}>${ssrInterpolate(crumb.label)}</button>`);
                } else {
                  _push2(`<span class="${ssrRenderClass(index === breadcrumbTrail.value.length - 1 ? "text-gray-900 dark:text-gray-100 font-semibold" : "")}"${_scopeId}>${ssrInterpolate(crumb.label)}</span>`);
                }
                if (index < breadcrumbTrail.value.length - 1) {
                  _push2(`<span class="text-gray-400 dark:text-gray-500 mx-1"${_scopeId}> / </span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<!--]-->`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (activeFilterPills.value.length) {
              _push2(`<div class="flex flex-wrap items-center gap-2"${_scopeId}><!--[-->`);
              ssrRenderList(activeFilterPills.value, (pill) => {
                _push2(`<button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs font-medium text-gray-700 dark:text-gray-100 shadow-sm hover:border-blue-500 hover:text-blue-600 dark:hover:border-blue-400 dark:hover:text-blue-300 transition"${_scopeId}><span${_scopeId}>${ssrInterpolate(pill.label)}</span><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"${_scopeId}></path></svg></button>`);
              });
              _push2(`<!--]-->`);
              if (hasRemovableFilters.value) {
                _push2(`<button type="button" class="ml-auto text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline"${_scopeId}> Clear filters </button>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div>`);
            if (navigationLevel.value === "categories") {
              _push2(`<div class="max-w-5xl"${_scopeId}><h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6"${_scopeId}>Browse Catalog</h1>`);
              if (categoriesLoading.value) {
                _push2(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"${_scopeId}><!--[-->`);
                ssrRenderList(6, (i) => {
                  _push2(`<div class="animate-pulse"${_scopeId}><div class="h-32 bg-gray-200 dark:bg-gray-700 rounded-lg"${_scopeId}></div></div>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"${_scopeId}><!--[-->`);
                ssrRenderList(parentCategories.value, (category) => {
                  _push2(`<button class="text-left p-6 bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md transition-all"${_scopeId}><h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2"${_scopeId}>${ssrInterpolate(category.name)}</h3><p class="text-sm text-gray-600 dark:text-gray-400"${_scopeId}>${ssrInterpolate(category.description)}</p></button>`);
                });
                _push2(`<!--]--></div>`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            if (navigationLevel.value === "subcategories") {
              _push2(`<div class="max-w-5xl"${_scopeId}><h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"${_scopeId}>${ssrInterpolate(selectedParentCategory.value?.name)}</h1><p class="text-gray-600 dark:text-gray-400 mb-8"${_scopeId}>${ssrInterpolate(selectedParentCategory.value?.description)}</p><div class="space-y-3"${_scopeId}><!--[-->`);
              ssrRenderList(subcategories.value, (subcategory) => {
                _push2(`<button class="w-full text-left px-6 py-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md transition-all group"${_scopeId}><div class="flex items-center justify-between"${_scopeId}><div${_scopeId}><h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 mb-1"${_scopeId}>${ssrInterpolate(subcategory.name)}</h3><p class="text-sm text-gray-600 dark:text-gray-400"${_scopeId}>${ssrInterpolate(subcategory.description)}</p></div><svg class="w-6 h-6 text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"${_scopeId}></path></svg></div></button>`);
              });
              _push2(`<!--]--></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (showingProductView.value) {
              _push2(`<div class="grid grid-cols-12 gap-6"${_scopeId}>`);
              if (showFilters.value) {
                _push2(`<div class="col-span-12 lg:col-span-3"${_scopeId}>`);
                _push2(ssrRenderComponent(_sfc_main$3, {
                  filters: availableFilters.value,
                  "active-filters": activeFilters.value,
                  loading: filtersLoading.value,
                  "onUpdate:activeFilters": handleFiltersUpdate,
                  onClear: handleClearFilters
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="${ssrRenderClass(["col-span-12", showFilters.value ? "lg:col-span-9" : "lg:col-span-12"])}"${_scopeId}>`);
              if (isGlobalSearch.value) {
                _push2(`<div class="mb-4 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-3 text-sm text-gray-600 dark:text-gray-300"${_scopeId}> Showing results across all categories for &quot;<span class="font-semibold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(searchTerm.value)}</span>&quot;. </div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(ssrRenderComponent(_sfc_main$1, {
                products: products.value,
                loading: productsLoading.value,
                meta: pagination.value,
                "is-search": isSearching.value,
                "current-page": currentPage.value,
                "total-pages": totalPages.value,
                onPrevious: goToPreviousPage,
                onNext: goToNextPage,
                onClearFilters: handleClearFilters,
                onResetNavigation: resetToCategories
              }, null, _parent2, _scopeId));
              _push2(`</div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", { class: "bg-gray-50 dark:bg-gray-900 min-h-screen" }, [
                createVNode("div", { class: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" }, [
                  createVNode("div", { class: "mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between" }, [
                    createVNode("div", { class: "space-y-2" }, [
                      createVNode("p", { class: "text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase" }, "Industrial Supply Catalog"),
                      createVNode("h1", { class: "text-3xl font-bold text-gray-900 dark:text-white" }, "Browse products & build your cart"),
                      createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, " Use the search bar and filters below to zero in on the exact specs you need. ")
                    ]),
                    createVNode(unref(Link), {
                      href: _ctx.route("store.cart"),
                      class: "inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors whitespace-nowrap"
                    }, {
                      default: withCtx(() => [
                        (openBlock(), createBlock("svg", {
                          class: "h-4 w-4",
                          fill: "none",
                          stroke: "currentColor",
                          "stroke-width": "1.5",
                          viewBox: "0 0 24 24"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            d: "M16.5 3.75h-9A2.25 2.25 0 005.25 6v12a2.25 2.25 0 002.25 2.25h9A2.25 2.25 0 0018.75 18V6a2.25 2.25 0 00-2.25-2.25z"
                          }),
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            d: "M8.25 7.5h7.5M8.25 12h7.5M8.25 16.5h4.5"
                          })
                        ])),
                        createVNode("span", null, "View Cart"),
                        cartItemCount.value ? (openBlock(), createBlock("span", {
                          key: 0,
                          class: "ml-1 inline-flex items-center justify-center rounded-full bg-gray-900 dark:bg-blue-600 px-2 py-0.5 text-xs font-bold text-white"
                        }, toDisplayString(cartItemCount.value), 1)) : createCommentVNode("", true)
                      ]),
                      _: 1
                    }, 8, ["href"])
                  ]),
                  createVNode("div", { class: "sticky top-24 z-0 -mx-4 sm:-mx-6 lg:-mx-8 mb-8" }, [
                    createVNode("div", { class: "bg-gray-50/95 dark:bg-gray-900/95 border border-gray-200 dark:border-gray-700 shadow-sm backdrop-blur rounded-b-2xl px-4 sm:px-6 lg:px-8 py-3 space-y-3" }, [
                      createVNode(_sfc_main$4, {
                        modelValue: searchTerm.value,
                        "onUpdate:modelValue": ($event) => searchTerm.value = $event,
                        variant: "inline",
                        loading: navigationLevel.value === "products" ? productsLoading.value : false,
                        placeholder: "Search by product, SKU, or spec",
                        onSearch: handleSearch
                      }, null, 8, ["modelValue", "onUpdate:modelValue", "loading"]),
                      breadcrumbTrail.value.length ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "flex flex-wrap items-center gap-1 text-xs font-medium text-gray-600 dark:text-gray-300"
                      }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(breadcrumbTrail.value, (crumb, index) => {
                          return openBlock(), createBlock(Fragment, {
                            key: crumb.key
                          }, [
                            crumb.clickable ? (openBlock(), createBlock("button", {
                              key: 0,
                              type: "button",
                              class: "text-blue-600 dark:text-blue-400 hover:underline",
                              onClick: ($event) => handleBreadcrumbClick(crumb)
                            }, toDisplayString(crumb.label), 9, ["onClick"])) : (openBlock(), createBlock("span", {
                              key: 1,
                              class: index === breadcrumbTrail.value.length - 1 ? "text-gray-900 dark:text-gray-100 font-semibold" : ""
                            }, toDisplayString(crumb.label), 3)),
                            index < breadcrumbTrail.value.length - 1 ? (openBlock(), createBlock("span", {
                              key: 2,
                              class: "text-gray-400 dark:text-gray-500 mx-1"
                            }, " / ")) : createCommentVNode("", true)
                          ], 64);
                        }), 128))
                      ])) : createCommentVNode("", true),
                      activeFilterPills.value.length ? (openBlock(), createBlock("div", {
                        key: 1,
                        class: "flex flex-wrap items-center gap-2"
                      }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(activeFilterPills.value, (pill) => {
                          return openBlock(), createBlock("button", {
                            key: pill.id,
                            type: "button",
                            class: "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs font-medium text-gray-700 dark:text-gray-100 shadow-sm hover:border-blue-500 hover:text-blue-600 dark:hover:border-blue-400 dark:hover:text-blue-300 transition",
                            onClick: ($event) => removeFilterPill(pill)
                          }, [
                            createVNode("span", null, toDisplayString(pill.label), 1),
                            (openBlock(), createBlock("svg", {
                              class: "h-3.5 w-3.5",
                              fill: "none",
                              stroke: "currentColor",
                              viewBox: "0 0 24 24"
                            }, [
                              createVNode("path", {
                                "stroke-linecap": "round",
                                "stroke-linejoin": "round",
                                "stroke-width": "2",
                                d: "M6 18L18 6M6 6l12 12"
                              })
                            ]))
                          ], 8, ["onClick"]);
                        }), 128)),
                        hasRemovableFilters.value ? (openBlock(), createBlock("button", {
                          key: 0,
                          type: "button",
                          class: "ml-auto text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline",
                          onClick: handleClearFilters
                        }, " Clear filters ")) : createCommentVNode("", true)
                      ])) : createCommentVNode("", true)
                    ])
                  ]),
                  navigationLevel.value === "categories" ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "max-w-5xl"
                  }, [
                    createVNode("h1", { class: "text-3xl font-bold text-gray-900 dark:text-white mb-6" }, "Browse Catalog"),
                    categoriesLoading.value ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                    }, [
                      (openBlock(), createBlock(Fragment, null, renderList(6, (i) => {
                        return createVNode("div", {
                          key: i,
                          class: "animate-pulse"
                        }, [
                          createVNode("div", { class: "h-32 bg-gray-200 dark:bg-gray-700 rounded-lg" })
                        ]);
                      }), 64))
                    ])) : (openBlock(), createBlock("div", {
                      key: 1,
                      class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                    }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(parentCategories.value, (category) => {
                        return openBlock(), createBlock("button", {
                          key: category.id,
                          onClick: ($event) => selectParentCategory(category),
                          class: "text-left p-6 bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md transition-all"
                        }, [
                          createVNode("h3", { class: "text-lg font-semibold text-gray-900 dark:text-white mb-2" }, toDisplayString(category.name), 1),
                          createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, toDisplayString(category.description), 1)
                        ], 8, ["onClick"]);
                      }), 128))
                    ]))
                  ])) : createCommentVNode("", true),
                  navigationLevel.value === "subcategories" ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "max-w-5xl"
                  }, [
                    createVNode("h1", { class: "text-3xl font-bold text-gray-900 dark:text-white mb-2" }, toDisplayString(selectedParentCategory.value?.name), 1),
                    createVNode("p", { class: "text-gray-600 dark:text-gray-400 mb-8" }, toDisplayString(selectedParentCategory.value?.description), 1),
                    createVNode("div", { class: "space-y-3" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(subcategories.value, (subcategory) => {
                        return openBlock(), createBlock("button", {
                          key: subcategory.id,
                          onClick: ($event) => selectSubcategory(subcategory),
                          class: "w-full text-left px-6 py-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md transition-all group"
                        }, [
                          createVNode("div", { class: "flex items-center justify-between" }, [
                            createVNode("div", null, [
                              createVNode("h3", { class: "text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 mb-1" }, toDisplayString(subcategory.name), 1),
                              createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, toDisplayString(subcategory.description), 1)
                            ]),
                            (openBlock(), createBlock("svg", {
                              class: "w-6 h-6 text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400",
                              fill: "none",
                              stroke: "currentColor",
                              viewBox: "0 0 24 24"
                            }, [
                              createVNode("path", {
                                "stroke-linecap": "round",
                                "stroke-linejoin": "round",
                                "stroke-width": "2",
                                d: "M9 5l7 7-7 7"
                              })
                            ]))
                          ])
                        ], 8, ["onClick"]);
                      }), 128))
                    ])
                  ])) : createCommentVNode("", true),
                  showingProductView.value ? (openBlock(), createBlock("div", {
                    key: 2,
                    class: "grid grid-cols-12 gap-6"
                  }, [
                    showFilters.value ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "col-span-12 lg:col-span-3"
                    }, [
                      createVNode(_sfc_main$3, {
                        filters: availableFilters.value,
                        "active-filters": activeFilters.value,
                        loading: filtersLoading.value,
                        "onUpdate:activeFilters": handleFiltersUpdate,
                        onClear: handleClearFilters
                      }, null, 8, ["filters", "active-filters", "loading"])
                    ])) : createCommentVNode("", true),
                    createVNode("div", {
                      class: ["col-span-12", showFilters.value ? "lg:col-span-9" : "lg:col-span-12"]
                    }, [
                      isGlobalSearch.value ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "mb-4 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-3 text-sm text-gray-600 dark:text-gray-300"
                      }, [
                        createTextVNode(' Showing results across all categories for "'),
                        createVNode("span", { class: "font-semibold text-gray-900 dark:text-white" }, toDisplayString(searchTerm.value), 1),
                        createTextVNode('". ')
                      ])) : createCommentVNode("", true),
                      createVNode(_sfc_main$1, {
                        products: products.value,
                        loading: productsLoading.value,
                        meta: pagination.value,
                        "is-search": isSearching.value,
                        "current-page": currentPage.value,
                        "total-pages": totalPages.value,
                        onPrevious: goToPreviousPage,
                        onNext: goToNextPage,
                        onClearFilters: handleClearFilters,
                        onResetNavigation: resetToCategories
                      }, null, 8, ["products", "loading", "meta", "is-search", "current-page", "total-pages"])
                    ], 2)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Store/StoreIndex.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
