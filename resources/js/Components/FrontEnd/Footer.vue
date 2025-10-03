<template>
  <footer class="bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
    <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
      <div class="grid grid-cols-1 gap-12 lg:grid-cols-4">
        <!-- Company Info -->
        <div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
            Colorado Supply & Procurement LLC
          </h3>
          <p class="mt-4 text-gray-600 dark:text-gray-400">
            Colorado Springs, CO — Serving Nationwide
          </p>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">
            Registered Small Business | SAM.gov Active
          </p>
        </div>

        <!-- Quick Links -->
        <div>
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Navigation</h4>
          <ul role="list" class="mt-4 space-y-2">
            <li><a href="#home" class="footer-link">Home</a></li>
            <li><a href="#about" class="footer-link">About</a></li>
            <li><a href="#capabilities" class="footer-link">Capabilities</a></li>
            <li><a href="#industries" class="footer-link">Industries</a></li>
            <li><a href="#credentials" class="footer-link">Credentials</a></li>
            <li><a href="#contact" class="footer-link">Contact</a></li>
          </ul>
        </div>

        <!-- Resources -->
        <div>
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Resources</h4>
          <ul role="list" class="mt-4 space-y-2">
            <li>
              <a href="/files/Colorado-Supply-Capabilities.pdf" class="footer-link">
                Capabilities Statement
              </a>
            </li>
            <li>
              <a href="https://sam.gov" target="_blank" rel="noopener noreferrer" class="footer-link">
                SAM.gov Profile
              </a>
            </li>
            <li>
              <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="footer-link">
                LinkedIn
              </a>
            </li>
          </ul>
        </div>

        <!-- Compliance Info -->
        <div>
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Compliance</h4>
          <ul role="list" class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <li><span class="font-semibold">CAGE Code:</span> 15NL2</li>
            <li><span class="font-semibold">UEI:</span> DBGXU2BFS5N7</li>
            <li><span class="font-semibold">NAICS:</span> 423840, 423830, 423710, 423120, 423610, 423450</li>
          </ul>
          <div class="mt-6">
            <a
              href="/files/Colorado-Supply-Capabilities.pdf"
              class="inline-flex items-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-amber-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600"
            >
              Download Capabilities
            </a>
          </div>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="mt-12 border-t border-gray-200 pt-6 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-500">
          © {{ new Date().getFullYear() }} Colorado Supply & Procurement LLC. All rights reserved.
        </p>
        
          <span class="text-xs text-blue-300">Website by</span> 
          <a href="https://www.rockymountainweb.design" class="text-xs hover:underline text-blue-500 dark:text-blue-500">
            Rocky Mountain Web Design LLC
          </a>
      </div>
    </div>
  </footer>
</template>

<script setup>
// Simple utility for link styling reuse
</script>

<style scoped>
.footer-link {
  @apply text-sm text-gray-600 hover:text-amber-600 dark:text-gray-400 dark:hover:text-amber-400;
}
</style>
