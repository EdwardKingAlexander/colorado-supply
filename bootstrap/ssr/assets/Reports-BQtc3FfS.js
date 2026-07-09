import { reactive, mergeProps, useSSRContext, unref, withCtx, createTextVNode, createVNode } from "vue";
import { ssrRenderAttrs, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrInterpolate, ssrRenderComponent } from "vue/server-renderer";
import { _ as _sfc_main$3 } from "./AuthenticatedLayout-BaKoCKvn.js";
import { Head, Link } from "@inertiajs/vue3";
import "./ApplicationLogo-B2173abF.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main$2 = {
  __name: "ReportBuilderForm",
  __ssrInlineRender: true,
  props: {
    filters: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const form = reactive({
      range: props.filters.key ?? props.filters.range ?? "last_30_days",
      start_date: props.filters.start_date,
      end_date: props.filters.end_date,
      location_id: props.filters.location_id ?? "",
      sublocation_id: props.filters.sublocation_id ?? "",
      group_by: props.filters.group_by ?? "month"
    });
    const exportUrl = () => route("dashboard.reports.export", {
      ...form,
      location_id: form.location_id || void 0,
      sublocation_id: form.sublocation_id || void 0
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "rounded-lg border border-gray-200 bg-white p-5 shadow-sm" }, _attrs))}><div class="grid gap-4 md:grid-cols-6"><label class="text-sm"><span class="font-medium text-gray-700">Range</span><select class="mt-1 block w-full rounded-md border-gray-300 text-sm"><option value="this_month"${ssrIncludeBooleanAttr(Array.isArray(form.range) ? ssrLooseContain(form.range, "this_month") : ssrLooseEqual(form.range, "this_month")) ? " selected" : ""}>This month</option><option value="last_30_days"${ssrIncludeBooleanAttr(Array.isArray(form.range) ? ssrLooseContain(form.range, "last_30_days") : ssrLooseEqual(form.range, "last_30_days")) ? " selected" : ""}>Last 30 days</option><option value="quarter_to_date"${ssrIncludeBooleanAttr(Array.isArray(form.range) ? ssrLooseContain(form.range, "quarter_to_date") : ssrLooseEqual(form.range, "quarter_to_date")) ? " selected" : ""}>Quarter to date</option><option value="year_to_date"${ssrIncludeBooleanAttr(Array.isArray(form.range) ? ssrLooseContain(form.range, "year_to_date") : ssrLooseEqual(form.range, "year_to_date")) ? " selected" : ""}>Year to date</option><option value="last_12_months"${ssrIncludeBooleanAttr(Array.isArray(form.range) ? ssrLooseContain(form.range, "last_12_months") : ssrLooseEqual(form.range, "last_12_months")) ? " selected" : ""}>Last 12 months</option><option value="custom"${ssrIncludeBooleanAttr(Array.isArray(form.range) ? ssrLooseContain(form.range, "custom") : ssrLooseEqual(form.range, "custom")) ? " selected" : ""}>Custom</option></select></label><label class="text-sm"><span class="font-medium text-gray-700">Start</span><input${ssrRenderAttr("value", form.start_date)} type="date"${ssrIncludeBooleanAttr(form.range !== "custom") ? " disabled" : ""} class="mt-1 block w-full rounded-md border-gray-300 text-sm disabled:bg-gray-100"></label><label class="text-sm"><span class="font-medium text-gray-700">End</span><input${ssrRenderAttr("value", form.end_date)} type="date"${ssrIncludeBooleanAttr(form.range !== "custom") ? " disabled" : ""} class="mt-1 block w-full rounded-md border-gray-300 text-sm disabled:bg-gray-100"></label><label class="text-sm"><span class="font-medium text-gray-700">Group by</span><select class="mt-1 block w-full rounded-md border-gray-300 text-sm"><!--[-->`);
      ssrRenderList(__props.filters.options.group_by, (option) => {
        _push(`<option${ssrRenderAttr("value", option.value)}${ssrIncludeBooleanAttr(Array.isArray(form.group_by) ? ssrLooseContain(form.group_by, option.value) : ssrLooseEqual(form.group_by, option.value)) ? " selected" : ""}>${ssrInterpolate(option.label)}</option>`);
      });
      _push(`<!--]--></select></label><label class="text-sm"><span class="font-medium text-gray-700">Location</span><select class="mt-1 block w-full rounded-md border-gray-300 text-sm"><option value=""${ssrIncludeBooleanAttr(Array.isArray(form.location_id) ? ssrLooseContain(form.location_id, "") : ssrLooseEqual(form.location_id, "")) ? " selected" : ""}>All</option><!--[-->`);
      ssrRenderList(__props.filters.options.locations, (location) => {
        _push(`<option${ssrRenderAttr("value", location.id)}${ssrIncludeBooleanAttr(Array.isArray(form.location_id) ? ssrLooseContain(form.location_id, location.id) : ssrLooseEqual(form.location_id, location.id)) ? " selected" : ""}>${ssrInterpolate(location.name)}</option>`);
      });
      _push(`<!--]--></select></label><label class="text-sm"><span class="font-medium text-gray-700">Sublocation</span><select class="mt-1 block w-full rounded-md border-gray-300 text-sm"><option value=""${ssrIncludeBooleanAttr(Array.isArray(form.sublocation_id) ? ssrLooseContain(form.sublocation_id, "") : ssrLooseEqual(form.sublocation_id, "")) ? " selected" : ""}>All</option><!--[-->`);
      ssrRenderList(__props.filters.options.locations, (location) => {
        _push(`<!--[--><!--[-->`);
        ssrRenderList(location.children, (child) => {
          _push(`<option${ssrRenderAttr("value", child.id)}${ssrIncludeBooleanAttr(Array.isArray(form.sublocation_id) ? ssrLooseContain(form.sublocation_id, child.id) : ssrLooseEqual(form.sublocation_id, child.id)) ? " selected" : ""}>${ssrInterpolate(location.name)} / ${ssrInterpolate(child.name)}</option>`);
        });
        _push(`<!--]--><!--]-->`);
      });
      _push(`<!--]--></select></label></div><div class="mt-5 flex flex-wrap gap-2"><button type="button" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800"> Preview report </button><a${ssrRenderAttr("href", exportUrl())} class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"> Export CSV </a></div></section>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Dashboard/ReportBuilderForm.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "ReportPreviewTable",
  __ssrInlineRender: true,
  props: {
    columns: {
      type: Array,
      default: () => []
    },
    rows: {
      type: Array,
      default: () => []
    },
    rowCount: {
      type: Number,
      default: 0
    }
  },
  setup(__props) {
    const label = (column) => column.replaceAll("_", " ");
    const format = (value, column) => {
      if (column === "spend") {
        return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(Number(value) || 0);
      }
      return value ?? "";
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "rounded-lg border border-gray-200 bg-white shadow-sm" }, _attrs))}><div class="flex items-center justify-between border-b border-gray-200 px-5 py-4"><h2 class="text-base font-semibold text-gray-900">Report preview</h2><p class="text-sm text-gray-500">${ssrInterpolate(__props.rowCount)} rows</p></div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><tr><!--[-->`);
      ssrRenderList(__props.columns, (column) => {
        _push(`<th class="px-5 py-3">${ssrInterpolate(label(column))}</th>`);
      });
      _push(`<!--]--></tr></thead><tbody class="divide-y divide-gray-100"><!--[-->`);
      ssrRenderList(__props.rows, (row, index) => {
        _push(`<tr><!--[-->`);
        ssrRenderList(__props.columns, (column) => {
          _push(`<td class="whitespace-nowrap px-5 py-3 text-gray-700">${ssrInterpolate(format(row[column], column))}</td>`);
        });
        _push(`<!--]--></tr>`);
      });
      _push(`<!--]-->`);
      if (__props.rows.length === 0) {
        _push(`<tr><td${ssrRenderAttr("colspan", __props.columns.length || 1)} class="px-5 py-8 text-center text-gray-500">No matching report data.</td></tr>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</tbody></table></div></section>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Dashboard/ReportPreviewTable.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "Reports",
  __ssrInlineRender: true,
  props: {
    filters: Object,
    columns: Array,
    rows: Array,
    row_count: Number
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Purchasing Reports" }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$3, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="min-h-screen bg-gray-50"${_scopeId}><div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8"${_scopeId}><div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"${_scopeId}><div${_scopeId}><p class="text-sm font-semibold uppercase tracking-wide text-gray-500"${_scopeId}>Reports</p><h1 class="mt-1 text-2xl font-semibold text-gray-900"${_scopeId}>Purchasing report builder</h1><p class="mt-1 text-sm text-gray-600"${_scopeId}>Preview and export spending by month, location, sublocation, product, or order.</p></div>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("dashboard"),
              class: "rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Back to dashboard `);
                } else {
                  return [
                    createTextVNode(" Back to dashboard ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
            _push2(ssrRenderComponent(_sfc_main$2, { filters: __props.filters }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$1, {
              columns: __props.columns,
              rows: __props.rows,
              "row-count": __props.row_count
            }, null, _parent2, _scopeId));
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", { class: "min-h-screen bg-gray-50" }, [
                createVNode("div", { class: "mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8" }, [
                  createVNode("div", { class: "flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between" }, [
                    createVNode("div", null, [
                      createVNode("p", { class: "text-sm font-semibold uppercase tracking-wide text-gray-500" }, "Reports"),
                      createVNode("h1", { class: "mt-1 text-2xl font-semibold text-gray-900" }, "Purchasing report builder"),
                      createVNode("p", { class: "mt-1 text-sm text-gray-600" }, "Preview and export spending by month, location, sublocation, product, or order.")
                    ]),
                    createVNode(unref(Link), {
                      href: _ctx.route("dashboard"),
                      class: "rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" Back to dashboard ")
                      ]),
                      _: 1
                    }, 8, ["href"])
                  ]),
                  createVNode(_sfc_main$2, { filters: __props.filters }, null, 8, ["filters"]),
                  createVNode(_sfc_main$1, {
                    columns: __props.columns,
                    rows: __props.rows,
                    "row-count": __props.row_count
                  }, null, 8, ["columns", "rows", "row-count"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Dashboard/Reports.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
