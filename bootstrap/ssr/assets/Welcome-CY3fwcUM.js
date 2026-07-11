import { ref, mergeProps, withCtx, unref, createVNode, createBlock, openBlock, createTextVNode, useSSRContext, useModel, resolveDynamicComponent } from "vue";
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr, ssrRenderTeleport, ssrRenderList, ssrRenderClass, ssrRenderVNode } from "vue/server-renderer";
import { XMarkIcon, WrenchScrewdriverIcon, TruckIcon, Cog6ToothIcon, CubeIcon, BuildingOffice2Icon, UsersIcon, ShieldCheckIcon, ClipboardDocumentCheckIcon, IdentificationIcon, BriefcaseIcon, DocumentTextIcon, TrophyIcon, TagIcon } from "@heroicons/vue/24/outline";
import { _ as _sfc_main$a } from "./Modal-CPfU9EoI.js";
import { a as pipelineMobileImageUrl, p as pipelineImageUrl } from "./pipeline-900-CwP-DKPX.js";
import { CloudArrowUpIcon, LockClosedIcon, ServerIcon } from "@heroicons/vue/20/solid";
import { _ as _sfc_main$b } from "./AppLayout-BeNNp45Q.js";
import { F as Footer } from "./Footer-Dxg1LD3z.js";
import { Head } from "@inertiajs/vue3";
import "./CookieConsentBanner-ByAlkSbo.js";
import "@headlessui/vue";
import "axios";
import "./logo-cleansed-light-B5mBHTsK.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main$9 = {
  __name: "Hero",
  __ssrInlineRender: true,
  props: {
    lightImage: {
      type: String,
      default: pipelineImageUrl
    },
    lightImageSrcset: {
      type: String,
      default: `${pipelineMobileImageUrl} 900w, ${pipelineImageUrl} 1600w`
    },
    darkImage: {
      type: String,
      default: pipelineImageUrl
    },
    darkImageSrcset: {
      type: String,
      default: `${pipelineMobileImageUrl} 900w, ${pipelineImageUrl} 1600w`
    }
  },
  setup(__props) {
    const isModalOpen = ref(false);
    const pdfLoaded = ref(false);
    const closeModal = () => {
      isModalOpen.value = false;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        id: "home",
        class: "relative isolate flex min-h-[88svh] items-center overflow-hidden pb-12 pt-24 sm:min-h-[92svh] sm:pb-16"
      }, _attrs))}><div class="absolute inset-0 -z-10 hidden dark:block"><img${ssrRenderAttr("src", __props.darkImage)}${ssrRenderAttr("srcset", __props.darkImageSrcset)} sizes="100vw" alt="" width="1600" height="1067" fetchpriority="high" loading="eager" decoding="async" class="absolute inset-0 size-full object-cover object-center"><div class="absolute inset-0 bg-black/50"></div></div><div class="absolute inset-0 -z-10 dark:hidden"><img${ssrRenderAttr("src", __props.lightImage)}${ssrRenderAttr("srcset", __props.lightImageSrcset)} sizes="100vw" alt="" width="1600" height="1067" fetchpriority="high" loading="eager" decoding="async" class="absolute inset-0 size-full object-cover object-center"><div class="absolute inset-0 bg-black/30"></div></div><div class="mobile-page-gutter mx-auto w-full max-w-7xl lg:px-8"><div class="mx-auto max-w-2xl text-center"><p class="mb-4 text-base font-extrabold leading-6 text-primary-900 dark:text-primary-200"> Colorado-Based • SAM Registered • CAGE &amp; DUNS Verified </p><h1 class="text-4xl font-bold leading-tight tracking-normal text-gray-950 sm:text-6xl lg:text-7xl dark:text-white"> Reliable Government Supply Chain Solutions </h1><p class="mx-auto mt-6 max-w-2xl text-base leading-7 text-gray-800 sm:mt-8 sm:text-xl sm:leading-8 dark:text-gray-200"> With over 10 years of experience in industrial supply chain management, we deliver dependable products and services to federal, state, and local agencies. Our mission is simple: reliable supply, competitive pricing, and on-time delivery every time. </p><div class="mx-auto mt-8 flex w-full max-w-md flex-col items-stretch justify-center gap-3 sm:mt-10 sm:max-w-none sm:flex-row sm:items-center sm:gap-4"><button class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-primary-700 px-5 py-3 text-base font-semibold text-white shadow-md transition-colors hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 sm:w-auto"> View Capabilities Statement </button><a href="#contact" class="inline-flex min-h-12 w-full items-center justify-center rounded-md px-5 py-3 text-base font-semibold text-gray-950 hover:bg-white/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 sm:w-auto dark:text-white dark:hover:bg-black/20"> Contact Us → </a></div></div></div>`);
      _push(ssrRenderComponent(_sfc_main$a, {
        show: isModalOpen.value,
        "max-width": "2xl",
        onClose: closeModal
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="relative overflow-hidden bg-white dark:bg-gray-900"${_scopeId}><div class="flex min-h-16 items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-800 sm:px-6"${_scopeId}><h2 class="text-lg font-semibold leading-6 text-gray-900 dark:text-white sm:text-xl"${_scopeId}> Capabilities Statement </h2><button class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-md text-gray-600 transition-colors hover:bg-gray-200 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white" aria-label="Close modal"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(XMarkIcon), { class: "h-6 w-6" }, null, _parent2, _scopeId));
            _push2(`</button></div><div class="relative bg-gray-100 dark:bg-gray-800"${_scopeId}>`);
            if (pdfLoaded.value) {
              _push2(`<div class="h-[min(68svh,48rem)] w-full"${_scopeId}><iframe src="/docs/Colorado_Supply_Capabilities_Statement.pdf" class="w-full h-full border-0" title="Capabilities Statement PDF"${_scopeId}></iframe></div>`);
            } else {
              _push2(`<div class="flex h-[min(68svh,48rem)] items-center justify-center"${_scopeId}><div class="text-center"${_scopeId}><div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-700"${_scopeId}></div><p class="mt-4 text-gray-600 dark:text-gray-400"${_scopeId}>Loading PDF...</p></div></div>`);
            }
            _push2(`</div><div class="safe-bottom border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6"${_scopeId}><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"${_scopeId}><p class="text-sm leading-5 text-gray-600 dark:text-gray-300"${_scopeId}> Colorado Supply &amp; Procurement LLC </p><a href="/docs/Colorado_Supply_Capabilities_Statement.pdf" download class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-primary-700 px-4 py-3 text-base font-semibold text-white shadow-sm transition-colors hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 sm:w-auto"${_scopeId}><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"${_scopeId}></path></svg> Download PDF </a></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "relative overflow-hidden bg-white dark:bg-gray-900" }, [
                createVNode("div", { class: "flex min-h-16 items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-800 sm:px-6" }, [
                  createVNode("h2", { class: "text-lg font-semibold leading-6 text-gray-900 dark:text-white sm:text-xl" }, " Capabilities Statement "),
                  createVNode("button", {
                    onClick: closeModal,
                    class: "inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-md text-gray-600 transition-colors hover:bg-gray-200 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white",
                    "aria-label": "Close modal"
                  }, [
                    createVNode(unref(XMarkIcon), { class: "h-6 w-6" })
                  ])
                ]),
                createVNode("div", { class: "relative bg-gray-100 dark:bg-gray-800" }, [
                  pdfLoaded.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "h-[min(68svh,48rem)] w-full"
                  }, [
                    createVNode("iframe", {
                      src: "/docs/Colorado_Supply_Capabilities_Statement.pdf",
                      class: "w-full h-full border-0",
                      title: "Capabilities Statement PDF"
                    })
                  ])) : (openBlock(), createBlock("div", {
                    key: 1,
                    class: "flex h-[min(68svh,48rem)] items-center justify-center"
                  }, [
                    createVNode("div", { class: "text-center" }, [
                      createVNode("div", { class: "inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-700" }),
                      createVNode("p", { class: "mt-4 text-gray-600 dark:text-gray-400" }, "Loading PDF...")
                    ])
                  ]))
                ]),
                createVNode("div", { class: "safe-bottom border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6" }, [
                  createVNode("div", { class: "flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" }, [
                    createVNode("p", { class: "text-sm leading-5 text-gray-600 dark:text-gray-300" }, " Colorado Supply & Procurement LLC "),
                    createVNode("a", {
                      href: "/docs/Colorado_Supply_Capabilities_Statement.pdf",
                      download: "",
                      class: "inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-primary-700 px-4 py-3 text-base font-semibold text-white shadow-sm transition-colors hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 sm:w-auto"
                    }, [
                      (openBlock(), createBlock("svg", {
                        class: "h-4 w-4",
                        fill: "none",
                        viewBox: "0 0 24 24",
                        "stroke-width": "2",
                        stroke: "currentColor"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"
                        })
                      ])),
                      createTextVNode(" Download PDF ")
                    ])
                  ])
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/Hero.vue");
  return _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const aboutImageUrl = "/build/assets/grinding-900-w6ZCeDKB.webp";
const _sfc_main$8 = {
  __name: "About",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        id: "about",
        class: "relative bg-white dark:bg-gray-900 scroll-mt-20"
      }, _attrs))}><div class="mx-auto max-w-7xl lg:flex lg:justify-between lg:px-8 xl:justify-end"><div class="lg:flex lg:w-1/2 lg:shrink lg:grow-0 xl:absolute xl:inset-y-0 xl:right-1/2 xl:w-1/2"><div class="relative h-80 lg:-ml-8 lg:h-auto lg:w-full lg:grow xl:ml-0"><img class="absolute inset-0 size-full bg-gray-50 object-cover dark:bg-gray-800"${ssrRenderAttr("src", unref(aboutImageUrl))} alt="Industrial supplies warehouse" width="900" height="1200" loading="lazy" decoding="async"></div></div><div class="mobile-page-gutter lg:contents"><div class="mx-auto max-w-2xl py-16 sm:py-24 lg:mr-0 lg:ml-8 lg:w-full lg:max-w-lg lg:flex-none xl:w-1/2"><p class="text-base/7 font-semibold text-amber-600 dark:text-amber-400">Who We Are</p><h1 class="mt-2 text-3xl font-semibold leading-10 tracking-normal text-pretty text-gray-900 sm:text-4xl dark:text-white"> About Colorado Supply &amp; Procurement LLC </h1><p class="mt-6 text-lg leading-8 text-gray-700 sm:text-xl dark:text-gray-300"> Colorado-based with nationwide reach, we bring over a decade of industrial supply chain experience, delivering dependable goods and services tailored to the needs of federal, state, and local agencies. </p><div class="mt-10 max-w-xl text-base/7 text-gray-600 lg:max-w-none dark:text-gray-400"><p> We specialize in sourcing and delivering MRO and OEM supplies across categories including fasteners, welding, plumbing, power transmission, fluid power, filtration, and cutting tools. Our broad vendor relationships allow us to provide a wide range of industrial goods and services quickly and reliably. </p><ul role="list" class="mt-8 space-y-8 text-gray-600 dark:text-gray-400"><li class="flex gap-x-3">`);
      _push(ssrRenderComponent(unref(CloudArrowUpIcon), {
        class: "mt-1 size-5 flex-none text-amber-600 dark:text-amber-400",
        "aria-hidden": "true"
      }, null, _parent));
      _push(`<span><strong class="font-semibold text-gray-900 dark:text-white">Nationwide Reach.</strong> Based in Colorado, but trusted by agencies and contractors across the U.S. </span></li><li class="flex gap-x-3">`);
      _push(ssrRenderComponent(unref(LockClosedIcon), {
        class: "mt-1 size-5 flex-none text-amber-600 dark:text-amber-400",
        "aria-hidden": "true"
      }, null, _parent));
      _push(`<span><strong class="font-semibold text-gray-900 dark:text-white">Trusted Experience.</strong> Over 10 years in supply chain management with expertise across multiple industries. </span></li><li class="flex gap-x-3">`);
      _push(ssrRenderComponent(unref(ServerIcon), {
        class: "mt-1 size-5 flex-none text-amber-600 dark:text-amber-400",
        "aria-hidden": "true"
      }, null, _parent));
      _push(`<span><strong class="font-semibold text-gray-900 dark:text-white">Wide Capabilities.</strong> From industrial consumables to CNC machining and procurement support, we deliver complete solutions. </span></li></ul><p class="mt-8"> Founded after more than 10 years in the industrial supply chain industry, Colorado Supply &amp; Procurement LLC was created with a simple mission: <span class="italic">to bridge the gap between large-scale supply capabilities and the personalized service of a local small business.</span> We understand the challenges agencies and contractors face when sourcing critical materials — and we pride ourselves on being the partner who delivers what you need, when you need it. </p></div></div></div></div></div>`);
    };
  }
};
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/About.vue");
  return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const _sfc_main$7 = {
  __name: "Capabilities",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({
        id: "capabilities",
        class: "scroll-mt-20 bg-white py-16 sm:py-24 dark:bg-gray-900"
      }, _attrs))}><div class="mobile-page-gutter mx-auto max-w-7xl lg:px-8"><div class="mx-auto max-w-2xl text-center"><h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">Our Expertise</h2><p class="mt-2 text-3xl font-bold leading-10 tracking-normal text-gray-900 sm:text-4xl dark:text-white"> Capabilities </p><p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300"> Colorado Supply &amp; Procurement LLC delivers dependable products and services across industries, supporting government agencies and contractors nationwide. </p></div><div class="mx-auto mt-12 grid max-w-4xl grid-cols-1 gap-10 sm:grid-cols-2 lg:mt-16 lg:grid-cols-3 lg:gap-12"><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(WrenchScrewdriverIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Industrial Supplies</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> Full range of MRO &amp; OEM products, including fasteners, welding, plumbing, filtration, and cutting tools. </p></div><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(TruckIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Procurement &amp; Sourcing</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> Nationwide vendor network with competitive pricing and reliable sourcing strategies. </p></div><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(Cog6ToothIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Custom Manufacturing</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> CNC machining, fabrication, and industrial electronic repair through a skilled contractor network. </p></div><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(CubeIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Inventory Solutions</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> Stock management, consignment, and vendor-managed inventory to keep your operation running. </p></div><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(BuildingOffice2Icon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Government Contracting</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> Proven experience working with federal, state, and local agencies and their contractors. </p></div><div class="flex flex-col items-start">`);
      _push(ssrRenderComponent(unref(UsersIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Partnership Services</h3><p class="mt-2 text-gray-600 dark:text-gray-400"> Access to a trusted subcontractor network offering specialized skills across industries. </p></div></div><div class="mt-16 text-center sm:mt-20"><h3 class="text-2xl font-bold text-gray-900 dark:text-white">Ready to work with us?</h3><p class="mt-4 text-lg text-gray-600 dark:text-gray-300"> Download our Capabilities Statement or contact us today to discuss how we can support your next project. </p><div class="mx-auto mt-8 flex max-w-md flex-col justify-center gap-3 sm:max-w-none sm:flex-row sm:items-center"><a href="/docs/Colorado_Supply_Capabilities_Statement.pdf" download class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-amber-700 px-5 py-3 text-base font-semibold text-white shadow hover:bg-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 sm:w-auto"> Download Capabilities Statement </a><a href="#contact" class="inline-flex min-h-12 w-full items-center justify-center rounded-md px-5 py-3 text-base font-semibold text-gray-900 hover:bg-gray-100 hover:text-amber-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 sm:w-auto dark:text-white dark:hover:bg-white/10 dark:hover:text-amber-300"> Contact Us → </a></div></div></div></section>`);
    };
  }
};
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/Capabilities.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const _sfc_main$6 = {
  __name: "RepairServicesTeaser",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({
        id: "repair-services-teaser",
        class: "scroll-mt-20 bg-gray-50 py-16 sm:py-24 dark:bg-gray-800"
      }, _attrs))}><div class="mobile-page-gutter mx-auto max-w-7xl lg:px-8"><div class="mx-auto max-w-3xl rounded-2xl bg-white dark:bg-gray-900 p-10 shadow-sm ring-1 ring-gray-900/5 dark:ring-white/10 sm:p-12"><div class="flex flex-col items-center text-center">`);
      _push(ssrRenderComponent(unref(WrenchScrewdriverIcon), { class: "h-10 w-10 text-amber-600 dark:text-amber-400" }, null, _parent));
      _push(`<h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl"> Industrial Equipment Repair Services </h2><p class="mt-4 text-lg text-gray-600 dark:text-gray-300"> We also repair industrial electronics, servo motors, AC &amp; DC motors, and hydraulics &amp; pneumatics — for up to 50% less than buying new, backed by a 2-year warranty and a free, risk-free evaluation. </p><a${ssrRenderAttr("href", _ctx.route("repair-services.index"))} class="mt-8 inline-flex min-h-12 w-full items-center justify-center rounded-md bg-amber-700 px-5 py-3 text-base font-semibold text-white shadow hover:bg-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 sm:w-auto"> Explore Repair Services → </a></div></div></div></section>`);
    };
  }
};
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/RepairServicesTeaser.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const _sfc_main$5 = {
  __name: "Contact",
  __ssrInlineRender: true,
  setup(__props) {
    const form = ref({
      name: "",
      email: "",
      phone: "",
      message: "",
      company: ""
    });
    const successMessage = ref("");
    const errors = ref({});
    const isSubmitting = ref(false);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({
        id: "contact",
        class: "scroll-mt-20 bg-white py-16 sm:py-24 dark:bg-gray-900"
      }, _attrs))}><div class="mobile-page-gutter mx-auto max-w-7xl lg:px-8"><div class="mx-auto max-w-2xl text-center"><h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">Get in Touch</h2><p class="mt-2 text-3xl font-bold leading-10 tracking-normal text-gray-900 sm:text-4xl dark:text-white"> Contact Us </p><p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300"> Colorado Supply &amp; Procurement LLC is ready to support your next contract or project. Reach us directly, or send a quick message using the form. </p></div><div class="mx-auto mt-12 grid max-w-4xl grid-cols-1 gap-12 lg:mt-16 lg:grid-cols-2"><div><h3 class="text-2xl font-bold text-gray-900 dark:text-white">Business Information</h3><dl class="mt-6 space-y-4 text-lg text-gray-600 dark:text-gray-300"><div><dt class="font-semibold text-gray-900 dark:text-white">Phone</dt><dd><a class="inline-flex min-h-12 items-center text-blue-700 underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-amber-600 dark:text-blue-300" href="tel:7194259634">(719) 425-9634</a></dd></div><div><dt class="font-semibold text-gray-900 dark:text-white">Email</dt><dd><a class="inline-flex min-h-12 max-w-full items-center break-all underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-amber-600" href="mailto:Edward@cogovsupply.com">Edward@cogovsupply.com</a></dd></div><div><dt class="font-semibold text-gray-900 dark:text-white">Location</dt><dd>Colorado Springs, Colorado</dd><dd>Serving Nationwide</dd></div><div><dt class="font-semibold text-gray-900 dark:text-white">Registrations</dt><dd> SAM.gov Active | CAGE Code: 15NL2 | EIN: 39-3537490 </dd></div></dl></div><div><h3 class="text-2xl font-bold text-gray-900 dark:text-white">Send a Message</h3><form class="mt-6 space-y-6"><div><label for="name" class="block text-sm font-semibold text-gray-900 dark:text-white">Name</label><input id="name" type="text" autocomplete="name"${ssrRenderAttr("value", form.value.name)} required class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm">`);
      if (errors.value.name) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.name[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label for="email" class="block text-sm font-semibold text-gray-900 dark:text-white">Email</label><input id="email" type="email" autocomplete="email"${ssrRenderAttr("value", form.value.email)} required class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm">`);
      if (errors.value.email) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.email[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label for="phone" class="block text-sm font-semibold text-gray-900 dark:text-white">Phone (optional)</label><input id="phone" type="tel" inputmode="tel" autocomplete="tel"${ssrRenderAttr("value", form.value.phone)} class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm">`);
      if (errors.value.phone) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.phone[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label for="message" class="block text-sm font-semibold text-gray-900 dark:text-white">Message</label><textarea id="message" rows="4" required class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm">${ssrInterpolate(form.value.message)}</textarea>`);
      if (errors.value.message) {
        _push(`<p class="text-red-600 text-sm mt-1">${ssrInterpolate(errors.value.message[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="hidden" aria-hidden="true"><label for="company">Company</label><input id="company" type="text"${ssrRenderAttr("value", form.value.company)} tabindex="-1" autocomplete="off"></div><div><button type="submit"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} class="w-full rounded-md bg-amber-700 px-4 py-3 text-base font-semibold text-white shadow hover:bg-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 disabled:cursor-not-allowed disabled:opacity-60">`);
      if (isSubmitting.value) {
        _push(`<span>Sending...</span>`);
      } else {
        _push(`<span>Send Message</span>`);
      }
      _push(`</button>`);
      if (errors.value.captcha_token) {
        _push(`<p class="text-red-600 text-sm mt-2">${ssrInterpolate(errors.value.captcha_token[0])}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></form>`);
      if (successMessage.value) {
        _push(`<p class="mt-4 text-base font-medium text-green-700 dark:text-green-400" role="status">${ssrInterpolate(successMessage.value)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div></section>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/Contact.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const _sfc_main$4 = {
  __name: "NAICSCodes",
  __ssrInlineRender: true,
  props: {
    "modelValue": { type: Boolean, default: false },
    "modelModifiers": {}
  },
  emits: ["update:modelValue"],
  setup(__props) {
    const isOpen = useModel(__props, "modelValue");
    const naicsCodes = [
      // Primary
      {
        code: "423840",
        description: "Industrial Supplies Merchant Wholesalers",
        isPrimary: true,
        details: "Wholesale distribution of industrial supplies including fasteners, welding equipment, plumbing supplies, and MRO products."
      },
      // Supporting Codes
      {
        code: "423830",
        description: "Industrial Machinery and Equipment Merchant Wholesalers",
        isPrimary: false,
        details: "Distribution of industrial machinery, equipment, and related parts and supplies."
      },
      {
        code: "423710",
        description: "Hardware Merchant Wholesalers",
        isPrimary: false,
        details: "Wholesale of hardware products, hand tools, and related items."
      },
      {
        code: "423720",
        description: "Plumbing and Heating Equipment and Supplies (Hydronics) Merchant Wholesalers",
        isPrimary: false,
        details: "Distribution of plumbing, heating equipment, valves, pipes, fittings, and related supplies."
      },
      {
        code: "332710",
        description: "Machine Shops",
        isPrimary: false,
        details: "CNC machining, custom manufacturing, fabrication, and precision manufacturing services through contractor network."
      },
      {
        code: "423860",
        description: "Transportation Equipment and Supplies (except Motor Vehicle) Merchant Wholesalers",
        isPrimary: false,
        details: "Distribution of transportation-related equipment and supplies."
      },
      {
        code: "423990",
        description: "Other Miscellaneous Durable Goods Merchant Wholesalers",
        isPrimary: false,
        details: "Wholesale of miscellaneous durable goods not elsewhere classified."
      },
      {
        code: "811310",
        description: "Commercial and Industrial Machinery and Equipment (except Automotive and Electronic) Repair and Maintenance",
        isPrimary: false,
        details: "Industrial equipment repair and maintenance services through skilled contractor network."
      },
      {
        code: "423450",
        description: "Medical, Dental, and Hospital Equipment and Supplies Merchant Wholesalers",
        isPrimary: false,
        details: "Distribution of medical and hospital equipment and supplies."
      },
      {
        code: "423730",
        description: "Warm Air Heating and Air-Conditioning Equipment and Supplies Merchant Wholesalers",
        isPrimary: false,
        details: "Wholesale of HVAC equipment and related supplies."
      },
      {
        code: "423810",
        description: "Construction and Mining (except Oil Well) Machinery and Equipment Merchant Wholesalers",
        isPrimary: false,
        details: "Distribution of construction and mining machinery and equipment."
      },
      {
        code: "541330",
        description: "Engineering Services",
        isPrimary: false,
        details: "Engineering consultation and support services through professional network."
      },
      {
        code: "541614",
        description: "Process, Physical Distribution, and Logistics Consulting Services",
        isPrimary: false,
        details: "Supply chain, logistics, and procurement consulting services."
      },
      {
        code: "423820",
        description: "Farm and Garden Machinery and Equipment Merchant Wholesalers",
        isPrimary: false,
        details: "Distribution of agricultural and garden machinery and equipment."
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        if (isOpen.value) {
          _push2(`<div class="fixed inset-0 z-50 overflow-y-auto"><div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div><div class="safe-y flex min-h-full items-end justify-center p-4 sm:items-center">`);
          if (isOpen.value) {
            _push2(`<div class="relative w-full max-w-5xl overflow-hidden rounded-lg bg-white shadow-2xl dark:bg-gray-900"><div class="sticky top-0 z-10 flex min-h-16 items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-800 sm:px-6"><div><h2 class="text-xl font-semibold text-gray-900 dark:text-white"> NAICS Codes </h2><p class="mt-1 text-sm text-gray-600 dark:text-gray-400"> North American Industry Classification System codes we serve </p></div><button class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-md text-gray-600 transition-colors hover:bg-gray-200 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white" aria-label="Close modal">`);
            _push2(ssrRenderComponent(unref(XMarkIcon), { class: "h-6 w-6" }, null, _parent));
            _push2(`</button></div><div class="max-h-[68svh] overflow-y-auto p-4 sm:p-6"><div class="space-y-4"><!--[-->`);
            ssrRenderList(naicsCodes, (naics) => {
              _push2(`<div class="${ssrRenderClass([naics.isPrimary ? "bg-amber-50 dark:bg-amber-900/20 border-amber-500 dark:border-amber-500" : "bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-400", "rounded-lg border p-4 transition-all sm:p-5"])}"><div class="flex flex-col items-start gap-3 sm:flex-row sm:gap-4"><div class="flex-shrink-0"><span class="${ssrRenderClass([naics.isPrimary ? "text-amber-800 bg-amber-200 dark:text-amber-200 dark:bg-amber-800" : "text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30", "inline-flex items-center justify-center px-3 py-1 text-sm font-semibold rounded-md"])}">${ssrInterpolate(naics.code)}</span>`);
              if (naics.isPrimary) {
                _push2(`<div class="mt-2"><span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-amber-800 bg-amber-200 dark:text-amber-200 dark:bg-amber-800 rounded"> PRIMARY </span></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><div class="flex-1 min-w-0"><h3 class="text-base font-semibold text-gray-900 dark:text-white">${ssrInterpolate(naics.description)}</h3><p class="mt-2 text-sm text-gray-600 dark:text-gray-400">${ssrInterpolate(naics.details)}</p></div></div></div>`);
            });
            _push2(`<!--]--></div></div><div class="safe-bottom sticky bottom-0 border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm text-gray-600 dark:text-gray-400"> Total: <span class="font-semibold text-gray-900 dark:text-white">${ssrInterpolate(naicsCodes.length)}</span> NAICS Codes <span class="ml-2 text-amber-600 dark:text-amber-400">(1 Primary, ${ssrInterpolate(naicsCodes.length - 1)} Supporting)</span></p><button class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-gray-200 px-4 py-3 text-base font-semibold text-gray-900 transition-colors hover:bg-gray-300 sm:w-auto dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600"> Close </button></div></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/NAICSCodes.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const _sfc_main$3 = {
  __name: "PSCCodes",
  __ssrInlineRender: true,
  props: {
    "modelValue": { type: Boolean, default: false },
    "modelModifiers": {}
  },
  emits: ["update:modelValue"],
  setup(__props) {
    const isOpen = useModel(__props, "modelValue");
    const pscCodes = [
      // Fasteners & Hardware
      { code: "5305", description: "Screws" },
      { code: "5306", description: "Bolts" },
      { code: "5307", description: "Studs" },
      { code: "5310", description: "Nuts and Washers" },
      { code: "5315", description: "Nails, Machine Keys, and Pins" },
      { code: "5320", description: "Rivets" },
      { code: "5325", description: "Fastening Devices" },
      // Gaskets, Packing & Sealing
      { code: "5330", description: "Packing and Gasket Materials" },
      { code: "5331", description: "O-Rings" },
      { code: "5335", description: "Metal Screening" },
      // Welding Equipment
      { code: "3431", description: "Electric Arc Welding Equipment" },
      { code: "3432", description: "Welding Positioners and Manipulators" },
      { code: "3433", description: "Gas Welding, Heat Cutting, and Metalizing Equipment" },
      { code: "3439", description: "Miscellaneous Welding Equipment" },
      // Plumbing & Piping
      { code: "4710", description: "Pipe and Tube" },
      { code: "4720", description: "Hose and Tubing, Flexible" },
      { code: "4730", description: "Hose, Pipe, Tube, Lubrication, and Railing Fittings" },
      { code: "4820", description: "Valves, Nonpowered" },
      { code: "4810", description: "Valves, Powered" },
      // Tools & Machinery
      { code: "5130", description: "Hand Tools, Power Driven" },
      { code: "5133", description: "Drill Bits, Counterbores, and Countersinks" },
      { code: "5136", description: "Taps, Dies, and Collets" },
      { code: "5140", description: "Hand Tools, Edged, Nonpowered" },
      { code: "5180", description: "Sets, Kits, and Outfits of Hand Tools" },
      { code: "3405", description: "Sawing Machines" },
      { code: "3416", description: "Drilling and Boring Machines" },
      { code: "3417", description: "Grinding Machines" },
      { code: "3418", description: "Cutting and Forming Machines" },
      // Abrasives & Cutting Tools
      { code: "5345", description: "Disks and Stones, Abrasive" },
      { code: "5350", description: "Abrasive Materials" },
      { code: "3460", description: "Machine Tools, Portable" },
      // Bearings & Power Transmission
      { code: "3110", description: "Bearings, Antifriction, Unmounted" },
      { code: "3120", description: "Bearings, Plain, Unmounted" },
      { code: "3010", description: "Torque Converters and Speed Changers" },
      { code: "3020", description: "Gears, Pulleys, Sprockets, and Transmission Chain" },
      // Electrical & Electronics
      { code: "5961", description: "Semiconductors and Hardware Devices" },
      { code: "5962", description: "Electronic Microcircuits, Digital" },
      { code: "5975", description: "Electrical Hardware and Supplies" },
      { code: "5977", description: "Electrical Contact Brushes and Electrodes" },
      { code: "5995", description: "Cable, Cord, and Wire Assemblies: Communication Equipment" },
      { code: "5998", description: "Electrical and Electronic Assemblies, Boards, Cards, and Associated Hardware" },
      { code: "6150", description: "Miscellaneous Electric Power and Distribution Equipment" },
      // Pumps & Compressors
      { code: "4320", description: "Power and Hand Pumps" },
      { code: "4310", description: "Compressors and Vacuum Pumps" },
      // Lubricants & Chemicals
      { code: "9150", description: "Oils and Greases: Cutting, Lubricating, and Hydraulic" },
      { code: "6850", description: "Miscellaneous Chemical Specialties" },
      { code: "8030", description: "Preservative and Sealing Compounds" },
      // Filters & Strainers
      { code: "4330", description: "Centrifugals, Separators, and Pressure and Vacuum Filters" },
      { code: "4730", description: "Strainers and In-Line Filter Elements" },
      // Motors & Generators
      { code: "6105", description: "Motors, Electrical" },
      { code: "6115", description: "Generators and Generator Sets, Electrical" },
      { code: "6116", description: "Fuel Cell Power Units, Components and Accessories" },
      // Safety Equipment
      { code: "4240", description: "Safety and Rescue Equipment" },
      { code: "8415", description: "Clothing, Special Purpose" },
      { code: "8465", description: "Individual Safety and Protection Equipment" },
      // Measuring & Testing
      { code: "6625", description: "Electrical and Electronic Properties Measuring and Testing Instruments" },
      { code: "6630", description: "Chemical Analysis Instruments" },
      { code: "6635", description: "Physical Properties Testing and Inspection Equipment" },
      { code: "5210", description: "Measuring Tools, Craftsmen" },
      // Maintenance & Repair
      { code: "4940", description: "Maintenance and Repair Shop Equipment" },
      { code: "3439", description: "Miscellaneous Maintenance and Repair Shop Specialized Equipment" },
      // Materials & Raw Stock
      { code: "9505", description: "Wire, Nonelectrical" },
      { code: "9515", description: "Bars and Rods" },
      { code: "9520", description: "Structural Shapes" },
      { code: "9525", description: "Wire Cloth and Other Screen" },
      { code: "9530", description: "Nonmetallic Fabricated Materials" },
      { code: "9535", description: "Rubber Fabricated Materials" }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        if (isOpen.value) {
          _push2(`<div class="fixed inset-0 z-50 overflow-y-auto"><div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div><div class="safe-y flex min-h-full items-end justify-center p-4 sm:items-center">`);
          if (isOpen.value) {
            _push2(`<div class="relative w-full max-w-5xl overflow-hidden rounded-lg bg-white shadow-2xl dark:bg-gray-900"><div class="sticky top-0 z-10 flex min-h-16 items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-800 sm:px-6"><div><h2 class="text-xl font-semibold text-gray-900 dark:text-white"> Product Service Codes (PSC) </h2><p class="mt-1 text-sm text-gray-600 dark:text-gray-400"> Complete list of PSC codes we serve </p></div><button class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-md text-gray-600 transition-colors hover:bg-gray-200 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white" aria-label="Close modal">`);
            _push2(ssrRenderComponent(unref(XMarkIcon), { class: "h-6 w-6" }, null, _parent));
            _push2(`</button></div><div class="max-h-[68svh] overflow-y-auto p-4 sm:p-6"><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><!--[-->`);
            ssrRenderList(pscCodes, (psc) => {
              _push2(`<div class="flex gap-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-amber-500 dark:hover:border-amber-500 transition-colors"><div class="flex-shrink-0"><span class="inline-flex items-center justify-center px-3 py-1 text-sm font-semibold text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30 rounded-md">${ssrInterpolate(psc.code)}</span></div><div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white">${ssrInterpolate(psc.description)}</p></div></div>`);
            });
            _push2(`<!--]--></div></div><div class="safe-bottom sticky bottom-0 border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm text-gray-600 dark:text-gray-400"> Total: <span class="font-semibold text-gray-900 dark:text-white">${ssrInterpolate(pscCodes.length)}</span> PSC Codes </p><button class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-gray-200 px-4 py-3 text-base font-semibold text-gray-900 transition-colors hover:bg-gray-300 sm:w-auto dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600"> Close </button></div></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/PSCCodes.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const complianceImageUrl = "/build/assets/compliance-bg-1400-DS7FZsym.webp";
const _sfc_main$2 = {
  __name: "Compliance",
  __ssrInlineRender: true,
  setup(__props) {
    const showNAICSModal = ref(false);
    const showPSCModal = ref(false);
    const cards = [
      {
        name: "CAGE Code",
        description: "15NL2",
        icon: ShieldCheckIcon
      },
      {
        name: "SAM.gov Status",
        description: "Active Registration",
        icon: ClipboardDocumentCheckIcon
      },
      {
        name: "EIN",
        description: "39-3537490",
        icon: IdentificationIcon
      },
      {
        name: "Business Type",
        description: "Single-Member LLC | Small Business Concern",
        icon: BriefcaseIcon
      },
      {
        name: "NAICS (Primary & Supporting)",
        description: "423840 – Industrial Supplies Merchant Wholesalers (Primary)\n423830 – Industrial Machinery & Equipment Merchant Wholesalers\n423710 – Hardware Merchant Wholesalers\n423720 – Plumbing & Heating Equipment & Supplies Wholesalers\n332710 – Machine Shops (CNC)",
        icon: DocumentTextIcon,
        hasModal: true,
        modalType: "naics"
      },
      {
        name: "Experience",
        description: "14+ years in supply chain & procurement",
        icon: TrophyIcon
      },
      {
        name: "Top PSC Codes",
        description: "5305 – Screws • 5306 – Bolts • 5310 – Nuts & Washers • 5330 – Packing & Gaskets\n3431 – Electric Arc Welding Equip • 3433 – Gas Welding/Heat Cutting Equip\n4730 – Hose/Pipe/Tube/Fittings • 4820 – Valves, Nonpowered",
        icon: TagIcon,
        hasModal: true,
        modalType: "psc"
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        id: "credentials",
        class: "relative isolate overflow-hidden bg-white py-16 sm:py-24 dark:bg-gray-900"
      }, _attrs))}><img${ssrRenderAttr("src", unref(complianceImageUrl))} alt="" width="1400" height="1050" loading="lazy" decoding="async" class="absolute inset-0 -z-10 size-full object-cover object-right opacity-10 md:object-center dark:hidden"><img${ssrRenderAttr("src", unref(complianceImageUrl))} alt="" width="1400" height="1050" loading="lazy" decoding="async" class="absolute inset-0 -z-10 size-full object-cover object-right opacity-45 not-dark:hidden md:object-center"><div class="absolute inset-0 -z-10 hidden bg-gray-950/65 dark:block"></div><div class="mobile-page-gutter mx-auto max-w-7xl lg:px-8"><div class="mx-auto max-w-2xl lg:mx-0"><h2 class="text-3xl font-semibold leading-10 tracking-normal text-gray-900 sm:text-5xl dark:text-white"> Credentials &amp; Compliance </h2><p class="mt-8 text-lg font-medium text-pretty text-gray-600 sm:text-xl/8 dark:text-gray-400"> Fully registered and compliant to work with federal, state, and local government agencies nationwide. </p></div><div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-6 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-3 lg:gap-8"><!--[-->`);
      ssrRenderList(cards, (card) => {
        _push(`<div class="flex flex-col gap-y-4 rounded-lg bg-white/90 p-5 shadow-sm ring-1 ring-gray-900/10 backdrop-blur-md sm:p-6 dark:bg-gray-950/85 dark:ring-white/15"><div class="flex gap-x-4">`);
        ssrRenderVNode(_push, createVNode(resolveDynamicComponent(card.icon), {
          class: "h-7 w-7 flex-none text-amber-600 dark:text-amber-400",
          "aria-hidden": "true"
        }, null), _parent);
        _push(`<div class="text-base/7"><h3 class="font-semibold text-gray-900 dark:text-white">${ssrInterpolate(card.name)}</h3><p class="mt-2 text-gray-700 dark:text-gray-200 whitespace-pre-line">${ssrInterpolate(card.description)}</p></div></div>`);
        if (card.hasModal) {
          _push(`<div class="mt-2"><button class="inline-flex min-h-12 items-center rounded-md px-3 py-2 text-base font-semibold text-amber-700 transition-colors hover:bg-amber-50 hover:text-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-600 dark:text-amber-300 dark:hover:bg-white/10 dark:hover:text-amber-200"> View all codes → </button></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      });
      _push(`<!--]--></div></div>`);
      _push(ssrRenderComponent(_sfc_main$4, {
        modelValue: showNAICSModal.value,
        "onUpdate:modelValue": ($event) => showNAICSModal.value = $event
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$3, {
        modelValue: showPSCModal.value,
        "onUpdate:modelValue": ($event) => showPSCModal.value = $event
      }, null, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/Compliance.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const aerospaceImageUrl = "/build/assets/aerospace-6eD9rQ9B.webp";
const constructionImageUrl = "/build/assets/construction-site-C3jnF-2n.webp";
const foodProcessingImageUrl = "/build/assets/food-processing-D3fITbFN.webp";
const manufacturingImageUrl = "/build/assets/manufacturing-BwW-KBCn.webp";
const automotiveImageUrl = "/build/assets/automotive-CR5sa78h.webp";
const energyImageUrl = "/build/assets/energy-DutLTZ5b.webp";
const _sfc_main$1 = {
  __name: "IndustriesServed",
  __ssrInlineRender: true,
  setup(__props) {
    const industries = [
      {
        name: "Aerospace & Defense",
        description: "Supplying precision components, fasteners, and specialized materials for aerospace and defense projects.",
        image: aerospaceImageUrl,
        width: 768,
        height: 512
      },
      {
        name: "Construction & Infrastructure",
        description: "Providing tools, safety gear, and materials for government and commercial construction projects.",
        image: constructionImageUrl,
        width: 768,
        height: 576
      },
      {
        name: "Food Processing",
        description: "Specialized equipment, MRO supplies, and safety products tailored for food production environments.",
        image: foodProcessingImageUrl,
        width: 768,
        height: 576
      },
      {
        name: "Manufacturing & Industrial",
        description: "Wide range of OEM and MRO solutions for diverse industrial manufacturing needs.",
        image: manufacturingImageUrl,
        width: 768,
        height: 576
      },
      {
        name: "Automotive & Repair",
        description: "Fasteners, tools, and repair shop essentials for automotive and fleet maintenance operations.",
        image: automotiveImageUrl,
        width: 768,
        height: 512
      },
      {
        name: "Energy & Utilities",
        description: "Supplying fluid power, welding, and transmission solutions for energy and utility sectors.",
        image: energyImageUrl,
        width: 768,
        height: 512
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({
        id: "industries",
        class: "relative isolate overflow-hidden bg-white py-16 sm:py-24 dark:bg-gray-900"
      }, _attrs))}><div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true"><div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[72.1875rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-amber-400 to-indigo-600 opacity-20 sm:left-[calc(50%-30rem)] sm:w-[90rem]"></div></div><div class="mobile-page-gutter mx-auto max-w-7xl lg:px-8"><div class="mx-auto max-w-2xl text-center"><h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">Industries We Serve</h2><p class="mt-2 text-3xl font-bold leading-10 tracking-normal text-gray-900 sm:text-4xl dark:text-white"> Broad Experience, Proven Reliability </p><p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300"> Our expertise spans multiple sectors, ensuring dependable procurement and supply solutions for diverse government and commercial needs. </p></div><div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 lg:max-w-none"><!--[-->`);
      ssrRenderList(industries, (industry) => {
        _push(`<div class="overflow-hidden rounded-xl shadow bg-white dark:bg-gray-800"><img${ssrRenderAttr("src", industry.image)} alt="" class="h-48 w-full object-cover"${ssrRenderAttr("width", industry.width)}${ssrRenderAttr("height", industry.height)} loading="lazy" decoding="async"><div class="p-6"><h3 class="text-xl font-semibold text-gray-900 dark:text-white">${ssrInterpolate(industry.name)}</h3><p class="mt-3 text-gray-600 dark:text-gray-400">${ssrInterpolate(industry.description)}</p></div></div>`);
      });
      _push(`<!--]--></div></div></section>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/FrontEnd/IndustriesServed.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "Welcome",
  __ssrInlineRender: true,
  props: {
    appName: String
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$b, mergeProps({ appName: __props.appName }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Head), null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<title${_scopeId2}>Colorado Supply &amp; Procurement | Trusted Industrial Supplier for Government Contracts</title><meta name="description" content="Colorado Supply &amp; Procurement provides reliable industrial supply chain services to federal, state, and local agencies. Over 10 years of experience delivering quality products, competitive pricing, and on-time performance."${_scopeId2}><meta name="author" content="Colorado Supply &amp; Procurement LLC"${_scopeId2}><meta property="og:type" content="website"${_scopeId2}><meta property="og:url" content="https://cogovsupply.com/"${_scopeId2}><meta property="og:title" content="Colorado Supply &amp; Procurement | Trusted Industrial Supplier for Government Contracts"${_scopeId2}><meta property="og:description" content="Reliable industrial supply chain partner for federal, state, and local government agencies. Over 10 years of experience, competitive pricing, and on-time delivery."${_scopeId2}><meta property="og:image" content="https://cogovsupply.com/images/og-image.jpg"${_scopeId2}><meta name="twitter:card" content="summary_large_image"${_scopeId2}><meta name="twitter:url" content="https://cogovsupply.com/"${_scopeId2}><meta name="twitter:title" content="Colorado Supply &amp; Procurement | Trusted Government Supplier"${_scopeId2}><meta name="twitter:description" content="Reliable government-focused procurement and industrial supply chain services. Experienced, dependable, on-time."${_scopeId2}><meta name="twitter:image" content="https://cogovsupply.com/images/og-image.jpg"${_scopeId2}><link rel="icon" href="/favicon.ico" type="image/x-icon"${_scopeId2}><link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png"${_scopeId2}><link rel="manifest" href="/site.webmanifest"${_scopeId2}>`);
                } else {
                  return [
                    createVNode("title", null, "Colorado Supply & Procurement | Trusted Industrial Supplier for Government Contracts"),
                    createVNode("meta", {
                      name: "description",
                      content: "Colorado Supply & Procurement provides reliable industrial supply chain services to federal, state, and local agencies. Over 10 years of experience delivering quality products, competitive pricing, and on-time performance."
                    }),
                    createVNode("meta", {
                      name: "author",
                      content: "Colorado Supply & Procurement LLC"
                    }),
                    createVNode("meta", {
                      property: "og:type",
                      content: "website"
                    }),
                    createVNode("meta", {
                      property: "og:url",
                      content: "https://cogovsupply.com/"
                    }),
                    createVNode("meta", {
                      property: "og:title",
                      content: "Colorado Supply & Procurement | Trusted Industrial Supplier for Government Contracts"
                    }),
                    createVNode("meta", {
                      property: "og:description",
                      content: "Reliable industrial supply chain partner for federal, state, and local government agencies. Over 10 years of experience, competitive pricing, and on-time delivery."
                    }),
                    createVNode("meta", {
                      property: "og:image",
                      content: "https://cogovsupply.com/images/og-image.jpg"
                    }),
                    createVNode("meta", {
                      name: "twitter:card",
                      content: "summary_large_image"
                    }),
                    createVNode("meta", {
                      name: "twitter:url",
                      content: "https://cogovsupply.com/"
                    }),
                    createVNode("meta", {
                      name: "twitter:title",
                      content: "Colorado Supply & Procurement | Trusted Government Supplier"
                    }),
                    createVNode("meta", {
                      name: "twitter:description",
                      content: "Reliable government-focused procurement and industrial supply chain services. Experienced, dependable, on-time."
                    }),
                    createVNode("meta", {
                      name: "twitter:image",
                      content: "https://cogovsupply.com/images/og-image.jpg"
                    }),
                    createVNode("link", {
                      rel: "icon",
                      href: "/favicon.ico",
                      type: "image/x-icon"
                    }),
                    createVNode("link", {
                      rel: "apple-touch-icon",
                      sizes: "180x180",
                      href: "/apple-touch-icon.png"
                    }),
                    createVNode("link", {
                      rel: "manifest",
                      href: "/site.webmanifest"
                    })
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$9, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$8, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$7, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$6, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$5, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$2, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$1, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(Footer, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(unref(Head), null, {
                default: withCtx(() => [
                  createVNode("title", null, "Colorado Supply & Procurement | Trusted Industrial Supplier for Government Contracts"),
                  createVNode("meta", {
                    name: "description",
                    content: "Colorado Supply & Procurement provides reliable industrial supply chain services to federal, state, and local agencies. Over 10 years of experience delivering quality products, competitive pricing, and on-time performance."
                  }),
                  createVNode("meta", {
                    name: "author",
                    content: "Colorado Supply & Procurement LLC"
                  }),
                  createVNode("meta", {
                    property: "og:type",
                    content: "website"
                  }),
                  createVNode("meta", {
                    property: "og:url",
                    content: "https://cogovsupply.com/"
                  }),
                  createVNode("meta", {
                    property: "og:title",
                    content: "Colorado Supply & Procurement | Trusted Industrial Supplier for Government Contracts"
                  }),
                  createVNode("meta", {
                    property: "og:description",
                    content: "Reliable industrial supply chain partner for federal, state, and local government agencies. Over 10 years of experience, competitive pricing, and on-time delivery."
                  }),
                  createVNode("meta", {
                    property: "og:image",
                    content: "https://cogovsupply.com/images/og-image.jpg"
                  }),
                  createVNode("meta", {
                    name: "twitter:card",
                    content: "summary_large_image"
                  }),
                  createVNode("meta", {
                    name: "twitter:url",
                    content: "https://cogovsupply.com/"
                  }),
                  createVNode("meta", {
                    name: "twitter:title",
                    content: "Colorado Supply & Procurement | Trusted Government Supplier"
                  }),
                  createVNode("meta", {
                    name: "twitter:description",
                    content: "Reliable government-focused procurement and industrial supply chain services. Experienced, dependable, on-time."
                  }),
                  createVNode("meta", {
                    name: "twitter:image",
                    content: "https://cogovsupply.com/images/og-image.jpg"
                  }),
                  createVNode("link", {
                    rel: "icon",
                    href: "/favicon.ico",
                    type: "image/x-icon"
                  }),
                  createVNode("link", {
                    rel: "apple-touch-icon",
                    sizes: "180x180",
                    href: "/apple-touch-icon.png"
                  }),
                  createVNode("link", {
                    rel: "manifest",
                    href: "/site.webmanifest"
                  })
                ]),
                _: 1
              }),
              createVNode(_sfc_main$9),
              createVNode(_sfc_main$8),
              createVNode(_sfc_main$7),
              createVNode(_sfc_main$6),
              createVNode(_sfc_main$5),
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Welcome.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
