import { mergeProps, useSSRContext, unref, createVNode, resolveDynamicComponent, ref, withCtx } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrRenderVNode, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import { CurrencyDollarIcon, ShieldCheckIcon, ClipboardDocumentCheckIcon, TruckIcon, ClockIcon, ArrowsRightLeftIcon, CpuChipIcon, BoltIcon, Cog6ToothIcon, AdjustmentsHorizontalIcon } from "@heroicons/vue/24/outline";
import { F as Footer } from "./Footer-WiuE2eSG.js";
import { _ as _sfc_main$6 } from "./AppLayout-BKnJF8b8.js";
import { Head } from "@inertiajs/vue3";
import "@headlessui/vue";
import "./logo-cleansed-CSOLeLOy.js";
const _sfc_main$5 = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<section${ssrRenderAttrs(mergeProps({
    id: "repair-top",
    class: "relative isolate overflow-hidden bg-white pt-32 pb-20 sm:pt-40 sm:pb-28 dark:bg-gray-900"
  }, _attrs))}><div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true"><div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.0625rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-primary-800 to-slate-700 opacity-25 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"></div></div><div class="mx-auto max-w-7xl px-6 lg:px-8"><div class="mx-auto max-w-2xl text-center"><p class="text-md font-extrabold text-primary-800 dark:text-primary-300 mb-4"> Industrial Repair Program </p><h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl dark:text-white"> Expert Industrial Electronics &amp; Motor Repair — Fast, Reliable, Guaranteed </h1><p class="mt-8 text-lg leading-8 sm:text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto"> Colorado Supply &amp; Procurement repairs, sells, and services industrial electronics, servo motors, AC &amp; DC motors, and hydraulics &amp; pneumatics — often for a fraction of the cost of new equipment. Every job starts with a free, risk-free evaluation, with no freight cost and nothing due upfront. If a unit can&#39;t be repaired, we&#39;ll source and quote a suitable alternative. </p><div class="mt-10 flex items-center justify-center gap-x-6"><a href="#repair-form" class="rounded-md bg-primary-700 px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 transition-colors"> Request a Repair Quote </a><a href="tel:7194259634" class="text-sm font-semibold text-gray-900 dark:text-gray-100"> Call (719) 425-9634 → </a></div></div></div></section>`);
}
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/RepairHero.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const RepairHero = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["ssrRender", _sfc_ssrRender]]);
const _sfc_main$4 = {
  __name: "RepairValueProps",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({
        id: "repair-advantages",
        class: "bg-white dark:bg-gray-900 py-24 sm:py-32 scroll-mt-20"
      }, _attrs))}><div class="mx-auto max-w-7xl px-6 lg:px-8"><div class="mx-auto max-w-2xl text-center"><h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">Why Repair With Us</h2><p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl"> The Colorado Supply Repair Advantage </p><p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300"> A straightforward, low-risk way to get critical equipment back in service. </p></div><div class="mx-auto mt-16 max-w-4xl grid grid-cols-1 gap-12 sm:grid-cols-2 lg:grid-cols-3"><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(CurrencyDollarIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Save Up to 50%</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> Repair costs a fraction of buying new, without sacrificing performance or reliability. </p></div><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(ShieldCheckIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">2-Year In-Service Warranty</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> Every repair is backed by a full two-year warranty for peace of mind. </p></div><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(ClipboardDocumentCheckIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Free, Risk-Free Evaluation</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> We diagnose your equipment before any work begins. No surprises, no obligation. </p></div><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(TruckIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">No Freight or Upfront Cost</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> We cover shipping both ways and never bill you before you approve the work. </p></div><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(ClockIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">3-5 Day Repair Turnaround</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> Once your equipment reaches our facility, most repairs are completed in 3-5 business days — shipping time to and from us is separate. Rush service is available when you can&#39;t wait. </p></div><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(ArrowsRightLeftIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">We Source Alternatives</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> If a unit is beyond repair, we&#39;ll find and quote a suitable replacement. No dead ends. </p></div></div></div></section>`);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/RepairValueProps.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const _sfc_main$3 = {
  __name: "RepairCategories",
  __ssrInlineRender: true,
  setup(__props) {
    const categories = [
      {
        icon: CpuChipIcon,
        name: "Industrial Electronics",
        items: "Circuit boards, drives, PLCs, HMIs & touchscreens, power supplies, process controls"
      },
      {
        icon: BoltIcon,
        name: "Servo Motors",
        items: "AC & DC servo motors, continuous rotational, linear, and positional rotation types"
      },
      {
        icon: Cog6ToothIcon,
        name: "AC & DC Motors",
        items: "3-phase, brushless, gear, stepper, and spindle motors"
      },
      {
        icon: AdjustmentsHorizontalIcon,
        name: "Hydraulics & Pneumatics",
        items: "Actuators, cylinders, pumps, valves, and manifolds"
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({
        id: "repair-categories",
        class: "bg-gray-50 dark:bg-gray-800 py-24 sm:py-32 scroll-mt-20"
      }, _attrs))}><div class="mx-auto max-w-7xl px-6 lg:px-8"><div class="mx-auto max-w-2xl text-center"><h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">What We Repair</h2><p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl"> Equipment Categories </p><p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300"> A broad range of industrial equipment, backed by the same repair program regardless of category. </p></div><div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 sm:grid-cols-2 lg:max-w-none"><!--[-->`);
      ssrRenderList(categories, (category) => {
        _push(`<div class="rounded-xl bg-white dark:bg-gray-900 p-8 shadow-sm ring-1 ring-gray-900/5 dark:ring-white/10">`);
        ssrRenderVNode(_push, createVNode(resolveDynamicComponent(category.icon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null), _parent);
        _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">${ssrInterpolate(category.name)}</h3><p class="mt-2 text-gray-600 dark:text-gray-400">${ssrInterpolate(category.items)}</p></div>`);
      });
      _push(`<!--]--></div></div></section>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/RepairCategories.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = {
  __name: "RepairProcess",
  __ssrInlineRender: true,
  setup(__props) {
    const steps = [
      {
        number: "1",
        title: "Submit Your Request",
        description: "Tell us about your equipment and the issue you're experiencing using the form below."
      },
      {
        number: "2",
        title: "Free Evaluation & Quote",
        description: "We review your submission and provide a no-obligation quote, typically within 24 hours."
      },
      {
        number: "3",
        title: "Ship It to Us, Free",
        description: "Once you approve, we arrange free shipping to our repair facility — no cost to you."
      },
      {
        number: "4",
        title: "3-5 Day Repair — or a Sourced Alternative",
        description: "Once your unit arrives, most repairs are completed in 3-5 business days. If it can't be repaired, we'll quote a suitable replacement instead."
      },
      {
        number: "5",
        title: "Fast, No-Cost Return",
        description: "Your equipment ships back at no charge, backed by a 2-year in-service warranty."
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({
        id: "repair-process",
        class: "bg-white dark:bg-gray-900 py-24 sm:py-32 scroll-mt-20"
      }, _attrs))}><div class="mx-auto max-w-7xl px-6 lg:px-8"><div class="mx-auto max-w-2xl text-center"><h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">How It Works</h2><p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl"> Simple, Transparent Process </p></div><div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-12 sm:grid-cols-2 lg:max-w-none lg:grid-cols-5"><!--[-->`);
      ssrRenderList(steps, (step) => {
        _push(`<div class="flex flex-col items-start"><div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-700 text-lg font-bold text-white">${ssrInterpolate(step.number)}</div><h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">${ssrInterpolate(step.title)}</h3><p class="mt-2 text-gray-600 dark:text-gray-400">${ssrInterpolate(step.description)}</p></div>`);
      });
      _push(`<!--]--></div></div></section>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/RepairProcess.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "RepairRequestForm",
  __ssrInlineRender: true,
  setup(__props) {
    const equipmentTypes = [
      "Industrial Electronics",
      "Servo Motor",
      "AC Motor",
      "DC Motor",
      "Drive / Inverter",
      "HMI / Touchscreen",
      "Hydraulic Component",
      "Pneumatic Component",
      "PLC",
      "Other"
    ];
    const form = ref({
      name: "",
      email: "",
      phone: "",
      company: "",
      equipment_type: "",
      manufacturer: "",
      model_number: "",
      serial_number: "",
      issue_description: "",
      urgency: "",
      website: ""
    });
    const successMessage = ref("");
    const errors = ref({});
    const isSubmitting = ref(false);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({
        id: "repair-form",
        class: "bg-gray-50 dark:bg-gray-800 py-24 sm:py-32 scroll-mt-20"
      }, _attrs))}><div class="mx-auto max-w-7xl px-6 lg:px-8"><div class="mx-auto max-w-2xl text-center"><h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">Get Started</h2><p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl"> Request a Repair Quote </p><p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300"> Tell us about your equipment and we&#39;ll follow up with a free evaluation and quote. Only your name, email, equipment type, model number, and a description of the issue are required — everything else helps us move faster, but isn&#39;t required. </p></div><div class="mx-auto mt-16 max-w-2xl"><form class="space-y-6 rounded-xl bg-white dark:bg-gray-900 p-8 shadow-sm ring-1 ring-gray-900/5 dark:ring-white/10"><div class="grid grid-cols-1 gap-6 sm:grid-cols-2"><div><label for="repair-name" class="block text-sm font-semibold text-gray-900 dark:text-white">Name *</label><input id="repair-name" type="text"${ssrRenderAttr("value", form.value.name)} required class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm">`);
      if (errors.value.name) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.name[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label for="repair-email" class="block text-sm font-semibold text-gray-900 dark:text-white">Email *</label><input id="repair-email" type="email"${ssrRenderAttr("value", form.value.email)} required class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm">`);
      if (errors.value.email) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.email[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label for="repair-phone" class="block text-sm font-semibold text-gray-900 dark:text-white">Phone</label><input id="repair-phone" type="text"${ssrRenderAttr("value", form.value.phone)} class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm">`);
      if (errors.value.phone) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.phone[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label for="repair-company" class="block text-sm font-semibold text-gray-900 dark:text-white">Company</label><input id="repair-company" type="text"${ssrRenderAttr("value", form.value.company)} class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm">`);
      if (errors.value.company) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.company[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label for="repair-equipment-type" class="block text-sm font-semibold text-gray-900 dark:text-white">Equipment Type *</label><select id="repair-equipment-type" required class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"><option value="" disabled${ssrIncludeBooleanAttr(Array.isArray(form.value.equipment_type) ? ssrLooseContain(form.value.equipment_type, "") : ssrLooseEqual(form.value.equipment_type, "")) ? " selected" : ""}>Select equipment type</option><!--[-->`);
      ssrRenderList(equipmentTypes, (option) => {
        _push(`<option${ssrRenderAttr("value", option)}${ssrIncludeBooleanAttr(Array.isArray(form.value.equipment_type) ? ssrLooseContain(form.value.equipment_type, option) : ssrLooseEqual(form.value.equipment_type, option)) ? " selected" : ""}>${ssrInterpolate(option)}</option>`);
      });
      _push(`<!--]--></select>`);
      if (errors.value.equipment_type) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.equipment_type[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label for="repair-manufacturer" class="block text-sm font-semibold text-gray-900 dark:text-white">Manufacturer</label><input id="repair-manufacturer" type="text"${ssrRenderAttr("value", form.value.manufacturer)} class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm">`);
      if (errors.value.manufacturer) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.manufacturer[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label for="repair-model-number" class="block text-sm font-semibold text-gray-900 dark:text-white">Model Number *</label><input id="repair-model-number" type="text"${ssrRenderAttr("value", form.value.model_number)} required class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm">`);
      if (errors.value.model_number) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.model_number[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label for="repair-serial-number" class="block text-sm font-semibold text-gray-900 dark:text-white">Serial Number</label><input id="repair-serial-number" type="text"${ssrRenderAttr("value", form.value.serial_number)} class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Not always available — that&#39;s okay.</p>`);
      if (errors.value.serial_number) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.serial_number[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div><label for="repair-urgency" class="block text-sm font-semibold text-gray-900 dark:text-white">Urgency</label><select id="repair-urgency" class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"><option value=""${ssrIncludeBooleanAttr(Array.isArray(form.value.urgency) ? ssrLooseContain(form.value.urgency, "") : ssrLooseEqual(form.value.urgency, "")) ? " selected" : ""}>Standard (3-5 Business Days Once Received)</option><option value="rush"${ssrIncludeBooleanAttr(Array.isArray(form.value.urgency) ? ssrLooseContain(form.value.urgency, "rush") : ssrLooseEqual(form.value.urgency, "rush")) ? " selected" : ""}>Rush Service</option></select>`);
      if (errors.value.urgency) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.urgency[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label for="repair-issue" class="block text-sm font-semibold text-gray-900 dark:text-white">Describe the Issue *</label><textarea id="repair-issue" rows="4" required class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm">${ssrInterpolate(form.value.issue_description)}</textarea>`);
      if (errors.value.issue_description) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.issue_description[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="hidden" aria-hidden="true"><label for="repair-website">Website</label><input id="repair-website" type="text"${ssrRenderAttr("value", form.value.website)} tabindex="-1" autocomplete="off"></div><div><button type="submit"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} class="w-full rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 disabled:cursor-not-allowed disabled:opacity-60">`);
      if (isSubmitting.value) {
        _push(`<span>Sending...</span>`);
      } else {
        _push(`<span>Request a Repair Quote</span>`);
      }
      _push(`</button>`);
      if (errors.value.captcha_token) {
        _push(`<p class="text-red-600 text-sm mt-2">${ssrInterpolate(errors.value.captcha_token[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (successMessage.value) {
        _push(`<p class="mt-4 text-green-600">${ssrInterpolate(successMessage.value)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</form></div></div></section>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/RepairRequestForm.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$6, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Head), null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<title${_scopeId2}>Industrial Repair Services | Colorado Supply &amp; Procurement</title><meta name="description" content="Colorado Supply &amp; Procurement repairs industrial electronics, servo motors, AC &amp; DC motors, and hydraulics &amp; pneumatics for up to 50% less than new — free evaluation, no upfront cost, 2-year warranty."${_scopeId2}>`);
                } else {
                  return [
                    createVNode("title", null, "Industrial Repair Services | Colorado Supply & Procurement"),
                    createVNode("meta", {
                      name: "description",
                      content: "Colorado Supply & Procurement repairs industrial electronics, servo motors, AC & DC motors, and hydraulics & pneumatics for up to 50% less than new — free evaluation, no upfront cost, 2-year warranty."
                    })
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(RepairHero, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$2, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$1, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(Footer, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(unref(Head), null, {
                default: withCtx(() => [
                  createVNode("title", null, "Industrial Repair Services | Colorado Supply & Procurement"),
                  createVNode("meta", {
                    name: "description",
                    content: "Colorado Supply & Procurement repairs industrial electronics, servo motors, AC & DC motors, and hydraulics & pneumatics for up to 50% less than new — free evaluation, no upfront cost, 2-year warranty."
                  })
                ]),
                _: 1
              }),
              createVNode(RepairHero),
              createVNode(_sfc_main$4),
              createVNode(_sfc_main$3),
              createVNode(_sfc_main$2),
              createVNode(_sfc_main$1),
              createVNode(Footer)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/RepairServices/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
