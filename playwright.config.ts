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
  webServer: {
    command: 'php artisan serve --host=127.0.0.1 --port=8123',
    url: 'http://127.0.0.1:8123',
    reuseExistingServer: true,
    timeout: 30_000,
  },
})
