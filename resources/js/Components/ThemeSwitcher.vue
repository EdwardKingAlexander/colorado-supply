<script setup>
import { ComputerDesktopIcon, MoonIcon, SunIcon } from '@heroicons/vue/20/solid'
import { setThemePreference, themePreference } from '@/Support/theme'

// Segmented Light / Dark / System control (native radios for keyboard + screen readers).
const options = [
  { value: 'light', label: 'Light', icon: SunIcon },
  { value: 'dark', label: 'Dark', icon: MoonIcon },
  { value: 'system', label: 'System', icon: ComputerDesktopIcon },
]
</script>

<template>
  <fieldset>
    <legend class="text-sm font-semibold text-st-ink">Theme</legend>
    <div class="mt-2 grid grid-cols-3 gap-1 rounded-lg border border-st-line bg-st-sunk p-1">
      <label
        v-for="option in options"
        :key="option.value"
        class="relative flex min-h-11 cursor-pointer items-center justify-center gap-1.5 rounded-md text-sm font-medium transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-st-action"
        :class="themePreference === option.value ? 'bg-st-panel text-st-ink shadow-sm' : 'text-st-muted hover:text-st-ink'"
      >
        <input
          type="radio"
          name="theme-preference"
          class="sr-only"
          :value="option.value"
          :checked="themePreference === option.value"
          @change="setThemePreference(option.value)"
        />
        <component :is="option.icon" class="h-4 w-4" aria-hidden="true" />
        {{ option.label }}
      </label>
    </div>
  </fieldset>
</template>
