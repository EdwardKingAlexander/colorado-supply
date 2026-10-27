import { ref, mergeProps, withCtx, unref, createTextVNode, createVNode, createBlock, createCommentVNode, openBlock, toDisplayString, withModifiers, Fragment, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./BrandedAuthLayout-C1BpIGle.js";
import { _ as _sfc_main$4 } from "./InputError-C5XExbFq.js";
import { _ as _sfc_main$2, a as _sfc_main$3 } from "./TextInput-CcK4WCIH.js";
import { P as PrimaryButton } from "./PrimaryButton-qTs2i3In.js";
import { useForm, Head, Link } from "@inertiajs/vue3";
import "./CookieConsentBanner-ByAlkSbo.js";
import "@headlessui/vue";
import "axios";
import "./logo-cleansed-light-B5mBHTsK.js";
import "./pipeline-900-CwP-DKPX.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "Challenge",
  __ssrInlineRender: true,
  props: {
    method: { type: String, default: null },
    status: { type: String, default: null }
  },
  setup(__props) {
    const useRecovery = ref(false);
    const form = useForm({
      code: "",
      recovery_code: ""
    });
    const submit = () => {
      form.transform(
        (data) => useRecovery.value ? { recovery_code: data.recovery_code } : { code: data.code }
      ).post(route("mfa.challenge.verify"), {
        onFinish: () => form.reset("code", "recovery_code")
      });
    };
    const emailForm = useForm({});
    const sendEmailCode = () => {
      emailForm.post(route("mfa.challenge.email"), { preserveScroll: true });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, mergeProps({
        title: "Two-step verification",
        subtitle: "Confirm it's you to finish signing in to your Colorado Supply account."
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Head), { title: "Two-step verification" }, null, _parent2, _scopeId));
            if (__props.status) {
              _push2(`<div class="mb-6 border-l-4 border-green-500 bg-green-50 px-4 py-3 text-base font-medium leading-6 text-green-800" role="status"${_scopeId}>${ssrInterpolate(__props.status)}</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<form class="space-y-5"${_scopeId}>`);
            if (!useRecovery.value) {
              _push2(`<!--[--><div${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$2, {
                for: "code",
                value: "Verification code",
                class: "text-gray-800"
              }, null, _parent2, _scopeId));
              _push2(`<p class="mt-1 text-sm text-gray-600"${_scopeId}>${ssrInterpolate(__props.method === "totp" ? "Enter the 6-digit code from your authenticator app." : "Enter the 6-digit code we emailed to you.")}</p>`);
              _push2(ssrRenderComponent(_sfc_main$3, {
                id: "code",
                type: "text",
                inputmode: "numeric",
                autocomplete: "one-time-code",
                autofocus: "",
                class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
                modelValue: unref(form).code,
                "onUpdate:modelValue": ($event) => unref(form).code = $event
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$4, {
                class: "mt-2",
                message: unref(form).errors.code
              }, null, _parent2, _scopeId));
              _push2(`</div><button type="button" class="text-sm font-semibold text-amber-700 hover:text-amber-800"${_scopeId}> Email me a code </button><!--]-->`);
            } else {
              _push2(`<div${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$2, {
                for: "recovery_code",
                value: "Recovery code",
                class: "text-gray-800"
              }, null, _parent2, _scopeId));
              _push2(`<p class="mt-1 text-sm text-gray-600"${_scopeId}> Enter one of the recovery codes you saved when you set up two-factor authentication. </p>`);
              _push2(ssrRenderComponent(_sfc_main$3, {
                id: "recovery_code",
                type: "text",
                autocomplete: "one-time-code",
                class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
                modelValue: unref(form).recovery_code,
                "onUpdate:modelValue": ($event) => unref(form).recovery_code = $event
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$4, {
                class: "mt-2",
                message: unref(form).errors.recovery_code
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            }
            _push2(`<div class="flex items-center justify-between pt-2"${_scopeId}><button type="button" class="text-sm font-semibold text-amber-700 hover:text-amber-800"${_scopeId}>${ssrInterpolate(useRecovery.value ? "Use a verification code" : "Use a recovery code")}</button>`);
            _push2(ssrRenderComponent(PrimaryButton, {
              class: ["justify-center bg-primary-700 px-5 py-3 text-base hover:bg-primary-600 focus:ring-amber-500", { "opacity-25": unref(form).processing }],
              disabled: unref(form).processing
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Verify `);
                } else {
                  return [
                    createTextVNode(" Verify ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></form><div class="mt-6 text-center"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("logout"),
              as: "button",
              method: "post",
              class: "text-sm text-gray-500 hover:text-gray-700"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Log out `);
                } else {
                  return [
                    createTextVNode(" Log out ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode(unref(Head), { title: "Two-step verification" }),
              __props.status ? (openBlock(), createBlock("div", {
                key: 0,
                class: "mb-6 border-l-4 border-green-500 bg-green-50 px-4 py-3 text-base font-medium leading-6 text-green-800",
                role: "status"
              }, toDisplayString(__props.status), 1)) : createCommentVNode("", true),
              createVNode("form", {
                class: "space-y-5",
                onSubmit: withModifiers(submit, ["prevent"])
              }, [
                !useRecovery.value ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                  createVNode("div", null, [
                    createVNode(_sfc_main$2, {
                      for: "code",
                      value: "Verification code",
                      class: "text-gray-800"
                    }),
                    createVNode("p", { class: "mt-1 text-sm text-gray-600" }, toDisplayString(__props.method === "totp" ? "Enter the 6-digit code from your authenticator app." : "Enter the 6-digit code we emailed to you."), 1),
                    createVNode(_sfc_main$3, {
                      id: "code",
                      type: "text",
                      inputmode: "numeric",
                      autocomplete: "one-time-code",
                      autofocus: "",
                      class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
                      modelValue: unref(form).code,
                      "onUpdate:modelValue": ($event) => unref(form).code = $event
                    }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                    createVNode(_sfc_main$4, {
                      class: "mt-2",
                      message: unref(form).errors.code
                    }, null, 8, ["message"])
                  ]),
                  createVNode("button", {
                    type: "button",
                    class: "text-sm font-semibold text-amber-700 hover:text-amber-800",
                    onClick: sendEmailCode
                  }, " Email me a code ")
                ], 64)) : (openBlock(), createBlock("div", { key: 1 }, [
                  createVNode(_sfc_main$2, {
                    for: "recovery_code",
                    value: "Recovery code",
                    class: "text-gray-800"
                  }),
                  createVNode("p", { class: "mt-1 text-sm text-gray-600" }, " Enter one of the recovery codes you saved when you set up two-factor authentication. "),
                  createVNode(_sfc_main$3, {
                    id: "recovery_code",
                    type: "text",
                    autocomplete: "one-time-code",
                    class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
                    modelValue: unref(form).recovery_code,
                    "onUpdate:modelValue": ($event) => unref(form).recovery_code = $event
                  }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                  createVNode(_sfc_main$4, {
                    class: "mt-2",
                    message: unref(form).errors.recovery_code
                  }, null, 8, ["message"])
                ])),
                createVNode("div", { class: "flex items-center justify-between pt-2" }, [
                  createVNode("button", {
                    type: "button",
                    class: "text-sm font-semibold text-amber-700 hover:text-amber-800",
                    onClick: ($event) => useRecovery.value = !useRecovery.value
                  }, toDisplayString(useRecovery.value ? "Use a verification code" : "Use a recovery code"), 9, ["onClick"]),
                  createVNode(PrimaryButton, {
                    class: ["justify-center bg-primary-700 px-5 py-3 text-base hover:bg-primary-600 focus:ring-amber-500", { "opacity-25": unref(form).processing }],
                    disabled: unref(form).processing
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" Verify ")
                    ]),
                    _: 1
                  }, 8, ["class", "disabled"])
                ])
              ], 32),
              createVNode("div", { class: "mt-6 text-center" }, [
                createVNode(unref(Link), {
                  href: _ctx.route("logout"),
                  as: "button",
                  method: "post",
                  class: "text-sm text-gray-500 hover:text-gray-700"
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Log out ")
                  ]),
                  _: 1
                }, 8, ["href"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Auth/Mfa/Challenge.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
