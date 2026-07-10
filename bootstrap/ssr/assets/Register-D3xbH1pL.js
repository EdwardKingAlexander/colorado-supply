import { mergeProps, withCtx, unref, createTextVNode, createVNode, withModifiers, useSSRContext } from "vue";
import { ssrRenderComponent } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./BrandedAuthLayout-CToNMlXT.js";
import { _ as _sfc_main$4 } from "./InputError-C5XExbFq.js";
import { _ as _sfc_main$2, a as _sfc_main$3 } from "./TextInput-CcK4WCIH.js";
import { P as PrimaryButton } from "./PrimaryButton-qTs2i3In.js";
import { useForm, Head, Link } from "@inertiajs/vue3";
import "./logo-cleansed-light-B5mBHTsK.js";
import "./pipeline-900-CwP-DKPX.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "Register",
  __ssrInlineRender: true,
  setup(__props) {
    const form = useForm({
      name: "",
      email: "",
      password: "",
      password_confirmation: ""
    });
    const submit = () => {
      form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation")
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, mergeProps({
        title: "Create your account",
        subtitle: "Set up access for quotes, ordering, and customer purchasing workflows with Colorado Supply.",
        "secondary-action-label": "Log in",
        "secondary-action-href": _ctx.route("login")
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Head), { title: "Register" }, null, _parent2, _scopeId));
            _push2(`<form class="space-y-5"${_scopeId}><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "name",
              value: "Name",
              class: "text-gray-800"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "name",
              type: "text",
              class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
              modelValue: unref(form).name,
              "onUpdate:modelValue": ($event) => unref(form).name = $event,
              required: "",
              autofocus: "",
              autocomplete: "name"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-2",
              message: unref(form).errors.name
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "email",
              value: "Email",
              class: "text-gray-800"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "email",
              type: "email",
              class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
              modelValue: unref(form).email,
              "onUpdate:modelValue": ($event) => unref(form).email = $event,
              required: "",
              autocomplete: "username"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-2",
              message: unref(form).errors.email
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "password",
              value: "Password",
              class: "text-gray-800"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "password",
              type: "password",
              class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
              modelValue: unref(form).password,
              "onUpdate:modelValue": ($event) => unref(form).password = $event,
              required: "",
              autocomplete: "new-password"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-2",
              message: unref(form).errors.password
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "password_confirmation",
              value: "Confirm Password",
              class: "text-gray-800"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "password_confirmation",
              type: "password",
              class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
              modelValue: unref(form).password_confirmation,
              "onUpdate:modelValue": ($event) => unref(form).password_confirmation = $event,
              required: "",
              autocomplete: "new-password"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-2",
              message: unref(form).errors.password_confirmation
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between"${_scopeId}><p class="text-base leading-6 text-gray-600"${_scopeId}> Already have an account? `);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("login"),
              class: "font-semibold text-amber-700 hover:text-amber-800"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Log in `);
                } else {
                  return [
                    createTextVNode(" Log in ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</p>`);
            _push2(ssrRenderComponent(PrimaryButton, {
              class: ["w-full justify-center bg-primary-700 px-5 py-3 text-base hover:bg-primary-600 focus:bg-primary-600 focus:ring-amber-500 active:bg-primary-800 sm:w-auto", { "opacity-25": unref(form).processing }],
              disabled: unref(form).processing
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Register `);
                } else {
                  return [
                    createTextVNode(" Register ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></form>`);
          } else {
            return [
              createVNode(unref(Head), { title: "Register" }),
              createVNode("form", {
                class: "space-y-5",
                onSubmit: withModifiers(submit, ["prevent"])
              }, [
                createVNode("div", null, [
                  createVNode(_sfc_main$2, {
                    for: "name",
                    value: "Name",
                    class: "text-gray-800"
                  }),
                  createVNode(_sfc_main$3, {
                    id: "name",
                    type: "text",
                    class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
                    modelValue: unref(form).name,
                    "onUpdate:modelValue": ($event) => unref(form).name = $event,
                    required: "",
                    autofocus: "",
                    autocomplete: "name"
                  }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                  createVNode(_sfc_main$4, {
                    class: "mt-2",
                    message: unref(form).errors.name
                  }, null, 8, ["message"])
                ]),
                createVNode("div", null, [
                  createVNode(_sfc_main$2, {
                    for: "email",
                    value: "Email",
                    class: "text-gray-800"
                  }),
                  createVNode(_sfc_main$3, {
                    id: "email",
                    type: "email",
                    class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
                    modelValue: unref(form).email,
                    "onUpdate:modelValue": ($event) => unref(form).email = $event,
                    required: "",
                    autocomplete: "username"
                  }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                  createVNode(_sfc_main$4, {
                    class: "mt-2",
                    message: unref(form).errors.email
                  }, null, 8, ["message"])
                ]),
                createVNode("div", null, [
                  createVNode(_sfc_main$2, {
                    for: "password",
                    value: "Password",
                    class: "text-gray-800"
                  }),
                  createVNode(_sfc_main$3, {
                    id: "password",
                    type: "password",
                    class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
                    modelValue: unref(form).password,
                    "onUpdate:modelValue": ($event) => unref(form).password = $event,
                    required: "",
                    autocomplete: "new-password"
                  }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                  createVNode(_sfc_main$4, {
                    class: "mt-2",
                    message: unref(form).errors.password
                  }, null, 8, ["message"])
                ]),
                createVNode("div", null, [
                  createVNode(_sfc_main$2, {
                    for: "password_confirmation",
                    value: "Confirm Password",
                    class: "text-gray-800"
                  }),
                  createVNode(_sfc_main$3, {
                    id: "password_confirmation",
                    type: "password",
                    class: "mt-2 block w-full border-gray-300 bg-white px-3 py-2.5 text-gray-950 shadow-sm focus:border-amber-500 focus:ring-amber-500",
                    modelValue: unref(form).password_confirmation,
                    "onUpdate:modelValue": ($event) => unref(form).password_confirmation = $event,
                    required: "",
                    autocomplete: "new-password"
                  }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                  createVNode(_sfc_main$4, {
                    class: "mt-2",
                    message: unref(form).errors.password_confirmation
                  }, null, 8, ["message"])
                ]),
                createVNode("div", { class: "flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between" }, [
                  createVNode("p", { class: "text-base leading-6 text-gray-600" }, [
                    createTextVNode(" Already have an account? "),
                    createVNode(unref(Link), {
                      href: _ctx.route("login"),
                      class: "font-semibold text-amber-700 hover:text-amber-800"
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" Log in ")
                      ]),
                      _: 1
                    }, 8, ["href"])
                  ]),
                  createVNode(PrimaryButton, {
                    class: ["w-full justify-center bg-primary-700 px-5 py-3 text-base hover:bg-primary-600 focus:bg-primary-600 focus:ring-amber-500 active:bg-primary-800 sm:w-auto", { "opacity-25": unref(form).processing }],
                    disabled: unref(form).processing
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" Register ")
                    ]),
                    _: 1
                  }, 8, ["class", "disabled"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Auth/Register.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
