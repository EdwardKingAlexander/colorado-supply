import { computed, reactive, ref, onMounted, unref, withCtx, createTextVNode, createBlock, openBlock, createVNode, withModifiers, withDirectives, vModelText, createCommentVNode, vModelCheckbox, Fragment, renderList, toDisplayString, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderList } from "vue/server-renderer";
import axios from "axios";
import { _ as _sfc_main$1 } from "./AuthenticatedLayout-BXbec8wQ.js";
import { _ as _sfc_main$4 } from "./InputError-C5XExbFq.js";
import { _ as _sfc_main$2, a as _sfc_main$3 } from "./TextInput-CcK4WCIH.js";
import { P as PrimaryButton } from "./PrimaryButton-qTs2i3In.js";
import { router, Head, Link } from "@inertiajs/vue3";
import { u as useCartStore } from "./useCartStore-OHIRORWN.js";
import "./ApplicationLogo-B2173abF.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
import "@headlessui/vue";
const _sfc_main = {
  __name: "Checkout",
  __ssrInlineRender: true,
  props: {
    locations: {
      type: Array,
      default: () => []
    },
    contact: {
      type: Object,
      default: () => ({})
    }
  },
  setup(__props) {
    const cartStore = useCartStore();
    const props = __props;
    const locations = computed(() => props.locations ?? []);
    const hasItems = computed(() => cartStore.items.length > 0);
    const currencyFormatter = new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD"
    });
    const formatCurrency = (amount) => currencyFormatter.format(Number(amount) || 0);
    const totalDisplay = computed(() => formatCurrency(cartStore.total.value));
    const locationNameById = computed(
      () => locations.value.reduce(
        (acc, location) => {
          acc[location.id] = location.name;
          return acc;
        },
        { 0: "Main Store" }
      )
    );
    const groupedItems = computed(() => {
      const groups = {};
      cartStore.items.forEach((item) => {
        const locationId = item.location_id || 0;
        if (!groups[locationId]) {
          groups[locationId] = [];
        }
        groups[locationId].push(item);
      });
      return groups;
    });
    const form = reactive({
      contact_name: props.contact?.name ?? "",
      contact_email: props.contact?.email ?? "",
      contact_phone: "",
      company_name: "",
      po_number: "",
      job_number: "",
      notes: "",
      billing_address: {
        line1: "",
        line2: "",
        city: "",
        state: "",
        postal_code: "",
        country: "US"
      },
      shipping_same_as_billing: true,
      shipping_address: {
        line1: "",
        line2: "",
        city: "",
        state: "",
        postal_code: "",
        country: "US"
      }
    });
    const errors = ref({});
    const submitting = ref(false);
    const fieldError = (path) => errors.value[path]?.[0];
    onMounted(() => {
      if (!hasItems.value) {
        router.visit(route("store.cart"));
      }
    });
    const submit = async () => {
      if (submitting.value || !hasItems.value) {
        return;
      }
      submitting.value = true;
      errors.value = {};
      const payload = {
        items: cartStore.items.map((item) => ({
          id: item.id,
          product_id: item.productId ?? item.id ?? null,
          name: item.name,
          quantity: item.quantity,
          price: item.price,
          slug: item.slug,
          location_id: item.location_id ?? null
        })),
        contact_name: form.contact_name,
        contact_email: form.contact_email,
        contact_phone: form.contact_phone || null,
        company_name: form.company_name || null,
        po_number: form.po_number || null,
        job_number: form.job_number || null,
        notes: form.notes || null,
        billing_address: form.billing_address,
        shipping_same_as_billing: form.shipping_same_as_billing,
        shipping_address: form.shipping_same_as_billing ? null : form.shipping_address
      };
      try {
        const response = await axios.post("/api/v1/store/checkout", payload);
        cartStore.clearCart();
        router.visit(route("store.checkout.pay", { order: response.data.order.id }));
      } catch (error) {
        if (error.response?.status === 422) {
          errors.value = error.response.data.errors ?? {};
        } else {
          console.error("Failed to create order", error);
        }
      } finally {
        submitting.value = false;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Checkout" }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-gray-50 min-h-screen"${_scopeId}><div class="mobile-page-gutter mx-auto max-w-5xl space-y-6 py-6 sm:py-8 lg:px-8"${_scopeId}><div${_scopeId}><p class="text-sm text-gray-500 uppercase tracking-wide"${_scopeId}>Checkout</p><h1 class="text-2xl font-semibold text-gray-900"${_scopeId}>Shipping &amp; Billing Details</h1></div><div class="grid grid-cols-1 lg:grid-cols-3 gap-6"${_scopeId}><form class="checkout-form space-y-6 lg:col-span-2"${_scopeId}><div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6"${_scopeId}><h2 class="text-lg font-semibold text-gray-900"${_scopeId}>Contact Information</h2><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"${_scopeId}><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "contact_name",
              value: "Full Name"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "contact_name",
              modelValue: form.contact_name,
              "onUpdate:modelValue": ($event) => form.contact_name = $event,
              class: "mt-2 block w-full",
              required: "",
              autocomplete: "name"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("contact_name")
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "contact_email",
              value: "Email"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "contact_email",
              type: "email",
              modelValue: form.contact_email,
              "onUpdate:modelValue": ($event) => form.contact_email = $event,
              class: "mt-2 block w-full",
              required: "",
              autocomplete: "email"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("contact_email")
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "contact_phone",
              value: "Phone (optional)"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "contact_phone",
              type: "tel",
              inputmode: "tel",
              autocomplete: "tel",
              modelValue: form.contact_phone,
              "onUpdate:modelValue": ($event) => form.contact_phone = $event,
              class: "mt-2 block w-full"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("contact_phone")
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "company_name",
              value: "Company (optional)"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "company_name",
              modelValue: form.company_name,
              "onUpdate:modelValue": ($event) => form.company_name = $event,
              class: "mt-2 block w-full",
              autocomplete: "organization"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("company_name")
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "po_number",
              value: "PO Number (optional)"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "po_number",
              modelValue: form.po_number,
              "onUpdate:modelValue": ($event) => form.po_number = $event,
              class: "mt-1 block w-full"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("po_number")
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "job_number",
              value: "Job Number (optional)"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "job_number",
              modelValue: form.job_number,
              "onUpdate:modelValue": ($event) => form.job_number = $event,
              class: "mt-1 block w-full"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("job_number")
            }, null, _parent2, _scopeId));
            _push2(`</div></div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "notes",
              value: "Order Notes (optional)"
            }, null, _parent2, _scopeId));
            _push2(`<textarea id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"${_scopeId}>${ssrInterpolate(form.notes)}</textarea>`);
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("notes")
            }, null, _parent2, _scopeId));
            _push2(`</div></div><div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6"${_scopeId}><h2 class="text-lg font-semibold text-gray-900"${_scopeId}>Billing Address</h2><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"${_scopeId}><div class="sm:col-span-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "billing_line1",
              value: "Address Line 1"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "billing_line1",
              modelValue: form.billing_address.line1,
              "onUpdate:modelValue": ($event) => form.billing_address.line1 = $event,
              class: "mt-1 block w-full",
              required: ""
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("billing_address.line1")
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="sm:col-span-2"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "billing_line2",
              value: "Address Line 2 (optional)"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "billing_line2",
              modelValue: form.billing_address.line2,
              "onUpdate:modelValue": ($event) => form.billing_address.line2 = $event,
              class: "mt-1 block w-full"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("billing_address.line2")
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "billing_city",
              value: "City"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "billing_city",
              modelValue: form.billing_address.city,
              "onUpdate:modelValue": ($event) => form.billing_address.city = $event,
              class: "mt-1 block w-full",
              required: ""
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("billing_address.city")
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "billing_state",
              value: "State"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "billing_state",
              modelValue: form.billing_address.state,
              "onUpdate:modelValue": ($event) => form.billing_address.state = $event,
              class: "mt-1 block w-full",
              required: ""
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("billing_address.state")
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "billing_postal_code",
              value: "Postal Code"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "billing_postal_code",
              modelValue: form.billing_address.postal_code,
              "onUpdate:modelValue": ($event) => form.billing_address.postal_code = $event,
              class: "mt-1 block w-full",
              required: ""
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("billing_address.postal_code")
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              for: "billing_country",
              value: "Country (2-letter code)"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              id: "billing_country",
              modelValue: form.billing_address.country,
              "onUpdate:modelValue": ($event) => form.billing_address.country = $event,
              maxlength: "2",
              class: "mt-1 block w-full uppercase",
              required: ""
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, {
              class: "mt-1",
              message: fieldError("billing_address.country")
            }, null, _parent2, _scopeId));
            _push2(`</div></div></div><div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6"${_scopeId}><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"${_scopeId}><h2 class="text-lg font-semibold text-gray-900"${_scopeId}>Shipping Address</h2><label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-md px-2 text-base text-gray-700 hover:bg-gray-50 focus-within:ring-2 focus-within:ring-indigo-500"${_scopeId}><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(form.shipping_same_as_billing) ? ssrLooseContain(form.shipping_same_as_billing, null) : form.shipping_same_as_billing) ? " checked" : ""} class="h-5 w-5 rounded border-gray-300"${_scopeId}> Same as billing address </label></div>`);
            if (!form.shipping_same_as_billing) {
              _push2(`<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"${_scopeId}><div class="sm:col-span-2"${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$2, {
                for: "shipping_line1",
                value: "Address Line 1"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$3, {
                id: "shipping_line1",
                modelValue: form.shipping_address.line1,
                "onUpdate:modelValue": ($event) => form.shipping_address.line1 = $event,
                class: "mt-1 block w-full"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$4, {
                class: "mt-1",
                message: fieldError("shipping_address.line1")
              }, null, _parent2, _scopeId));
              _push2(`</div><div class="sm:col-span-2"${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$2, {
                for: "shipping_line2",
                value: "Address Line 2 (optional)"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$3, {
                id: "shipping_line2",
                modelValue: form.shipping_address.line2,
                "onUpdate:modelValue": ($event) => form.shipping_address.line2 = $event,
                class: "mt-1 block w-full"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$4, {
                class: "mt-1",
                message: fieldError("shipping_address.line2")
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$2, {
                for: "shipping_city",
                value: "City"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$3, {
                id: "shipping_city",
                modelValue: form.shipping_address.city,
                "onUpdate:modelValue": ($event) => form.shipping_address.city = $event,
                class: "mt-1 block w-full"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$4, {
                class: "mt-1",
                message: fieldError("shipping_address.city")
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$2, {
                for: "shipping_state",
                value: "State"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$3, {
                id: "shipping_state",
                modelValue: form.shipping_address.state,
                "onUpdate:modelValue": ($event) => form.shipping_address.state = $event,
                class: "mt-1 block w-full"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$4, {
                class: "mt-1",
                message: fieldError("shipping_address.state")
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$2, {
                for: "shipping_postal_code",
                value: "Postal Code"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$3, {
                id: "shipping_postal_code",
                modelValue: form.shipping_address.postal_code,
                "onUpdate:modelValue": ($event) => form.shipping_address.postal_code = $event,
                class: "mt-1 block w-full"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$4, {
                class: "mt-1",
                message: fieldError("shipping_address.postal_code")
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$2, {
                for: "shipping_country",
                value: "Country (2-letter code)"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$3, {
                id: "shipping_country",
                modelValue: form.shipping_address.country,
                "onUpdate:modelValue": ($event) => form.shipping_address.country = $event,
                maxlength: "2",
                class: "mt-1 block w-full uppercase"
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(_sfc_main$4, {
                class: "mt-1",
                message: fieldError("shipping_address.country")
              }, null, _parent2, _scopeId));
              _push2(`</div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
            _push2(ssrRenderComponent(_sfc_main$4, {
              message: fieldError("items")
            }, null, _parent2, _scopeId));
            _push2(`<div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("store.cart"),
              class: "inline-flex min-h-12 items-center justify-center rounded-md px-4 py-3 text-base font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` ← Back to Cart `);
                } else {
                  return [
                    createTextVNode(" ← Back to Cart ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(PrimaryButton, {
              class: "w-full sm:w-auto",
              disabled: submitting.value || !hasItems.value
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  if (submitting.value) {
                    _push3(`<span${_scopeId2}>Placing Order…</span>`);
                  } else {
                    _push3(`<span${_scopeId2}>Place Order</span>`);
                  }
                } else {
                  return [
                    submitting.value ? (openBlock(), createBlock("span", { key: 0 }, "Placing Order…")) : (openBlock(), createBlock("span", { key: 1 }, "Place Order"))
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></form><div class="lg:col-span-1"${_scopeId}><div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:sticky lg:top-20"${_scopeId}><h2 class="text-lg font-semibold text-gray-900"${_scopeId}>Order Summary</h2><div class="space-y-4 divide-y divide-gray-100"${_scopeId}><!--[-->`);
            ssrRenderList(groupedItems.value, (group, locationId) => {
              _push2(`<div class="pt-2 first:pt-0"${_scopeId}><p class="mb-2 text-sm font-semibold uppercase text-gray-600"${_scopeId}>${ssrInterpolate(locationNameById.value[locationId])}</p><!--[-->`);
              ssrRenderList(group, (item) => {
                _push2(`<div class="flex items-start justify-between gap-3 py-2 text-base"${_scopeId}><div${_scopeId}><p class="font-medium text-gray-900"${_scopeId}>${ssrInterpolate(item.name)}</p><p class="text-sm text-gray-600"${_scopeId}>Qty ${ssrInterpolate(item.quantity)} × ${ssrInterpolate(formatCurrency(item.price))}</p></div><p class="font-semibold text-gray-900"${_scopeId}>${ssrInterpolate(formatCurrency(item.price * item.quantity))}</p></div>`);
              });
              _push2(`<!--]--></div>`);
            });
            _push2(`<!--]--></div><div class="border-t border-gray-200 pt-4 flex items-center justify-between"${_scopeId}><p class="text-sm font-semibold text-gray-700"${_scopeId}>Total</p><p class="text-xl font-bold text-gray-900"${_scopeId}>${ssrInterpolate(totalDisplay.value)}</p></div></div></div></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "bg-gray-50 min-h-screen" }, [
                createVNode("div", { class: "mobile-page-gutter mx-auto max-w-5xl space-y-6 py-6 sm:py-8 lg:px-8" }, [
                  createVNode("div", null, [
                    createVNode("p", { class: "text-sm text-gray-500 uppercase tracking-wide" }, "Checkout"),
                    createVNode("h1", { class: "text-2xl font-semibold text-gray-900" }, "Shipping & Billing Details")
                  ]),
                  createVNode("div", { class: "grid grid-cols-1 lg:grid-cols-3 gap-6" }, [
                    createVNode("form", {
                      class: "checkout-form space-y-6 lg:col-span-2",
                      onSubmit: withModifiers(submit, ["prevent"])
                    }, [
                      createVNode("div", { class: "space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6" }, [
                        createVNode("h2", { class: "text-lg font-semibold text-gray-900" }, "Contact Information"),
                        createVNode("div", { class: "grid grid-cols-1 sm:grid-cols-2 gap-4" }, [
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "contact_name",
                              value: "Full Name"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "contact_name",
                              modelValue: form.contact_name,
                              "onUpdate:modelValue": ($event) => form.contact_name = $event,
                              class: "mt-2 block w-full",
                              required: "",
                              autocomplete: "name"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("contact_name")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "contact_email",
                              value: "Email"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "contact_email",
                              type: "email",
                              modelValue: form.contact_email,
                              "onUpdate:modelValue": ($event) => form.contact_email = $event,
                              class: "mt-2 block w-full",
                              required: "",
                              autocomplete: "email"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("contact_email")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "contact_phone",
                              value: "Phone (optional)"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "contact_phone",
                              type: "tel",
                              inputmode: "tel",
                              autocomplete: "tel",
                              modelValue: form.contact_phone,
                              "onUpdate:modelValue": ($event) => form.contact_phone = $event,
                              class: "mt-2 block w-full"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("contact_phone")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "company_name",
                              value: "Company (optional)"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "company_name",
                              modelValue: form.company_name,
                              "onUpdate:modelValue": ($event) => form.company_name = $event,
                              class: "mt-2 block w-full",
                              autocomplete: "organization"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("company_name")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "po_number",
                              value: "PO Number (optional)"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "po_number",
                              modelValue: form.po_number,
                              "onUpdate:modelValue": ($event) => form.po_number = $event,
                              class: "mt-1 block w-full"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("po_number")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "job_number",
                              value: "Job Number (optional)"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "job_number",
                              modelValue: form.job_number,
                              "onUpdate:modelValue": ($event) => form.job_number = $event,
                              class: "mt-1 block w-full"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("job_number")
                            }, null, 8, ["message"])
                          ])
                        ]),
                        createVNode("div", null, [
                          createVNode(_sfc_main$2, {
                            for: "notes",
                            value: "Order Notes (optional)"
                          }),
                          withDirectives(createVNode("textarea", {
                            id: "notes",
                            "onUpdate:modelValue": ($event) => form.notes = $event,
                            rows: "3",
                            class: "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, form.notes]
                          ]),
                          createVNode(_sfc_main$4, {
                            class: "mt-1",
                            message: fieldError("notes")
                          }, null, 8, ["message"])
                        ])
                      ]),
                      createVNode("div", { class: "space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6" }, [
                        createVNode("h2", { class: "text-lg font-semibold text-gray-900" }, "Billing Address"),
                        createVNode("div", { class: "grid grid-cols-1 sm:grid-cols-2 gap-4" }, [
                          createVNode("div", { class: "sm:col-span-2" }, [
                            createVNode(_sfc_main$2, {
                              for: "billing_line1",
                              value: "Address Line 1"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "billing_line1",
                              modelValue: form.billing_address.line1,
                              "onUpdate:modelValue": ($event) => form.billing_address.line1 = $event,
                              class: "mt-1 block w-full",
                              required: ""
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("billing_address.line1")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", { class: "sm:col-span-2" }, [
                            createVNode(_sfc_main$2, {
                              for: "billing_line2",
                              value: "Address Line 2 (optional)"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "billing_line2",
                              modelValue: form.billing_address.line2,
                              "onUpdate:modelValue": ($event) => form.billing_address.line2 = $event,
                              class: "mt-1 block w-full"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("billing_address.line2")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "billing_city",
                              value: "City"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "billing_city",
                              modelValue: form.billing_address.city,
                              "onUpdate:modelValue": ($event) => form.billing_address.city = $event,
                              class: "mt-1 block w-full",
                              required: ""
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("billing_address.city")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "billing_state",
                              value: "State"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "billing_state",
                              modelValue: form.billing_address.state,
                              "onUpdate:modelValue": ($event) => form.billing_address.state = $event,
                              class: "mt-1 block w-full",
                              required: ""
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("billing_address.state")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "billing_postal_code",
                              value: "Postal Code"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "billing_postal_code",
                              modelValue: form.billing_address.postal_code,
                              "onUpdate:modelValue": ($event) => form.billing_address.postal_code = $event,
                              class: "mt-1 block w-full",
                              required: ""
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("billing_address.postal_code")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "billing_country",
                              value: "Country (2-letter code)"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "billing_country",
                              modelValue: form.billing_address.country,
                              "onUpdate:modelValue": ($event) => form.billing_address.country = $event,
                              maxlength: "2",
                              class: "mt-1 block w-full uppercase",
                              required: ""
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("billing_address.country")
                            }, null, 8, ["message"])
                          ])
                        ])
                      ]),
                      createVNode("div", { class: "space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6" }, [
                        createVNode("div", { class: "flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" }, [
                          createVNode("h2", { class: "text-lg font-semibold text-gray-900" }, "Shipping Address"),
                          createVNode("label", { class: "flex min-h-12 cursor-pointer items-center gap-3 rounded-md px-2 text-base text-gray-700 hover:bg-gray-50 focus-within:ring-2 focus-within:ring-indigo-500" }, [
                            withDirectives(createVNode("input", {
                              type: "checkbox",
                              "onUpdate:modelValue": ($event) => form.shipping_same_as_billing = $event,
                              class: "h-5 w-5 rounded border-gray-300"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelCheckbox, form.shipping_same_as_billing]
                            ]),
                            createTextVNode(" Same as billing address ")
                          ])
                        ]),
                        !form.shipping_same_as_billing ? (openBlock(), createBlock("div", {
                          key: 0,
                          class: "grid grid-cols-1 sm:grid-cols-2 gap-4"
                        }, [
                          createVNode("div", { class: "sm:col-span-2" }, [
                            createVNode(_sfc_main$2, {
                              for: "shipping_line1",
                              value: "Address Line 1"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "shipping_line1",
                              modelValue: form.shipping_address.line1,
                              "onUpdate:modelValue": ($event) => form.shipping_address.line1 = $event,
                              class: "mt-1 block w-full"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("shipping_address.line1")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", { class: "sm:col-span-2" }, [
                            createVNode(_sfc_main$2, {
                              for: "shipping_line2",
                              value: "Address Line 2 (optional)"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "shipping_line2",
                              modelValue: form.shipping_address.line2,
                              "onUpdate:modelValue": ($event) => form.shipping_address.line2 = $event,
                              class: "mt-1 block w-full"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("shipping_address.line2")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "shipping_city",
                              value: "City"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "shipping_city",
                              modelValue: form.shipping_address.city,
                              "onUpdate:modelValue": ($event) => form.shipping_address.city = $event,
                              class: "mt-1 block w-full"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("shipping_address.city")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "shipping_state",
                              value: "State"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "shipping_state",
                              modelValue: form.shipping_address.state,
                              "onUpdate:modelValue": ($event) => form.shipping_address.state = $event,
                              class: "mt-1 block w-full"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("shipping_address.state")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "shipping_postal_code",
                              value: "Postal Code"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "shipping_postal_code",
                              modelValue: form.shipping_address.postal_code,
                              "onUpdate:modelValue": ($event) => form.shipping_address.postal_code = $event,
                              class: "mt-1 block w-full"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("shipping_address.postal_code")
                            }, null, 8, ["message"])
                          ]),
                          createVNode("div", null, [
                            createVNode(_sfc_main$2, {
                              for: "shipping_country",
                              value: "Country (2-letter code)"
                            }),
                            createVNode(_sfc_main$3, {
                              id: "shipping_country",
                              modelValue: form.shipping_address.country,
                              "onUpdate:modelValue": ($event) => form.shipping_address.country = $event,
                              maxlength: "2",
                              class: "mt-1 block w-full uppercase"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                            createVNode(_sfc_main$4, {
                              class: "mt-1",
                              message: fieldError("shipping_address.country")
                            }, null, 8, ["message"])
                          ])
                        ])) : createCommentVNode("", true)
                      ]),
                      createVNode(_sfc_main$4, {
                        message: fieldError("items")
                      }, null, 8, ["message"]),
                      createVNode("div", { class: "flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between" }, [
                        createVNode(unref(Link), {
                          href: _ctx.route("store.cart"),
                          class: "inline-flex min-h-12 items-center justify-center rounded-md px-4 py-3 text-base font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        }, {
                          default: withCtx(() => [
                            createTextVNode(" ← Back to Cart ")
                          ]),
                          _: 1
                        }, 8, ["href"]),
                        createVNode(PrimaryButton, {
                          class: "w-full sm:w-auto",
                          disabled: submitting.value || !hasItems.value
                        }, {
                          default: withCtx(() => [
                            submitting.value ? (openBlock(), createBlock("span", { key: 0 }, "Placing Order…")) : (openBlock(), createBlock("span", { key: 1 }, "Place Order"))
                          ]),
                          _: 1
                        }, 8, ["disabled"])
                      ])
                    ], 32),
                    createVNode("div", { class: "lg:col-span-1" }, [
                      createVNode("div", { class: "space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:sticky lg:top-20" }, [
                        createVNode("h2", { class: "text-lg font-semibold text-gray-900" }, "Order Summary"),
                        createVNode("div", { class: "space-y-4 divide-y divide-gray-100" }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(groupedItems.value, (group, locationId) => {
                            return openBlock(), createBlock("div", {
                              key: locationId,
                              class: "pt-2 first:pt-0"
                            }, [
                              createVNode("p", { class: "mb-2 text-sm font-semibold uppercase text-gray-600" }, toDisplayString(locationNameById.value[locationId]), 1),
                              (openBlock(true), createBlock(Fragment, null, renderList(group, (item) => {
                                return openBlock(), createBlock("div", {
                                  key: item.id,
                                  class: "flex items-start justify-between gap-3 py-2 text-base"
                                }, [
                                  createVNode("div", null, [
                                    createVNode("p", { class: "font-medium text-gray-900" }, toDisplayString(item.name), 1),
                                    createVNode("p", { class: "text-sm text-gray-600" }, "Qty " + toDisplayString(item.quantity) + " × " + toDisplayString(formatCurrency(item.price)), 1)
                                  ]),
                                  createVNode("p", { class: "font-semibold text-gray-900" }, toDisplayString(formatCurrency(item.price * item.quantity)), 1)
                                ]);
                              }), 128))
                            ]);
                          }), 128))
                        ]),
                        createVNode("div", { class: "border-t border-gray-200 pt-4 flex items-center justify-between" }, [
                          createVNode("p", { class: "text-sm font-semibold text-gray-700" }, "Total"),
                          createVNode("p", { class: "text-xl font-bold text-gray-900" }, toDisplayString(totalDisplay.value), 1)
                        ])
                      ])
                    ])
                  ])
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Store/Checkout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
