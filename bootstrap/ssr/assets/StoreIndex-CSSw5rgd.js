import { computed, useSSRContext, ref, onBeforeUnmount, mergeProps, unref, withCtx, createBlock, openBlock, createVNode, createTextVNode, toDisplayString, watch, onMounted, createCommentVNode, Fragment, renderList } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrRenderClass, ssrIncludeBooleanAttr, ssrRenderComponent } from "vue/server-renderer";
import axios from "axios";
import { Link, usePage, Head, router } from "@inertiajs/vue3";
import { Dialog, DialogPanel } from "@headlessui/vue";
import { u as useCartStore, _ as _sfc_main$4 } from "./StoreLayout-UuGiv97H.js";
import "./CookieConsentBanner-ByAlkSbo.js";
import "./logo-cleansed-light-B5mBHTsK.js";
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
    const optionsFor = (filter) => {
      if (filter.type === "boolean") {
        return [{ value: "true", label: "Yes" }, { value: "false", label: "No" }];
      }
      return (filter.values ?? []).map((value) => ({ value, label: value }));
    };
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
      _push(`<div${ssrRenderAttrs(_attrs)}><div class="mb-1 flex min-h-11 items-center justify-between"><h2 class="text-base font-bold text-st-ink">Filter by spec</h2>`);
      if (hasActiveFilters.value) {
        _push(`<button type="button" class="inline-flex min-h-11 items-center rounded-md px-2 text-sm font-semibold text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"> Clear all (${ssrInterpolate(activeFilterCount.value)}) </button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (__props.loading) {
        _push(`<div class="space-y-5 py-2" aria-busy="true"><!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="space-y-2"><div class="h-3.5 w-24 animate-pulse rounded bg-st-sunk"></div><!--[-->`);
          ssrRenderList(3, (j) => {
            _push(`<div class="h-3 w-full animate-pulse rounded bg-st-sunk"></div>`);
          });
          _push(`<!--]--></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (__props.filters.length === 0) {
        _push(`<p class="py-2 text-sm text-st-muted"> This category has no spec filters yet. </p>`);
      } else {
        _push(`<div class="divide-y divide-st-line"><!--[-->`);
        ssrRenderList(orderedFilters.value, (filter, index) => {
          _push(`<div role="group"${ssrRenderAttr("aria-labelledby", `spec-filter-${index}`)} class="py-3"><p${ssrRenderAttr("id", `spec-filter-${index}`)} class="mb-1 text-sm font-semibold text-st-ink">${ssrInterpolate(filter.name)}</p><!--[-->`);
          ssrRenderList(optionsFor(filter), (option) => {
            _push(`<label class="flex min-h-11 cursor-pointer items-center gap-3 rounded px-2 text-[0.9375rem] text-st-ink/85 hover:bg-st-sunk lg:min-h-9"><input${ssrRenderAttr("type", isProductTypeFilter(filter) ? "checkbox" : "radio")}${ssrRenderAttr("name", isProductTypeFilter(filter) ? `filter-${filter.name}-${option.value}` : `filter-${filter.name}`)}${ssrRenderAttr("value", option.value)}${ssrIncludeBooleanAttr(isValueSelected(filter.name, option.value)) ? " checked" : ""} class="${ssrRenderClass([isProductTypeFilter(filter) ? "rounded" : "", "h-4 w-4 border-st-muted/70 bg-st-sunk text-st-fill focus:ring-st-action focus:ring-offset-st-panel"])}"><span>${ssrInterpolate(option.label)}</span></label>`);
          });
          _push(`<!--]--></div>`);
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
      return Number.isFinite(price) && price > 0 ? currencyFormatter.format(price) : null;
    });
    const unitLabel = computed(() => props.product.unit ?? "each");
    const specPreview = computed(() => {
      if (!Array.isArray(props.product.specifications) || props.product.specifications.length === 0) {
        return [];
      }
      return props.product.specifications.slice(0, 3);
    });
    const productUrl = computed(() => route("store.product", { product: props.product.id, slug: props.product.slug ?? void 0 }));
    const productImage = computed(() => {
      const image = props.product.image;
      if (typeof image !== "string" || image.length === 0) {
        return null;
      }
      if (image.startsWith("http://") || image.startsWith("https://")) {
        return image;
      }
      return `/storage/${image.replace(/^\/+/, "")}`;
    });
    const imageFailed = ref(false);
    const availability = computed(() => {
      if (props.product.in_stock) {
        return { label: "In stock", tone: "ok" };
      }
      if (props.product.lead_time_days) {
        return { label: `Ships in ${props.product.lead_time_days} days`, tone: "warn" };
      }
      return { label: "Backordered", tone: "warn" };
    });
    useCartStore();
    const justAdded = ref(false);
    const minQuantity = computed(() => Math.max(1, Number(props.product.minimum_order_quantity) || 1));
    const quantity = ref(minQuantity.value);
    let feedbackTimer;
    onBeforeUnmount(() => clearTimeout(feedbackTimer));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "grid grid-cols-[4rem_minmax(0,1fr)] gap-x-4 gap-y-3 px-4 py-4 hover:bg-st-sunk/60 sm:px-5 lg:grid-cols-[3.5rem_9rem_minmax(0,1fr)_8.5rem_7.5rem_12rem] lg:items-center lg:gap-y-0 lg:py-3" }, _attrs))}>`);
      _push(ssrRenderComponent(unref(Link), {
        href: productUrl.value,
        class: "row-span-2 block h-16 w-16 overflow-hidden rounded-md border border-st-line bg-st-sunk focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:row-span-1 lg:h-14 lg:w-14",
        tabindex: "-1",
        "aria-hidden": "true"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (productImage.value && !imageFailed.value) {
              _push2(`<img${ssrRenderAttr("src", productImage.value)} alt="" loading="lazy" class="h-full w-full object-cover"${_scopeId}>`);
            } else {
              _push2(`<svg class="h-full w-full p-3.5 text-st-muted/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"${_scopeId}><path stroke-linejoin="round" d="M12 2.75 20 7.25v9.5l-8 4.5-8-4.5v-9.5l8-4.5Z"${_scopeId}></path><path stroke-linejoin="round" d="m4 7.25 8 4.5 8-4.5M12 11.75v9.5"${_scopeId}></path></svg>`);
            }
          } else {
            return [
              productImage.value && !imageFailed.value ? (openBlock(), createBlock("img", {
                key: 0,
                src: productImage.value,
                alt: "",
                loading: "lazy",
                class: "h-full w-full object-cover",
                onError: ($event) => imageFailed.value = true
              }, null, 40, ["src", "onError"])) : (openBlock(), createBlock("svg", {
                key: 1,
                class: "h-full w-full p-3.5 text-st-muted/70",
                viewBox: "0 0 24 24",
                fill: "none",
                stroke: "currentColor",
                "stroke-width": "1.4",
                "aria-hidden": "true"
              }, [
                createVNode("path", {
                  "stroke-linejoin": "round",
                  d: "M12 2.75 20 7.25v9.5l-8 4.5-8-4.5v-9.5l8-4.5Z"
                }),
                createVNode("path", {
                  "stroke-linejoin": "round",
                  d: "m4 7.25 8 4.5 8-4.5M12 11.75v9.5"
                })
              ]))
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="hidden lg:block"><p class="st-num break-all text-sm font-semibold text-st-ink">${ssrInterpolate(partNumber.value)}</p></div><div class="min-w-0">`);
      _push(ssrRenderComponent(unref(Link), {
        href: productUrl.value,
        class: "text-base font-semibold leading-snug text-st-ink hover:text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:text-[0.9375rem]"
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
      _push(`<p class="st-num mt-0.5 text-sm text-st-muted lg:hidden">${ssrInterpolate(partNumber.value)}</p>`);
      if (specPreview.value.length) {
        _push(`<dl class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-sm text-st-muted"><!--[-->`);
        ssrRenderList(specPreview.value, (spec) => {
          _push(`<div class="flex gap-1"><dt>${ssrInterpolate(spec.name)}:</dt><dd class="text-st-ink/85">${ssrInterpolate(spec.value)}</dd></div>`);
        });
        _push(`<!--]--></dl>`);
      } else if (__props.product.description) {
        _push(`<p class="mt-1 line-clamp-1 text-sm text-st-muted">${ssrInterpolate(__props.product.description)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="col-start-2 flex items-baseline justify-between gap-3 lg:col-start-auto lg:block"><p class="${ssrRenderClass([availability.value.tone === "ok" ? "text-st-ok" : "text-st-warn", "inline-flex items-center gap-1.5 text-sm font-medium"])}"><span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span> ${ssrInterpolate(availability.value.label)}</p><p class="st-num text-right text-base font-semibold text-st-ink lg:hidden">${ssrInterpolate(priceDisplay.value ?? "Request price")} `);
      if (priceDisplay.value) {
        _push(`<span class="text-sm font-normal text-st-muted">/ ${ssrInterpolate(unitLabel.value)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</p></div><div class="hidden text-right lg:block"><p class="st-num text-[0.9375rem] font-semibold text-st-ink">${ssrInterpolate(priceDisplay.value ?? "Request price")}</p>`);
      if (priceDisplay.value) {
        _push(`<p class="text-sm text-st-muted">per ${ssrInterpolate(unitLabel.value)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (!priceDisplay.value) {
        _push(`<div class="col-span-2 flex items-center lg:col-span-1 lg:justify-end">`);
        _push(ssrRenderComponent(unref(Link), {
          href: productUrl.value,
          class: "inline-flex h-11 flex-1 items-center justify-center rounded-md border border-st-line px-4 text-sm font-semibold text-st-ink transition-colors hover:bg-st-sunk focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:h-10 lg:flex-none"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Request quote `);
            } else {
              return [
                createTextVNode(" Request quote ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<div class="col-span-2 flex items-center gap-2 lg:col-span-1 lg:justify-end"><label${ssrRenderAttr("for", `qty-${__props.product.id}`)} class="sr-only">Quantity for ${ssrInterpolate(__props.product.name)}</label><input${ssrRenderAttr("id", `qty-${__props.product.id}`)}${ssrRenderAttr("value", quantity.value)} type="number"${ssrRenderAttr("min", minQuantity.value)} inputmode="numeric" class="st-num h-11 w-20 rounded-md border border-st-line bg-st-sunk px-2 text-center text-base text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40 lg:h-10 lg:w-16"><button type="button" class="${ssrRenderClass([justAdded.value ? "bg-st-ok/15 text-st-ok" : "bg-st-fill text-white hover:bg-st-fill/85", "h-11 flex-1 rounded-md px-4 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action focus-visible:ring-offset-2 focus-visible:ring-offset-st-panel lg:h-10 lg:flex-none"])}"><span aria-live="polite">${ssrInterpolate(justAdded.value ? "Added to cart" : "Add to cart")}</span></button></div>`);
      }
      _push(`</div>`);
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
    hasFilters: {
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
    const rangeLabel = computed(() => {
      if (props.loading) {
        return "Loading products";
      }
      const total = props.meta?.total ?? props.products.length;
      const noun = total === 1 ? "product" : "products";
      if (props.totalPages <= 1) {
        return `${total} ${noun}`;
      }
      return `${props.meta?.from ?? 1}–${props.meta?.to ?? props.products.length} of ${total} ${noun}`;
    });
    const canGoPrevious = computed(() => props.currentPage > 1);
    const canGoNext = computed(() => props.currentPage < props.totalPages);
    const showPagination = computed(() => props.totalPages > 1);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({
        class: "overflow-hidden rounded-lg border border-st-line bg-st-panel",
        "aria-label": "Products"
      }, _attrs))}><div class="flex items-center justify-between gap-4 border-b border-st-line px-4 py-3 sm:px-5"><p class="st-num text-sm text-st-muted" aria-live="polite">${ssrInterpolate(rangeLabel.value)}</p>`);
      if (showPagination.value) {
        _push(`<p class="st-num text-sm text-st-muted">Page ${ssrInterpolate(__props.currentPage)} of ${ssrInterpolate(__props.totalPages)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="hidden border-b border-st-line bg-st-sunk/70 px-5 py-2 text-sm font-medium text-st-muted lg:grid lg:grid-cols-[3.5rem_9rem_minmax(0,1fr)_8.5rem_7.5rem_12rem] lg:gap-x-4" aria-hidden="true"><span></span><span>Part number</span><span>Product</span><span>Availability</span><span class="text-right">Price</span><span class="text-right">Quantity</span></div>`);
      if (__props.loading) {
        _push(`<div class="divide-y divide-st-line" aria-busy="true"><!--[-->`);
        ssrRenderList(6, (row) => {
          _push(`<div class="flex items-center gap-4 px-5 py-4"><div class="h-14 w-14 shrink-0 animate-pulse rounded-md bg-st-sunk"></div><div class="flex-1 space-y-2"><div class="h-4 w-2/5 animate-pulse rounded bg-st-sunk"></div><div class="h-3 w-3/5 animate-pulse rounded bg-st-sunk"></div></div><div class="hidden h-10 w-44 animate-pulse rounded bg-st-sunk sm:block"></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!--[-->`);
        if (__props.products.length) {
          _push(`<div class="divide-y divide-st-line"><!--[-->`);
          ssrRenderList(__props.products, (product) => {
            _push(ssrRenderComponent(_sfc_main$2, {
              key: product.id,
              product
            }, null, _parent));
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<div class="px-6 py-14 text-center"><p class="st-display text-lg font-bold text-st-ink">${ssrInterpolate(__props.isSearch ? "No parts match that search" : "No parts match these specs")}</p><p class="mx-auto mt-2 max-w-md text-base text-st-muted">${ssrInterpolate(__props.isSearch ? "Check the part number, try a shorter term, or browse the catalog by department." : "Remove a filter to widen the results, or go back to the catalog.")}</p><div class="mt-6 flex flex-wrap justify-center gap-3">`);
          if (__props.hasFilters) {
            _push(`<button type="button" class="inline-flex min-h-11 items-center rounded-md border border-st-line px-4 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"> Clear filters </button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<button type="button" class="inline-flex min-h-11 items-center rounded-md bg-st-fill px-4 text-sm font-semibold text-white hover:bg-st-fill/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"> Browse the catalog </button></div></div>`);
        }
        _push(`<!--]-->`);
      }
      if (showPagination.value) {
        _push(`<nav class="flex items-center justify-between gap-2 border-t border-st-line px-4 py-3 sm:px-5" aria-label="Pagination"><button type="button" class="inline-flex min-h-11 items-center rounded-md border border-st-line px-4 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-st-line"${ssrIncludeBooleanAttr(!canGoPrevious.value) ? " disabled" : ""}> Previous page </button><button type="button" class="inline-flex min-h-11 items-center rounded-md border border-st-line px-4 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-st-line"${ssrIncludeBooleanAttr(!canGoNext.value) ? " disabled" : ""}> Next page </button></nav>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</section>`);
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
  props: {
    location: {
      type: Object,
      default: null
    }
  },
  setup(__props) {
    const page = usePage();
    const allCategories = ref([]);
    const categoriesLoading = ref(true);
    const selectedDepartment = ref(null);
    const selectedCategory = ref(null);
    const searchTerm = ref("");
    const view = ref("index");
    const products = ref([]);
    const productsLoading = ref(false);
    const pagination = ref(null);
    const currentPage = ref(1);
    const totalPages = ref(1);
    const availableFilters = ref([]);
    const activeFilters = ref({});
    const filtersLoading = ref(false);
    const mobileFiltersOpen = ref(false);
    let loadedProductsKey = null;
    const isSearching = computed(() => searchTerm.value.length > 0);
    const isGlobalSearch = computed(() => view.value === "products" && !selectedCategory.value && isSearching.value);
    const showFilters = computed(() => view.value === "products" && Boolean(selectedCategory.value));
    const activeFilterCount = computed(() => Object.values(activeFilters.value).reduce(
      (count, value) => count + (Array.isArray(value) ? value.length : 1),
      0
    ));
    const byName = (a, b) => a.name.localeCompare(b.name);
    const childrenOf = (category) => allCategories.value.filter((cat) => cat.parent_id === category.id).sort(byName);
    const departments = computed(() => allCategories.value.filter((cat) => !cat.parent_id).map((department) => {
      const children = childrenOf(department);
      const total = (department.products_count ?? 0) + children.reduce((sum, child) => sum + (child.products_count ?? 0), 0);
      return { ...department, children, total };
    }).filter((department) => department.total > 0).sort(byName));
    const catalogTotal = computed(() => departments.value.reduce((sum, d) => sum + d.total, 0));
    const findBySlug = (slug) => slug ? allCategories.value.find((cat) => cat.slug === slug) ?? null : null;
    const departmentFor = (category) => {
      if (!category) {
        return null;
      }
      const rootId = category.parent_id ?? category.id;
      return departments.value.find((d) => d.id === rootId) ?? null;
    };
    const currentDepartment = computed(() => departmentFor(selectedCategory.value) ?? selectedDepartment.value);
    const siblingCategories = computed(() => currentDepartment.value?.children ?? []);
    const visit = (params, { replace = false } = {}) => {
      const query = new URLSearchParams();
      Object.entries(params).forEach(([key, value]) => {
        if (value) {
          query.set(key, value);
        }
      });
      const qs = query.toString();
      const url = window.location.pathname + (qs ? `?${qs}` : "");
      const options = { url, preserveState: true, preserveScroll: replace };
      if (replace) {
        router.replace(options);
      } else {
        router.push(options);
      }
    };
    const openIndex = () => visit({});
    const openDepartment = (department) => {
      if (!department.children?.length) {
        visit({ category: department.slug });
        return;
      }
      visit({ department: department.slug });
    };
    const openCategory = (category) => visit({ category: category.slug });
    const applyUrl = async () => {
      if (categoriesLoading.value) {
        return;
      }
      const params = new URL(page.url, window.location.origin).searchParams;
      const category = findBySlug(params.get("category"));
      const department = category ? departmentFor(category) : departments.value.find((d) => d.slug === params.get("department")) ?? null;
      const term = (params.get("q") ?? "").trim();
      selectedCategory.value = category;
      selectedDepartment.value = department;
      searchTerm.value = term;
      if (!category && !term) {
        view.value = department ? "department" : "index";
        loadedProductsKey = null;
        products.value = [];
        availableFilters.value = [];
        activeFilters.value = {};
        mobileFiltersOpen.value = false;
        return;
      }
      view.value = "products";
      const key = `${category?.id ?? ""}|${term}`;
      if (key === loadedProductsKey) {
        return;
      }
      loadedProductsKey = key;
      activeFilters.value = {};
      currentPage.value = 1;
      availableFilters.value = [];
      await Promise.all([category ? fetchFilters() : Promise.resolve(), fetchProducts()]);
    };
    watch(() => page.url, applyUrl);
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
    const fetchFilters = async () => {
      if (!selectedCategory.value) {
        return;
      }
      filtersLoading.value = true;
      try {
        const { data } = await axios.get("/api/v1/store/filters", {
          params: {
            category_id: selectedCategory.value.id,
            ...Object.keys(activeFilters.value).length > 0 ? { filters: activeFilters.value } : {}
          }
        });
        availableFilters.value = data.data ?? [];
      } catch (error) {
        console.error("Failed to load filters", error);
        availableFilters.value = [];
      } finally {
        filtersLoading.value = false;
      }
    };
    const fetchProducts = async () => {
      if (!selectedCategory.value && !isSearching.value) {
        return;
      }
      productsLoading.value = true;
      const params = { page: currentPage.value };
      if (selectedCategory.value) {
        params.category_id = selectedCategory.value.id;
      }
      if (isSearching.value) {
        params.query = searchTerm.value;
      }
      if (Object.keys(activeFilters.value).length > 0) {
        params.filters = activeFilters.value;
      }
      try {
        const endpoint = isSearching.value ? "/api/v1/store/search" : "/api/v1/store/products";
        const { data } = await axios.get(endpoint, { params });
        products.value = data.data ?? [];
        pagination.value = data.meta ?? null;
        currentPage.value = data.meta?.current_page ?? 1;
        totalPages.value = data.meta?.last_page ?? 1;
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
    const handleSearch = (term) => {
      const trimmed = term.trim();
      if (trimmed === searchTerm.value) {
        return;
      }
      visit(
        { category: selectedCategory.value?.slug, q: trimmed },
        { replace: isSearching.value && trimmed.length > 0 }
      );
    };
    const refreshFiltersAndProducts = () => {
      currentPage.value = 1;
      return Promise.all([fetchFilters(), fetchProducts()]);
    };
    const handleFiltersUpdate = (filters) => {
      activeFilters.value = filters;
      return refreshFiltersAndProducts();
    };
    const handleClearFilters = () => {
      activeFilters.value = {};
      return refreshFiltersAndProducts();
    };
    const removeFilterValue = (attribute, value) => {
      const next = { ...activeFilters.value };
      const current = next[attribute];
      if (Array.isArray(current) && current.length > 1) {
        next[attribute] = current.filter((v) => v !== value);
      } else {
        delete next[attribute];
      }
      return handleFiltersUpdate(next);
    };
    const filterPills = computed(() => Object.entries(activeFilters.value).flatMap(([attribute, value]) => {
      const values = Array.isArray(value) ? value : [value];
      return values.map((v) => ({
        key: `${attribute}:${v}`,
        attribute,
        value: v,
        label: `${attribute}: ${v === "true" ? "Yes" : v === "false" ? "No" : v}`
      }));
    }));
    const scrollToResults = () => {
      document.getElementById("catalog-results")?.scrollIntoView({ behavior: "smooth", block: "start" });
    };
    const goToPage = async (pageNumber) => {
      currentPage.value = pageNumber;
      await fetchProducts();
      scrollToResults();
    };
    const pageTitle = computed(() => {
      if (view.value === "products") {
        if (selectedCategory.value) {
          return selectedCategory.value.name;
        }
        return `Results for “${searchTerm.value}”`;
      }
      if (view.value === "department") {
        return currentDepartment.value?.name ?? "Catalog";
      }
      return "Catalog";
    });
    onMounted(async () => {
      await fetchCategories();
      await applyUrl();
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: view.value === "index" ? "Store" : `${pageTitle.value} – Store`
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$4, {
        search: searchTerm.value,
        "live-search": "",
        searching: productsLoading.value && isSearching.value,
        onSearch: handleSearch
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mx-auto max-w-screen-2xl px-4 pb-20 pt-6 sm:px-6 lg:px-8 lg:pt-8"${_scopeId}>`);
            if (view.value !== "index") {
              _push2(`<nav aria-label="Breadcrumb" class="mb-3 text-sm"${_scopeId}><ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-st-muted"${_scopeId}><li${_scopeId}><button type="button" class="rounded hover:text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"${_scopeId}>Catalog</button></li>`);
              if (currentDepartment.value && (view.value === "products" || view.value === "department")) {
                _push2(`<!--[--><li aria-hidden="true"${_scopeId}>/</li><li${_scopeId}>`);
                if (view.value === "products" && currentDepartment.value.children.length) {
                  _push2(`<button type="button" class="rounded hover:text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"${_scopeId}>${ssrInterpolate(currentDepartment.value.name)}</button>`);
                } else {
                  _push2(`<span aria-current="page" class="text-st-ink"${_scopeId}>${ssrInterpolate(currentDepartment.value.name)}</span>`);
                }
                _push2(`</li><!--]-->`);
              } else {
                _push2(`<!---->`);
              }
              if (selectedCategory.value && selectedCategory.value.parent_id) {
                _push2(`<!--[--><li aria-hidden="true"${_scopeId}>/</li><li aria-current="page" class="text-st-ink"${_scopeId}>${ssrInterpolate(selectedCategory.value.name)}</li><!--]-->`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</ol></nav>`);
            } else {
              _push2(`<!---->`);
            }
            if (view.value === "index") {
              _push2(`<!--[--><div class="mb-8 flex flex-col gap-3 border-b border-st-line pb-6 lg:flex-row lg:items-end lg:justify-between"${_scopeId}><div${_scopeId}><h1 class="st-display text-4xl font-extrabold leading-none text-st-ink sm:text-5xl"${_scopeId}>Catalog</h1><p class="mt-3 max-w-2xl text-base text-st-muted"${_scopeId}>`);
              if (!categoriesLoading.value && departments.value.length) {
                _push2(`<!--[--><span class="st-num"${_scopeId}>${ssrInterpolate(catalogTotal.value)}</span> products across <span class="st-num"${_scopeId}>${ssrInterpolate(departments.value.length)}</span> departments. <!--]-->`);
              } else {
                _push2(`<!---->`);
              }
              _push2(` Pick a category to filter by spec, or search by part number above. </p></div>`);
              if (__props.location) {
                _push2(`<p class="text-sm text-st-muted"${_scopeId}> Stock shown for <span class="font-semibold text-st-ink"${_scopeId}>${ssrInterpolate(__props.location.name)}</span></p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
              if (categoriesLoading.value) {
                _push2(`<div class="columns-1 gap-x-12 sm:columns-2 lg:columns-3 2xl:columns-4" aria-busy="true"${_scopeId}><!--[-->`);
                ssrRenderList(8, (i) => {
                  _push2(`<div class="mb-10 break-inside-avoid border-t-2 border-st-line pt-3"${_scopeId}><div class="mb-4 h-5 w-2/3 animate-pulse rounded bg-st-panel"${_scopeId}></div><!--[-->`);
                  ssrRenderList(4, (j) => {
                    _push2(`<div class="mb-3 h-3.5 w-full animate-pulse rounded bg-st-panel"${_scopeId}></div>`);
                  });
                  _push2(`<!--]--></div>`);
                });
                _push2(`<!--]--></div>`);
              } else if (departments.value.length) {
                _push2(`<div class="columns-1 gap-x-12 sm:columns-2 lg:columns-3 2xl:columns-4"${_scopeId}><!--[-->`);
                ssrRenderList(departments.value, (department) => {
                  _push2(`<section class="mb-10 break-inside-avoid border-t-2 border-st-ink pt-3"${ssrRenderAttr("aria-labelledby", `dept-${department.id}`)}${_scopeId}><h2${ssrRenderAttr("id", `dept-${department.id}`)} class="mb-2"${_scopeId}><button type="button" class="group flex w-full items-baseline justify-between gap-3 rounded text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"${_scopeId}><span class="st-display text-lg font-extrabold leading-tight text-st-ink group-hover:text-st-action"${_scopeId}>${ssrInterpolate(department.name)}</span><span class="st-num shrink-0 text-sm text-st-muted"${_scopeId}>${ssrInterpolate(department.total)}</span></button></h2><ul${_scopeId}><!--[-->`);
                  ssrRenderList(department.children, (category) => {
                    _push2(`<li${_scopeId}><button type="button" class="group flex min-h-11 w-full items-baseline rounded py-1.5 text-left text-[0.9375rem] text-st-ink/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action sm:min-h-0"${_scopeId}><span class="group-hover:text-st-action group-hover:underline"${_scopeId}>${ssrInterpolate(category.name)}</span><span class="st-leader" aria-hidden="true"${_scopeId}></span><span class="st-num text-sm text-st-muted"${_scopeId}>${ssrInterpolate(category.products_count ?? "")}<span class="sr-only"${_scopeId}> products</span></span></button></li>`);
                  });
                  _push2(`<!--]--></ul></section>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<div class="max-w-xl rounded-lg border border-st-line bg-st-panel p-6"${_scopeId}><p class="text-lg font-bold"${_scopeId}>The catalog is empty right now</p><p class="mt-1 text-st-muted"${_scopeId}>Products will show here as soon as they&#39;re added. You can still search by part number above.</p></div>`);
              }
              _push2(`<div class="mt-6 grid gap-8 border-t border-st-line pt-8 sm:grid-cols-2 lg:grid-cols-3"${_scopeId}><div${_scopeId}><h2 class="text-base font-bold text-st-ink"${_scopeId}>Can&#39;t find a part?</h2><p class="mt-1 text-[0.9375rem] text-st-muted"${_scopeId}>Send us the part number, NSN, or spec sheet and we&#39;ll source it for you.</p><a href="/#contact" class="mt-2 inline-flex min-h-11 items-center font-semibold text-st-action hover:underline sm:min-h-0"${_scopeId}>Request a sourcing quote</a></div><div${_scopeId}><h2 class="text-base font-bold text-st-ink"${_scopeId}>Need equipment repaired?</h2><p class="mt-1 text-[0.9375rem] text-st-muted"${_scopeId}>Tell us what broke and we will follow up with next steps.</p>`);
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("repair-services.index"),
                class: "mt-2 inline-flex min-h-11 items-center font-semibold text-st-action hover:underline sm:min-h-0"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Start a repair request`);
                  } else {
                    return [
                      createTextVNode("Start a repair request")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div>`);
              if (_ctx.$page.props.auth?.user) {
                _push2(`<div${_scopeId}><h2 class="text-base font-bold text-st-ink"${_scopeId}>Reordering?</h2><p class="mt-1 text-[0.9375rem] text-st-muted"${_scopeId}>See what your company has bought before and export it for your records.</p>`);
                _push2(ssrRenderComponent(unref(Link), {
                  href: _ctx.route("dashboard.reports"),
                  class: "mt-2 inline-flex min-h-11 items-center font-semibold text-st-action hover:underline sm:min-h-0"
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`View order history`);
                    } else {
                      return [
                        createTextVNode("View order history")
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
                _push2(`</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><!--]-->`);
            } else if (view.value === "department" && currentDepartment.value) {
              _push2(`<!--[--><div class="mb-8 border-b border-st-line pb-6"${_scopeId}><h1 class="st-display text-3xl font-extrabold leading-tight text-st-ink sm:text-4xl"${_scopeId}>${ssrInterpolate(currentDepartment.value.name)}</h1><p class="mt-2 text-base text-st-muted"${_scopeId}><span class="st-num"${_scopeId}>${ssrInterpolate(currentDepartment.value.total)}</span> products in <span class="st-num"${_scopeId}>${ssrInterpolate(currentDepartment.value.children.length)}</span> categories </p></div><ul class="grid max-w-5xl gap-x-12 sm:grid-cols-2"${_scopeId}><!--[-->`);
              ssrRenderList(currentDepartment.value.children, (category) => {
                _push2(`<li class="border-b border-st-line"${_scopeId}><button type="button" class="group flex min-h-14 w-full items-baseline py-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"${_scopeId}><span class="text-lg font-semibold text-st-ink group-hover:text-st-action group-hover:underline"${_scopeId}>${ssrInterpolate(category.name)}</span><span class="st-leader" aria-hidden="true"${_scopeId}></span><span class="st-num text-st-muted"${_scopeId}>${ssrInterpolate(category.products_count)}<span class="sr-only"${_scopeId}> products</span></span></button></li>`);
              });
              _push2(`<!--]--></ul><!--]-->`);
            } else if (view.value === "products") {
              _push2(`<!--[--><div class="mb-5 flex flex-col gap-1"${_scopeId}><h1 class="st-display text-2xl font-extrabold leading-tight text-st-ink sm:text-3xl"${_scopeId}>${ssrInterpolate(pageTitle.value)}</h1>`);
              if (isGlobalSearch.value) {
                _push2(`<p class="text-base text-st-muted"${_scopeId}>Searching every department.</p>`);
              } else if (isSearching.value) {
                _push2(`<p class="text-base text-st-muted"${_scopeId}> Matching “${ssrInterpolate(searchTerm.value)}” in ${ssrInterpolate(selectedCategory.value?.name)}. <button type="button" class="font-semibold text-st-action hover:underline"${_scopeId}>Show all ${ssrInterpolate(selectedCategory.value?.name)}</button></p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
              if (showFilters.value && siblingCategories.value.length > 1) {
                _push2(`<div class="-mx-4 mb-4 overflow-x-auto px-4 lg:hidden"${_scopeId}><div class="flex gap-2 pb-1"${_scopeId}><!--[-->`);
                ssrRenderList(siblingCategories.value, (category) => {
                  _push2(`<button type="button" class="${ssrRenderClass([category.id === selectedCategory.value?.id ? "border-st-signal bg-st-signal/10 text-st-ink" : "border-st-line text-st-muted hover:text-st-ink", "inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 text-sm font-medium"])}"${ssrRenderAttr("aria-current", category.id === selectedCategory.value?.id ? "page" : void 0)}${_scopeId}>${ssrInterpolate(category.name)}</button>`);
                });
                _push2(`<!--]--></div></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="${ssrRenderClass([{ "lg:grid-cols-[16rem_minmax(0,1fr)]": showFilters.value }, "grid gap-6 xl:gap-8"])}"${_scopeId}>`);
              if (showFilters.value) {
                _push2(`<aside class="hidden lg:block" aria-label="Refine products"${_scopeId}><div class="sticky top-24 max-h-[calc(100vh-7rem)] space-y-6 overflow-y-auto overflow-x-hidden pb-6 pr-1"${_scopeId}>`);
                if (siblingCategories.value.length > 1) {
                  _push2(`<nav${ssrRenderAttr("aria-label", `${currentDepartment.value?.name} categories`)}${_scopeId}><h2 class="mb-1 text-base font-bold text-st-ink"${_scopeId}>${ssrInterpolate(currentDepartment.value?.name)}</h2><ul${_scopeId}><!--[-->`);
                  ssrRenderList(siblingCategories.value, (category) => {
                    _push2(`<li${_scopeId}><button type="button" class="${ssrRenderClass([category.id === selectedCategory.value?.id ? "border-st-signal font-semibold text-st-ink" : "border-transparent text-st-muted hover:text-st-ink", "flex min-h-9 w-full items-baseline border-l-2 py-1 pl-3 text-left text-[0.9375rem] focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"])}"${ssrRenderAttr("aria-current", category.id === selectedCategory.value?.id ? "page" : void 0)}${_scopeId}><span${_scopeId}>${ssrInterpolate(category.name)}</span><span class="st-num ml-auto pl-2 text-sm text-st-muted"${_scopeId}>${ssrInterpolate(category.products_count)}</span></button></li>`);
                  });
                  _push2(`<!--]--></ul></nav>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(ssrRenderComponent(_sfc_main$3, {
                  filters: availableFilters.value,
                  "active-filters": activeFilters.value,
                  loading: filtersLoading.value,
                  "onUpdate:activeFilters": handleFiltersUpdate,
                  onClear: handleClearFilters
                }, null, _parent2, _scopeId));
                _push2(`</div></aside>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div id="catalog-results" class="min-w-0 scroll-mt-28"${_scopeId}>`);
              if (showFilters.value || filterPills.value.length) {
                _push2(`<div class="mb-3 flex flex-wrap items-center gap-2"${_scopeId}>`);
                if (showFilters.value) {
                  _push2(`<button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-md border border-st-line bg-st-panel px-4 text-sm font-semibold text-st-ink lg:hidden"${_scopeId}> Filter by spec `);
                  if (activeFilterCount.value) {
                    _push2(`<span class="st-num rounded bg-st-fill px-1.5 text-xs text-white"${_scopeId}>${ssrInterpolate(activeFilterCount.value)}</span>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</button>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<!--[-->`);
                ssrRenderList(filterPills.value, (pill) => {
                  _push2(`<button type="button" class="inline-flex min-h-9 items-center gap-2 rounded-full border border-st-line bg-st-panel px-3 text-sm text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"${ssrRenderAttr("aria-label", `Remove filter ${pill.label}`)}${_scopeId}>${ssrInterpolate(pill.label)} <svg class="h-3.5 w-3.5 text-st-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"${_scopeId}><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"${_scopeId}></path></svg></button>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(ssrRenderComponent(_sfc_main$1, {
                products: products.value,
                loading: productsLoading.value,
                meta: pagination.value,
                "is-search": isSearching.value,
                "has-filters": activeFilterCount.value > 0,
                "current-page": currentPage.value,
                "total-pages": totalPages.value,
                onPrevious: ($event) => goToPage(currentPage.value - 1),
                onNext: ($event) => goToPage(currentPage.value + 1),
                onClearFilters: handleClearFilters,
                onResetNavigation: openIndex
              }, null, _parent2, _scopeId));
              _push2(`</div></div><!--]-->`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
            _push2(ssrRenderComponent(unref(Dialog), {
              class: "lg:hidden",
              open: mobileFiltersOpen.value,
              onClose: ($event) => mobileFiltersOpen.value = false
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="fixed inset-0 z-40 bg-black/60" aria-hidden="true"${_scopeId2}></div>`);
                  _push3(ssrRenderComponent(unref(DialogPanel), { class: "store-shell safe-y fixed inset-y-0 right-0 z-50 flex w-full max-w-drawer flex-col bg-st-panel text-st-ink shadow-2xl" }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`<div class="flex h-16 shrink-0 items-center justify-between border-b border-st-line px-4"${_scopeId3}><h2 class="text-lg font-bold"${_scopeId3}>Filter ${ssrInterpolate(selectedCategory.value?.name)}</h2><button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink" aria-label="Close filters"${_scopeId3}><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"${_scopeId3}><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"${_scopeId3}></path></svg></button></div><div class="min-h-0 flex-1 overflow-y-auto px-4 py-2"${_scopeId3}>`);
                        _push4(ssrRenderComponent(_sfc_main$3, {
                          filters: availableFilters.value,
                          "active-filters": activeFilters.value,
                          loading: filtersLoading.value,
                          "onUpdate:activeFilters": handleFiltersUpdate,
                          onClear: handleClearFilters
                        }, null, _parent4, _scopeId3));
                        _push4(`</div><div class="safe-bottom shrink-0 border-t border-st-line p-4"${_scopeId3}><button type="button" class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-st-fill px-4 text-base font-semibold text-white hover:bg-st-fill/85"${_scopeId3}> Show ${ssrInterpolate(pagination.value?.total ?? products.value.length)} products </button></div>`);
                      } else {
                        return [
                          createVNode("div", { class: "flex h-16 shrink-0 items-center justify-between border-b border-st-line px-4" }, [
                            createVNode("h2", { class: "text-lg font-bold" }, "Filter " + toDisplayString(selectedCategory.value?.name), 1),
                            createVNode("button", {
                              type: "button",
                              class: "inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink",
                              "aria-label": "Close filters",
                              onClick: ($event) => mobileFiltersOpen.value = false
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
                          createVNode("div", { class: "min-h-0 flex-1 overflow-y-auto px-4 py-2" }, [
                            createVNode(_sfc_main$3, {
                              filters: availableFilters.value,
                              "active-filters": activeFilters.value,
                              loading: filtersLoading.value,
                              "onUpdate:activeFilters": handleFiltersUpdate,
                              onClear: handleClearFilters
                            }, null, 8, ["filters", "active-filters", "loading"])
                          ]),
                          createVNode("div", { class: "safe-bottom shrink-0 border-t border-st-line p-4" }, [
                            createVNode("button", {
                              type: "button",
                              class: "inline-flex min-h-12 w-full items-center justify-center rounded-md bg-st-fill px-4 text-base font-semibold text-white hover:bg-st-fill/85",
                              onClick: ($event) => mobileFiltersOpen.value = false
                            }, " Show " + toDisplayString(pagination.value?.total ?? products.value.length) + " products ", 9, ["onClick"])
                          ])
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode("div", {
                      class: "fixed inset-0 z-40 bg-black/60",
                      "aria-hidden": "true"
                    }),
                    createVNode(unref(DialogPanel), { class: "store-shell safe-y fixed inset-y-0 right-0 z-50 flex w-full max-w-drawer flex-col bg-st-panel text-st-ink shadow-2xl" }, {
                      default: withCtx(() => [
                        createVNode("div", { class: "flex h-16 shrink-0 items-center justify-between border-b border-st-line px-4" }, [
                          createVNode("h2", { class: "text-lg font-bold" }, "Filter " + toDisplayString(selectedCategory.value?.name), 1),
                          createVNode("button", {
                            type: "button",
                            class: "inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink",
                            "aria-label": "Close filters",
                            onClick: ($event) => mobileFiltersOpen.value = false
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
                        createVNode("div", { class: "min-h-0 flex-1 overflow-y-auto px-4 py-2" }, [
                          createVNode(_sfc_main$3, {
                            filters: availableFilters.value,
                            "active-filters": activeFilters.value,
                            loading: filtersLoading.value,
                            "onUpdate:activeFilters": handleFiltersUpdate,
                            onClear: handleClearFilters
                          }, null, 8, ["filters", "active-filters", "loading"])
                        ]),
                        createVNode("div", { class: "safe-bottom shrink-0 border-t border-st-line p-4" }, [
                          createVNode("button", {
                            type: "button",
                            class: "inline-flex min-h-12 w-full items-center justify-center rounded-md bg-st-fill px-4 text-base font-semibold text-white hover:bg-st-fill/85",
                            onClick: ($event) => mobileFiltersOpen.value = false
                          }, " Show " + toDisplayString(pagination.value?.total ?? products.value.length) + " products ", 9, ["onClick"])
                        ])
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
              createVNode("div", { class: "mx-auto max-w-screen-2xl px-4 pb-20 pt-6 sm:px-6 lg:px-8 lg:pt-8" }, [
                view.value !== "index" ? (openBlock(), createBlock("nav", {
                  key: 0,
                  "aria-label": "Breadcrumb",
                  class: "mb-3 text-sm"
                }, [
                  createVNode("ol", { class: "flex flex-wrap items-center gap-x-2 gap-y-1 text-st-muted" }, [
                    createVNode("li", null, [
                      createVNode("button", {
                        type: "button",
                        class: "rounded hover:text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action",
                        onClick: openIndex
                      }, "Catalog")
                    ]),
                    currentDepartment.value && (view.value === "products" || view.value === "department") ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                      createVNode("li", { "aria-hidden": "true" }, "/"),
                      createVNode("li", null, [
                        view.value === "products" && currentDepartment.value.children.length ? (openBlock(), createBlock("button", {
                          key: 0,
                          type: "button",
                          class: "rounded hover:text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action",
                          onClick: ($event) => openDepartment(currentDepartment.value)
                        }, toDisplayString(currentDepartment.value.name), 9, ["onClick"])) : (openBlock(), createBlock("span", {
                          key: 1,
                          "aria-current": "page",
                          class: "text-st-ink"
                        }, toDisplayString(currentDepartment.value.name), 1))
                      ])
                    ], 64)) : createCommentVNode("", true),
                    selectedCategory.value && selectedCategory.value.parent_id ? (openBlock(), createBlock(Fragment, { key: 1 }, [
                      createVNode("li", { "aria-hidden": "true" }, "/"),
                      createVNode("li", {
                        "aria-current": "page",
                        class: "text-st-ink"
                      }, toDisplayString(selectedCategory.value.name), 1)
                    ], 64)) : createCommentVNode("", true)
                  ])
                ])) : createCommentVNode("", true),
                view.value === "index" ? (openBlock(), createBlock(Fragment, { key: 1 }, [
                  createVNode("div", { class: "mb-8 flex flex-col gap-3 border-b border-st-line pb-6 lg:flex-row lg:items-end lg:justify-between" }, [
                    createVNode("div", null, [
                      createVNode("h1", { class: "st-display text-4xl font-extrabold leading-none text-st-ink sm:text-5xl" }, "Catalog"),
                      createVNode("p", { class: "mt-3 max-w-2xl text-base text-st-muted" }, [
                        !categoriesLoading.value && departments.value.length ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                          createVNode("span", { class: "st-num" }, toDisplayString(catalogTotal.value), 1),
                          createTextVNode(" products across "),
                          createVNode("span", { class: "st-num" }, toDisplayString(departments.value.length), 1),
                          createTextVNode(" departments. ")
                        ], 64)) : createCommentVNode("", true),
                        createTextVNode(" Pick a category to filter by spec, or search by part number above. ")
                      ])
                    ]),
                    __props.location ? (openBlock(), createBlock("p", {
                      key: 0,
                      class: "text-sm text-st-muted"
                    }, [
                      createTextVNode(" Stock shown for "),
                      createVNode("span", { class: "font-semibold text-st-ink" }, toDisplayString(__props.location.name), 1)
                    ])) : createCommentVNode("", true)
                  ]),
                  categoriesLoading.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "columns-1 gap-x-12 sm:columns-2 lg:columns-3 2xl:columns-4",
                    "aria-busy": "true"
                  }, [
                    (openBlock(), createBlock(Fragment, null, renderList(8, (i) => {
                      return createVNode("div", {
                        key: i,
                        class: "mb-10 break-inside-avoid border-t-2 border-st-line pt-3"
                      }, [
                        createVNode("div", { class: "mb-4 h-5 w-2/3 animate-pulse rounded bg-st-panel" }),
                        (openBlock(), createBlock(Fragment, null, renderList(4, (j) => {
                          return createVNode("div", {
                            key: j,
                            class: "mb-3 h-3.5 w-full animate-pulse rounded bg-st-panel"
                          });
                        }), 64))
                      ]);
                    }), 64))
                  ])) : departments.value.length ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "columns-1 gap-x-12 sm:columns-2 lg:columns-3 2xl:columns-4"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(departments.value, (department) => {
                      return openBlock(), createBlock("section", {
                        key: department.id,
                        class: "mb-10 break-inside-avoid border-t-2 border-st-ink pt-3",
                        "aria-labelledby": `dept-${department.id}`
                      }, [
                        createVNode("h2", {
                          id: `dept-${department.id}`,
                          class: "mb-2"
                        }, [
                          createVNode("button", {
                            type: "button",
                            class: "group flex w-full items-baseline justify-between gap-3 rounded text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action",
                            onClick: ($event) => openDepartment(department)
                          }, [
                            createVNode("span", { class: "st-display text-lg font-extrabold leading-tight text-st-ink group-hover:text-st-action" }, toDisplayString(department.name), 1),
                            createVNode("span", { class: "st-num shrink-0 text-sm text-st-muted" }, toDisplayString(department.total), 1)
                          ], 8, ["onClick"])
                        ], 8, ["id"]),
                        createVNode("ul", null, [
                          (openBlock(true), createBlock(Fragment, null, renderList(department.children, (category) => {
                            return openBlock(), createBlock("li", {
                              key: category.id
                            }, [
                              createVNode("button", {
                                type: "button",
                                class: "group flex min-h-11 w-full items-baseline rounded py-1.5 text-left text-[0.9375rem] text-st-ink/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action sm:min-h-0",
                                onClick: ($event) => openCategory(category)
                              }, [
                                createVNode("span", { class: "group-hover:text-st-action group-hover:underline" }, toDisplayString(category.name), 1),
                                createVNode("span", {
                                  class: "st-leader",
                                  "aria-hidden": "true"
                                }),
                                createVNode("span", { class: "st-num text-sm text-st-muted" }, [
                                  createTextVNode(toDisplayString(category.products_count ?? ""), 1),
                                  createVNode("span", { class: "sr-only" }, " products")
                                ])
                              ], 8, ["onClick"])
                            ]);
                          }), 128))
                        ])
                      ], 8, ["aria-labelledby"]);
                    }), 128))
                  ])) : (openBlock(), createBlock("div", {
                    key: 2,
                    class: "max-w-xl rounded-lg border border-st-line bg-st-panel p-6"
                  }, [
                    createVNode("p", { class: "text-lg font-bold" }, "The catalog is empty right now"),
                    createVNode("p", { class: "mt-1 text-st-muted" }, "Products will show here as soon as they're added. You can still search by part number above.")
                  ])),
                  createVNode("div", { class: "mt-6 grid gap-8 border-t border-st-line pt-8 sm:grid-cols-2 lg:grid-cols-3" }, [
                    createVNode("div", null, [
                      createVNode("h2", { class: "text-base font-bold text-st-ink" }, "Can't find a part?"),
                      createVNode("p", { class: "mt-1 text-[0.9375rem] text-st-muted" }, "Send us the part number, NSN, or spec sheet and we'll source it for you."),
                      createVNode("a", {
                        href: "/#contact",
                        class: "mt-2 inline-flex min-h-11 items-center font-semibold text-st-action hover:underline sm:min-h-0"
                      }, "Request a sourcing quote")
                    ]),
                    createVNode("div", null, [
                      createVNode("h2", { class: "text-base font-bold text-st-ink" }, "Need equipment repaired?"),
                      createVNode("p", { class: "mt-1 text-[0.9375rem] text-st-muted" }, "Tell us what broke and we will follow up with next steps."),
                      createVNode(unref(Link), {
                        href: _ctx.route("repair-services.index"),
                        class: "mt-2 inline-flex min-h-11 items-center font-semibold text-st-action hover:underline sm:min-h-0"
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Start a repair request")
                        ]),
                        _: 1
                      }, 8, ["href"])
                    ]),
                    _ctx.$page.props.auth?.user ? (openBlock(), createBlock("div", { key: 0 }, [
                      createVNode("h2", { class: "text-base font-bold text-st-ink" }, "Reordering?"),
                      createVNode("p", { class: "mt-1 text-[0.9375rem] text-st-muted" }, "See what your company has bought before and export it for your records."),
                      createVNode(unref(Link), {
                        href: _ctx.route("dashboard.reports"),
                        class: "mt-2 inline-flex min-h-11 items-center font-semibold text-st-action hover:underline sm:min-h-0"
                      }, {
                        default: withCtx(() => [
                          createTextVNode("View order history")
                        ]),
                        _: 1
                      }, 8, ["href"])
                    ])) : createCommentVNode("", true)
                  ])
                ], 64)) : view.value === "department" && currentDepartment.value ? (openBlock(), createBlock(Fragment, { key: 2 }, [
                  createVNode("div", { class: "mb-8 border-b border-st-line pb-6" }, [
                    createVNode("h1", { class: "st-display text-3xl font-extrabold leading-tight text-st-ink sm:text-4xl" }, toDisplayString(currentDepartment.value.name), 1),
                    createVNode("p", { class: "mt-2 text-base text-st-muted" }, [
                      createVNode("span", { class: "st-num" }, toDisplayString(currentDepartment.value.total), 1),
                      createTextVNode(" products in "),
                      createVNode("span", { class: "st-num" }, toDisplayString(currentDepartment.value.children.length), 1),
                      createTextVNode(" categories ")
                    ])
                  ]),
                  createVNode("ul", { class: "grid max-w-5xl gap-x-12 sm:grid-cols-2" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(currentDepartment.value.children, (category) => {
                      return openBlock(), createBlock("li", {
                        key: category.id,
                        class: "border-b border-st-line"
                      }, [
                        createVNode("button", {
                          type: "button",
                          class: "group flex min-h-14 w-full items-baseline py-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action",
                          onClick: ($event) => openCategory(category)
                        }, [
                          createVNode("span", { class: "text-lg font-semibold text-st-ink group-hover:text-st-action group-hover:underline" }, toDisplayString(category.name), 1),
                          createVNode("span", {
                            class: "st-leader",
                            "aria-hidden": "true"
                          }),
                          createVNode("span", { class: "st-num text-st-muted" }, [
                            createTextVNode(toDisplayString(category.products_count), 1),
                            createVNode("span", { class: "sr-only" }, " products")
                          ])
                        ], 8, ["onClick"])
                      ]);
                    }), 128))
                  ])
                ], 64)) : view.value === "products" ? (openBlock(), createBlock(Fragment, { key: 3 }, [
                  createVNode("div", { class: "mb-5 flex flex-col gap-1" }, [
                    createVNode("h1", { class: "st-display text-2xl font-extrabold leading-tight text-st-ink sm:text-3xl" }, toDisplayString(pageTitle.value), 1),
                    isGlobalSearch.value ? (openBlock(), createBlock("p", {
                      key: 0,
                      class: "text-base text-st-muted"
                    }, "Searching every department.")) : isSearching.value ? (openBlock(), createBlock("p", {
                      key: 1,
                      class: "text-base text-st-muted"
                    }, [
                      createTextVNode(" Matching “" + toDisplayString(searchTerm.value) + "” in " + toDisplayString(selectedCategory.value?.name) + ". ", 1),
                      createVNode("button", {
                        type: "button",
                        class: "font-semibold text-st-action hover:underline",
                        onClick: ($event) => handleSearch("")
                      }, "Show all " + toDisplayString(selectedCategory.value?.name), 9, ["onClick"])
                    ])) : createCommentVNode("", true)
                  ]),
                  showFilters.value && siblingCategories.value.length > 1 ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "-mx-4 mb-4 overflow-x-auto px-4 lg:hidden"
                  }, [
                    createVNode("div", { class: "flex gap-2 pb-1" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(siblingCategories.value, (category) => {
                        return openBlock(), createBlock("button", {
                          key: category.id,
                          type: "button",
                          class: ["inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 text-sm font-medium", category.id === selectedCategory.value?.id ? "border-st-signal bg-st-signal/10 text-st-ink" : "border-st-line text-st-muted hover:text-st-ink"],
                          "aria-current": category.id === selectedCategory.value?.id ? "page" : void 0,
                          onClick: ($event) => openCategory(category)
                        }, toDisplayString(category.name), 11, ["aria-current", "onClick"]);
                      }), 128))
                    ])
                  ])) : createCommentVNode("", true),
                  createVNode("div", {
                    class: ["grid gap-6 xl:gap-8", { "lg:grid-cols-[16rem_minmax(0,1fr)]": showFilters.value }]
                  }, [
                    showFilters.value ? (openBlock(), createBlock("aside", {
                      key: 0,
                      class: "hidden lg:block",
                      "aria-label": "Refine products"
                    }, [
                      createVNode("div", { class: "sticky top-24 max-h-[calc(100vh-7rem)] space-y-6 overflow-y-auto overflow-x-hidden pb-6 pr-1" }, [
                        siblingCategories.value.length > 1 ? (openBlock(), createBlock("nav", {
                          key: 0,
                          "aria-label": `${currentDepartment.value?.name} categories`
                        }, [
                          createVNode("h2", { class: "mb-1 text-base font-bold text-st-ink" }, toDisplayString(currentDepartment.value?.name), 1),
                          createVNode("ul", null, [
                            (openBlock(true), createBlock(Fragment, null, renderList(siblingCategories.value, (category) => {
                              return openBlock(), createBlock("li", {
                                key: category.id
                              }, [
                                createVNode("button", {
                                  type: "button",
                                  class: ["flex min-h-9 w-full items-baseline border-l-2 py-1 pl-3 text-left text-[0.9375rem] focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action", category.id === selectedCategory.value?.id ? "border-st-signal font-semibold text-st-ink" : "border-transparent text-st-muted hover:text-st-ink"],
                                  "aria-current": category.id === selectedCategory.value?.id ? "page" : void 0,
                                  onClick: ($event) => openCategory(category)
                                }, [
                                  createVNode("span", null, toDisplayString(category.name), 1),
                                  createVNode("span", { class: "st-num ml-auto pl-2 text-sm text-st-muted" }, toDisplayString(category.products_count), 1)
                                ], 10, ["aria-current", "onClick"])
                              ]);
                            }), 128))
                          ])
                        ], 8, ["aria-label"])) : createCommentVNode("", true),
                        createVNode(_sfc_main$3, {
                          filters: availableFilters.value,
                          "active-filters": activeFilters.value,
                          loading: filtersLoading.value,
                          "onUpdate:activeFilters": handleFiltersUpdate,
                          onClear: handleClearFilters
                        }, null, 8, ["filters", "active-filters", "loading"])
                      ])
                    ])) : createCommentVNode("", true),
                    createVNode("div", {
                      id: "catalog-results",
                      class: "min-w-0 scroll-mt-28"
                    }, [
                      showFilters.value || filterPills.value.length ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "mb-3 flex flex-wrap items-center gap-2"
                      }, [
                        showFilters.value ? (openBlock(), createBlock("button", {
                          key: 0,
                          type: "button",
                          class: "inline-flex min-h-11 items-center gap-2 rounded-md border border-st-line bg-st-panel px-4 text-sm font-semibold text-st-ink lg:hidden",
                          onClick: ($event) => mobileFiltersOpen.value = true
                        }, [
                          createTextVNode(" Filter by spec "),
                          activeFilterCount.value ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: "st-num rounded bg-st-fill px-1.5 text-xs text-white"
                          }, toDisplayString(activeFilterCount.value), 1)) : createCommentVNode("", true)
                        ], 8, ["onClick"])) : createCommentVNode("", true),
                        (openBlock(true), createBlock(Fragment, null, renderList(filterPills.value, (pill) => {
                          return openBlock(), createBlock("button", {
                            key: pill.key,
                            type: "button",
                            class: "inline-flex min-h-9 items-center gap-2 rounded-full border border-st-line bg-st-panel px-3 text-sm text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action",
                            "aria-label": `Remove filter ${pill.label}`,
                            onClick: ($event) => removeFilterValue(pill.attribute, pill.value)
                          }, [
                            createTextVNode(toDisplayString(pill.label) + " ", 1),
                            (openBlock(), createBlock("svg", {
                              class: "h-3.5 w-3.5 text-st-muted",
                              viewBox: "0 0 24 24",
                              fill: "none",
                              stroke: "currentColor",
                              "stroke-width": "2.5",
                              "aria-hidden": "true"
                            }, [
                              createVNode("path", {
                                "stroke-linecap": "round",
                                d: "M6 6l12 12M18 6L6 18"
                              })
                            ]))
                          ], 8, ["aria-label", "onClick"]);
                        }), 128))
                      ])) : createCommentVNode("", true),
                      createVNode(_sfc_main$1, {
                        products: products.value,
                        loading: productsLoading.value,
                        meta: pagination.value,
                        "is-search": isSearching.value,
                        "has-filters": activeFilterCount.value > 0,
                        "current-page": currentPage.value,
                        "total-pages": totalPages.value,
                        onPrevious: ($event) => goToPage(currentPage.value - 1),
                        onNext: ($event) => goToPage(currentPage.value + 1),
                        onClearFilters: handleClearFilters,
                        onResetNavigation: openIndex
                      }, null, 8, ["products", "loading", "meta", "is-search", "has-filters", "current-page", "total-pages", "onPrevious", "onNext"])
                    ])
                  ], 2)
                ], 64)) : createCommentVNode("", true)
              ]),
              createVNode(unref(Dialog), {
                class: "lg:hidden",
                open: mobileFiltersOpen.value,
                onClose: ($event) => mobileFiltersOpen.value = false
              }, {
                default: withCtx(() => [
                  createVNode("div", {
                    class: "fixed inset-0 z-40 bg-black/60",
                    "aria-hidden": "true"
                  }),
                  createVNode(unref(DialogPanel), { class: "store-shell safe-y fixed inset-y-0 right-0 z-50 flex w-full max-w-drawer flex-col bg-st-panel text-st-ink shadow-2xl" }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "flex h-16 shrink-0 items-center justify-between border-b border-st-line px-4" }, [
                        createVNode("h2", { class: "text-lg font-bold" }, "Filter " + toDisplayString(selectedCategory.value?.name), 1),
                        createVNode("button", {
                          type: "button",
                          class: "inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink",
                          "aria-label": "Close filters",
                          onClick: ($event) => mobileFiltersOpen.value = false
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
                      createVNode("div", { class: "min-h-0 flex-1 overflow-y-auto px-4 py-2" }, [
                        createVNode(_sfc_main$3, {
                          filters: availableFilters.value,
                          "active-filters": activeFilters.value,
                          loading: filtersLoading.value,
                          "onUpdate:activeFilters": handleFiltersUpdate,
                          onClear: handleClearFilters
                        }, null, 8, ["filters", "active-filters", "loading"])
                      ]),
                      createVNode("div", { class: "safe-bottom shrink-0 border-t border-st-line p-4" }, [
                        createVNode("button", {
                          type: "button",
                          class: "inline-flex min-h-12 w-full items-center justify-center rounded-md bg-st-fill px-4 text-base font-semibold text-white hover:bg-st-fill/85",
                          onClick: ($event) => mobileFiltersOpen.value = false
                        }, " Show " + toDisplayString(pagination.value?.total ?? products.value.length) + " products ", 9, ["onClick"])
                      ])
                    ]),
                    _: 1
                  })
                ]),
                _: 1
              }, 8, ["open", "onClose"])
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
