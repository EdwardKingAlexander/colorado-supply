<template>
  <section id="contact" class="bg-white dark:bg-gray-900 py-24 sm:py-32 scroll-mt-20">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">Get in Touch</h2>
        <p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
          Contact Us
        </p>
        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
          Colorado Supply & Procurement LLC is ready to support your next contract or project.  
          Reach us directly, or send a quick message using the form.
        </p>
      </div>

      <div class="mx-auto mt-16 grid max-w-4xl grid-cols-1 gap-12 lg:grid-cols-2">
        <!-- Official Business Info -->
        <div>
          <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Business Information</h3>
          <dl class="mt-6 space-y-4 text-lg text-gray-600 dark:text-gray-300">
            <div>
              <dt class="font-semibold text-gray-900 dark:text-white">Phone</dt>
              <dd><a class="text-blue-600 dark:text-blue-400" href="tel:7194259634">(719) 425-9634</a></dd>
            </div>
            <div>
              <dt class="font-semibold text-gray-900 dark:text-white">Email</dt>
              <dd><a href="mailto:Edward@cogovsupply.com">Edward@cogovsupply.com</a></dd>
            </div>
            <div>
              <dt class="font-semibold text-gray-900 dark:text-white">Location</dt>
              <dd>Colorado Springs, Colorado</dd>
              <dd>Serving Nationwide</dd>
            </div>
            <div>
              <dt class="font-semibold text-gray-900 dark:text-white">Registrations</dt>
              <dd>
                SAM.gov Active | CAGE Code: 15NL2 | EIN: 39-3537490
              </dd>
            </div>
          </dl>
        </div>

        <!-- Contact Form -->
        <div>
          <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Send a Message</h3>

          <form @submit.prevent="submitForm" class="mt-6 space-y-6">
            <!-- Name -->
            <div>
              <label for="name" class="block text-sm font-semibold text-gray-900 dark:text-white">Name</label>
              <input
                id="name"
                type="text"
                v-model="form.name"
                required
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
              <p v-if="errors.name" class="text-red-600 text-sm mt-1">{{ errors.name[0] }}</p>
            </div>

            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-semibold text-gray-900 dark:text-white">Email</label>
              <input
                id="email"
                type="email"
                v-model="form.email"
                required
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
              <p v-if="errors.email" class="text-red-600 text-sm mt-1">{{ errors.email[0] }}</p>
            </div>

            <!-- Phone (optional) -->
            <div>
              <label for="phone" class="block text-sm font-semibold text-gray-900 dark:text-white">Phone (optional)</label>
              <input
                id="phone"
                type="text"
                v-model="form.phone"
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
              <p v-if="errors.phone" class="text-red-600 text-sm mt-1">{{ errors.phone[0] }}</p>
            </div>

            <!-- Message -->
            <div>
              <label for="message" class="block text-sm font-semibold text-gray-900 dark:text-white">Message</label>
              <textarea
                id="message"
                rows="4"
                v-model="form.message"
                required
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              ></textarea>
              <p v-if="errors.message" class="text-red-600 text-sm mt-1">{{ errors.message[0] }}</p>
            </div>

            <!-- Honeypot field to catch bots -->
            <div class="hidden" aria-hidden="true">
              <label for="company">Company</label>
              <input
                id="company"
                type="text"
                v-model="form.company"
                tabindex="-1"
                autocomplete="off"
              />
            </div>

            <!-- Submit -->
            <div>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="w-full rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-amber-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600 disabled:cursor-not-allowed disabled:opacity-60"
              >
                <span v-if="isSubmitting">Sending...</span>
                <span v-else>Send Message</span>
              </button>
              <p v-if="errors.captcha_token" class="text-red-600 text-sm mt-2">{{ errors.captcha_token[0] }}</p>
            </div>
          </form>

          <!-- success message -->
          <p v-if="successMessage" class="mt-4 text-green-600">{{ successMessage }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const form = ref({
  name: '',
  email: '',
  phone: '',
  message: '',
  company: '',
})

const successMessage = ref('')
const errors = ref({})
const isSubmitting = ref(false)

const recaptchaSiteKey = window.googleRecaptchaSiteKey || ''
const recaptchaAction = 'contact_form'

const getRecaptchaToken = () => new Promise((resolve, reject) => {
  if (!recaptchaSiteKey) {
    reject(new Error('Missing reCAPTCHA site key'))
    return
  }

  let attempts = 0
  const maxAttempts = 30

  const attempt = () => {
    const grecaptcha = window.grecaptcha
    if (grecaptcha?.execute) {
      grecaptcha.ready(() => {
        grecaptcha.execute(recaptchaSiteKey, { action: recaptchaAction })
          .then(resolve)
          .catch(reject)
      })
      return
    }

    if (attempts >= maxAttempts) {
      reject(new Error('reCAPTCHA unavailable'))
      return
    }

    attempts += 1
    setTimeout(attempt, 100)
  }

  attempt()
})

const submitForm = async () => {
  successMessage.value = ''
  errors.value = {}
  isSubmitting.value = true

  let captchaToken

  try {
    captchaToken = await getRecaptchaToken()
  } catch (error) {
    errors.value = { captcha_token: ['reCAPTCHA failed to load. Please refresh the page and try again.'] }
    isSubmitting.value = false
    return
  }

  try {
    const response = await axios.post('/contact', {
      ...form.value,
      captcha_token: captchaToken,
    })

    successMessage.value = response.data.message
    form.value = { name: '', email: '', phone: '', message: '', company: '' }
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
