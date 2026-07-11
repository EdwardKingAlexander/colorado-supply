import { ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain } from "vue/server-renderer";
import { reactive, watch, unref, mergeProps, withCtx, createTextVNode, createVNode, createBlock, openBlock, Fragment, renderList, withDirectives, toDisplayString, vModelCheckbox, useSSRContext, computed, ref, onMounted } from "vue";
import { Dialog, DialogPanel, DialogTitle } from "@headlessui/vue";
import axios from "axios";
import { usePage } from "@inertiajs/vue3";
const _sfc_main$1 = {
  __name: "PrivacyPreferencesModal",
  __ssrInlineRender: true,
  props: {
    open: {
      type: Boolean,
      required: true
    },
    categories: {
      type: Object,
      required: true
    },
    selectedCategories: {
      type: Array,
      default: () => ["essential"]
    },
    saving: {
      type: Boolean,
      default: false
    }
  },
  emits: ["close", "save"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const selections = reactive({});
    const resetSelections = () => {
      for (const [key, category] of Object.entries(props.categories)) {
        selections[key] = category.locked || props.selectedCategories.includes(key);
      }
    };
    watch(() => props.open, (open) => {
      if (open) {
        resetSelections();
      }
    }, { immediate: true });
    const save = () => {
      emit("save", Object.keys(selections).filter((key) => selections[key]));
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(unref(Dialog), mergeProps({
        open: __props.open,
        class: "relative z-[110]",
        onClose: ($event) => __props.saving ? null : emit("close")
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="fixed inset-0 bg-gray-950/60" aria-hidden="true"${_scopeId}></div><div class="safe-y fixed inset-0 overflow-y-auto px-4 py-6 sm:px-6"${_scopeId}><div class="flex min-h-full items-center justify-center"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(DialogPanel), { class: "w-full max-w-xl rounded-xl bg-white p-5 text-gray-900 shadow-2xl sm:p-7" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(unref(DialogTitle), { class: "text-2xl font-bold" }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`Privacy preferences`);
                      } else {
                        return [
                          createTextVNode("Privacy preferences")
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                  _push3(`<p class="mt-2 text-base leading-6 text-gray-600"${_scopeId2}> Choose which optional technologies may run. Strictly necessary services are always active. </p><div class="mt-6 divide-y divide-gray-200 rounded-lg border border-gray-200"${_scopeId2}><!--[-->`);
                  ssrRenderList(__props.categories, (category, key) => {
                    _push3(`<label class="${ssrRenderClass([category.locked ? "cursor-not-allowed bg-gray-50" : "cursor-pointer", "flex min-h-20 gap-4 p-4"])}"${_scopeId2}><span class="min-w-0 flex-1"${_scopeId2}><span class="block text-base font-semibold text-gray-900"${_scopeId2}>${ssrInterpolate(category.label)}</span><span class="mt-1 block text-sm leading-5 text-gray-600"${_scopeId2}>${ssrInterpolate(category.description)}</span></span><input${ssrIncludeBooleanAttr(Array.isArray(selections[key]) ? ssrLooseContain(selections[key], null) : selections[key]) ? " checked" : ""} type="checkbox"${ssrIncludeBooleanAttr(category.locked) ? " disabled" : ""} class="mt-1 h-6 w-6 rounded border-gray-300 text-indigo-700 focus:ring-indigo-600 disabled:opacity-60"${_scopeId2}></label>`);
                  });
                  _push3(`<!--]--></div><div class="mt-6 grid gap-3 sm:grid-cols-2"${_scopeId2}><button type="button" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 bg-white px-4 text-base font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"${ssrIncludeBooleanAttr(__props.saving) ? " disabled" : ""}${_scopeId2}> Cancel </button><button type="button" class="inline-flex min-h-12 items-center justify-center rounded-md border border-indigo-800 bg-indigo-800 px-4 text-base font-semibold text-white hover:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:opacity-60"${ssrIncludeBooleanAttr(__props.saving) ? " disabled" : ""}${_scopeId2}>${ssrInterpolate(__props.saving ? "Saving…" : "Save preferences")}</button></div>`);
                } else {
                  return [
                    createVNode(unref(DialogTitle), { class: "text-2xl font-bold" }, {
                      default: withCtx(() => [
                        createTextVNode("Privacy preferences")
                      ]),
                      _: 1
                    }),
                    createVNode("p", { class: "mt-2 text-base leading-6 text-gray-600" }, " Choose which optional technologies may run. Strictly necessary services are always active. "),
                    createVNode("div", { class: "mt-6 divide-y divide-gray-200 rounded-lg border border-gray-200" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(__props.categories, (category, key) => {
                        return openBlock(), createBlock("label", {
                          key,
                          class: ["flex min-h-20 gap-4 p-4", category.locked ? "cursor-not-allowed bg-gray-50" : "cursor-pointer"]
                        }, [
                          createVNode("span", { class: "min-w-0 flex-1" }, [
                            createVNode("span", { class: "block text-base font-semibold text-gray-900" }, toDisplayString(category.label), 1),
                            createVNode("span", { class: "mt-1 block text-sm leading-5 text-gray-600" }, toDisplayString(category.description), 1)
                          ]),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => selections[key] = $event,
                            type: "checkbox",
                            disabled: category.locked,
                            class: "mt-1 h-6 w-6 rounded border-gray-300 text-indigo-700 focus:ring-indigo-600 disabled:opacity-60"
                          }, null, 8, ["onUpdate:modelValue", "disabled"]), [
                            [vModelCheckbox, selections[key]]
                          ])
                        ], 2);
                      }), 128))
                    ]),
                    createVNode("div", { class: "mt-6 grid gap-3 sm:grid-cols-2" }, [
                      createVNode("button", {
                        type: "button",
                        class: "inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 bg-white px-4 text-base font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2",
                        disabled: __props.saving,
                        onClick: ($event) => emit("close")
                      }, " Cancel ", 8, ["disabled", "onClick"]),
                      createVNode("button", {
                        type: "button",
                        class: "inline-flex min-h-12 items-center justify-center rounded-md border border-indigo-800 bg-indigo-800 px-4 text-base font-semibold text-white hover:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:opacity-60",
                        disabled: __props.saving,
                        onClick: save
                      }, toDisplayString(__props.saving ? "Saving…" : "Save preferences"), 9, ["disabled"])
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", {
                class: "fixed inset-0 bg-gray-950/60",
                "aria-hidden": "true"
              }),
              createVNode("div", { class: "safe-y fixed inset-0 overflow-y-auto px-4 py-6 sm:px-6" }, [
                createVNode("div", { class: "flex min-h-full items-center justify-center" }, [
                  createVNode(unref(DialogPanel), { class: "w-full max-w-xl rounded-xl bg-white p-5 text-gray-900 shadow-2xl sm:p-7" }, {
                    default: withCtx(() => [
                      createVNode(unref(DialogTitle), { class: "text-2xl font-bold" }, {
                        default: withCtx(() => [
                          createTextVNode("Privacy preferences")
                        ]),
                        _: 1
                      }),
                      createVNode("p", { class: "mt-2 text-base leading-6 text-gray-600" }, " Choose which optional technologies may run. Strictly necessary services are always active. "),
                      createVNode("div", { class: "mt-6 divide-y divide-gray-200 rounded-lg border border-gray-200" }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(__props.categories, (category, key) => {
                          return openBlock(), createBlock("label", {
                            key,
                            class: ["flex min-h-20 gap-4 p-4", category.locked ? "cursor-not-allowed bg-gray-50" : "cursor-pointer"]
                          }, [
                            createVNode("span", { class: "min-w-0 flex-1" }, [
                              createVNode("span", { class: "block text-base font-semibold text-gray-900" }, toDisplayString(category.label), 1),
                              createVNode("span", { class: "mt-1 block text-sm leading-5 text-gray-600" }, toDisplayString(category.description), 1)
                            ]),
                            withDirectives(createVNode("input", {
                              "onUpdate:modelValue": ($event) => selections[key] = $event,
                              type: "checkbox",
                              disabled: category.locked,
                              class: "mt-1 h-6 w-6 rounded border-gray-300 text-indigo-700 focus:ring-indigo-600 disabled:opacity-60"
                            }, null, 8, ["onUpdate:modelValue", "disabled"]), [
                              [vModelCheckbox, selections[key]]
                            ])
                          ], 2);
                        }), 128))
                      ]),
                      createVNode("div", { class: "mt-6 grid gap-3 sm:grid-cols-2" }, [
                        createVNode("button", {
                          type: "button",
                          class: "inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 bg-white px-4 text-base font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2",
                          disabled: __props.saving,
                          onClick: ($event) => emit("close")
                        }, " Cancel ", 8, ["disabled", "onClick"]),
                        createVNode("button", {
                          type: "button",
                          class: "inline-flex min-h-12 items-center justify-center rounded-md border border-indigo-800 bg-indigo-800 px-4 text-base font-semibold text-white hover:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:opacity-60",
                          disabled: __props.saving,
                          onClick: save
                        }, toDisplayString(__props.saving ? "Saving…" : "Save preferences"), 9, ["disabled"])
                      ])
                    ]),
                    _: 1
                  })
                ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Privacy/PrivacyPreferencesModal.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const deniedConsent = {
  analytics_storage: "denied",
  ad_storage: "denied",
  ad_user_data: "denied",
  ad_personalization: "denied"
};
const applyConsent = (categories, gpc = false) => {
  if (typeof window === "undefined") {
    return;
  }
  const analyticsGranted = !gpc && categories.includes("analytics");
  window.gtag?.("consent", "update", {
    ...deniedConsent,
    analytics_storage: analyticsGranted ? "granted" : "denied"
  });
  if (!analyticsGranted) {
    expireGoogleAnalyticsCookies();
  }
};
const saveConsent = async (categories, gpc = false) => {
  const { data } = await axios.post(route("privacy.consent.store"), {
    categories,
    gpc
  });
  applyConsent(data.consent.categories, data.gpc_applied);
  return data;
};
const expireGoogleAnalyticsCookies = () => {
  if (typeof document === "undefined") {
    return;
  }
  const names = document.cookie.split(";").map((entry) => entry.trim().split("=")[0]).filter((name) => name === "_ga" || name.startsWith("_ga_"));
  const hostname = window.location.hostname;
  const domains = ["", hostname, `.${hostname}`];
  for (const name of names) {
    for (const domain of domains) {
      const domainPart = domain ? `; domain=${domain}` : "";
      document.cookie = `${name}=; Max-Age=0; path=/${domainPart}; SameSite=Lax`;
    }
  }
};
const _sfc_main = {
  __name: "CookieConsentBanner",
  __ssrInlineRender: true,
  setup(__props) {
    const page = usePage();
    const privacy = computed(() => page.props.privacy ?? {});
    const consent = ref(privacy.value.consent ?? null);
    const browserGpc = ref(false);
    const gpcDismissed = ref(false);
    const preferencesOpen = ref(false);
    const saving = ref(false);
    const errorMessage = ref("");
    const effectiveGpc = computed(() => Boolean(privacy.value.gpc || browserGpc.value));
    const currentConsent = computed(() => consent.value?.version === privacy.value.policyVersion ? consent.value : null);
    const categories = computed(() => privacy.value.categories ?? {});
    computed(() => Object.keys(categories.value));
    const selectedCategories = computed(() => currentConsent.value?.categories ?? ["essential"]);
    const showBanner = computed(() => !effectiveGpc.value && !currentConsent.value);
    const showGpcNotice = computed(() => effectiveGpc.value && !gpcDismissed.value);
    const persist = async (selected, gpc = false) => {
      saving.value = true;
      errorMessage.value = "";
      try {
        const data = await saveConsent(selected, gpc);
        consent.value = data.consent;
        preferencesOpen.value = false;
      } catch {
        errorMessage.value = "We could not save your privacy choice. Please try again.";
      } finally {
        saving.value = false;
      }
    };
    onMounted(async () => {
      browserGpc.value = navigator.globalPrivacyControl === true;
      gpcDismissed.value = sessionStorage.getItem("cs-gpc-notice-dismissed") === "1";
      if (!effectiveGpc.value) {
        return;
      }
      applyConsent(["essential"], true);
      const needsGpcReceipt = !currentConsent.value || currentConsent.value.categories.some((category) => category !== "essential");
      if (needsGpcReceipt) {
        await persist(["essential"], true);
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(_sfc_main$1, {
        open: preferencesOpen.value,
        categories: categories.value,
        "selected-categories": selectedCategories.value,
        saving: saving.value,
        onClose: ($event) => preferencesOpen.value = false,
        onSave: ($event) => persist($event)
      }, null, _parent));
      if (showBanner.value) {
        _push(`<aside class="safe-bottom fixed inset-x-0 bottom-0 z-[100] border-t border-gray-300 bg-white p-4 text-gray-900 shadow-[0_-8px_30px_rgba(15,23,42,0.18)] sm:p-5" aria-label="Cookie consent"><div class="mx-auto max-w-6xl lg:flex lg:items-center lg:gap-8"><div class="min-w-0 flex-1"><h2 class="text-xl font-bold">Your privacy choices</h2><p class="mt-2 text-base leading-6 text-gray-600"> We use strictly necessary technologies to operate this site. With your permission, analytics helps us improve it. Marketing cookies are not currently used. </p>`);
        if (errorMessage.value) {
          _push(`<p class="mt-2 text-sm font-semibold text-red-700" role="alert">${ssrInterpolate(errorMessage.value)}</p>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="mt-4 grid gap-3 sm:grid-cols-3 lg:mt-0 lg:w-[34rem]"><button type="button" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-400 bg-white px-4 text-base font-semibold text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:opacity-60"${ssrIncludeBooleanAttr(saving.value) ? " disabled" : ""}> Accept all </button><button type="button" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-400 bg-white px-4 text-base font-semibold text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:opacity-60"${ssrIncludeBooleanAttr(saving.value) ? " disabled" : ""}> Essential only </button><button type="button" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-400 bg-white px-4 text-base font-semibold text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:opacity-60"${ssrIncludeBooleanAttr(saving.value) ? " disabled" : ""}> Preferences </button></div></div></aside>`);
      } else if (showGpcNotice.value) {
        _push(`<aside class="safe-bottom fixed inset-x-0 bottom-0 z-[100] border-t border-emerald-300 bg-emerald-50 p-4 text-emerald-950 shadow-[0_-8px_30px_rgba(15,23,42,0.16)] sm:p-5" aria-label="Global Privacy Control honored"><div class="mx-auto flex max-w-6xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-bold">Global Privacy Control honored</h2><p class="mt-1 text-base leading-6">Optional analytics and advertising storage are disabled for this browser.</p>`);
        if (errorMessage.value) {
          _push(`<p class="mt-2 text-sm font-semibold text-red-700" role="alert">${ssrInterpolate(errorMessage.value)}</p>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><button type="button" class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-md border border-emerald-800 bg-white px-5 text-base font-semibold text-emerald-950 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2"> Dismiss </button></div></aside>`);
      } else if (currentConsent.value && !effectiveGpc.value) {
        _push(`<button type="button" class="safe-bottom fixed bottom-3 left-3 z-[90] inline-flex min-h-11 items-center justify-center rounded-full border border-gray-400 bg-white px-4 text-sm font-semibold text-gray-800 shadow-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 sm:bottom-4 sm:left-4"> Privacy choices </button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Privacy/CookieConsentBanner.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as _
};
