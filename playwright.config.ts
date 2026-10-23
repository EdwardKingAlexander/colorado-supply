import { defineConfig } from '@playwright/test'

export default defineConfig({
  testDir: './tests/Browser',
  fullyParallel: false,
  timeout: 30_000,
  expect: {
    timeout: 5_000,
  },
  reporter: [['list']],
  use: {
    baseURL: 'http://127.0.0.1:8123',
    browserName: 'chromium',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
  webServer: [
    {
      // PHP's built-in server with Laravel's router (what `artisan serve`
      // runs), started directly so these variables reach the app; `artisan
      // serve` strips them. Notifications run inline instead of waiting for a
      // queue worker, mail is kept in memory, and web push is off, so test
      // alerts never reach real inboxes or devices.
      command: 'php -S 127.0.0.1:8123 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php',
      cwd: './public',
      url: 'http://127.0.0.1:8123',
      env: {
        QUEUE_CONNECTION: 'sync',
        MAIL_MAILER: 'array',
        VAPID_PUBLIC_KEY: '',
        VAPID_PRIVATE_KEY: '',
        // The store's API calls (checkout) use the session from this host.
        SANCTUM_STATEFUL_DOMAINS: '127.0.0.1:8123',
      },
      reuseExistingServer: true,
      timeout: 30_000,
    },
    {
      // Live notifications (tests/Browser/will-call.spec.ts).
      command: 'php artisan reverb:start',
      port: 8080,
      reuseExistingServer: true,
      timeout: 30_000,
    },
  ],
})
