import { computed, mergeProps, withCtx, unref, createTextVNode, createVNode, createBlock, createCommentVNode, openBlock, withModifiers, useSSRContext } from "vue";
import { ssrRenderComponent } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./BrandedAuthLayout-C1BpIGle.js";
import { P as PrimaryButton } from "./PrimaryButton-qTs2i3In.js";
import { useForm, Head, Link } from "@inertiajs/vue3";
import "./CookieConsentBanner-ByAlkSbo.js";
import "@headlessui/vue";
import "axios";
import "./logo-cleansed-light-B5mBHTsK.js";
import "./pipeline-900-CwP-DKPX.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "VerifyEmail",
  __ssrInlineRender: true,
  props: {
    status: {
      type: String
    }
  },
  setup(__props) {
    const props = __props;
    const form = useForm({});
    const submit = () => {
      form.post(route("verification.send"));
    };
    const verificationLinkSent = computed(
      () => props.status === "verification-link-sent"
    );
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, mergeProps({
        title: "Verify your email address",
        subtitle: "Thanks for signing up! We've emailed you a verification link — click it to activate your account. If it hasn't arrived, we will gladly send you another."
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Head), { title: "Email Verification" }, null, _parent2, _scopeId));
            if (verificationLinkSent.value) {
              _push2(`<div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-base font-medium leading-6 text-green-800" role="status"${_scopeId}> A new verification link has been sent to the email address you provided during registration. </div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<form${_scopeId}><div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"${_scopeId}>`);
            _push2(ssrRenderComponent(PrimaryButton, {
              class: ["w-full sm:w-auto", { "opacity-25": unref(form).processing }],
              disabled: unref(form).processing
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Resend Verification Email `);
                } else {
                  return [
                    createTextVNode(" Resend Verification Email ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("logout"),
              method: "post",
              as: "button",
              class: "inline-flex min-h-12 w-full items-center justify-center rounded-md px-4 py-3 text-base text-gray-700 underline hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 sm:w-auto"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Log Out`);
                } else {
                  return [
                    createTextVNode("Log Out")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></form>`);
          } else {
            return [
              createVNode(unref(Head), { title: "Email Verification" }),
              verificationLinkSent.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "mb-4 rounded-md bg-green-50 px-4 py-3 text-base font-medium leading-6 text-green-800",
                role: "status"
              }, " A new verification link has been sent to the email address you provided during registration. ")) : createCommentVNode("", true),
              createVNode("form", {
                onSubmit: withModifiers(submit, ["prevent"])
              }, [
                createVNode("div", { class: "mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between" }, [
                  createVNode(PrimaryButton, {
                    class: ["w-full sm:w-auto", { "opacity-25": unref(form).processing }],
                    disabled: unref(form).processing
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" Resend Verification Email ")
                    ]),
                    _: 1
                  }, 8, ["class", "disabled"]),
                  createVNode(unref(Link), {
                    href: _ctx.route("logout"),
                    method: "post",
                    as: "button",
                    class: "inline-flex min-h-12 w-full items-center justify-center rounded-md px-4 py-3 text-base text-gray-700 underline hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 sm:w-auto"
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Log Out")
                    ]),
                    _: 1
                  }, 8, ["href"])
                ])
              ], 32)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Auth/VerifyEmail.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
