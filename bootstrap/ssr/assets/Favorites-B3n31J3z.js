import { ref, onMounted, mergeProps, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrIncludeBooleanAttr, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrRenderClass } from "vue/server-renderer";
import axios from "axios";
const baseUrl = "/api/v1/sam-opportunities/favorites";
const _sfc_main = {
  __name: "Favorites",
  __ssrInlineRender: true,
  setup(__props) {
    const favorites = ref([]);
    const loading = ref(true);
    const errorMessage = ref("");
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      next_page_url: null,
      prev_page_url: null
    });
    const toggling = ref(/* @__PURE__ */ new Set());
    const documents = ref({});
    const documentsLoading = ref(/* @__PURE__ */ new Set());
    const documentsError = ref({});
    const openDocsFor = ref(null);
    const uploading = ref(/* @__PURE__ */ new Set());
    const insightsOpen = ref(null);
    const insightsQuestion = ref("");
    const insightsAnswer = ref({});
    const insightsLoading = ref(/* @__PURE__ */ new Set());
    const insightsError = ref({});
    const exporting = ref(false);
    const exportError = ref("");
    const setPagination = (meta = {}) => {
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        next_page_url: meta.next_page_url ?? null,
        prev_page_url: meta.prev_page_url ?? null
      };
    };
    const fetchFavorites = async (url = baseUrl) => {
      loading.value = true;
      errorMessage.value = "";
      try {
        const { data } = await axios.get(url);
        favorites.value = data.data ?? [];
        setPagination(data.meta ?? {});
      } catch (error) {
        errorMessage.value = "Failed to load favorites.";
      } finally {
        loading.value = false;
      }
    };
    const formatSize = (bytes) => {
      if (bytes === void 0 || bytes === null) return "N/A";
      if (bytes < 1024) return `${bytes} B`;
      if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
      return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };
    onMounted(() => {
      fetchFavorites();
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "mobile-page-gutter mx-auto max-w-6xl space-y-6 py-6 sm:py-8 lg:px-8" }, _attrs))}><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h1 class="text-2xl font-semibold text-gray-900">SAM Opportunities</h1><p class="text-gray-600">Favorited opportunities for your account.</p></div><div class="flex items-center gap-3">`);
      if (!loading.value && favorites.value.length > 0) {
        _push(`<button type="button" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-blue-700 px-4 py-3 text-base font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"${ssrIncludeBooleanAttr(exporting.value) ? " disabled" : ""}>`);
        if (!exporting.value) {
          _push(`<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>`);
        } else {
          _push(`<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`);
        }
        _push(`<span>${ssrInterpolate(exporting.value ? "Exporting..." : "Export to Excel")}</span></button>`);
      } else {
        _push(`<!---->`);
      }
      if (loading.value) {
        _push(`<div class="text-sm text-gray-500">Loading...</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
      if (errorMessage.value) {
        _push(`<div class="rounded-md bg-red-50 p-4 text-red-700 text-sm">${ssrInterpolate(errorMessage.value)}</div>`);
      } else {
        _push(`<!---->`);
      }
      if (exportError.value) {
        _push(`<div class="rounded-md bg-red-50 p-4 text-red-700 text-sm">${ssrInterpolate(exportError.value)}</div>`);
      } else {
        _push(`<!---->`);
      }
      if (!loading.value && favorites.value.length === 0) {
        _push(`<div class="rounded-lg border border-dashed border-gray-200 p-8 text-center text-gray-600"> No favorited opportunities yet. </div>`);
      } else if (!loading.value) {
        _push(`<div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm"><table class="responsive-data-table min-w-full divide-y divide-gray-200 bg-white"><thead class="bg-gray-50"><tr><th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Title</th><th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Agency</th><th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Posted</th><th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Due</th><th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Link</th><th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Favorite</th><th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Documents</th><th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Insights</th></tr></thead><tbody class="divide-y divide-gray-100"><!--[-->`);
        ssrRenderList(favorites.value, (opp) => {
          _push(`<!--[--><tr class="hover:bg-gray-50"><td data-label="Opportunity" class="px-4 py-3 text-sm text-gray-900"><div class="font-medium">${ssrInterpolate(opp.title || "Untitled opportunity")}</div>`);
          if (opp.naics_code || opp.psc_code) {
            _push(`<div class="text-gray-500 text-xs">`);
            if (opp.naics_code) {
              _push(`<span>NAICS: ${ssrInterpolate(opp.naics_code)}</span>`);
            } else {
              _push(`<!---->`);
            }
            if (opp.naics_code && opp.psc_code) {
              _push(`<span class="mx-1">•</span>`);
            } else {
              _push(`<!---->`);
            }
            if (opp.psc_code) {
              _push(`<span>PSC: ${ssrInterpolate(opp.psc_code)}</span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</td><td data-label="Agency" class="px-4 py-3 text-sm text-gray-700">${ssrInterpolate(opp.agency || "—")}</td><td data-label="Posted" class="px-4 py-3 text-sm text-gray-700">${ssrInterpolate(opp.posted_date || "—")}</td><td data-label="Due" class="px-4 py-3 text-sm text-gray-700">${ssrInterpolate(opp.response_deadline || "—")}</td><td data-label="SAM.gov" class="px-4 py-3 text-sm text-blue-600">`);
          if (opp.sam_url) {
            _push(`<a${ssrRenderAttr("href", opp.sam_url)} class="inline-flex min-h-12 items-center rounded-md px-3 text-base font-semibold hover:bg-blue-50 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-600" target="_blank" rel="noopener noreferrer">View opportunity</a>`);
          } else {
            _push(`<span class="text-gray-500">—</span>`);
          }
          _push(`</td><td data-label="Favorite" class="px-4 py-3 text-right"><button type="button" class="${ssrRenderClass([opp.is_favorite ? "border-amber-400 text-amber-600 bg-amber-50" : "border-gray-300 text-gray-700 hover:border-amber-300 hover:text-amber-600", "inline-flex min-h-12 items-center rounded-full border px-4 py-2 text-base font-medium transition focus:outline-none focus:ring-2 focus:ring-amber-500"])}"${ssrIncludeBooleanAttr(toggling.value.has(opp.id)) ? " disabled" : ""}><svg class="${ssrRenderClass([opp.is_favorite ? "fill-amber-500" : "fill-none stroke-current", "h-4 w-4 mr-1"])}" viewBox="0 0 24 24" stroke-width="1.5"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"${ssrRenderAttr("fill", opp.is_favorite ? "currentColor" : "none")}${ssrRenderAttr("stroke", opp.is_favorite ? "currentColor" : "currentColor")}></path></svg><span>${ssrInterpolate(opp.is_favorite ? "Favorited" : "Favorite")}</span></button></td><td data-label="Documents" class="px-4 py-3 text-right"><button type="button" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-4 py-3 text-base font-semibold text-gray-700 transition hover:border-blue-400 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600"> Manage Documents </button></td><td data-label="Insights" class="px-4 py-3 text-right"><button type="button" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-4 py-3 text-base font-semibold text-gray-700 transition hover:border-green-400 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-600"> Insights </button></td></tr>`);
          if (openDocsFor.value === opp.id) {
            _push(`<tr class="bg-gray-50"><td colspan="8" class="px-4 py-4"><div class="flex items-start justify-between"><div class="space-y-3 w-full"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-sm font-semibold text-gray-900">Documents</h3><p class="text-xs text-gray-600">Upload and manage documents for this opportunity.</p></div><div class="flex items-center gap-2"><label class="inline-flex min-h-12 cursor-pointer items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-3 text-base font-semibold text-gray-700 transition hover:border-blue-300 hover:text-blue-700 focus-within:ring-2 focus-within:ring-blue-600"><input type="file" class="hidden"${ssrIncludeBooleanAttr(uploading.value.has(opp.id)) ? " disabled" : ""}>`);
            if (!uploading.value.has(opp.id)) {
              _push(`<span>Upload</span>`);
            } else {
              _push(`<span>Uploading...</span>`);
            }
            _push(`</label></div></div>`);
            if (documentsError.value[opp.id]) {
              _push(`<div class="rounded-md bg-red-50 p-3 text-sm text-red-700">${ssrInterpolate(documentsError.value[opp.id])}</div>`);
            } else {
              _push(`<!---->`);
            }
            if (documentsLoading.value.has(opp.id)) {
              _push(`<div class="text-sm text-gray-600">Loading documents...</div>`);
            } else if ((documents.value[opp.id] || []).length === 0) {
              _push(`<div class="rounded-md border border-dashed border-gray-200 p-4 text-sm text-gray-600"> No documents yet for this opportunity. </div>`);
            } else {
              _push(`<div class="rounded-md border border-gray-200 bg-white divide-y divide-gray-100"><!--[-->`);
              ssrRenderList(documents.value[opp.id], (doc) => {
                _push(`<div class="flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-center sm:justify-between"><div><div class="text-sm text-gray-900 font-medium">${ssrInterpolate(doc.original_filename)}</div><div class="text-xs text-gray-600">${ssrInterpolate(doc.mime_type || "Unknown type")} · ${ssrInterpolate(formatSize(doc.size_bytes))} · `);
                if (doc.uploaded_at) {
                  _push(`<span>Uploaded ${ssrInterpolate(new Date(doc.uploaded_at).toLocaleDateString())}</span>`);
                } else {
                  _push(`<!---->`);
                }
                _push(`</div></div><div class="flex items-center gap-2">`);
                if (doc.uploaded_by) {
                  _push(`<span class="text-xs text-gray-500">by ${ssrInterpolate(doc.uploaded_by.name)}</span>`);
                } else {
                  _push(`<!---->`);
                }
                _push(`<button type="button" class="inline-flex min-h-12 items-center justify-center rounded-md px-4 py-2 text-base font-semibold text-red-700 hover:bg-red-50 hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-600 disabled:opacity-50"${ssrIncludeBooleanAttr(uploading.value.has(doc.id)) ? " disabled" : ""}> Delete </button></div></div>`);
              });
              _push(`<!--]--></div>`);
            }
            _push(`</div></div></td></tr>`);
          } else {
            _push(`<!---->`);
          }
          if (insightsOpen.value === opp.id) {
            _push(`<tr class="bg-gray-50"><td colspan="8" class="px-4 py-4"><div class="space-y-3"><div class="flex items-center justify-between"><div><h3 class="text-sm font-semibold text-gray-900">Insights</h3><p class="text-xs text-gray-600">Ask a question about this opportunity or its documents.</p></div></div><div class="flex flex-col gap-2 sm:flex-row sm:items-center"><input${ssrRenderAttr("value", insightsQuestion.value)} type="text" class="min-h-12 w-full rounded-md border border-gray-300 px-3 py-3 text-base focus:border-blue-500 focus:ring-2 focus:ring-blue-500" placeholder="Ask a question..."><button type="button" class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-blue-700 px-4 py-3 text-base font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 sm:w-auto"${ssrIncludeBooleanAttr(insightsLoading.value.has(opp.id)) ? " disabled" : ""}>`);
            if (!insightsLoading.value.has(opp.id)) {
              _push(`<span>Ask</span>`);
            } else {
              _push(`<span>Loading...</span>`);
            }
            _push(`</button></div>`);
            if (insightsError.value[opp.id]) {
              _push(`<div class="rounded-md bg-red-50 p-3 text-sm text-red-700">${ssrInterpolate(insightsError.value[opp.id])}</div>`);
            } else {
              _push(`<!---->`);
            }
            if (insightsLoading.value.has(opp.id)) {
              _push(`<div class="text-sm text-gray-600">Retrieving insights...</div>`);
            } else if (insightsAnswer.value[opp.id]) {
              _push(`<div class="space-y-2"><div class="rounded-md border border-gray-200 bg-white p-3"><h4 class="text-sm font-semibold text-gray-900">Answer</h4><p class="text-sm text-gray-800 whitespace-pre-line">${ssrInterpolate(insightsAnswer.value[opp.id].answer)}</p></div>`);
              if ((insightsAnswer.value[opp.id].top_chunks || []).length) {
                _push(`<div class="rounded-md border border-gray-200 bg-white p-3 space-y-2"><h4 class="text-sm font-semibold text-gray-900">Supporting context</h4><ul class="space-y-1"><!--[-->`);
                ssrRenderList(insightsAnswer.value[opp.id].top_chunks, (ctx) => {
                  _push(`<li class="text-sm text-gray-700"><span class="text-xs text-gray-500">Doc ${ssrInterpolate(ctx.document_id)} · Score ${ssrInterpolate(ctx.score.toFixed(2))}</span><div class="text-sm text-gray-800 truncate">${ssrInterpolate(ctx.text)}</div></li>`);
                });
                _push(`<!--]--></ul></div>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div>`);
            } else {
              _push(`<div class="text-sm text-gray-600"> Ask a question to see insights. </div>`);
            }
            _push(`</div></td></tr>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--]-->`);
        });
        _push(`<!--]--></tbody></table>`);
        if (pagination.value.last_page > 1) {
          _push(`<div class="flex flex-col gap-3 border-t border-gray-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"><div class="grid grid-cols-2 gap-2 sm:flex sm:items-center"><button type="button" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-4 py-3 text-base font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-600 disabled:cursor-not-allowed disabled:opacity-50"${ssrIncludeBooleanAttr(!pagination.value.prev_page_url) ? " disabled" : ""}> Previous </button><button type="button" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-4 py-3 text-base font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-600 disabled:cursor-not-allowed disabled:opacity-50"${ssrIncludeBooleanAttr(!pagination.value.next_page_url) ? " disabled" : ""}> Next </button></div><div class="text-sm text-gray-600"> Page ${ssrInterpolate(pagination.value.current_page)} of ${ssrInterpolate(pagination.value.last_page)}</div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Sam/Favorites.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
