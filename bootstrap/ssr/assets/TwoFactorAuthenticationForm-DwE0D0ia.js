import { computed, ref, mergeProps, withCtx, createTextVNode, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderList, ssrInterpolate, ssrRenderComponent } from "vue/server-renderer";
import { _ as _sfc_main$1, D as DangerButton } from "./SecondaryButton-3xRiMLwn.js";
import { _ as _sfc_main$4 } from "./InputError-C5XExbFq.js";
import { _ as _sfc_main$2, a as _sfc_main$3 } from "./TextInput-CcK4WCIH.js";
import { P as PrimaryButton } from "./PrimaryButton-qTs2i3In.js";
import { useForm } from "@inertiajs/vue3";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "TwoFactorAuthenticationForm",
  __ssrInlineRender: true,
  props: {
    enabled: Boolean,
    method: { type: String, default: null },
    setup: { type: Object, default: null },
    recoveryCodes: { type: Array, default: null }
  },
  setup(__props) {
    const props = __props;
    const enrolling = computed(() => props.setup !== null);
    const enableForm = useForm({ method: "totp" });
    const confirmForm = useForm({ code: "" });
    const disableForm = useForm({ password: "" });
    const recoveryForm = useForm({});
    const showDisable = ref(false);
    const startEnroll = (method) => {
      enableForm.method = method;
      enableForm.post(route("mfa.enable"), { preserveScroll: true });
    };
    const regenerate = () => {
      recoveryForm.post(route("mfa.recovery-codes"), { preserveScroll: true });
    };
    const copyRecoveryCodes = () => {
      if (props.recoveryCodes) {
        navigator.clipboard?.writeText(props.recoveryCodes.join("\n"));
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><header><h2 class="text-lg font-medium text-gray-900"> Two-Factor Authentication </h2><p class="mt-1 text-sm text-gray-600"> Add a second step to sign-in using an authenticator app or a code emailed to you. This is required for accessing government and CUI-related data. </p></header>`);
      if (__props.recoveryCodes) {
        _push(`<div class="rounded-md border border-amber-300 bg-amber-50 p-4"><p class="text-sm font-medium text-amber-800"> Save your recovery codes </p><p class="mt-1 text-sm text-amber-700"> Store these somewhere safe. Each code can be used once to sign in if you lose access to your authenticator or email. They will not be shown again. </p><ul class="mt-3 grid grid-cols-2 gap-1 font-mono text-sm text-amber-900"><!--[-->`);
        ssrRenderList(__props.recoveryCodes, (code) => {
          _push(`<li>${ssrInterpolate(code)}</li>`);
        });
        _push(`<!--]--></ul>`);
        _push(ssrRenderComponent(_sfc_main$1, {
          class: "mt-3",
          onClick: copyRecoveryCodes
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Copy codes `);
            } else {
              return [
                createTextVNode(" Copy codes ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.enabled && !enrolling.value) {
        _push(`<div class="space-y-4"><p class="text-sm text-green-700"> Two-factor authentication is <strong>enabled</strong> using ${ssrInterpolate(__props.method === "totp" ? "an authenticator app" : "email codes")}. </p><div class="flex flex-wrap gap-3">`);
        _push(ssrRenderComponent(_sfc_main$1, {
          disabled: unref(recoveryForm).processing,
          onClick: regenerate
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Regenerate recovery codes `);
            } else {
              return [
                createTextVNode(" Regenerate recovery codes ")
              ];
            }
          }),
          _: 1
        }, _parent));
        if (!showDisable.value) {
          _push(ssrRenderComponent(DangerButton, {
            onClick: ($event) => showDisable.value = true
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(` Disable `);
              } else {
                return [
                  createTextVNode(" Disable ")
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (showDisable.value) {
          _push(`<form class="max-w-md space-y-3">`);
          _push(ssrRenderComponent(_sfc_main$2, {
            for: "disable_password",
            value: "Confirm your password"
          }, null, _parent));
          _push(ssrRenderComponent(_sfc_main$3, {
            id: "disable_password",
            modelValue: unref(disableForm).password,
            "onUpdate:modelValue": ($event) => unref(disableForm).password = $event,
            type: "password",
            class: "mt-1 block w-full",
            autocomplete: "current-password"
          }, null, _parent));
          _push(ssrRenderComponent(_sfc_main$4, {
            message: unref(disableForm).errors.password
          }, null, _parent));
          _push(`<div class="flex gap-3">`);
          _push(ssrRenderComponent(DangerButton, {
            disabled: unref(disableForm).processing
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(` Disable two-factor `);
              } else {
                return [
                  createTextVNode(" Disable two-factor ")
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(ssrRenderComponent(_sfc_main$1, {
            type: "button",
            onClick: ($event) => showDisable.value = false
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(` Cancel `);
              } else {
                return [
                  createTextVNode(" Cancel ")
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div></form>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else if (enrolling.value) {
        _push(`<div class="space-y-4">`);
        if (__props.setup.method === "totp") {
          _push(`<div class="space-y-3"><p class="text-sm text-gray-600"> Scan this QR code with your authenticator app (Google Authenticator, Authy, 1Password, …), then enter the 6-digit code it shows. </p><div class="inline-block rounded-md bg-white p-2 ring-1 ring-gray-200">${__props.setup.qr ?? ""}</div><p class="text-xs text-gray-500"> Can’t scan? Enter this key manually: <span class="font-mono">${ssrInterpolate(__props.setup.secret)}</span></p></div>`);
        } else {
          _push(`<p class="text-sm text-gray-600"> We emailed a 6-digit code to your address. Enter it below to turn on email-based two-factor authentication. </p>`);
        }
        _push(`<form class="max-w-xs space-y-3">`);
        _push(ssrRenderComponent(_sfc_main$2, {
          for: "mfa_code",
          value: "Verification code"
        }, null, _parent));
        _push(ssrRenderComponent(_sfc_main$3, {
          id: "mfa_code",
          modelValue: unref(confirmForm).code,
          "onUpdate:modelValue": ($event) => unref(confirmForm).code = $event,
          type: "text",
          inputmode: "numeric",
          autocomplete: "one-time-code",
          class: "mt-1 block w-full"
        }, null, _parent));
        _push(ssrRenderComponent(_sfc_main$4, {
          message: unref(confirmForm).errors.code
        }, null, _parent));
        _push(ssrRenderComponent(PrimaryButton, {
          disabled: unref(confirmForm).processing
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Confirm &amp; enable `);
            } else {
              return [
                createTextVNode(" Confirm & enable ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</form></div>`);
      } else {
        _push(`<div class="flex flex-wrap gap-3">`);
        _push(ssrRenderComponent(PrimaryButton, {
          disabled: unref(enableForm).processing,
          onClick: ($event) => startEnroll("totp")
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Set up authenticator app `);
            } else {
              return [
                createTextVNode(" Set up authenticator app ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_sfc_main$1, {
          disabled: unref(enableForm).processing,
          onClick: ($event) => startEnroll("email")
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Use email codes `);
            } else {
              return [
                createTextVNode(" Use email codes ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_sfc_main$4, {
          message: unref(enableForm).errors.method,
          class: "w-full"
        }, null, _parent));
        _push(`</div>`);
      }
      _push(`</section>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
