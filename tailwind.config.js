import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',

  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',
  ],

  theme: {
    extend: {
      screens: {
        xs: '390px',
      },
      spacing: {
        control: '3rem',
        appbar: '4rem',
      },
      maxWidth: {
        drawer: '22.5rem',
      },
       fontFamily: {
        sans: ['Inter', ...defaultTheme.fontFamily.sans],
        catalog: ['Archivo', 'Inter', ...defaultTheme.fontFamily.sans],
      },
      colors: {
        // Store catalog tokens — values live in resources/css/app.css (.store-shell)
        st: {
          bg: 'rgb(var(--st-bg) / <alpha-value>)',
          panel: 'rgb(var(--st-panel) / <alpha-value>)',
          sunk: 'rgb(var(--st-sunk) / <alpha-value>)',
          line: 'rgb(var(--st-line) / <alpha-value>)',
          ink: 'rgb(var(--st-ink) / <alpha-value>)',
          muted: 'rgb(var(--st-muted) / <alpha-value>)',
          action: 'rgb(var(--st-action) / <alpha-value>)',
          fill: 'rgb(var(--st-fill) / <alpha-value>)',
          signal: 'rgb(var(--st-signal) / <alpha-value>)',
          ok: 'rgb(var(--st-ok) / <alpha-value>)',
          warn: 'rgb(var(--st-warn) / <alpha-value>)',
        },
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8', // brand blue
          800: '#1e40af',
          900: '#1e3a8a',
        },
        secondary: {
          50: '#f9fafb',
          100: '#f3f4f6',
          200: '#e5e7eb',
          300: '#d1d5db',
          400: '#9ca3af',
          500: '#6b7280',
          600: '#4b5563',
          700: '#374151',
          800: '#1f2937',
          900: '#111827',
        },
        accent: {
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#ef4444',
          600: '#dc2626', // brand red (alerts/highlight)
          700: '#b91c1c',
          800: '#991b1b',
          900: '#7f1d1d',
        },
      },
    },
  },

  plugins: [forms],
}
