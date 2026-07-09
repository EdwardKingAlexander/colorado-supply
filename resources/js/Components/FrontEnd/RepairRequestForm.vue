<template>
  <section id="repair-form" class="bg-gray-50 dark:bg-gray-800 py-24 sm:py-32 scroll-mt-20">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">Get Started</h2>
        <p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
          Request a Repair Quote
        </p>
        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
          Tell us about your equipment and we'll follow up with a free evaluation and quote.
          Only your name, email, equipment type, model number, and a description of the issue are required —
          everything else helps us move faster, but isn't required.
        </p>
      </div>

      <div class="mx-auto mt-16 max-w-2xl">
        <form @submit.prevent="submitForm" class="space-y-6 rounded-xl bg-white dark:bg-gray-900 p-8 shadow-sm ring-1 ring-gray-900/5 dark:ring-white/10">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Name -->
            <div>
              <label for="repair-name" class="block text-sm font-semibold text-gray-900 dark:text-white">Name *</label>
              <input
                id="repair-name"
                type="text"
                v-model="form.name"
                required
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
              <p v-if="errors.name" class="text-red-600 text-sm mt-1">{{ errors.name[0] }}</p>
            </div>

            <!-- Email -->
            <div>
              <label for="repair-email" class="block text-sm font-semibold text-gray-900 dark:text-white">Email *</label>
              <input
                id="repair-email"
                type="email"
                v-model="form.email"
                required
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
              <p v-if="errors.email" class="text-red-600 text-sm mt-1">{{ errors.email[0] }}</p>
            </div>

            <!-- Phone -->
            <div>
              <label for="repair-phone" class="block text-sm font-semibold text-gray-900 dark:text-white">Phone</label>
              <input
                id="repair-phone"
                type="text"
                v-model="form.phone"
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
              <p v-if="errors.phone" class="text-red-600 text-sm mt-1">{{ errors.phone[0] }}</p>
            </div>

            <!-- Company -->
            <div>
              <label for="repair-company" class="block text-sm font-semibold text-gray-900 dark:text-white">Company</label>
              <input
                id="repair-company"
                type="text"
                v-model="form.company"
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
              <p v-if="errors.company" class="text-red-600 text-sm mt-1">{{ errors.company[0] }}</p>
            </div>

            <!-- Equipment Type -->
            <div>
              <label for="repair-equipment-type" class="block text-sm font-semibold text-gray-900 dark:text-white">Equipment Type *</label>
              <select
                id="repair-equipment-type"
                v-model="form.equipment_type"
                required
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              >
                <option value="" disabled>Select equipment type</option>
                <option v-for="option in equipmentTypes" :key="option" :value="option">{{ option }}</option>
              </select>
              <p v-if="errors.equipment_type" class="text-red-600 text-sm mt-1">{{ errors.equipment_type[0] }}</p>
            </div>

            <!-- Manufacturer -->
            <div>
              <label for="repair-manufacturer" class="block text-sm font-semibold text-gray-900 dark:text-white">Manufacturer</label>
              <input
                id="repair-manufacturer"
                type="text"
                v-model="form.manufacturer"
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
              <p v-if="errors.manufacturer" class="text-red-600 text-sm mt-1">{{ errors.manufacturer[0] }}</p>
            </div>

            <!-- Model Number -->
            <div>
              <label for="repair-model-number" class="block text-sm font-semibold text-gray-900 dark:text-white">Model Number *</label>
              <input
                id="repair-model-number"
                type="text"
                v-model="form.model_number"
                required
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
              <p v-if="errors.model_number" class="text-red-600 text-sm mt-1">{{ errors.model_number[0] }}</p>
            </div>

            <!-- Serial Number -->
            <div>
              <label for="repair-serial-number" class="block text-sm font-semibold text-gray-900 dark:text-white">Serial Number</label>
              <input
                id="repair-serial-number"
                type="text"
                v-model="form.serial_number"
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Not always available — that's okay.</p>
              <p v-if="errors.serial_number" class="text-red-600 text-sm mt-1">{{ errors.serial_number[0] }}</p>
            </div>
          </div>

          <!-- Urgency -->
          <div>
            <label for="repair-urgency" class="block text-sm font-semibold text-gray-900 dark:text-white">Urgency</label>
            <select
              id="repair-urgency"
              v-model="form.urgency"
              class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
            >
              <option value="">Standard (3-5 Business Days Once Received)</option>
              <option value="rush">Rush Service</option>
            </select>
            <p v-if="errors.urgency" class="text-red-600 text-sm mt-1">{{ errors.urgency[0] }}</p>
          </div>

          <!-- Issue Description -->
          <div>
            <label for="repair-issue" class="block text-sm font-semibold text-gray-900 dark:text-white">Describe the Issue *</label>
            <textarea
              id="repair-issue"
              rows="4"
              v-model="form.issue_description"
              required
              class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
            ></textarea>
            <p v-if="errors.issue_description" class="text-red-600 text-sm mt-1">{{ errors.issue_description[0] }}</p>
          </div>

          <!-- Honeypot field to catch bots (distinct from the real "company" field above) -->
          <div class="hidden" aria-hidden="true">
            <label for="repair-website">Website</label>
            <input
              id="repair-website"
              type="text"
              v-model="form.website"
              tabindex="-1"
              autocomplete="off"
            />
          </div>

          <!-- Submit -->
          <div>
            <button
              type="submit"
              :disabled="isSubmitting"
              class="w-full rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
              <span v-if="isSubmitting">Sending...</span>
              <span v-else>Request a Repair Quote</span>
            </button>
            <p v-if="errors.captcha_token" class="text-red-600 text-sm mt-2">{{ errors.captcha_token[0] }}</p>
          </div>

          <!-- success message -->
          <p v-if="successMessage" class="mt-4 text-green-600">{{ successMessage }}</p>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { getRecaptchaToken } from '@/Support/recaptcha'

const equipmentTypes = [
  'Industrial Electronics',
  'Servo Motor',
  'AC Motor',
  'DC Motor',
  'Drive / Inverter',
  'HMI / Touchscreen',
  'Hydraulic Component',
  'Pneumatic Component',
  'PLC',
  'Other',
]

const form = ref({
  name: '',
  email: '',
  phone: '',
  company: '',
  equipment_type: '',
  manufacturer: '',
  model_number: '',
  serial_number: '',
  issue_description: '',
  urgency: '',
  website: '',
})

const successMessage = ref('')
const errors = ref({})
const isSubmitting = ref(false)

const recaptchaAction = 'repair_request_form'

const submitForm = async () => {
  successMessage.value = ''
  errors.value = {}
  isSubmitting.value = true

  let captchaToken

  try {
    captchaToken = await getRecaptchaToken(recaptchaAction)
  } catch (error) {
    errors.value = { captcha_token: ['reCAPTCHA failed to load. Please refresh the page and try again.'] }
    isSubmitting.value = false
    return
  }

  try {
    const response = await axios.post('/repair-services', {
      ...form.value,
      captcha_token: captchaToken,
    })

    successMessage.value = response.data.message
    form.value = {
      name: '',
      email: '',
      phone: '',
      company: '',
      equipment_type: '',
      manufacturer: '',
      model_number: '',
      serial_number: '',
      issue_description: '',
      urgency: '',
      website: '',
    }
  } catch (error) {
    if (error.response && error.response.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { captcha_token: ['We could not submit your request. Please try again.'] }
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>
