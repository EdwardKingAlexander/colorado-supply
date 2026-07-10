import { ref, computed, mergeProps, unref, withCtx, createVNode, createBlock, openBlock, Fragment, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderComponent, ssrRenderSlot } from "vue/server-renderer";
import { Dialog, DialogPanel } from "@headlessui/vue";
import { Bars3Icon, XMarkIcon } from "@heroicons/vue/24/outline";
import { usePage } from "@inertiajs/vue3";
import { l as logo } from "./logo-cleansed-CSOLeLOy.js";
const _sfc_main$1 = {
  __name: "Navigation",
  __ssrInlineRender: true,
  setup(__props) {
    const mobileMenuOpen = ref(false);
    const page = usePage();
    const isAdminAuthenticated = computed(() => page.props?.auth?.guards?.admin ?? false);
    const isUserAuthenticated = computed(() => Boolean(page.props?.auth?.user));
    const shouldShowStoreLink = computed(() => isUserAuthenticated.value || isAdminAuthenticated.value);
    const adminDashboardHref = computed(() => {
      if (!isAdminAuthenticated.value) {
        return null;
      }
      if (typeof route === "function") {
        return route("filament.admin.pages.dashboard");
      }
      return "/admin";
    });
    const storeHref = computed(() => {
      if (!shouldShowStoreLink.value) {
        return null;
      }
      if (typeof route === "function") {
        return route("store.index");
      }
      return "/store";
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<header${ssrRenderAttrs(mergeProps({ class: "fixed top-0 inset-x-0 z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-sm" }, _attrs))}><nav class="flex items-center justify-between p-2 lg:px-8" aria-label="Global"><div class="flex lg:flex-1"><a href="#home" class="-m-1.5 p-1.5"><span class="sr-only">Colorado Supply &amp; Procurement LLC</span><img class="h-12 w-auto sm:h-14 lg:h-16 dark:hidden"${ssrRenderAttr("src", unref(logo))} alt="" aria-hidden="true" width="193" height="64"><img class="h-12 w-auto sm:h-14 lg:h-16 hidden dark:block"${ssrRenderAttr("src", unref(logo))} alt="" aria-hidden="true" width="193" height="64"></a></div><div class="flex lg:hidden"><button type="button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-500 dark:text-gray-400"><span class="sr-only">Open main menu</span>`);
      _push(ssrRenderComponent(unref(Bars3Icon), {
        class: "size-6",
        "aria-hidden": "true"
      }, null, _parent));
      _push(`</button></div><div class="hidden lg:flex lg:gap-x-12"><a href="#home" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">Home</a><a href="#about" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">About</a><a href="#capabilities" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">Capabilities</a><a${ssrRenderAttr("href", _ctx.route("repair-services.index"))} class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">Repair Services</a><a href="#contact" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">Contact</a></div><div class="hidden lg:flex lg:flex-1 lg:justify-end gap-x-6">`);
      if (isAdminAuthenticated.value) {
        _push(`<!--[-->`);
        if (adminDashboardHref.value) {
          _push(`<a${ssrRenderAttr("href", adminDashboardHref.value)} class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400"> Admin Dashboard </a>`);
        } else {
          _push(`<!---->`);
        }
        if (storeHref.value) {
          _push(`<a${ssrRenderAttr("href", storeHref.value)} class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400"> Store </a>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      } else if (shouldShowStoreLink.value) {
        _push(`<!--[-->`);
        if (storeHref.value) {
          _push(`<a${ssrRenderAttr("href", storeHref.value)} class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400"> Store </a>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      } else {
        _push(`<!--[--><a${ssrRenderAttr("href", _ctx.route("login"))} class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400"> Log in <span aria-hidden="true">→</span></a><a${ssrRenderAttr("href", _ctx.route("register"))} class="text-sm font-semibold text-blue-700 dark:text-blue-400 hover:underline"> Register </a><!--]-->`);
      }
      _push(`</div></nav>`);
      _push(ssrRenderComponent(unref(Dialog), {
        class: "lg:hidden",
        onClose: ($event) => mobileMenuOpen.value = false,
        open: mobileMenuOpen.value
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="fixed inset-0 z-50"${_scopeId}></div>`);
            _push2(ssrRenderComponent(unref(DialogPanel), { class: "fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white p-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10 dark:bg-gray-900 dark:sm:ring-gray-100/10" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="flex items-center justify-between"${_scopeId2}><a href="#home" class="-m-1.5 p-1.5"${_scopeId2}><span class="sr-only"${_scopeId2}>Colorado Supply &amp; Procurement LLC</span><img class="h-10 w-auto dark:hidden"${ssrRenderAttr("src", unref(logo))} alt="" aria-hidden="true" width="120" height="40"${_scopeId2}><img class="h-10 w-auto hidden dark:block"${ssrRenderAttr("src", unref(logo))} alt="" aria-hidden="true" width="120" height="40"${_scopeId2}></a><button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-700 dark:text-gray-400"${_scopeId2}><span class="sr-only"${_scopeId2}>Close menu</span>`);
                  _push3(ssrRenderComponent(unref(XMarkIcon), {
                    class: "size-6",
                    "aria-hidden": "true"
                  }, null, _parent3, _scopeId2));
                  _push3(`</button></div><div class="mt-6 flow-root"${_scopeId2}><div class="-my-6 divide-y divide-gray-500/10 dark:divide-gray-500/25"${_scopeId2}><div class="space-y-2 py-6"${_scopeId2}><a href="#home" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"${_scopeId2}> Home </a><a href="#about" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"${_scopeId2}> About </a><a href="#capabilities" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"${_scopeId2}> Capabilities </a><a${ssrRenderAttr("href", _ctx.route("repair-services.index"))} class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"${_scopeId2}> Repair Services </a><a href="#contact" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"${_scopeId2}> Contact </a></div><div class="py-6 space-y-2"${_scopeId2}>`);
                  if (isAdminAuthenticated.value) {
                    _push3(`<!--[-->`);
                    if (adminDashboardHref.value) {
                      _push3(`<a${ssrRenderAttr("href", adminDashboardHref.value)} class="block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"${_scopeId2}> Admin Dashboard </a>`);
                    } else {
                      _push3(`<!---->`);
                    }
                    if (storeHref.value) {
                      _push3(`<a${ssrRenderAttr("href", storeHref.value)} class="block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"${_scopeId2}> Store </a>`);
                    } else {
                      _push3(`<!---->`);
                    }
                    _push3(`<!--]-->`);
                  } else if (shouldShowStoreLink.value) {
                    _push3(`<!--[-->`);
                    if (storeHref.value) {
                      _push3(`<a${ssrRenderAttr("href", storeHref.value)} class="block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"${_scopeId2}> Store </a>`);
                    } else {
                      _push3(`<!---->`);
                    }
                    _push3(`<!--]-->`);
                  } else {
                    _push3(`<!--[--><a${ssrRenderAttr("href", _ctx.route("login"))} class="block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"${_scopeId2}> Log in </a><a${ssrRenderAttr("href", _ctx.route("register"))} class="block rounded-lg px-3 py-2.5 text-base font-semibold text-blue-700 dark:text-blue-400 hover:bg-gray-50 dark:hover:bg-white/5"${_scopeId2}> Register </a><!--]-->`);
                  }
                  _push3(`</div></div></div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex items-center justify-between" }, [
                      createVNode("a", {
                        href: "#home",
                        class: "-m-1.5 p-1.5",
                        onClick: ($event) => mobileMenuOpen.value = false
                      }, [
                        createVNode("span", { class: "sr-only" }, "Colorado Supply & Procurement LLC"),
                        createVNode("img", {
                          class: "h-10 w-auto dark:hidden",
                          src: unref(logo),
                          alt: "",
                          "aria-hidden": "true",
                          width: "120",
                          height: "40"
                        }, null, 8, ["src"]),
                        createVNode("img", {
                          class: "h-10 w-auto hidden dark:block",
                          src: unref(logo),
                          alt: "",
                          "aria-hidden": "true",
                          width: "120",
                          height: "40"
                        }, null, 8, ["src"])
                      ], 8, ["onClick"]),
                      createVNode("button", {
                        type: "button",
                        class: "-m-2.5 rounded-md p-2.5 text-gray-700 dark:text-gray-400",
                        onClick: ($event) => mobileMenuOpen.value = false
                      }, [
                        createVNode("span", { class: "sr-only" }, "Close menu"),
                        createVNode(unref(XMarkIcon), {
                          class: "size-6",
                          "aria-hidden": "true"
                        })
                      ], 8, ["onClick"])
                    ]),
                    createVNode("div", { class: "mt-6 flow-root" }, [
                      createVNode("div", { class: "-my-6 divide-y divide-gray-500/10 dark:divide-gray-500/25" }, [
                        createVNode("div", { class: "space-y-2 py-6" }, [
                          createVNode("a", {
                            href: "#home",
                            onClick: ($event) => mobileMenuOpen.value = false,
                            class: "block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                          }, " Home ", 8, ["onClick"]),
                          createVNode("a", {
                            href: "#about",
                            onClick: ($event) => mobileMenuOpen.value = false,
                            class: "block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                          }, " About ", 8, ["onClick"]),
                          createVNode("a", {
                            href: "#capabilities",
                            onClick: ($event) => mobileMenuOpen.value = false,
                            class: "block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                          }, " Capabilities ", 8, ["onClick"]),
                          createVNode("a", {
                            href: _ctx.route("repair-services.index"),
                            onClick: ($event) => mobileMenuOpen.value = false,
                            class: "block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                          }, " Repair Services ", 8, ["href", "onClick"]),
                          createVNode("a", {
                            href: "#contact",
                            onClick: ($event) => mobileMenuOpen.value = false,
                            class: "block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                          }, " Contact ", 8, ["onClick"])
                        ]),
                        createVNode("div", { class: "py-6 space-y-2" }, [
                          isAdminAuthenticated.value ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                            adminDashboardHref.value ? (openBlock(), createBlock("a", {
                              key: 0,
                              href: adminDashboardHref.value,
                              onClick: ($event) => mobileMenuOpen.value = false,
                              class: "block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                            }, " Admin Dashboard ", 8, ["href", "onClick"])) : createCommentVNode("", true),
                            storeHref.value ? (openBlock(), createBlock("a", {
                              key: 1,
                              href: storeHref.value,
                              onClick: ($event) => mobileMenuOpen.value = false,
                              class: "block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                            }, " Store ", 8, ["href", "onClick"])) : createCommentVNode("", true)
                          ], 64)) : shouldShowStoreLink.value ? (openBlock(), createBlock(Fragment, { key: 1 }, [
                            storeHref.value ? (openBlock(), createBlock("a", {
                              key: 0,
                              href: storeHref.value,
                              onClick: ($event) => mobileMenuOpen.value = false,
                              class: "block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                            }, " Store ", 8, ["href", "onClick"])) : createCommentVNode("", true)
                          ], 64)) : (openBlock(), createBlock(Fragment, { key: 2 }, [
                            createVNode("a", {
                              href: _ctx.route("login"),
                              onClick: ($event) => mobileMenuOpen.value = false,
                              class: "block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                            }, " Log in ", 8, ["href", "onClick"]),
                            createVNode("a", {
                              href: _ctx.route("register"),
                              onClick: ($event) => mobileMenuOpen.value = false,
                              class: "block rounded-lg px-3 py-2.5 text-base font-semibold text-blue-700 dark:text-blue-400 hover:bg-gray-50 dark:hover:bg-white/5"
                            }, " Register ", 8, ["href", "onClick"])
                          ], 64))
                        ])
                      ])
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode("div", { class: "fixed inset-0 z-50" }),
              createVNode(unref(DialogPanel), { class: "fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white p-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10 dark:bg-gray-900 dark:sm:ring-gray-100/10" }, {
                default: withCtx(() => [
                  createVNode("div", { class: "flex items-center justify-between" }, [
                    createVNode("a", {
                      href: "#home",
                      class: "-m-1.5 p-1.5",
                      onClick: ($event) => mobileMenuOpen.value = false
                    }, [
                      createVNode("span", { class: "sr-only" }, "Colorado Supply & Procurement LLC"),
                      createVNode("img", {
                        class: "h-10 w-auto dark:hidden",
                        src: unref(logo),
                        alt: "",
                        "aria-hidden": "true",
                        width: "120",
                        height: "40"
                      }, null, 8, ["src"]),
                      createVNode("img", {
                        class: "h-10 w-auto hidden dark:block",
                        src: unref(logo),
                        alt: "",
                        "aria-hidden": "true",
                        width: "120",
                        height: "40"
                      }, null, 8, ["src"])
                    ], 8, ["onClick"]),
                    createVNode("button", {
                      type: "button",
                      class: "-m-2.5 rounded-md p-2.5 text-gray-700 dark:text-gray-400",
                      onClick: ($event) => mobileMenuOpen.value = false
                    }, [
                      createVNode("span", { class: "sr-only" }, "Close menu"),
                      createVNode(unref(XMarkIcon), {
                        class: "size-6",
                        "aria-hidden": "true"
                      })
                    ], 8, ["onClick"])
                  ]),
                  createVNode("div", { class: "mt-6 flow-root" }, [
                    createVNode("div", { class: "-my-6 divide-y divide-gray-500/10 dark:divide-gray-500/25" }, [
                      createVNode("div", { class: "space-y-2 py-6" }, [
                        createVNode("a", {
                          href: "#home",
                          onClick: ($event) => mobileMenuOpen.value = false,
                          class: "block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                        }, " Home ", 8, ["onClick"]),
                        createVNode("a", {
                          href: "#about",
                          onClick: ($event) => mobileMenuOpen.value = false,
                          class: "block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                        }, " About ", 8, ["onClick"]),
                        createVNode("a", {
                          href: "#capabilities",
                          onClick: ($event) => mobileMenuOpen.value = false,
                          class: "block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                        }, " Capabilities ", 8, ["onClick"]),
                        createVNode("a", {
                          href: _ctx.route("repair-services.index"),
                          onClick: ($event) => mobileMenuOpen.value = false,
                          class: "block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                        }, " Repair Services ", 8, ["href", "onClick"]),
                        createVNode("a", {
                          href: "#contact",
                          onClick: ($event) => mobileMenuOpen.value = false,
                          class: "block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                        }, " Contact ", 8, ["onClick"])
                      ]),
                      createVNode("div", { class: "py-6 space-y-2" }, [
                        isAdminAuthenticated.value ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                          adminDashboardHref.value ? (openBlock(), createBlock("a", {
                            key: 0,
                            href: adminDashboardHref.value,
                            onClick: ($event) => mobileMenuOpen.value = false,
                            class: "block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                          }, " Admin Dashboard ", 8, ["href", "onClick"])) : createCommentVNode("", true),
                          storeHref.value ? (openBlock(), createBlock("a", {
                            key: 1,
                            href: storeHref.value,
                            onClick: ($event) => mobileMenuOpen.value = false,
                            class: "block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                          }, " Store ", 8, ["href", "onClick"])) : createCommentVNode("", true)
                        ], 64)) : shouldShowStoreLink.value ? (openBlock(), createBlock(Fragment, { key: 1 }, [
                          storeHref.value ? (openBlock(), createBlock("a", {
                            key: 0,
                            href: storeHref.value,
                            onClick: ($event) => mobileMenuOpen.value = false,
                            class: "block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                          }, " Store ", 8, ["href", "onClick"])) : createCommentVNode("", true)
                        ], 64)) : (openBlock(), createBlock(Fragment, { key: 2 }, [
                          createVNode("a", {
                            href: _ctx.route("login"),
                            onClick: ($event) => mobileMenuOpen.value = false,
                            class: "block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                          }, " Log in ", 8, ["href", "onClick"]),
                          createVNode("a", {
                            href: _ctx.route("register"),
                            onClick: ($event) => mobileMenuOpen.value = false,
                            class: "block rounded-lg px-3 py-2.5 text-base font-semibold text-blue-700 dark:text-blue-400 hover:bg-gray-50 dark:hover:bg-white/5"
                          }, " Register ", 8, ["href", "onClick"])
                        ], 64))
                      ])
                    ])
                  ])
                ]),
                _: 1
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</header>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Navigation.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "AppLayout",
  __ssrInlineRender: true,
  props: {
    appName: String
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen w-full m-0 p-0 bg-white dark:bg-gray-700" }, _attrs))}>`);
      _push(ssrRenderComponent(_sfc_main$1, {
        appName: _ctx.$page.props.appName
      }, null, _parent));
      _push(`<main class="w-full m-0 p-0">`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</main></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Layouts/AppLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as _
};
