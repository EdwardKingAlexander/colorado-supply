import { onMounted, onUnmounted, computed, ref, mergeProps, useSSRContext, unref, withCtx, renderSlot, onBeforeUnmount, createVNode, createTextVNode, createBlock, createCommentVNode, openBlock, toDisplayString } from "vue";
import { ssrRenderAttrs, ssrRenderSlot, ssrRenderStyle, ssrRenderClass, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderList } from "vue/server-renderer";
import { A as ApplicationLogo } from "./ApplicationLogo-B2173abF.js";
import { Link, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { _ as _sfc_main$6 } from "./CookieConsentBanner-ByAlkSbo.js";
import { Dialog, DialogPanel } from "@headlessui/vue";
const _sfc_main$5 = {
  __name: "Dropdown",
  __ssrInlineRender: true,
  props: {
    align: {
      type: String,
      default: "right"
    },
    width: {
      type: String,
      default: "48"
    },
    contentClasses: {
      type: String,
      default: "py-1 bg-white"
    }
  },
  setup(__props) {
    const props = __props;
    const closeOnEscape = (e) => {
      if (open.value && e.key === "Escape") {
        open.value = false;
      }
    };
    onMounted(() => document.addEventListener("keydown", closeOnEscape));
    onUnmounted(() => document.removeEventListener("keydown", closeOnEscape));
    const widthClass = computed(() => {
      return {
        48: "w-48"
      }[props.width.toString()];
    });
    const alignmentClasses = computed(() => {
      if (props.align === "left") {
        return "ltr:origin-top-left rtl:origin-top-right start-0";
      } else if (props.align === "right") {
        return "ltr:origin-top-right rtl:origin-top-left end-0";
      } else {
        return "origin-top";
      }
    });
    const open = ref(false);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "relative" }, _attrs))}><div>`);
      ssrRenderSlot(_ctx.$slots, "trigger", {}, null, _push, _parent);
      _push(`</div><div style="${ssrRenderStyle(open.value ? null : { display: "none" })}" class="fixed inset-0 z-40"></div><div style="${ssrRenderStyle([
        open.value ? null : { display: "none" },
        { "display": "none" }
      ])}" class="${ssrRenderClass([[widthClass.value, alignmentClasses.value], "absolute z-50 mt-2 rounded-md shadow-lg"])}"><div class="${ssrRenderClass([__props.contentClasses, "rounded-md ring-1 ring-black ring-opacity-5"])}">`);
      ssrRenderSlot(_ctx.$slots, "content", {}, null, _push, _parent);
      _push(`</div></div></div>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/Dropdown.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const _sfc_main$4 = {
  __name: "DropdownLink",
  __ssrInlineRender: true,
  props: {
    href: {
      type: String,
      required: true
    },
    external: {
      type: Boolean,
      default: false
    }
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      if (__props.external) {
        _push(`<a${ssrRenderAttrs(mergeProps({
          href: __props.href,
          class: "flex min-h-12 w-full items-center px-4 py-3 text-start text-base leading-6 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
        }, _attrs))}>`);
        ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
        _push(`</a>`);
      } else {
        _push(ssrRenderComponent(unref(Link), mergeProps({
          href: __props.href,
          class: "flex min-h-12 w-full items-center px-4 py-3 text-start text-base leading-6 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
        }, _attrs), {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
            } else {
              return [
                renderSlot(_ctx.$slots, "default")
              ];
            }
          }),
          _: 3
        }, _parent));
      }
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/DropdownLink.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const _sfc_main$3 = {
  __name: "NavLink",
  __ssrInlineRender: true,
  props: {
    href: {
      type: String,
      required: true
    },
    active: {
      type: Boolean
    }
  },
  setup(__props) {
    const props = __props;
    const classes = computed(
      () => props.active ? "inline-flex min-h-12 items-center border-b-2 border-indigo-500 px-2 text-base font-medium leading-6 text-gray-900 transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" : "inline-flex min-h-12 items-center border-b-2 border-transparent px-2 text-base font-medium leading-6 text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
    );
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(unref(Link), mergeProps({
        href: __props.href,
        class: classes.value
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
          } else {
            return [
              renderSlot(_ctx.$slots, "default")
            ];
          }
        }),
        _: 3
      }, _parent));
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/NavLink.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = {
  __name: "NotificationBell",
  __ssrInlineRender: true,
  setup(__props) {
    const root = ref(null);
    const open = ref(false);
    const loading = ref(false);
    const notifications = ref([]);
    const unreadCount = ref(0);
    const loadError = ref(false);
    const loadNotifications = async () => {
      loading.value = true;
      loadError.value = false;
      try {
        const { data } = await axios.get(route("notifications.index"));
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
      } catch {
        loadError.value = true;
      } finally {
        loading.value = false;
      }
    };
    const closeOutside = (event) => {
      if (open.value && root.value && !root.value.contains(event.target)) {
        open.value = false;
      }
    };
    onMounted(() => {
      document.addEventListener("click", closeOutside);
      loadNotifications();
    });
    onBeforeUnmount(() => document.removeEventListener("click", closeOutside));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        ref_key: "root",
        ref: root,
        class: "relative"
      }, _attrs))}><button type="button" class="relative inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"${ssrRenderAttr("aria-expanded", open.value)} aria-haspopup="true" aria-label="Order notifications"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.85 23.85 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"></path></svg>`);
      if (unreadCount.value) {
        _push(`<span class="absolute right-1 top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white">${ssrInterpolate(unreadCount.value > 99 ? "99+" : unreadCount.value)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</button>`);
      if (open.value) {
        _push(`<div class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-lg bg-white shadow-xl ring-1 ring-black/10"><div class="flex items-center justify-between border-b border-gray-200 px-4 py-3"><h2 class="text-base font-semibold text-gray-900">Order notifications</h2>`);
        if (unreadCount.value) {
          _push(`<button type="button" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 focus:outline-none focus:underline"> Mark all read </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (loading.value && !notifications.value.length) {
          _push(`<div class="px-4 py-6 text-center text-sm text-gray-500"> Loading updates… </div>`);
        } else if (loadError.value) {
          _push(`<div class="px-4 py-6 text-center text-sm text-red-700"> Notifications could not be loaded. </div>`);
        } else if (!notifications.value.length) {
          _push(`<div class="px-4 py-6 text-center text-sm text-gray-500"> No order updates yet. </div>`);
        } else {
          _push(`<ul class="max-h-96 divide-y divide-gray-100 overflow-y-auto"><!--[-->`);
          ssrRenderList(notifications.value, (notification) => {
            _push(`<li><a${ssrRenderAttr("href", notification.tracker_url || "#")} class="${ssrRenderClass([{ "bg-indigo-50/60": !notification.read_at }, "block px-4 py-3 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none"])}"><p class="text-sm font-medium text-gray-900">${ssrInterpolate(notification.label)}</p>`);
            if (notification.order_number) {
              _push(`<p class="mt-0.5 text-sm text-gray-600">${ssrInterpolate(notification.order_number)}</p>`);
            } else {
              _push(`<!---->`);
            }
            _push(`<p class="mt-1 text-xs text-gray-500">${ssrInterpolate(notification.created_human)}</p></a></li>`);
          });
          _push(`<!--]--></ul>`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/NotificationBell.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "ResponsiveNavLink",
  __ssrInlineRender: true,
  props: {
    href: {
      type: String,
      required: true
    },
    active: {
      type: Boolean
    },
    external: {
      type: Boolean,
      default: false
    }
  },
  setup(__props) {
    const props = __props;
    const classes = computed(
      () => props.active ? "flex min-h-[52px] w-full items-center border-l-4 border-indigo-500 bg-indigo-50 py-3 pe-4 ps-4 text-start text-base font-medium leading-6 text-indigo-700 transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" : "flex min-h-[52px] w-full items-center border-l-4 border-transparent py-3 pe-4 ps-4 text-start text-base font-medium leading-6 text-gray-700 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
    );
    return (_ctx, _push, _parent, _attrs) => {
      if (__props.external) {
        _push(`<a${ssrRenderAttrs(mergeProps({
          href: __props.href,
          class: classes.value
        }, _attrs))}>`);
        ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
        _push(`</a>`);
      } else {
        _push(ssrRenderComponent(unref(Link), mergeProps({
          href: __props.href,
          class: classes.value
        }, _attrs), {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
            } else {
              return [
                renderSlot(_ctx.$slots, "default")
              ];
            }
          }),
          _: 3
        }, _parent));
      }
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/ResponsiveNavLink.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "AuthenticatedLayout",
  __ssrInlineRender: true,
  setup(__props) {
    const showingNavigationDropdown = ref(false);
    const page = usePage();
    const accountName = computed(() => {
      return page.props.auth?.user?.name ?? page.props.auth?.admin?.name ?? "Account";
    });
    const accountEmail = computed(() => {
      return page.props.auth?.user?.email ?? page.props.auth?.admin?.email ?? null;
    });
    const showProfileLink = computed(() => Boolean(page.props.auth?.user));
    const showAdminPanelLink = computed(() => Boolean(page.props.auth?.admin));
    const canLogout = computed(() => showProfileLink.value || showAdminPanelLink.value);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}><div class="min-h-screen bg-gray-100"><nav class="safe-top relative z-50 border-b border-gray-200 bg-white"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><div class="flex h-16 justify-between"><div class="flex"><div class="flex shrink-0 items-center">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("dashboard"),
        class: "flex min-h-12 items-center rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(ApplicationLogo, { class: "block h-9 w-auto fill-current text-gray-800" }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(ApplicationLogo, { class: "block h-9 w-auto fill-current text-gray-800" })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="hidden space-x-4 md:-my-px md:ms-8 md:flex lg:space-x-8">`);
      _push(ssrRenderComponent(_sfc_main$3, {
        href: _ctx.route("dashboard"),
        active: _ctx.route().current("dashboard")
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
      _push(ssrRenderComponent(_sfc_main$3, {
        href: _ctx.route("store.index"),
        active: _ctx.route().current("store.index")
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Store `);
          } else {
            return [
              createTextVNode(" Store ")
            ];
          }
        }),
        _: 1
      }, _parent));
      if (showProfileLink.value) {
        _push(ssrRenderComponent(_sfc_main$3, {
          href: _ctx.route("dashboard.reports"),
          active: _ctx.route().current("dashboard.reports")
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Reports `);
            } else {
              return [
                createTextVNode(" Reports ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
      if (showProfileLink.value) {
        _push(`<div class="ml-auto flex items-center">`);
        _push(ssrRenderComponent(_sfc_main$2, null, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="hidden md:ms-6 md:flex md:items-center"><div class="relative ms-3">`);
      _push(ssrRenderComponent(_sfc_main$5, {
        align: "right",
        width: "48"
      }, {
        trigger: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<span class="inline-flex rounded-md"${_scopeId}><button type="button" class="inline-flex min-h-12 items-center rounded-md border border-transparent bg-white px-3 py-2 text-base font-medium leading-6 text-gray-600 transition duration-150 ease-in-out hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"${_scopeId}>${ssrInterpolate(accountName.value)} <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"${_scopeId}><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"${_scopeId}></path></svg></button></span>`);
          } else {
            return [
              createVNode("span", { class: "inline-flex rounded-md" }, [
                createVNode("button", {
                  type: "button",
                  class: "inline-flex min-h-12 items-center rounded-md border border-transparent bg-white px-3 py-2 text-base font-medium leading-6 text-gray-600 transition duration-150 ease-in-out hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                }, [
                  createTextVNode(toDisplayString(accountName.value) + " ", 1),
                  (openBlock(), createBlock("svg", {
                    class: "-me-0.5 ms-2 h-4 w-4",
                    xmlns: "http://www.w3.org/2000/svg",
                    viewBox: "0 0 20 20",
                    fill: "currentColor"
                  }, [
                    createVNode("path", {
                      "fill-rule": "evenodd",
                      d: "M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z",
                      "clip-rule": "evenodd"
                    })
                  ]))
                ])
              ])
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (showProfileLink.value) {
              _push2(ssrRenderComponent(_sfc_main$4, {
                href: _ctx.route("profile.edit")
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` Profile `);
                  } else {
                    return [
                      createTextVNode(" Profile ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            if (showAdminPanelLink.value) {
              _push2(ssrRenderComponent(_sfc_main$4, {
                href: _ctx.route("filament.admin.pages.dashboard"),
                external: ""
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` Admin Panel `);
                  } else {
                    return [
                      createTextVNode(" Admin Panel ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            if (canLogout.value) {
              _push2(ssrRenderComponent(_sfc_main$4, {
                href: _ctx.route("logout"),
                method: "post",
                as: "button"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` Log Out `);
                  } else {
                    return [
                      createTextVNode(" Log Out ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              showProfileLink.value ? (openBlock(), createBlock(_sfc_main$4, {
                key: 0,
                href: _ctx.route("profile.edit")
              }, {
                default: withCtx(() => [
                  createTextVNode(" Profile ")
                ]),
                _: 1
              }, 8, ["href"])) : createCommentVNode("", true),
              showAdminPanelLink.value ? (openBlock(), createBlock(_sfc_main$4, {
                key: 1,
                href: _ctx.route("filament.admin.pages.dashboard"),
                external: ""
              }, {
                default: withCtx(() => [
                  createTextVNode(" Admin Panel ")
                ]),
                _: 1
              }, 8, ["href"])) : createCommentVNode("", true),
              canLogout.value ? (openBlock(), createBlock(_sfc_main$4, {
                key: 2,
                href: _ctx.route("logout"),
                method: "post",
                as: "button"
              }, {
                default: withCtx(() => [
                  createTextVNode(" Log Out ")
                ]),
                _: 1
              }, 8, ["href"])) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="flex items-center md:hidden"><button class="inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"${ssrRenderAttr("aria-expanded", showingNavigationDropdown.value)} aria-controls="customer-mobile-menu" aria-label="Toggle account navigation"><svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path class="${ssrRenderClass({
        hidden: showingNavigationDropdown.value,
        "inline-flex": !showingNavigationDropdown.value
      })}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path><path class="${ssrRenderClass({
        hidden: !showingNavigationDropdown.value,
        "inline-flex": showingNavigationDropdown.value
      })}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div></div></div>`);
      _push(ssrRenderComponent(unref(Dialog), {
        class: "md:hidden",
        open: showingNavigationDropdown.value,
        onClose: ($event) => showingNavigationDropdown.value = false
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="fixed inset-0 z-40 bg-gray-950/55" aria-hidden="true"${_scopeId}></div>`);
            _push2(ssrRenderComponent(unref(DialogPanel), {
              id: "customer-mobile-menu",
              class: "safe-y fixed inset-y-0 right-0 z-50 w-full max-w-drawer overflow-y-auto bg-white pb-6 shadow-2xl ring-1 ring-gray-900/10"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="flex h-16 items-center justify-between border-b border-gray-200 px-4"${_scopeId2}><p class="text-base font-semibold text-gray-900"${_scopeId2}>Account navigation</p><button type="button" class="inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Close account navigation"${_scopeId2}><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"${_scopeId2}><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"${_scopeId2}></path></svg></button></div><div class="space-y-1 pb-3 pt-2"${_scopeId2}>`);
                  _push3(ssrRenderComponent(_sfc_main$1, {
                    href: _ctx.route("dashboard"),
                    active: _ctx.route().current("dashboard"),
                    onClick: ($event) => showingNavigationDropdown.value = false
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(` Dashboard `);
                      } else {
                        return [
                          createTextVNode(" Dashboard ")
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(_sfc_main$1, {
                    href: _ctx.route("store.index"),
                    active: _ctx.route().current("store.index"),
                    onClick: ($event) => showingNavigationDropdown.value = false
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(` Store `);
                      } else {
                        return [
                          createTextVNode(" Store ")
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                  if (showProfileLink.value) {
                    _push3(ssrRenderComponent(_sfc_main$1, {
                      href: _ctx.route("dashboard.reports"),
                      active: _ctx.route().current("dashboard.reports"),
                      onClick: ($event) => showingNavigationDropdown.value = false
                    }, {
                      default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(` Reports `);
                        } else {
                          return [
                            createTextVNode(" Reports ")
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  _push3(`</div><div class="border-t border-gray-200 pb-1 pt-4"${_scopeId2}><div class="px-4 pb-2"${_scopeId2}><div class="text-base font-medium text-gray-800"${_scopeId2}>${ssrInterpolate(accountName.value)}</div>`);
                  if (accountEmail.value) {
                    _push3(`<div class="text-sm font-medium text-gray-500"${_scopeId2}>${ssrInterpolate(accountEmail.value)}</div>`);
                  } else {
                    _push3(`<!---->`);
                  }
                  _push3(`</div>`);
                  if (canLogout.value) {
                    _push3(`<div class="mt-3 space-y-1"${_scopeId2}>`);
                    if (showProfileLink.value) {
                      _push3(ssrRenderComponent(_sfc_main$1, {
                        href: _ctx.route("profile.edit"),
                        onClick: ($event) => showingNavigationDropdown.value = false
                      }, {
                        default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                          if (_push4) {
                            _push4(` Profile `);
                          } else {
                            return [
                              createTextVNode(" Profile ")
                            ];
                          }
                        }),
                        _: 1
                      }, _parent3, _scopeId2));
                    } else {
                      _push3(`<!---->`);
                    }
                    if (showAdminPanelLink.value) {
                      _push3(ssrRenderComponent(_sfc_main$1, {
                        href: _ctx.route("filament.admin.pages.dashboard"),
                        external: "",
                        onClick: ($event) => showingNavigationDropdown.value = false
                      }, {
                        default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                          if (_push4) {
                            _push4(` Admin Panel `);
                          } else {
                            return [
                              createTextVNode(" Admin Panel ")
                            ];
                          }
                        }),
                        _: 1
                      }, _parent3, _scopeId2));
                    } else {
                      _push3(`<!---->`);
                    }
                    _push3(ssrRenderComponent(_sfc_main$1, {
                      href: _ctx.route("logout"),
                      method: "post",
                      as: "button",
                      onClick: ($event) => showingNavigationDropdown.value = false
                    }, {
                      default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(` Log Out `);
                        } else {
                          return [
                            createTextVNode(" Log Out ")
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                    _push3(`</div>`);
                  } else {
                    _push3(`<!---->`);
                  }
                  _push3(`</div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex h-16 items-center justify-between border-b border-gray-200 px-4" }, [
                      createVNode("p", { class: "text-base font-semibold text-gray-900" }, "Account navigation"),
                      createVNode("button", {
                        type: "button",
                        class: "inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500",
                        "aria-label": "Close account navigation",
                        onClick: ($event) => showingNavigationDropdown.value = false
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
                    createVNode("div", { class: "space-y-1 pb-3 pt-2" }, [
                      createVNode(_sfc_main$1, {
                        href: _ctx.route("dashboard"),
                        active: _ctx.route().current("dashboard"),
                        onClick: ($event) => showingNavigationDropdown.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Dashboard ")
                        ]),
                        _: 1
                      }, 8, ["href", "active", "onClick"]),
                      createVNode(_sfc_main$1, {
                        href: _ctx.route("store.index"),
                        active: _ctx.route().current("store.index"),
                        onClick: ($event) => showingNavigationDropdown.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Store ")
                        ]),
                        _: 1
                      }, 8, ["href", "active", "onClick"]),
                      showProfileLink.value ? (openBlock(), createBlock(_sfc_main$1, {
                        key: 0,
                        href: _ctx.route("dashboard.reports"),
                        active: _ctx.route().current("dashboard.reports"),
                        onClick: ($event) => showingNavigationDropdown.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Reports ")
                        ]),
                        _: 1
                      }, 8, ["href", "active", "onClick"])) : createCommentVNode("", true)
                    ]),
                    createVNode("div", { class: "border-t border-gray-200 pb-1 pt-4" }, [
                      createVNode("div", { class: "px-4 pb-2" }, [
                        createVNode("div", { class: "text-base font-medium text-gray-800" }, toDisplayString(accountName.value), 1),
                        accountEmail.value ? (openBlock(), createBlock("div", {
                          key: 0,
                          class: "text-sm font-medium text-gray-500"
                        }, toDisplayString(accountEmail.value), 1)) : createCommentVNode("", true)
                      ]),
                      canLogout.value ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "mt-3 space-y-1"
                      }, [
                        showProfileLink.value ? (openBlock(), createBlock(_sfc_main$1, {
                          key: 0,
                          href: _ctx.route("profile.edit"),
                          onClick: ($event) => showingNavigationDropdown.value = false
                        }, {
                          default: withCtx(() => [
                            createTextVNode(" Profile ")
                          ]),
                          _: 1
                        }, 8, ["href", "onClick"])) : createCommentVNode("", true),
                        showAdminPanelLink.value ? (openBlock(), createBlock(_sfc_main$1, {
                          key: 1,
                          href: _ctx.route("filament.admin.pages.dashboard"),
                          external: "",
                          onClick: ($event) => showingNavigationDropdown.value = false
                        }, {
                          default: withCtx(() => [
                            createTextVNode(" Admin Panel ")
                          ]),
                          _: 1
                        }, 8, ["href", "onClick"])) : createCommentVNode("", true),
                        createVNode(_sfc_main$1, {
                          href: _ctx.route("logout"),
                          method: "post",
                          as: "button",
                          onClick: ($event) => showingNavigationDropdown.value = false
                        }, {
                          default: withCtx(() => [
                            createTextVNode(" Log Out ")
                          ]),
                          _: 1
                        }, 8, ["href", "onClick"])
                      ])) : createCommentVNode("", true)
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode("div", {
                class: "fixed inset-0 z-40 bg-gray-950/55",
                "aria-hidden": "true"
              }),
              createVNode(unref(DialogPanel), {
                id: "customer-mobile-menu",
                class: "safe-y fixed inset-y-0 right-0 z-50 w-full max-w-drawer overflow-y-auto bg-white pb-6 shadow-2xl ring-1 ring-gray-900/10"
              }, {
                default: withCtx(() => [
                  createVNode("div", { class: "flex h-16 items-center justify-between border-b border-gray-200 px-4" }, [
                    createVNode("p", { class: "text-base font-semibold text-gray-900" }, "Account navigation"),
                    createVNode("button", {
                      type: "button",
                      class: "inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500",
                      "aria-label": "Close account navigation",
                      onClick: ($event) => showingNavigationDropdown.value = false
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
                  createVNode("div", { class: "space-y-1 pb-3 pt-2" }, [
                    createVNode(_sfc_main$1, {
                      href: _ctx.route("dashboard"),
                      active: _ctx.route().current("dashboard"),
                      onClick: ($event) => showingNavigationDropdown.value = false
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" Dashboard ")
                      ]),
                      _: 1
                    }, 8, ["href", "active", "onClick"]),
                    createVNode(_sfc_main$1, {
                      href: _ctx.route("store.index"),
                      active: _ctx.route().current("store.index"),
                      onClick: ($event) => showingNavigationDropdown.value = false
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" Store ")
                      ]),
                      _: 1
                    }, 8, ["href", "active", "onClick"]),
                    showProfileLink.value ? (openBlock(), createBlock(_sfc_main$1, {
                      key: 0,
                      href: _ctx.route("dashboard.reports"),
                      active: _ctx.route().current("dashboard.reports"),
                      onClick: ($event) => showingNavigationDropdown.value = false
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" Reports ")
                      ]),
                      _: 1
                    }, 8, ["href", "active", "onClick"])) : createCommentVNode("", true)
                  ]),
                  createVNode("div", { class: "border-t border-gray-200 pb-1 pt-4" }, [
                    createVNode("div", { class: "px-4 pb-2" }, [
                      createVNode("div", { class: "text-base font-medium text-gray-800" }, toDisplayString(accountName.value), 1),
                      accountEmail.value ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "text-sm font-medium text-gray-500"
                      }, toDisplayString(accountEmail.value), 1)) : createCommentVNode("", true)
                    ]),
                    canLogout.value ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "mt-3 space-y-1"
                    }, [
                      showProfileLink.value ? (openBlock(), createBlock(_sfc_main$1, {
                        key: 0,
                        href: _ctx.route("profile.edit"),
                        onClick: ($event) => showingNavigationDropdown.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Profile ")
                        ]),
                        _: 1
                      }, 8, ["href", "onClick"])) : createCommentVNode("", true),
                      showAdminPanelLink.value ? (openBlock(), createBlock(_sfc_main$1, {
                        key: 1,
                        href: _ctx.route("filament.admin.pages.dashboard"),
                        external: "",
                        onClick: ($event) => showingNavigationDropdown.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Admin Panel ")
                        ]),
                        _: 1
                      }, 8, ["href", "onClick"])) : createCommentVNode("", true),
                      createVNode(_sfc_main$1, {
                        href: _ctx.route("logout"),
                        method: "post",
                        as: "button",
                        onClick: ($event) => showingNavigationDropdown.value = false
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Log Out ")
                        ]),
                        _: 1
                      }, 8, ["href", "onClick"])
                    ])) : createCommentVNode("", true)
                  ])
                ]),
                _: 1
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</nav>`);
      if (_ctx.$slots.header) {
        _push(`<header class="bg-white shadow"><div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">`);
        ssrRenderSlot(_ctx.$slots, "header", {}, null, _push, _parent);
        _push(`</div></header>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<main>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</main>`);
      _push(ssrRenderComponent(_sfc_main$6, null, null, _parent));
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Layouts/AuthenticatedLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as _
};
