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
              <dd>(719) 555-1234</dd>
            </div>
            <div>
              <dt class="font-semibold text-gray-900 dark:text-white">Email</dt>
              <dd>info@coloradosupply.com</dd>
            </div>
            <div>
              <dt class="font-semibold text-gray-900 dark:text-white">Location</dt>
              <dd>Colorado Springs, CO — Serving Nationwide</dd>
            </div>
            <div>
              <dt class="font-semibold text-gray-900 dark:text-white">Registrations</dt>
              <dd>
                SAM.gov Active | CAGE Code: XXXX | EIN: XX-XXXXXXX
              </dd>
            </div>
          </dl>
        </div>

        <!-- Contact Form -->
        <div>
          <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Send a Message</h3>
          <form @submit.prevent="submitForm" class="mt-6 space-y-6">
            <div>
              <label for="name" class="block text-sm font-semibold text-gray-900 dark:text-white">Name</label>
              <input
                id="name"
                type="text"
                v-model="form.name"
                required
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
            </div>
            <div>
              <label for="email" class="block text-sm font-semibold text-gray-900 dark:text-white">Email</label>
              <input
                id="email"
                type="email"
                v-model="form.email"
                required
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              />
            </div>
            <div>
              <label for="message" class="block text-sm font-semibold text-gray-900 dark:text-white">Message</label>
              <textarea
                id="message"
                rows="4"
                v-model="form.message"
                required
                class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-gray-900 dark:text-white shadow-sm focus:ring-2 focus:ring-amber-600 sm:text-sm"
              ></textarea>
            </div>
            <div>
              <button
                type="submit"
                class="w-full rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-amber-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600"
              >
                Send Message
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'

const form = ref({
  name: '',
  email: '',
  message: '',
})

function submitForm() {
  // TODO: connect to Laravel backend (e.g., route to Mail or DB)
  console.log('Form submitted:', form.value)
  alert('Thank you for your message! We will get back to you shortly.')
  form.value = { name: '', email: '', message: '' }
}
</script>
