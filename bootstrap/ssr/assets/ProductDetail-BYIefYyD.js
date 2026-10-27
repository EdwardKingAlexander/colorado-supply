import { mergeModels, useModel, computed, ref, onBeforeUnmount, mergeProps, unref, withCtx, createTextVNode, useSSRContext, watch, createVNode, createBlock, openBlock, createCommentVNode, toDisplayString, Fragment, withModifiers, withDirectives, vModelText, onMounted, renderList, Transition } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr, ssrRenderComponent, ssrRenderStyle } from "vue/server-renderer";
import { a as unitPriceFor, u as useCartStore, c as currency, n as normalizeQuantity, _ as _sfc_main$8 } from "./StoreLayout-UuGiv97H.js";
import { Link, Head } from "@inertiajs/vue3";
import axios from "axios";
import { _ as _sfc_main$7 } from "./Modal-CPfU9EoI.js";
import "@headlessui/vue";
import "./CookieConsentBanner-ByAlkSbo.js";
import "./logo-cleansed-light-B5mBHTsK.js";
const _sfc_main$6 = {
  __name: "ProductBuyBox",
  __ssrInlineRender: true,
  props: /* @__PURE__ */ mergeModels({
    product: { type: Object, required: true }
  }, {
    "quantity": { type: Number, default: 1 },
    "quantityModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["request-quote"], ["update:quantity"]),
  setup(__props, { emit: __emit }) {
    const props = __props;
    const quantity = useModel(__props, "quantity");
    const pricing = computed(() => props.product.pricing ?? { priced: false });
    const minQuantity = computed(() => props.product.minimum_order_quantity ?? 1);
    const unit = computed(() => props.product.unit_of_measure ?? "EA");
    const onHand = computed(() => props.product.stock?.on_hand);
    const unitPrice = computed(() => unitPriceFor(pricing.value, quantity.value));
    const lineTotal = computed(() => unitPrice.value === null ? null : unitPrice.value * quantity.value);
    const canBuy = computed(() => unitPrice.value !== null);
    const firstTier = computed(() => pricing.value.tiers?.[0] ?? null);
    const belowFirstTier = computed(() => !canBuy.value && firstTier.value && quantity.value < firstTier.value.min_quantity);
    const backorderQuantity = computed(
      () => typeof onHand.value === "number" && quantity.value > onHand.value ? quantity.value - Math.max(0, onHand.value) : 0
    );
    const tierRows = computed(() => {
      const tiers = pricing.value.tiers ?? [];
      const rows = [];
      if (typeof pricing.value.base_price === "number" && tiers.length && tiers[0].min_quantity > 1) {
        rows.push({ from: 1, to: tiers[0].min_quantity - 1, price: pricing.value.base_price });
      }
      tiers.forEach((tier, index) => {
        const next = tiers[index + 1];
        rows.push({ from: tier.min_quantity, to: next ? next.min_quantity - 1 : null, price: tier.unit_price });
      });
      return rows.map((row) => ({
        ...row,
        active: quantity.value >= row.from && (row.to === null || quantity.value <= row.to)
      }));
    });
    const belowMinimum = computed(() => quantity.value < minQuantity.value);
    useCartStore();
    const added = ref(null);
    let feedbackTimer;
    onBeforeUnmount(() => clearTimeout(feedbackTimer));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({
        "aria-labelledby": "buy-box-heading",
        class: "rounded-lg border border-st-line bg-st-bg p-4 sm:p-5"
      }, _attrs))}><h2 id="buy-box-heading" class="sr-only">Order this product</h2>`);
      if (tierRows.value.length) {
        _push(`<div class="mb-4"><h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-st-muted">Quantity pricing</h3><table class="w-full text-sm"><thead class="sr-only"><tr><th scope="col">Quantity</th><th scope="col">Price per ${ssrInterpolate(unit.value)}</th></tr></thead><tbody class="divide-y divide-st-line"><!--[-->`);
        ssrRenderList(tierRows.value, (row) => {
          _push(`<tr class="${ssrRenderClass(row.active ? "font-semibold text-st-ink" : "text-st-muted")}"><td class="st-num py-1.5">${ssrInterpolate(row.to === null ? `${row.from.toLocaleString()}+` : `${row.from.toLocaleString()}–${row.to.toLocaleString()}`)} `);
          if (row.active) {
            _push(`<span class="sr-only">(your quantity)</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</td><td class="st-num py-1.5 text-right">${ssrInterpolate(unref(currency).format(row.price))}</td></tr>`);
        });
        _push(`<!--]--></tbody></table></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<label for="buy-quantity" class="block text-sm font-semibold text-st-ink">Quantity (${ssrInterpolate(unit.value)})</label><div class="mt-2 flex items-center gap-2"><button type="button" class="inline-flex h-12 w-12 items-center justify-center rounded-md border border-st-line text-xl font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action disabled:cursor-not-allowed disabled:opacity-50"${ssrIncludeBooleanAttr(quantity.value <= minQuantity.value) ? " disabled" : ""} aria-label="Decrease quantity"> − </button><input id="buy-quantity"${ssrRenderAttr("value", quantity.value)} type="number"${ssrRenderAttr("min", minQuantity.value)} inputmode="numeric" aria-describedby="buy-quantity-help" class="st-num h-12 w-28 rounded-md border border-st-line bg-st-panel px-2 text-center text-lg font-semibold text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40"><button type="button" class="inline-flex h-12 w-12 items-center justify-center rounded-md border border-st-line text-xl font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action" aria-label="Increase quantity"> + </button></div><div id="buy-quantity-help" class="mt-2 space-y-1 text-sm text-st-muted">`);
      if (minQuantity.value > 1) {
        _push(`<p class="${ssrRenderClass({ "font-semibold text-st-warn": belowMinimum.value })}">Minimum order: ${ssrInterpolate(minQuantity.value.toLocaleString())} ${ssrInterpolate(unit.value)}</p>`);
      } else {
        _push(`<!---->`);
      }
      if (backorderQuantity.value > 0) {
        _push(`<p>${ssrInterpolate(backorderQuantity.value.toLocaleString())} will be backordered`);
        if (__props.product.stock?.lead_time_days) {
          _push(`<span> (ships in about ${ssrInterpolate(__props.product.stock.lead_time_days)} ${ssrInterpolate(__props.product.stock.lead_time_days === 1 ? "day" : "days")})</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`. </p>`);
      } else {
        _push(`<!---->`);
      }
      if (belowFirstTier.value) {
        _push(`<p>Priced from ${ssrInterpolate(firstTier.value.min_quantity.toLocaleString())} ${ssrInterpolate(unit.value)}. Increase the quantity or request a quote.</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (lineTotal.value !== null) {
        _push(`<div class="mt-4 flex items-baseline justify-between border-t border-st-line pt-4"><span class="text-sm text-st-muted">${ssrInterpolate(quantity.value.toLocaleString())} × ${ssrInterpolate(unref(currency).format(unitPrice.value))}</span><span class="st-num text-xl font-bold text-st-ink">${ssrInterpolate(unref(currency).format(lineTotal.value))}</span></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="mt-4 space-y-3">`);
      if (canBuy.value) {
        _push(`<button type="button" class="min-h-12 w-full rounded-md bg-st-fill px-6 py-3 text-base font-semibold text-white transition-colors hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2"> Add to Cart </button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<button type="button" class="${ssrRenderClass([canBuy.value ? "border border-st-line text-st-ink hover:bg-st-sunk" : "bg-st-fill text-white hover:bg-st-fill/85", "min-h-12 w-full rounded-md px-6 py-3 text-base font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2"])}"> Request a Quote </button></div><p aria-live="polite" class="mt-3 min-h-5 text-center text-sm">`);
      if (added.value) {
        _push(`<!--[--><span class="font-semibold text-st-ok">Added ${ssrInterpolate(added.value.toLocaleString())} to cart</span> · `);
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("store.cart"),
          class: "font-semibold text-st-action hover:underline"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`View cart`);
            } else {
              return [
                createTextVNode("View cart")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<!--]-->`);
      } else {
        _push(`<!---->`);
      }
      _push(`</p></section>`);
    };
  }
};
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Store/ProductBuyBox.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const _sfc_main$5 = {
  __name: "ProductGallery",
  __ssrInlineRender: true,
  props: {
    images: { type: Array, default: () => [] },
    name: { type: String, required: true }
  },
  setup(__props) {
    const props = __props;
    const active = ref(0);
    const zoomOpen = ref(false);
    ref(null);
    const current = computed(() => props.images[active.value] ?? null);
    const hasMany = computed(() => props.images.length > 1);
    watch(() => props.images, () => {
      active.value = 0;
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-3" }, _attrs))}><div class="relative overflow-hidden rounded-lg border border-st-line bg-white" style="${ssrRenderStyle({ "aspect-ratio": "1 / 1" })}">`);
      if (current.value) {
        _push(`<button type="button" class="block h-full w-full cursor-zoom-in focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-st-action"${ssrRenderAttr("aria-label", `Zoom image: ${current.value.alt}`)}><img${ssrRenderAttr("src", current.value.url)}${ssrRenderAttr("alt", current.value.alt)} class="h-full w-full object-contain" decoding="async" width="800" height="800"></button>`);
      } else {
        _push(`<div class="flex h-full w-full items-center justify-center bg-st-bg"><svg class="h-full w-full p-[22%] text-st-muted/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true"><path stroke-linejoin="round" d="M12 2.75 20 7.25v9.5l-8 4.5-8-4.5v-9.5l8-4.5Z"></path><path stroke-linejoin="round" d="m4 7.25 8 4.5 8-4.5M12 11.75v9.5"></path></svg><span class="sr-only">No image available for ${ssrInterpolate(__props.name)}</span></div>`);
      }
      if (hasMany.value) {
        _push(`<p class="st-num absolute bottom-2 right-2 rounded bg-black/60 px-2 py-0.5 text-xs text-white" aria-hidden="true">${ssrInterpolate(active.value + 1)} / ${ssrInterpolate(__props.images.length)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (hasMany.value) {
        _push(`<ul class="flex gap-2 overflow-x-auto pb-1" aria-label="Product images"><!--[-->`);
        ssrRenderList(__props.images, (image, index) => {
          _push(`<li class="shrink-0"><button type="button" class="${ssrRenderClass([index === active.value ? "border-st-action" : "border-st-line hover:border-st-muted", "h-16 w-16 overflow-hidden rounded-md border-2 bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"])}"${ssrRenderAttr("aria-label", `Show image ${index + 1} of ${__props.images.length}`)}${ssrRenderAttr("aria-current", index === active.value ? "true" : void 0)}><img${ssrRenderAttr("src", image.url)}${ssrRenderAttr("alt", "")} class="h-full w-full object-contain" loading="lazy" decoding="async" width="64" height="64"></button></li>`);
        });
        _push(`<!--]--></ul>`);
      } else {
        _push(`<!---->`);
      }
      if (zoomOpen.value) {
        _push(`<dialog class="m-0 h-full max-h-none w-full max-w-none bg-black/90 p-0 backdrop:bg-black/80"${ssrRenderAttr("aria-label", `${__props.name} image viewer`)}><div class="flex h-full w-full flex-col"><div class="flex items-center justify-between px-4 py-3 text-white"><p class="st-num text-sm">${ssrInterpolate(active.value + 1)} / ${ssrInterpolate(__props.images.length)}</p><button type="button" class="inline-flex h-12 w-12 items-center justify-center rounded-md text-2xl hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Close image viewer"> × </button></div><div class="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-6">`);
        if (current.value) {
          _push(`<img${ssrRenderAttr("src", current.value.url)}${ssrRenderAttr("alt", current.value.alt)} class="max-h-full max-w-full object-contain">`);
        } else {
          _push(`<!---->`);
        }
        if (hasMany.value) {
          _push(`<!--[--><button type="button" class="absolute left-2 top-1/2 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white hover:bg-white/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Previous image"> ‹ </button><button type="button" class="absolute right-2 top-1/2 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white hover:bg-white/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Next image"> › </button><!--]-->`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></dialog>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Store/ProductGallery.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const _sfc_main$4 = {
  __name: "ProductKeyFacts",
  __ssrInlineRender: true,
  props: {
    product: { type: Object, required: true }
  },
  setup(__props) {
    const props = __props;
    const formatNsn = (nsn) => /^\d{13}$/.test(nsn) ? `${nsn.slice(0, 4)}-${nsn.slice(4, 6)}-${nsn.slice(6, 9)}-${nsn.slice(9)}` : nsn;
    const facts = computed(() => {
      const ids = props.product.identifiers ?? {};
      const unit = props.product.unit_of_measure ?? "EA";
      const minimum = props.product.minimum_order_quantity ?? 1;
      return [
        { key: "sku", label: "SKU", value: props.product.sku, copy: true },
        { key: "mpn", label: "MPN", value: ids.mpn, copy: true },
        { key: "part_number", label: "Part number", value: ids.part_number, copy: true },
        { key: "nsn", label: "NSN", value: ids.nsn ? formatNsn(ids.nsn) : null, copy: true },
        { key: "cage_code", label: "CAGE", value: ids.cage_code, copy: true },
        { key: "unit", label: "Unit", value: unit, copy: false },
        { key: "minimum", label: "Minimum order", value: minimum > 1 ? `${minimum.toLocaleString()} ${unit}` : null, copy: false }
      ].filter((fact) => fact.value);
    });
    const copied = ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}><dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2"><!--[-->`);
      ssrRenderList(facts.value, (fact) => {
        _push(`<div class="flex min-w-0 items-center justify-between gap-2 border-b border-st-line/60 pb-2"><dt class="shrink-0 text-st-muted">${ssrInterpolate(fact.label)}</dt><dd class="flex min-w-0 items-center gap-1"><span class="st-num truncate font-medium text-st-ink">${ssrInterpolate(fact.value)}</span>`);
        if (fact.copy) {
          _push(`<button type="button" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-st-muted hover:bg-st-sunk hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"${ssrRenderAttr("aria-label", `Copy ${fact.label} ${fact.value}`)}>`);
          if (copied.value !== fact.key) {
            _push(`<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15V5a2 2 0 0 1 2-2h10"></path></svg>`);
          } else {
            _push(`<svg class="h-4 w-4 text-st-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m5 12 5 5L20 7"></path></svg>`);
          }
          _push(`</button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</dd></div>`);
      });
      _push(`<!--]--></dl><p class="sr-only" aria-live="polite">${ssrInterpolate(copied.value ? "Copied to clipboard" : "")}</p></div>`);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Store/ProductKeyFacts.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const _sfc_main$3 = {
  __name: "ProductSpecs",
  __ssrInlineRender: true,
  props: {
    product: { type: Object, required: true }
  },
  setup(__props) {
    const props = __props;
    const inches = (mm) => `${mm.toLocaleString()} mm (${(mm / 25.4).toFixed(2)} in)`;
    const weight = (g) => g >= 453.6 ? `${g.toLocaleString()} g (${(g / 453.592).toFixed(2)} lb)` : `${g.toLocaleString()} g (${(g / 28.3495).toFixed(2)} oz)`;
    const GENERAL_LABELS = { material: "Material", finish: "Finish", country_of_origin: "Country of origin" };
    const DIMENSION_ROWS = [
      ["length_mm", "Length", inches],
      ["width_mm", "Width", inches],
      ["height_mm", "Height", inches],
      ["weight_g", "Weight", weight]
    ];
    const CLASSIFICATION_LABELS = { unspsc: "UNSPSC", psc_fsc: "PSC / FSC", naics_code: "NAICS" };
    const BARCODE_LABELS = { gtin: "GTIN", upc: "UPC", ean: "EAN" };
    const groups = computed(() => {
      const specs = props.product.specs ?? {};
      const general = specs.general ?? {};
      const dimensions = specs.dimensions ?? {};
      const classification = props.product.classification ?? {};
      const identifiers = props.product.identifiers ?? {};
      return [
        {
          title: "General",
          rows: Object.entries(GENERAL_LABELS).filter(([key]) => general[key]).map(([key, label]) => ({ label, value: general[key] }))
        },
        {
          title: "Dimensions",
          rows: DIMENSION_ROWS.filter(([key]) => dimensions[key]).map(([key, label, format]) => ({ label, value: format(dimensions[key]) }))
        },
        {
          title: "Technical details",
          rows: (specs.attributes ?? []).map((attribute) => ({ label: attribute.name, value: attribute.value }))
        },
        {
          title: "Classification",
          rows: [
            ...Object.entries(CLASSIFICATION_LABELS).filter(([key]) => classification[key]).map(([key, label]) => ({ label, value: classification[key] })),
            ...Object.entries(BARCODE_LABELS).filter(([key]) => identifiers[key]).map(([key, label]) => ({ label, value: identifiers[key] }))
          ]
        }
      ].filter((group) => group.rows.length);
    });
    const details = computed(() => props.product.specs?.details ?? null);
    const documents = computed(() => props.product.documents ?? []);
    const hasAnything = computed(() => groups.value.length || details.value || documents.value.length);
    return (_ctx, _push, _parent, _attrs) => {
      if (hasAnything.value) {
        _push(`<section${ssrRenderAttrs(mergeProps({
          "aria-labelledby": "specs-heading",
          class: "space-y-6"
        }, _attrs))}><h2 id="specs-heading" class="st-display text-xl font-extrabold text-st-ink">Specifications</h2>`);
        if (groups.value.length) {
          _push(`<div class="grid gap-6 md:grid-cols-2"><!--[-->`);
          ssrRenderList(groups.value, (group) => {
            _push(`<div><h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-st-muted">${ssrInterpolate(group.title)}</h3><dl class="divide-y divide-st-line rounded-lg border border-st-line bg-st-bg"><!--[-->`);
            ssrRenderList(group.rows, (row) => {
              _push(`<div class="grid grid-cols-2 gap-4 px-4 py-2.5 text-sm"><dt class="font-medium text-st-ink/85">${ssrInterpolate(row.label)}</dt><dd class="st-num break-words text-st-ink">${ssrInterpolate(row.value)}</dd></div>`);
            });
            _push(`<!--]--></dl></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        if (details.value) {
          _push(`<div><h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-st-muted">Additional specifications</h3><p class="whitespace-pre-line text-sm leading-relaxed text-st-ink/85">${ssrInterpolate(details.value)}</p></div>`);
        } else {
          _push(`<!---->`);
        }
        if (documents.value.length) {
          _push(`<div><h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-st-muted">Documents</h3><ul class="flex flex-wrap gap-2"><!--[-->`);
          ssrRenderList(documents.value, (document) => {
            _push(`<li><a${ssrRenderAttr("href", document.url)} target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center gap-2 rounded-md border border-st-line px-4 py-2 text-sm font-semibold text-st-ink hover:bg-st-sunk focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"><svg class="h-4 w-4 text-st-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"></path><path d="M14 3v5h5"></path></svg> ${ssrInterpolate(document.label)} <span class="sr-only">(opens in a new tab)</span></a></li>`);
          });
          _push(`<!--]--></ul></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</section>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Store/ProductSpecs.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = {
  __name: "RelatedProducts",
  __ssrInlineRender: true,
  props: {
    related: { type: Object, default: () => ({}) }
  },
  setup(__props) {
    const props = __props;
    const GROUPS = [
      ["substitutes", "Substitutes", "Interchangeable alternatives"],
      ["accessories", "Accessories", "Often bought with this item"],
      ["related", "Related products", null]
    ];
    const groups = computed(() => GROUPS.map(([key, title, hint]) => ({ key, title, hint, items: props.related?.[key] ?? [] })).filter((group) => group.items.length));
    const priceLabel = (item) => typeof item.pricing?.unit_price === "number" ? `${currency.format(item.pricing.unit_price)} / ${item.unit_of_measure ?? "EA"}` : "Request a quote";
    return (_ctx, _push, _parent, _attrs) => {
      if (groups.value.length) {
        _push(`<section${ssrRenderAttrs(mergeProps({
          "aria-labelledby": "related-heading",
          class: "space-y-6"
        }, _attrs))}><h2 id="related-heading" class="sr-only">Related products</h2><!--[-->`);
        ssrRenderList(groups.value, (group) => {
          _push(`<div><div class="mb-3 flex items-baseline gap-3"><h3 class="st-display text-xl font-extrabold text-st-ink">${ssrInterpolate(group.title)}</h3>`);
          if (group.hint) {
            _push(`<p class="text-sm text-st-muted">${ssrInterpolate(group.hint)}</p>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"><!--[-->`);
          ssrRenderList(group.items, (item) => {
            _push(`<li>`);
            _push(ssrRenderComponent(unref(Link), {
              href: item.url,
              class: "flex h-full gap-3 rounded-lg border border-st-line bg-st-bg p-3 transition-colors hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`<div class="h-16 w-16 shrink-0 overflow-hidden rounded-md border border-st-line bg-white"${_scopeId}>`);
                  if (item.image) {
                    _push2(`<img${ssrRenderAttr("src", item.image)} alt="" class="h-full w-full object-contain" loading="lazy" decoding="async" width="64" height="64"${_scopeId}>`);
                  } else {
                    _push2(`<svg class="h-full w-full p-3 text-st-muted/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"${_scopeId}><path stroke-linejoin="round" d="M12 2.75 20 7.25v9.5l-8 4.5-8-4.5v-9.5l8-4.5Z"${_scopeId}></path></svg>`);
                  }
                  _push2(`</div><div class="min-w-0"${_scopeId}><p class="line-clamp-2 text-sm font-semibold text-st-ink"${_scopeId}>${ssrInterpolate(item.name)}</p>`);
                  if (item.sku) {
                    _push2(`<p class="st-num mt-0.5 truncate text-xs text-st-muted"${_scopeId}>${ssrInterpolate(item.sku)}</p>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`<p class="st-num mt-1 text-sm text-st-ink/85"${_scopeId}>${ssrInterpolate(priceLabel(item))}</p></div>`);
                } else {
                  return [
                    createVNode("div", { class: "h-16 w-16 shrink-0 overflow-hidden rounded-md border border-st-line bg-white" }, [
                      item.image ? (openBlock(), createBlock("img", {
                        key: 0,
                        src: item.image,
                        alt: "",
                        class: "h-full w-full object-contain",
                        loading: "lazy",
                        decoding: "async",
                        width: "64",
                        height: "64"
                      }, null, 8, ["src"])) : (openBlock(), createBlock("svg", {
                        key: 1,
                        class: "h-full w-full p-3 text-st-muted/60",
                        viewBox: "0 0 24 24",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "1.2",
                        "aria-hidden": "true"
                      }, [
                        createVNode("path", {
                          "stroke-linejoin": "round",
                          d: "M12 2.75 20 7.25v9.5l-8 4.5-8-4.5v-9.5l8-4.5Z"
                        })
                      ]))
                    ]),
                    createVNode("div", { class: "min-w-0" }, [
                      createVNode("p", { class: "line-clamp-2 text-sm font-semibold text-st-ink" }, toDisplayString(item.name), 1),
                      item.sku ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "st-num mt-0.5 truncate text-xs text-st-muted"
                      }, toDisplayString(item.sku), 1)) : createCommentVNode("", true),
                      createVNode("p", { class: "st-num mt-1 text-sm text-st-ink/85" }, toDisplayString(priceLabel(item)), 1)
                    ])
                  ];
                }
              }),
              _: 2
            }, _parent));
            _push(`</li>`);
          });
          _push(`<!--]--></ul></div>`);
        });
        _push(`<!--]--></section>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Store/RelatedProducts.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "RequestQuoteModal",
  __ssrInlineRender: true,
  props: /* @__PURE__ */ mergeModels({
    product: { type: Object, required: true },
    quantity: { type: Number, default: 1 }
  }, {
    "open": { type: Boolean, default: false },
    "openModifiers": {}
  }),
  emits: ["update:open"],
  setup(__props) {
    const props = __props;
    const open = useModel(__props, "open");
    const minQuantity = () => props.product.minimum_order_quantity ?? 1;
    const form = ref({ quantity: props.quantity, note: "" });
    const errors = ref({});
    const submitting = ref(false);
    const quoteNumber = ref(null);
    watch(open, (isOpen) => {
      if (isOpen) {
        form.value = { quantity: normalizeQuantity(props.quantity, minQuantity()), note: "" };
        errors.value = {};
        quoteNumber.value = null;
      }
    });
    const submit = async () => {
      submitting.value = true;
      errors.value = {};
      try {
        const { data } = await axios.post(route("store.product.quote", props.product.id), {
          quantity: normalizeQuantity(form.value.quantity, minQuantity()),
          note: form.value.note || null
        });
        quoteNumber.value = data.quote_number;
      } catch (error) {
        if (error.response?.status === 422) {
          errors.value = error.response.data.errors ?? {};
        } else if (error.response?.status === 429) {
          errors.value = { form: ["Too many quote requests. Please wait a minute and try again."] };
        } else {
          errors.value = { form: ["Something went wrong sending your request. Please try again."] };
        }
      } finally {
        submitting.value = false;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$7, mergeProps({
        show: open.value,
        "max-width": "lg",
        onClose: ($event) => open.value = false
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-st-panel p-5 text-st-ink sm:p-6"${_scopeId}>`);
            if (quoteNumber.value) {
              _push2(`<!--[--><h2 class="st-display text-xl font-extrabold"${_scopeId}>Quote requested</h2><p class="mt-3 text-sm text-st-ink/85" role="status"${_scopeId}> Your request <span class="st-num font-semibold"${_scopeId}>${ssrInterpolate(quoteNumber.value)}</span> was sent to our sales team. We&#39;ll follow up with pricing and availability. </p><div class="mt-6 flex justify-end"${_scopeId}><button type="button" class="min-h-12 rounded-md bg-st-fill px-6 py-3 text-base font-semibold text-white hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2"${_scopeId}> Done </button></div><!--]-->`);
            } else {
              _push2(`<form novalidate${_scopeId}><h2 class="st-display text-xl font-extrabold"${_scopeId}>Request a quote</h2><p class="mt-1 text-sm text-st-muted"${_scopeId}>${ssrInterpolate(__props.product.name)}`);
              if (__props.product.sku) {
                _push2(`<span${_scopeId}> · <span class="st-num"${_scopeId}>${ssrInterpolate(__props.product.sku)}</span></span>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</p><div class="mt-5 space-y-4"${_scopeId}><div${_scopeId}><label for="quote-quantity" class="block text-sm font-semibold"${_scopeId}>Quantity (${ssrInterpolate(__props.product.unit_of_measure ?? "EA")})</label><input id="quote-quantity"${ssrRenderAttr("value", form.value.quantity)} type="number"${ssrRenderAttr("min", __props.product.minimum_order_quantity ?? 1)} inputmode="numeric" required${ssrRenderAttr("aria-invalid", Boolean(errors.value.quantity))}${ssrRenderAttr("aria-describedby", errors.value.quantity ? "quote-quantity-error" : void 0)} class="st-num mt-1 h-12 w-40 rounded-md border border-st-line bg-st-sunk px-3 text-base text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40"${_scopeId}>`);
              if (errors.value.quantity) {
                _push2(`<p id="quote-quantity-error" class="mt-1 text-sm text-red-400"${_scopeId}>${ssrInterpolate(errors.value.quantity[0])}</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><div${_scopeId}><label for="quote-note" class="block text-sm font-semibold"${_scopeId}>Notes <span class="font-normal text-st-muted"${_scopeId}>(optional)</span></label><textarea id="quote-note" rows="3" maxlength="1000" placeholder="Delivery date, spec requirements, contract number…" class="mt-1 w-full rounded-md border border-st-line bg-st-sunk px-3 py-2 text-base text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40"${_scopeId}>${ssrInterpolate(form.value.note)}</textarea>`);
              if (errors.value.note) {
                _push2(`<p class="mt-1 text-sm text-red-400"${_scopeId}>${ssrInterpolate(errors.value.note[0])}</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
              if (errors.value.form) {
                _push2(`<p class="text-sm text-red-400" role="alert"${_scopeId}>${ssrInterpolate(errors.value.form[0])}</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"${_scopeId}><button type="button" class="min-h-12 rounded-md border border-st-line px-6 py-3 text-base font-semibold text-st-ink hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action"${_scopeId}> Cancel </button><button type="submit"${ssrIncludeBooleanAttr(submitting.value) ? " disabled" : ""} class="min-h-12 rounded-md bg-st-fill px-6 py-3 text-base font-semibold text-white hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2 disabled:opacity-60"${_scopeId}>${ssrInterpolate(submitting.value ? "Sending…" : "Send request")}</button></div></form>`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "bg-st-panel p-5 text-st-ink sm:p-6" }, [
                quoteNumber.value ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                  createVNode("h2", { class: "st-display text-xl font-extrabold" }, "Quote requested"),
                  createVNode("p", {
                    class: "mt-3 text-sm text-st-ink/85",
                    role: "status"
                  }, [
                    createTextVNode(" Your request "),
                    createVNode("span", { class: "st-num font-semibold" }, toDisplayString(quoteNumber.value), 1),
                    createTextVNode(" was sent to our sales team. We'll follow up with pricing and availability. ")
                  ]),
                  createVNode("div", { class: "mt-6 flex justify-end" }, [
                    createVNode("button", {
                      type: "button",
                      class: "min-h-12 rounded-md bg-st-fill px-6 py-3 text-base font-semibold text-white hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2",
                      onClick: ($event) => open.value = false
                    }, " Done ", 8, ["onClick"])
                  ])
                ], 64)) : (openBlock(), createBlock("form", {
                  key: 1,
                  novalidate: "",
                  onSubmit: withModifiers(submit, ["prevent"])
                }, [
                  createVNode("h2", { class: "st-display text-xl font-extrabold" }, "Request a quote"),
                  createVNode("p", { class: "mt-1 text-sm text-st-muted" }, [
                    createTextVNode(toDisplayString(__props.product.name), 1),
                    __props.product.sku ? (openBlock(), createBlock("span", { key: 0 }, [
                      createTextVNode(" · "),
                      createVNode("span", { class: "st-num" }, toDisplayString(__props.product.sku), 1)
                    ])) : createCommentVNode("", true)
                  ]),
                  createVNode("div", { class: "mt-5 space-y-4" }, [
                    createVNode("div", null, [
                      createVNode("label", {
                        for: "quote-quantity",
                        class: "block text-sm font-semibold"
                      }, "Quantity (" + toDisplayString(__props.product.unit_of_measure ?? "EA") + ")", 1),
                      withDirectives(createVNode("input", {
                        id: "quote-quantity",
                        "onUpdate:modelValue": ($event) => form.value.quantity = $event,
                        type: "number",
                        min: __props.product.minimum_order_quantity ?? 1,
                        inputmode: "numeric",
                        required: "",
                        "aria-invalid": Boolean(errors.value.quantity),
                        "aria-describedby": errors.value.quantity ? "quote-quantity-error" : void 0,
                        class: "st-num mt-1 h-12 w-40 rounded-md border border-st-line bg-st-sunk px-3 text-base text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40"
                      }, null, 8, ["onUpdate:modelValue", "min", "aria-invalid", "aria-describedby"]), [
                        [
                          vModelText,
                          form.value.quantity,
                          void 0,
                          { number: true }
                        ]
                      ]),
                      errors.value.quantity ? (openBlock(), createBlock("p", {
                        key: 0,
                        id: "quote-quantity-error",
                        class: "mt-1 text-sm text-red-400"
                      }, toDisplayString(errors.value.quantity[0]), 1)) : createCommentVNode("", true)
                    ]),
                    createVNode("div", null, [
                      createVNode("label", {
                        for: "quote-note",
                        class: "block text-sm font-semibold"
                      }, [
                        createTextVNode("Notes "),
                        createVNode("span", { class: "font-normal text-st-muted" }, "(optional)")
                      ]),
                      withDirectives(createVNode("textarea", {
                        id: "quote-note",
                        "onUpdate:modelValue": ($event) => form.value.note = $event,
                        rows: "3",
                        maxlength: "1000",
                        placeholder: "Delivery date, spec requirements, contract number…",
                        class: "mt-1 w-full rounded-md border border-st-line bg-st-sunk px-3 py-2 text-base text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, form.value.note]
                      ]),
                      errors.value.note ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "mt-1 text-sm text-red-400"
                      }, toDisplayString(errors.value.note[0]), 1)) : createCommentVNode("", true)
                    ]),
                    errors.value.form ? (openBlock(), createBlock("p", {
                      key: 0,
                      class: "text-sm text-red-400",
                      role: "alert"
                    }, toDisplayString(errors.value.form[0]), 1)) : createCommentVNode("", true)
                  ]),
                  createVNode("div", { class: "mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end" }, [
                    createVNode("button", {
                      type: "button",
                      class: "min-h-12 rounded-md border border-st-line px-6 py-3 text-base font-semibold text-st-ink hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action",
                      onClick: ($event) => open.value = false
                    }, " Cancel ", 8, ["onClick"]),
                    createVNode("button", {
                      type: "submit",
                      disabled: submitting.value,
                      class: "min-h-12 rounded-md bg-st-fill px-6 py-3 text-base font-semibold text-white hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2 disabled:opacity-60"
                    }, toDisplayString(submitting.value ? "Sending…" : "Send request"), 9, ["disabled"])
                  ])
                ], 32))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Store/RequestQuoteModal.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "ProductDetail",
  __ssrInlineRender: true,
  props: {
    product: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const product = computed(() => props.product);
    const pricing = computed(() => props.product.pricing ?? { priced: false });
    const unitLabel = computed(() => product.value.unit_of_measure ?? "EA");
    const quantity = ref(props.product.minimum_order_quantity ?? 1);
    const unitPrice = computed(() => unitPriceFor(pricing.value, quantity.value));
    const priceDisplay = computed(() => {
      if (unitPrice.value !== null) {
        return currency.format(unitPrice.value);
      }
      const firstTier = pricing.value.tiers?.[0];
      return firstTier ? `From ${currency.format(firstTier.unit_price)}` : "Price on request";
    });
    const showListPrice = computed(() => pricing.value.list_price && unitPrice.value !== null && pricing.value.list_price > unitPrice.value);
    const governmentPrices = computed(() => [
      ["GSA price", pricing.value.gsa_price],
      ["Contract price", pricing.value.contract_price]
    ].filter(([, value]) => typeof value === "number"));
    const breadcrumbs = computed(() => (product.value.breadcrumbs ?? []).map((crumb, index, all) => ({
      ...crumb,
      href: crumb.slug ? route("store.index", index === 0 && all.length > 1 ? { department: crumb.slug } : { category: crumb.slug }) : route("store.index")
    })));
    const COMPLIANCE = {
      berry_compliant: { label: "Berry compliant", hint: "Meets the Berry Amendment (US-made materials)." },
      taa_compliant: { label: "TAA compliant", hint: "Made in a Trade Agreements Act designated country." },
      made_in_usa: { label: "Made in USA", hint: null },
      gsa_approved: { label: "GSA approved", hint: "Available on GSA Schedule." },
      qpl_listed: { label: "QPL listed", hint: "On a Qualified Products List." }
    };
    const WARNINGS = {
      is_hazmat: { label: "Hazmat", hint: "Hazardous material: special shipping rules and lead times may apply." },
      export_controlled: { label: "Export controlled", hint: "May require an export license (ITAR/EAR) before shipping outside the US." }
    };
    const compliance = computed(() => (product.value.compliance ?? []).filter((key) => COMPLIANCE[key]).map((key) => ({ key, ...COMPLIANCE[key] })));
    const warnings = computed(() => (product.value.warnings ?? []).filter((key) => WARNINGS[key]).map((key) => ({ key, ...WARNINGS[key] })));
    const government = computed(() => product.value.government ?? null);
    const plural = (count, word) => `${count.toLocaleString()} ${word}${count === 1 ? "" : "s"}`;
    const availability = computed(() => {
      const stock = product.value.stock;
      if (!stock) {
        return null;
      }
      const leadTime = stock.lead_time_days ? `ships in ${plural(stock.lead_time_days, "day")}` : null;
      if (stock.in_stock === true) {
        return { tone: "ok", label: "In stock", detail: typeof stock.on_hand === "number" ? `${stock.on_hand.toLocaleString()} ${unitLabel.value} available` : null };
      }
      if (stock.in_stock === false) {
        return { tone: "warn", label: "Out of stock", detail: leadTime ? `Backorder ${leadTime}` : "Contact us for availability" };
      }
      return leadTime ? { tone: "warn", label: `Ships in ${plural(stock.lead_time_days, "day")}`, detail: null } : null;
    });
    const locations = computed(() => product.value.stock?.locations ?? []);
    const descriptionExpanded = ref(false);
    const longDescription = computed(() => (product.value.description ?? "").length > 400);
    const quoteOpen = ref(false);
    const buyBox = ref(null);
    const buyBoxVisible = ref(true);
    let buyBoxObserver;
    onMounted(() => {
      if (!buyBox.value || typeof IntersectionObserver === "undefined") {
        return;
      }
      buyBoxObserver = new IntersectionObserver(([entry]) => {
        buyBoxVisible.value = entry.isIntersecting;
      });
      buyBoxObserver.observe(buyBox.value);
    });
    onBeforeUnmount(() => buyBoxObserver?.disconnect());
    const jumpToBuyBox = () => {
      buyBox.value?.scrollIntoView({ behavior: "smooth", block: "center" });
      buyBox.value?.querySelector("#buy-quantity")?.focus({ preventScroll: true });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: product.value.name
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$8, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-st-bg min-h-screen"${_scopeId}><div class="mobile-page-gutter mx-auto max-w-7xl space-y-6 py-6 pb-28 sm:py-8 lg:px-8 lg:pb-8"${_scopeId}><nav aria-label="Breadcrumb"${_scopeId}><ol class="flex flex-wrap items-center gap-x-1 gap-y-1 text-sm text-st-muted"${_scopeId}><li${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("store.index"),
              class: "rounded px-1 py-1 hover:text-st-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Store`);
                } else {
                  return [
                    createTextVNode("Store")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</li><!--[-->`);
            ssrRenderList(breadcrumbs.value, (crumb) => {
              _push2(`<li class="flex items-center gap-1"${_scopeId}><span aria-hidden="true"${_scopeId}>›</span>`);
              _push2(ssrRenderComponent(unref(Link), {
                href: crumb.href,
                class: "rounded px-1 py-1 hover:text-st-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`${ssrInterpolate(crumb.name)}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(crumb.name), 1)
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
              _push2(`</li>`);
            });
            _push2(`<!--]--><li class="flex min-w-0 items-center gap-1"${_scopeId}><span aria-hidden="true"${_scopeId}>›</span><span aria-current="page" class="truncate px-1 text-st-ink/85"${_scopeId}>${ssrInterpolate(product.value.name)}</span></li></ol></nav>`);
            if (product.value.inactive) {
              _push2(`<p class="rounded-lg border border-st-warn/40 bg-st-warn/10 px-4 py-3 text-sm text-st-ink" role="note"${_scopeId}> This product is inactive. Only admins can see this page; buyers get &quot;not found&quot;. </p>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="rounded-lg bg-st-panel p-4 shadow-sm sm:p-8"${_scopeId}><header class="mb-6"${_scopeId}>`);
            if (product.value.vendor) {
              _push2(`<p class="text-sm font-semibold text-st-muted"${_scopeId}>${ssrInterpolate(product.value.vendor.name)}</p>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<h1 class="st-display mt-1 text-2xl font-extrabold text-st-ink sm:text-3xl"${_scopeId}>${ssrInterpolate(product.value.name)}</h1></header><div class="grid grid-cols-1 gap-8 lg:grid-cols-12"${_scopeId}><div class="lg:col-span-6"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$5, {
              images: product.value.images ?? [],
              name: product.value.name
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="space-y-6 lg:col-span-6"${_scopeId}><div${_scopeId}><p class="flex flex-wrap items-baseline gap-x-2"${_scopeId}><span class="st-num text-3xl font-bold text-st-ink"${_scopeId}>${ssrInterpolate(priceDisplay.value)}</span>`);
            if (unitPrice.value !== null) {
              _push2(`<span class="text-sm text-st-muted"${_scopeId}>per ${ssrInterpolate(unitLabel.value)}</span>`);
            } else {
              _push2(`<!---->`);
            }
            if (showListPrice.value) {
              _push2(`<span class="st-num text-sm text-st-muted line-through"${_scopeId}>List ${ssrInterpolate(unref(currency).format(pricing.value.list_price))}</span>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</p>`);
            if (pricing.value.source === "company") {
              _push2(`<p class="mt-1 inline-flex rounded-full bg-st-ok/15 px-2.5 py-0.5 text-xs font-semibold text-st-ok"${_scopeId}> Your company price </p>`);
            } else {
              _push2(`<!---->`);
            }
            if (governmentPrices.value.length) {
              _push2(`<dl class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm"${_scopeId}><!--[-->`);
              ssrRenderList(governmentPrices.value, ([label, value]) => {
                _push2(`<div class="flex gap-2"${_scopeId}><dt class="text-st-muted"${_scopeId}>${ssrInterpolate(label)}</dt><dd class="st-num font-semibold text-st-ink"${_scopeId}>${ssrInterpolate(unref(currency).format(value))}</dd></div>`);
              });
              _push2(`<!--]--></dl>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
            if (availability.value || locations.value.length) {
              _push2(`<div class="space-y-2"${_scopeId}>`);
              if (availability.value) {
                _push2(`<p class="flex flex-wrap items-center gap-2 text-sm"${_scopeId}><span class="${ssrRenderClass([availability.value.tone === "ok" ? "bg-st-ok/15 text-st-ok" : "bg-st-warn/15 text-st-warn", "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"])}"${_scopeId}>${ssrInterpolate(availability.value.label)}</span>`);
                if (availability.value.detail) {
                  _push2(`<span class="text-st-ink/85"${_scopeId}>${ssrInterpolate(availability.value.detail)}</span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</p>`);
              } else {
                _push2(`<!---->`);
              }
              if (locations.value.length) {
                _push2(`<ul class="space-y-1 text-sm" aria-label="Stock by location"${_scopeId}><!--[-->`);
                ssrRenderList(locations.value, (location) => {
                  _push2(`<li class="flex justify-between gap-4 border-b border-st-line/60 pb-1"${_scopeId}><span class="text-st-ink/85"${_scopeId}>${ssrInterpolate(location.name)}</span><span class="${ssrRenderClass([location.on_hand > 0 ? "text-st-ink" : "text-st-muted", "st-num"])}"${_scopeId}>${ssrInterpolate(location.on_hand > 0 ? `${location.on_hand.toLocaleString()} ${unitLabel.value}` : "Out of stock")}</span></li>`);
                });
                _push2(`<!--]--></ul>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            if (compliance.value.length || warnings.value.length || government.value) {
              _push2(`<div class="space-y-2"${_scopeId}>`);
              if (compliance.value.length || warnings.value.length) {
                _push2(`<ul class="flex flex-wrap gap-2" aria-label="Compliance"${_scopeId}><!--[-->`);
                ssrRenderList(compliance.value, (badge) => {
                  _push2(`<li class="inline-flex items-center gap-1 rounded-full border border-st-ok/40 bg-st-ok/10 px-3 py-1 text-xs font-semibold text-st-ok"${ssrRenderAttr("title", badge.hint ?? void 0)}${_scopeId}><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"${_scopeId}><path d="m5 12 5 5L20 7"${_scopeId}></path></svg> ${ssrInterpolate(badge.label)} `);
                  if (badge.hint) {
                    _push2(`<span class="sr-only"${_scopeId}>: ${ssrInterpolate(badge.hint)}</span>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</li>`);
                });
                _push2(`<!--]--><!--[-->`);
                ssrRenderList(warnings.value, (badge) => {
                  _push2(`<li class="inline-flex items-center gap-1 rounded-full border border-st-warn/50 bg-st-warn/10 px-3 py-1 text-xs font-semibold text-st-warn"${ssrRenderAttr("title", badge.hint)}${_scopeId}><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"${_scopeId}><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"${_scopeId}></path></svg> ${ssrInterpolate(badge.label)} <span class="sr-only"${_scopeId}>: ${ssrInterpolate(badge.hint)}</span></li>`);
                });
                _push2(`<!--]--></ul>`);
              } else {
                _push2(`<!---->`);
              }
              if (government.value) {
                _push2(`<p class="text-sm text-st-ink/85"${_scopeId}>`);
                if (government.value.standard_type) {
                  _push2(`<span class="font-semibold"${_scopeId}>${ssrInterpolate(government.value.standard_type)} standard</span>`);
                } else {
                  _push2(`<!---->`);
                }
                if (government.value.standard_type && government.value.mil_spec_number) {
                  _push2(`<span${_scopeId}> · </span>`);
                } else {
                  _push2(`<!---->`);
                }
                if (government.value.mil_spec_number) {
                  _push2(`<span class="st-num"${_scopeId}>${ssrInterpolate(government.value.mil_spec_number)}</span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$6, {
              quantity: quantity.value,
              "onUpdate:quantity": ($event) => quantity.value = $event,
              product: product.value,
              onRequestQuote: ($event) => quoteOpen.value = true
            }, null, _parent2, _scopeId));
            _push2(`</div>`);
            _push2(ssrRenderComponent(_sfc_main$4, { product: product.value }, null, _parent2, _scopeId));
            _push2(`</div></div>`);
            if (product.value.description) {
              _push2(`<section aria-labelledby="description-heading" class="mt-10 border-t border-st-line pt-8"${_scopeId}><h2 id="description-heading" class="st-display mb-3 text-xl font-extrabold text-st-ink"${_scopeId}>Description</h2><p id="product-description" class="${ssrRenderClass([{ "max-lg:line-clamp-6": longDescription.value && !descriptionExpanded.value }, "max-w-3xl whitespace-pre-line text-sm leading-relaxed text-st-ink/85"])}"${_scopeId}>${ssrInterpolate(product.value.description)}</p>`);
              if (longDescription.value) {
                _push2(`<button type="button" class="mt-2 min-h-11 text-sm font-semibold text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:hidden" aria-controls="product-description"${ssrRenderAttr("aria-expanded", descriptionExpanded.value)}${_scopeId}>${ssrInterpolate(descriptionExpanded.value ? "Show less" : "Show more")}</button>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</section>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(ssrRenderComponent(_sfc_main$3, {
              product: product.value,
              class: "mt-10 border-t border-st-line pt-8"
            }, null, _parent2, _scopeId));
            if (product.value.related) {
              _push2(ssrRenderComponent(_sfc_main$2, {
                related: product.value.related,
                class: "mt-10 border-t border-st-line pt-8"
              }, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div></div>`);
            _push2(ssrRenderComponent(_sfc_main$1, {
              open: quoteOpen.value,
              "onUpdate:open": ($event) => quoteOpen.value = $event,
              product: product.value,
              quantity: quantity.value
            }, null, _parent2, _scopeId));
            _push2(``);
            if (!buyBoxVisible.value) {
              _push2(`<div class="fixed inset-x-0 bottom-0 z-40 border-t border-st-line bg-st-panel/95 px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 backdrop-blur lg:hidden"${_scopeId}><div class="mx-auto flex max-w-7xl items-center justify-between gap-3"${_scopeId}><div class="min-w-0"${_scopeId}><p class="truncate text-sm text-st-muted"${_scopeId}>${ssrInterpolate(product.value.name)}</p><p class="st-num text-lg font-bold text-st-ink"${_scopeId}>${ssrInterpolate(priceDisplay.value)}`);
              if (unitPrice.value !== null) {
                _push2(`<span class="text-sm font-normal text-st-muted"${_scopeId}> / ${ssrInterpolate(unitLabel.value)}</span>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</p></div><button type="button" class="min-h-12 shrink-0 rounded-md bg-st-fill px-5 py-3 text-base font-semibold text-white hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2"${_scopeId}>${ssrInterpolate(unitPrice.value !== null ? "Buy" : "Request a Quote")}</button></div></div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              createVNode("div", { class: "bg-st-bg min-h-screen" }, [
                createVNode("div", { class: "mobile-page-gutter mx-auto max-w-7xl space-y-6 py-6 pb-28 sm:py-8 lg:px-8 lg:pb-8" }, [
                  createVNode("nav", { "aria-label": "Breadcrumb" }, [
                    createVNode("ol", { class: "flex flex-wrap items-center gap-x-1 gap-y-1 text-sm text-st-muted" }, [
                      createVNode("li", null, [
                        createVNode(unref(Link), {
                          href: _ctx.route("store.index"),
                          class: "rounded px-1 py-1 hover:text-st-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                        }, {
                          default: withCtx(() => [
                            createTextVNode("Store")
                          ]),
                          _: 1
                        }, 8, ["href"])
                      ]),
                      (openBlock(true), createBlock(Fragment, null, renderList(breadcrumbs.value, (crumb) => {
                        return openBlock(), createBlock("li", {
                          key: crumb.id,
                          class: "flex items-center gap-1"
                        }, [
                          createVNode("span", { "aria-hidden": "true" }, "›"),
                          createVNode(unref(Link), {
                            href: crumb.href,
                            class: "rounded px-1 py-1 hover:text-st-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                          }, {
                            default: withCtx(() => [
                              createTextVNode(toDisplayString(crumb.name), 1)
                            ]),
                            _: 2
                          }, 1032, ["href"])
                        ]);
                      }), 128)),
                      createVNode("li", { class: "flex min-w-0 items-center gap-1" }, [
                        createVNode("span", { "aria-hidden": "true" }, "›"),
                        createVNode("span", {
                          "aria-current": "page",
                          class: "truncate px-1 text-st-ink/85"
                        }, toDisplayString(product.value.name), 1)
                      ])
                    ])
                  ]),
                  product.value.inactive ? (openBlock(), createBlock("p", {
                    key: 0,
                    class: "rounded-lg border border-st-warn/40 bg-st-warn/10 px-4 py-3 text-sm text-st-ink",
                    role: "note"
                  }, ' This product is inactive. Only admins can see this page; buyers get "not found". ')) : createCommentVNode("", true),
                  createVNode("div", { class: "rounded-lg bg-st-panel p-4 shadow-sm sm:p-8" }, [
                    createVNode("header", { class: "mb-6" }, [
                      product.value.vendor ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "text-sm font-semibold text-st-muted"
                      }, toDisplayString(product.value.vendor.name), 1)) : createCommentVNode("", true),
                      createVNode("h1", { class: "st-display mt-1 text-2xl font-extrabold text-st-ink sm:text-3xl" }, toDisplayString(product.value.name), 1)
                    ]),
                    createVNode("div", { class: "grid grid-cols-1 gap-8 lg:grid-cols-12" }, [
                      createVNode("div", { class: "lg:col-span-6" }, [
                        createVNode(_sfc_main$5, {
                          images: product.value.images ?? [],
                          name: product.value.name
                        }, null, 8, ["images", "name"])
                      ]),
                      createVNode("div", { class: "space-y-6 lg:col-span-6" }, [
                        createVNode("div", null, [
                          createVNode("p", { class: "flex flex-wrap items-baseline gap-x-2" }, [
                            createVNode("span", { class: "st-num text-3xl font-bold text-st-ink" }, toDisplayString(priceDisplay.value), 1),
                            unitPrice.value !== null ? (openBlock(), createBlock("span", {
                              key: 0,
                              class: "text-sm text-st-muted"
                            }, "per " + toDisplayString(unitLabel.value), 1)) : createCommentVNode("", true),
                            showListPrice.value ? (openBlock(), createBlock("span", {
                              key: 1,
                              class: "st-num text-sm text-st-muted line-through"
                            }, "List " + toDisplayString(unref(currency).format(pricing.value.list_price)), 1)) : createCommentVNode("", true)
                          ]),
                          pricing.value.source === "company" ? (openBlock(), createBlock("p", {
                            key: 0,
                            class: "mt-1 inline-flex rounded-full bg-st-ok/15 px-2.5 py-0.5 text-xs font-semibold text-st-ok"
                          }, " Your company price ")) : createCommentVNode("", true),
                          governmentPrices.value.length ? (openBlock(), createBlock("dl", {
                            key: 1,
                            class: "mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm"
                          }, [
                            (openBlock(true), createBlock(Fragment, null, renderList(governmentPrices.value, ([label, value]) => {
                              return openBlock(), createBlock("div", {
                                key: label,
                                class: "flex gap-2"
                              }, [
                                createVNode("dt", { class: "text-st-muted" }, toDisplayString(label), 1),
                                createVNode("dd", { class: "st-num font-semibold text-st-ink" }, toDisplayString(unref(currency).format(value)), 1)
                              ]);
                            }), 128))
                          ])) : createCommentVNode("", true)
                        ]),
                        availability.value || locations.value.length ? (openBlock(), createBlock("div", {
                          key: 0,
                          class: "space-y-2"
                        }, [
                          availability.value ? (openBlock(), createBlock("p", {
                            key: 0,
                            class: "flex flex-wrap items-center gap-2 text-sm"
                          }, [
                            createVNode("span", {
                              class: ["inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold", availability.value.tone === "ok" ? "bg-st-ok/15 text-st-ok" : "bg-st-warn/15 text-st-warn"]
                            }, toDisplayString(availability.value.label), 3),
                            availability.value.detail ? (openBlock(), createBlock("span", {
                              key: 0,
                              class: "text-st-ink/85"
                            }, toDisplayString(availability.value.detail), 1)) : createCommentVNode("", true)
                          ])) : createCommentVNode("", true),
                          locations.value.length ? (openBlock(), createBlock("ul", {
                            key: 1,
                            class: "space-y-1 text-sm",
                            "aria-label": "Stock by location"
                          }, [
                            (openBlock(true), createBlock(Fragment, null, renderList(locations.value, (location) => {
                              return openBlock(), createBlock("li", {
                                key: location.id,
                                class: "flex justify-between gap-4 border-b border-st-line/60 pb-1"
                              }, [
                                createVNode("span", { class: "text-st-ink/85" }, toDisplayString(location.name), 1),
                                createVNode("span", {
                                  class: ["st-num", location.on_hand > 0 ? "text-st-ink" : "text-st-muted"]
                                }, toDisplayString(location.on_hand > 0 ? `${location.on_hand.toLocaleString()} ${unitLabel.value}` : "Out of stock"), 3)
                              ]);
                            }), 128))
                          ])) : createCommentVNode("", true)
                        ])) : createCommentVNode("", true),
                        compliance.value.length || warnings.value.length || government.value ? (openBlock(), createBlock("div", {
                          key: 1,
                          class: "space-y-2"
                        }, [
                          compliance.value.length || warnings.value.length ? (openBlock(), createBlock("ul", {
                            key: 0,
                            class: "flex flex-wrap gap-2",
                            "aria-label": "Compliance"
                          }, [
                            (openBlock(true), createBlock(Fragment, null, renderList(compliance.value, (badge) => {
                              return openBlock(), createBlock("li", {
                                key: badge.key,
                                class: "inline-flex items-center gap-1 rounded-full border border-st-ok/40 bg-st-ok/10 px-3 py-1 text-xs font-semibold text-st-ok",
                                title: badge.hint ?? void 0
                              }, [
                                (openBlock(), createBlock("svg", {
                                  class: "h-3.5 w-3.5",
                                  viewBox: "0 0 24 24",
                                  fill: "none",
                                  stroke: "currentColor",
                                  "stroke-width": "3",
                                  "aria-hidden": "true"
                                }, [
                                  createVNode("path", { d: "m5 12 5 5L20 7" })
                                ])),
                                createTextVNode(" " + toDisplayString(badge.label) + " ", 1),
                                badge.hint ? (openBlock(), createBlock("span", {
                                  key: 0,
                                  class: "sr-only"
                                }, ": " + toDisplayString(badge.hint), 1)) : createCommentVNode("", true)
                              ], 8, ["title"]);
                            }), 128)),
                            (openBlock(true), createBlock(Fragment, null, renderList(warnings.value, (badge) => {
                              return openBlock(), createBlock("li", {
                                key: badge.key,
                                class: "inline-flex items-center gap-1 rounded-full border border-st-warn/50 bg-st-warn/10 px-3 py-1 text-xs font-semibold text-st-warn",
                                title: badge.hint
                              }, [
                                (openBlock(), createBlock("svg", {
                                  class: "h-3.5 w-3.5",
                                  viewBox: "0 0 24 24",
                                  fill: "none",
                                  stroke: "currentColor",
                                  "stroke-width": "2.5",
                                  "aria-hidden": "true"
                                }, [
                                  createVNode("path", { d: "M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" })
                                ])),
                                createTextVNode(" " + toDisplayString(badge.label) + " ", 1),
                                createVNode("span", { class: "sr-only" }, ": " + toDisplayString(badge.hint), 1)
                              ], 8, ["title"]);
                            }), 128))
                          ])) : createCommentVNode("", true),
                          government.value ? (openBlock(), createBlock("p", {
                            key: 1,
                            class: "text-sm text-st-ink/85"
                          }, [
                            government.value.standard_type ? (openBlock(), createBlock("span", {
                              key: 0,
                              class: "font-semibold"
                            }, toDisplayString(government.value.standard_type) + " standard", 1)) : createCommentVNode("", true),
                            government.value.standard_type && government.value.mil_spec_number ? (openBlock(), createBlock("span", { key: 1 }, " · ")) : createCommentVNode("", true),
                            government.value.mil_spec_number ? (openBlock(), createBlock("span", {
                              key: 2,
                              class: "st-num"
                            }, toDisplayString(government.value.mil_spec_number), 1)) : createCommentVNode("", true)
                          ])) : createCommentVNode("", true)
                        ])) : createCommentVNode("", true),
                        createVNode("div", {
                          ref_key: "buyBox",
                          ref: buyBox
                        }, [
                          createVNode(_sfc_main$6, {
                            quantity: quantity.value,
                            "onUpdate:quantity": ($event) => quantity.value = $event,
                            product: product.value,
                            onRequestQuote: ($event) => quoteOpen.value = true
                          }, null, 8, ["quantity", "onUpdate:quantity", "product", "onRequestQuote"])
                        ], 512),
                        createVNode(_sfc_main$4, { product: product.value }, null, 8, ["product"])
                      ])
                    ]),
                    product.value.description ? (openBlock(), createBlock("section", {
                      key: 0,
                      "aria-labelledby": "description-heading",
                      class: "mt-10 border-t border-st-line pt-8"
                    }, [
                      createVNode("h2", {
                        id: "description-heading",
                        class: "st-display mb-3 text-xl font-extrabold text-st-ink"
                      }, "Description"),
                      createVNode("p", {
                        id: "product-description",
                        class: ["max-w-3xl whitespace-pre-line text-sm leading-relaxed text-st-ink/85", { "max-lg:line-clamp-6": longDescription.value && !descriptionExpanded.value }]
                      }, toDisplayString(product.value.description), 3),
                      longDescription.value ? (openBlock(), createBlock("button", {
                        key: 0,
                        type: "button",
                        class: "mt-2 min-h-11 text-sm font-semibold text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:hidden",
                        "aria-controls": "product-description",
                        "aria-expanded": descriptionExpanded.value,
                        onClick: ($event) => descriptionExpanded.value = !descriptionExpanded.value
                      }, toDisplayString(descriptionExpanded.value ? "Show less" : "Show more"), 9, ["aria-expanded", "onClick"])) : createCommentVNode("", true)
                    ])) : createCommentVNode("", true),
                    createVNode(_sfc_main$3, {
                      product: product.value,
                      class: "mt-10 border-t border-st-line pt-8"
                    }, null, 8, ["product"]),
                    product.value.related ? (openBlock(), createBlock(_sfc_main$2, {
                      key: 1,
                      related: product.value.related,
                      class: "mt-10 border-t border-st-line pt-8"
                    }, null, 8, ["related"])) : createCommentVNode("", true)
                  ])
                ])
              ]),
              createVNode(_sfc_main$1, {
                open: quoteOpen.value,
                "onUpdate:open": ($event) => quoteOpen.value = $event,
                product: product.value,
                quantity: quantity.value
              }, null, 8, ["open", "onUpdate:open", "product", "quantity"]),
              createVNode(Transition, {
                "enter-active-class": "transition duration-200 ease-out",
                "enter-from-class": "translate-y-full",
                "leave-active-class": "transition duration-150 ease-in",
                "leave-to-class": "translate-y-full"
              }, {
                default: withCtx(() => [
                  !buyBoxVisible.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "fixed inset-x-0 bottom-0 z-40 border-t border-st-line bg-st-panel/95 px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 backdrop-blur lg:hidden"
                  }, [
                    createVNode("div", { class: "mx-auto flex max-w-7xl items-center justify-between gap-3" }, [
                      createVNode("div", { class: "min-w-0" }, [
                        createVNode("p", { class: "truncate text-sm text-st-muted" }, toDisplayString(product.value.name), 1),
                        createVNode("p", { class: "st-num text-lg font-bold text-st-ink" }, [
                          createTextVNode(toDisplayString(priceDisplay.value), 1),
                          unitPrice.value !== null ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: "text-sm font-normal text-st-muted"
                          }, " / " + toDisplayString(unitLabel.value), 1)) : createCommentVNode("", true)
                        ])
                      ]),
                      createVNode("button", {
                        type: "button",
                        class: "min-h-12 shrink-0 rounded-md bg-st-fill px-5 py-3 text-base font-semibold text-white hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2",
                        onClick: ($event) => unitPrice.value !== null ? jumpToBuyBox() : quoteOpen.value = true
                      }, toDisplayString(unitPrice.value !== null ? "Buy" : "Request a Quote"), 9, ["onClick"])
                    ])
                  ])) : createCommentVNode("", true)
                ]),
                _: 1
              })
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
