import { mergeProps, unref, withCtx, createTextVNode, useSSRContext, reactive, watch, createVNode, createBlock, createCommentVNode, toDisplayString, openBlock } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderStyle } from "vue/server-renderer";
import { _ as _sfc_main$8 } from "./AuthenticatedLayout-DiECemAh.js";
import { Link, Head } from "@inertiajs/vue3";
import "./ApplicationLogo-B2173abF.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
import "axios";
import "./CookieConsentBanner-ByAlkSbo.js";
import "@headlessui/vue";
const _sfc_main$7 = {
  __name: "AccountSummaryPanel",
  __ssrInlineRender: true,
  props: {
    account: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "rounded-lg border border-gray-200 bg-white p-5 shadow-sm" }, _attrs))}><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Account</p><h2 class="mt-1 text-lg font-semibold text-gray-900">${ssrInterpolate(__props.account.name)}</h2><p class="text-sm text-gray-600">${ssrInterpolate(__props.account.email)}</p></div><span class="${ssrRenderClass([__props.account.profile_complete ? "bg-green-50 text-green-700" : "bg-amber-50 text-amber-700", "rounded-full px-2.5 py-1 text-xs font-semibold"])}">${ssrInterpolate(__props.account.profile_complete ? "Complete" : "Needs setup")}</span></div><dl class="mt-5 grid grid-cols-2 gap-4 text-sm"><div><dt class="text-gray-500">Company</dt><dd class="mt-1 font-medium text-gray-900">${ssrInterpolate(__props.account.company?.name ?? "Not assigned")}</dd></div><div><dt class="text-gray-500">Locations</dt><dd class="mt-1 font-medium text-gray-900">${ssrInterpolate(__props.account.locations_count)} / ${ssrInterpolate(__props.account.sublocations_count)} sub</dd></div></dl><div class="mt-5 flex flex-wrap gap-2">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("profile.edit"),
        class: "inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-4 py-3 text-base font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Edit profile `);
          } else {
            return [
              createTextVNode(" Edit profile ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("store.index"),
        class: "inline-flex min-h-12 items-center justify-center rounded-md bg-gray-900 px-4 py-3 text-base font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Browse store `);
          } else {
            return [
              createTextVNode(" Browse store ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></section>`);
    };
  }
};
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Dashboard/AccountSummaryPanel.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const _sfc_main$6 = {
  __name: "DashboardEmptyState",
  __ssrInlineRender: true,
  props: {
    account: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center" }, _attrs))}><h2 class="text-lg font-semibold text-gray-900">No purchasing activity yet</h2><p class="mx-auto mt-2 max-w-2xl text-base leading-6 text-gray-600"> Start an order from the store to populate spending trends, location rollups, and purchasing reports. `);
      if (!__props.account.company) {
        _push(`<span>Location reporting will appear once this account is assigned to a company.</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</p>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("store.index"),
        class: "mt-5 inline-flex min-h-12 items-center justify-center rounded-md bg-gray-900 px-4 py-3 text-base font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Browse store `);
          } else {
            return [
              createTextVNode(" Browse store ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</section>`);
    };
  }
};
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Dashboard/DashboardEmptyState.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const _sfc_main$5 = {
  __name: "DashboardKpiGrid",
  __ssrInlineRender: true,
  props: {
    summary: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const currency = new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" });
    const money = (value) => currency.format(Number(value) || 0);
    const cards = [
      { label: "Total spend", value: () => money(props.summary.total_spend) },
      { label: "Orders", value: () => props.summary.orders_count },
      { label: "Average order", value: () => money(props.summary.average_order_value) },
      { label: "Open / unpaid", value: () => `${props.summary.open_orders_count} / ${props.summary.unpaid_orders_count}` },
      { label: "Top location", value: () => props.summary.top_location?.label ?? "None" }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "grid gap-3 xs:grid-cols-2 xl:grid-cols-5" }, _attrs))}><!--[-->`);
      ssrRenderList(cards, (card) => {
        _push(`<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"><p class="text-sm font-semibold uppercase text-gray-600">${ssrInterpolate(card.label)}</p><p class="mt-2 break-words text-2xl font-semibold leading-8 text-gray-900">${ssrInterpolate(card.value())}</p></div>`);
      });
      _push(`<!--]--></section>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Dashboard/DashboardKpiGrid.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const _sfc_main$4 = {
  __name: "DateRangeControl",
  __ssrInlineRender: true,
  props: {
    filters: {
      type: Object,
      required: true
    },
    locations: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const props = __props;
    const form = reactive({
      range: props.filters.range ?? "last_30_days",
      start_date: props.filters.start_date,
      end_date: props.filters.end_date,
      location_id: props.filters.location_id ?? "",
      sublocation_id: props.filters.sublocation_id ?? ""
    });
    watch(() => form.location_id, () => {
      form.sublocation_id = "";
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "dashboard-filter rounded-lg border border-gray-200 bg-white p-4 shadow-sm" }, _attrs))}><div class="grid gap-3 md:grid-cols-6"><label class="text-base md:col-span-1"><span class="font-medium text-gray-700">Range</span><select class="mt-2 block w-full rounded-md border-gray-300 text-base"><!--[-->`);
      ssrRenderList(__props.filters.options.ranges, (option) => {
        _push(`<option${ssrRenderAttr("value", option.value)}${ssrIncludeBooleanAttr(Array.isArray(form.range) ? ssrLooseContain(form.range, option.value) : ssrLooseEqual(form.range, option.value)) ? " selected" : ""}>${ssrInterpolate(option.label)}</option>`);
      });
      _push(`<!--]--></select></label><label class="text-base md:col-span-1"><span class="font-medium text-gray-700">Start</span><input${ssrRenderAttr("value", form.start_date)} type="date"${ssrIncludeBooleanAttr(form.range !== "custom") ? " disabled" : ""} class="mt-2 block w-full rounded-md border-gray-300 text-base disabled:bg-gray-100"></label><label class="text-base md:col-span-1"><span class="font-medium text-gray-700">End</span><input${ssrRenderAttr("value", form.end_date)} type="date"${ssrIncludeBooleanAttr(form.range !== "custom") ? " disabled" : ""} class="mt-2 block w-full rounded-md border-gray-300 text-base disabled:bg-gray-100"></label><label class="text-base md:col-span-1"><span class="font-medium text-gray-700">Location</span><select class="mt-2 block w-full rounded-md border-gray-300 text-base"><option value=""${ssrIncludeBooleanAttr(Array.isArray(form.location_id) ? ssrLooseContain(form.location_id, "") : ssrLooseEqual(form.location_id, "")) ? " selected" : ""}>All</option><!--[-->`);
      ssrRenderList(__props.locations, (location) => {
        _push(`<option${ssrRenderAttr("value", location.id)}${ssrIncludeBooleanAttr(Array.isArray(form.location_id) ? ssrLooseContain(form.location_id, location.id) : ssrLooseEqual(form.location_id, location.id)) ? " selected" : ""}>${ssrInterpolate(location.name)}</option>`);
      });
      _push(`<!--]--></select></label><label class="text-base md:col-span-1"><span class="font-medium text-gray-700">Sublocation</span><select class="mt-2 block w-full rounded-md border-gray-300 text-base"><option value=""${ssrIncludeBooleanAttr(Array.isArray(form.sublocation_id) ? ssrLooseContain(form.sublocation_id, "") : ssrLooseEqual(form.sublocation_id, "")) ? " selected" : ""}>All</option><!--[-->`);
      ssrRenderList(__props.locations, (location) => {
        _push(`<!--[--><!--[-->`);
        ssrRenderList(location.children, (child) => {
          _push(`<option${ssrRenderAttr("value", child.id)}${ssrIncludeBooleanAttr(Array.isArray(form.sublocation_id) ? ssrLooseContain(form.sublocation_id, child.id) : ssrLooseEqual(form.sublocation_id, child.id)) ? " selected" : ""}>${ssrInterpolate(location.name)} / ${ssrInterpolate(child.name)}</option>`);
        });
        _push(`<!--]--><!--]-->`);
      });
      _push(`<!--]--></select></label><div class="flex items-end md:col-span-1"><button type="button" class="min-h-12 w-full rounded-md bg-gray-900 px-4 py-3 text-base font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"> Apply </button></div></div></section>`);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Dashboard/DateRangeControl.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const _sfc_main$3 = {
  __name: "RecentOrdersTable",
  __ssrInlineRender: true,
  props: {
    orders: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const currency = new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "rounded-lg border border-gray-200 bg-white shadow-sm" }, _attrs))}><div class="border-b border-gray-200 px-5 py-4"><h2 class="text-base font-semibold text-gray-900">Recent orders</h2></div><div><table class="responsive-data-table min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Payment</th><th class="px-5 py-3 text-right">Total</th></tr></thead><tbody class="divide-y divide-gray-100"><!--[-->`);
      ssrRenderList(__props.orders, (order) => {
        _push(`<tr><td data-label="Order" class="whitespace-nowrap px-5 py-3 font-medium text-gray-900">${ssrInterpolate(order.order_number)}</td><td data-label="Date" class="whitespace-nowrap px-5 py-3 text-gray-600">${ssrInterpolate(order.created_at)}</td><td data-label="Status" class="whitespace-nowrap px-5 py-3 text-gray-600">${ssrInterpolate(order.status_label)}</td><td data-label="Payment" class="whitespace-nowrap px-5 py-3 text-gray-600">${ssrInterpolate(order.payment_status_label)}</td><td data-label="Total" class="whitespace-nowrap px-5 py-3 text-right font-medium text-gray-900">${ssrInterpolate(unref(currency).format(order.grand_total))}</td></tr>`);
      });
      _push(`<!--]-->`);
      if (__props.orders.length === 0) {
        _push(`<tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">No orders in this account yet.</td></tr>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</tbody></table></div></section>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Dashboard/RecentOrdersTable.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = {
  __name: "SpendBarChart",
  __ssrInlineRender: true,
  props: {
    title: {
      type: String,
      required: true
    },
    rows: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const currency = new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" });
    const maxTotal = (rows) => Math.max(...rows.map((row) => Number(row.total) || 0), 1);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "rounded-lg border border-gray-200 bg-white p-5 shadow-sm" }, _attrs))}><h2 class="text-base font-semibold text-gray-900">${ssrInterpolate(__props.title)}</h2><div class="mt-4 space-y-3"><!--[-->`);
      ssrRenderList(__props.rows, (row) => {
        _push(`<div class="space-y-1"><div class="flex items-center justify-between gap-3 text-sm"><span class="truncate font-medium text-gray-700">${ssrInterpolate(row.label)}</span><span class="whitespace-nowrap text-gray-600">${ssrInterpolate(unref(currency).format(row.total))}</span></div><div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-blue-600" style="${ssrRenderStyle({ width: `${Math.max(4, Number(row.total) / maxTotal(__props.rows) * 100)}%` })}"></div></div></div>`);
      });
      _push(`<!--]-->`);
      if (__props.rows.length === 0) {
        _push(`<p class="py-8 text-center text-sm text-gray-500">No spending data for this period.</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></section>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Dashboard/SpendBarChart.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "TopItemsTable",
  __ssrInlineRender: true,
  props: {
    items: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const currency = new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "rounded-lg border border-gray-200 bg-white shadow-sm" }, _attrs))}><div class="border-b border-gray-200 px-5 py-4"><h2 class="text-base font-semibold text-gray-900">Top purchased items</h2></div><div class="divide-y divide-gray-100"><!--[-->`);
      ssrRenderList(__props.items, (item) => {
        _push(`<div class="flex items-center justify-between gap-4 px-5 py-3"><div class="min-w-0"><p class="truncate text-sm font-medium text-gray-900">${ssrInterpolate(item.name)}</p><p class="text-xs text-gray-500">${ssrInterpolate(item.sku ?? "No SKU")} · Qty ${ssrInterpolate(item.quantity)}</p></div><p class="whitespace-nowrap text-sm font-semibold text-gray-900">${ssrInterpolate(unref(currency).format(item.total))}</p></div>`);
      });
      _push(`<!--]-->`);
      if (__props.items.length === 0) {
        _push(`<div class="px-5 py-8 text-center text-sm text-gray-500">No item history yet.</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></section>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Dashboard/TopItemsTable.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "Dashboard",
  __ssrInlineRender: true,
  props: {
    filters: Object,
    account: Object,
    summary: Object,
    charts: Object,
    recent_orders: Array,
    top_items: Array,
    locations: Array
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Dashboard" }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$8, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="min-h-screen bg-gray-50"${_scopeId}><div class="mobile-page-gutter mx-auto max-w-7xl space-y-6 py-6 lg:px-8"${_scopeId}><div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"${_scopeId}><div${_scopeId}><p class="text-sm font-semibold uppercase tracking-wide text-gray-500"${_scopeId}>Customer dashboard</p><h1 class="mt-1 text-2xl font-semibold text-gray-900"${_scopeId}>Purchasing overview</h1><p class="mt-1 text-base text-gray-600"${_scopeId}>${ssrInterpolate(__props.filters.start_date)} through ${ssrInterpolate(__props.filters.end_date)}</p></div><div class="grid grid-cols-1 gap-2 xs:grid-cols-2 sm:flex"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("dashboard.reports"),
              class: "inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-3 text-base font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Build report `);
                } else {
                  return [
                    createTextVNode(" Build report ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("store.index"),
              class: "inline-flex min-h-12 items-center justify-center rounded-md bg-gray-900 px-4 py-3 text-base font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Browse store `);
                } else {
                  return [
                    createTextVNode(" Browse store ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div>`);
            _push2(ssrRenderComponent(_sfc_main$4, {
              filters: __props.filters,
              locations: __props.locations
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$5, { summary: __props.summary }, null, _parent2, _scopeId));
            if (__props.summary.orders_count === 0) {
              _push2(ssrRenderComponent(_sfc_main$6, { account: __props.account }, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="grid gap-6 xl:grid-cols-3"${_scopeId}><div class="space-y-6 xl:col-span-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              title: "Spend over time",
              rows: __props.charts.spend_over_time
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$2, {
              title: "Spend by location",
              rows: __props.charts.spend_by_location
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, { orders: __props.recent_orders }, null, _parent2, _scopeId));
            _push2(`</div><div class="space-y-6"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$7, { account: __props.account }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$2, {
              title: "Payment status",
              rows: __props.charts.payment_status_breakdown.map((row) => ({ label: row.label, total: row.total }))
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$1, { items: __props.top_items }, null, _parent2, _scopeId));
            _push2(`</div></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "min-h-screen bg-gray-50" }, [
                createVNode("div", { class: "mobile-page-gutter mx-auto max-w-7xl space-y-6 py-6 lg:px-8" }, [
                  createVNode("div", { class: "flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between" }, [
                    createVNode("div", null, [
                      createVNode("p", { class: "text-sm font-semibold uppercase tracking-wide text-gray-500" }, "Customer dashboard"),
                      createVNode("h1", { class: "mt-1 text-2xl font-semibold text-gray-900" }, "Purchasing overview"),
                      createVNode("p", { class: "mt-1 text-base text-gray-600" }, toDisplayString(__props.filters.start_date) + " through " + toDisplayString(__props.filters.end_date), 1)
                    ]),
                    createVNode("div", { class: "grid grid-cols-1 gap-2 xs:grid-cols-2 sm:flex" }, [
                      createVNode(unref(Link), {
                        href: _ctx.route("dashboard.reports"),
                        class: "inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-3 text-base font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Build report ")
                        ]),
                        _: 1
                      }, 8, ["href"]),
                      createVNode(unref(Link), {
                        href: _ctx.route("store.index"),
                        class: "inline-flex min-h-12 items-center justify-center rounded-md bg-gray-900 px-4 py-3 text-base font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Browse store ")
                        ]),
                        _: 1
                      }, 8, ["href"])
                    ])
                  ]),
                  createVNode(_sfc_main$4, {
                    filters: __props.filters,
                    locations: __props.locations
                  }, null, 8, ["filters", "locations"]),
                  createVNode(_sfc_main$5, { summary: __props.summary }, null, 8, ["summary"]),
                  __props.summary.orders_count === 0 ? (openBlock(), createBlock(_sfc_main$6, {
                    key: 0,
                    account: __props.account
                  }, null, 8, ["account"])) : createCommentVNode("", true),
                  createVNode("div", { class: "grid gap-6 xl:grid-cols-3" }, [
                    createVNode("div", { class: "space-y-6 xl:col-span-2" }, [
                      createVNode(_sfc_main$2, {
                        title: "Spend over time",
                        rows: __props.charts.spend_over_time
                      }, null, 8, ["rows"]),
                      createVNode(_sfc_main$2, {
                        title: "Spend by location",
                        rows: __props.charts.spend_by_location
                      }, null, 8, ["rows"]),
                      createVNode(_sfc_main$3, { orders: __props.recent_orders }, null, 8, ["orders"])
                    ]),
                    createVNode("div", { class: "space-y-6" }, [
                      createVNode(_sfc_main$7, { account: __props.account }, null, 8, ["account"]),
                      createVNode(_sfc_main$2, {
                        title: "Payment status",
                        rows: __props.charts.payment_status_breakdown.map((row) => ({ label: row.label, total: row.total }))
                      }, null, 8, ["rows"]),
                      createVNode(_sfc_main$1, { items: __props.top_items }, null, 8, ["items"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Dashboard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
