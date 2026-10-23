import AxeBuilder from '@axe-core/playwright'
import { execFileSync } from 'node:child_process'
import { randomBytes } from 'node:crypto'
import { expect, test, type Browser, type Page } from '@playwright/test'

// Will call and live notifications QA (ai/modules/will-call-notifications-complete,
// phase 7). The fixture creates a throwaway buyer, an MFA-enrolled admin, a
// pickup counter and a product in the database the Playwright server uses,
// and removes them, their orders and the alerts about those orders afterwards.
// The password is random per run and never stored. Live alerts need Reverb,
// which playwright.config.ts starts.
const password = randomBytes(18).toString('hex')
// Each worker (including one restarted after a failure) owns its own records.
const token = randomBytes(4).toString('hex')

type Fixture = {
  buyerEmail: string
  adminEmail: string
  locationId: number
  locationName: string
  cartItem: Record<string, unknown>
}
let fixture: Fixture

const runFixture = (command: 'setup' | 'otp' | 'order' | 'teardown') => execFileSync(
  'php',
  ['tests/Browser/support/will-call-fixture.php', command],
  { env: { ...process.env, E2E_PASSWORD: password, E2E_TOKEN: token }, encoding: 'utf8' },
)

test.beforeAll(() => {
  fixture = JSON.parse(runFixture('setup'))
})

test.afterAll(() => {
  runFixture('teardown')
})

const dismissConsent = async (page: Page) => {
  // A fresh browser gets the cookie-consent banner, which covers the form.
  const essentialOnly = page.getByRole('button', { name: 'Essential only' })
  if (await essentialOnly.isVisible()) {
    await essentialOnly.click()
  }
}

const loginBuyer = async (page: Page) => {
  await page.goto('/login')
  await dismissConsent(page)
  await page.locator('#email').fill(fixture.buyerEmail)
  await page.locator('#password').fill(password)
  await page.getByRole('button', { name: 'Log in' }).click()
  await page.waitForURL((url) => !url.pathname.startsWith('/login'))
}

const loginAdmin = async (page: Page) => {
  await page.goto('/admin/login')
  await page.getByLabel('Email address').fill(fixture.adminEmail)
  await page.getByLabel('Password', { exact: false }).first().fill(password)
  await page.getByRole('button', { name: 'Sign in' }).click()
  const code = page.getByRole('textbox', { name: /code/i }).first()
  await code.waitFor()
  await code.fill(runFixture('otp').trim())
  await page.getByRole('button', { name: 'Confirm sign in' }).first().click()
  await page.waitForURL((url) => !url.pathname.startsWith('/admin/login'))
}

// Resolves once the page's private notification channel is subscribed, so a
// broadcast sent afterwards is guaranteed to arrive.
const channelReady = (page: Page) => new Promise<void>((resolve) => {
  page.on('websocket', (socket) => socket.on('framereceived', (frame) => {
    if (String(frame.payload).includes('subscription_succeeded')) {
      resolve()
    }
  }))
})

const fillCart = async (page: Page) => {
  await page.evaluate((item) => localStorage.setItem('colorado-supply.cart', JSON.stringify([item])), fixture.cartItem)
}

const chooseWillCall = async (page: Page) => {
  await page.getByRole('radio', { name: /Will Call/ }).check()
  await page.getByRole('radio', { name: new RegExp(fixture.locationName) }).check()
}

const axeScan = async (page: Page) => {
  const results = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
    // The cookie-consent banner is shared site chrome, audited separately.
    .exclude('[aria-label="Privacy choices"]')
    .analyze()

  return results.violations.map((violation) => `${violation.id}: ${violation.nodes.map((node) => node.target.join(' ')).join(' | ')}`)
}

const expectNoSidewaysScroll = async (page: Page) => {
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)
  expect(overflow, 'no horizontal scroll').toBeLessThanOrEqual(1)
}

const expectTouchTargets = async (page: Page, selector: string) => {
  const targets = page.locator(selector)
  for (let index = 0; index < await targets.count(); index += 1) {
    const box = await targets.nth(index).boundingBox()
    const label = (await targets.nth(index).innerText()).split('\n')[0]
    expect(box!.height, `"${label}" is at least 44px tall`).toBeGreaterThanOrEqual(43.5)
  }
}

const viewports = [
  { name: 'compact', width: 320, height: 568 },
  { name: 'standard', width: 390, height: 844 },
  { name: 'desktop', width: 1280, height: 900 },
]

for (const viewport of viewports) {
  test.describe(`${viewport.name} ${viewport.width}px`, () => {
    test.use({ viewport: { width: viewport.width, height: viewport.height } })

    test('will call checkout reflows, hides shipping, and has no accessibility violations', async ({ page }) => {
      await loginBuyer(page)
      await fillCart(page)
      await page.goto('/store/checkout')

      await chooseWillCall(page)
      await expect(page.locator('#shipping_line1')).toBeHidden()
      await expect(page.getByText(/100 Supply Way/)).toBeVisible()

      await expectNoSidewaysScroll(page)
      await expectTouchTargets(page, 'label:has(input[name="fulfillment_method"]), label:has(input[name="pickup_location_id"])')
      expect(await axeScan(page)).toEqual([])
    })

    test('notifications page and bell are usable and accessible', async ({ page }) => {
      await loginBuyer(page)
      await page.goto('/notifications')
      await expect(page.getByRole('heading', { level: 1 })).toContainText('Notifications')

      await expectNoSidewaysScroll(page)
      expect(await axeScan(page)).toEqual([])

      const bell = page.getByRole('button', { name: /^Notifications/ })
      await expect(bell).toHaveAttribute('aria-expanded', 'false')
      await bell.click()
      await expect(bell).toHaveAttribute('aria-expanded', 'true')
      await expect(page.locator('#notification-panel')).toBeVisible()
      await page.keyboard.press('Escape')
      await expect(bell).toHaveAttribute('aria-expanded', 'false')
      await expect(bell).toBeFocused()
    })
  })
}

const newPage = async (browser: Browser, width: number, height: number) => (await browser.newContext({ viewport: { width, height } })).newPage()

test('will call order: live alerts to the admin and the buyer through ready and picked up', async ({ browser }) => {
  test.setTimeout(120_000)

  const admin = await newPage(browser, 1280, 900)
  const buyer = await newPage(browser, 390, 844)

  const adminReady = channelReady(admin)
  await loginAdmin(admin)
  await adminReady

  // The buyer places the order at the counter.
  await loginBuyer(buyer)
  await fillCart(buyer)
  // Checkout and the pay page are one Inertia visit apart: one socket.
  let buyerReady = channelReady(buyer)
  await buyer.goto('/store/checkout')
  await chooseWillCall(buyer)
  await buyer.locator('#billing_line1').fill('1 Test St')
  await buyer.locator('#billing_city').fill('Denver')
  await buyer.locator('#billing_state').fill('CO')
  await buyer.locator('#billing_postal_code').fill('80202')
  await buyer.locator('#billing_country').fill('US')
  const placed = buyer.waitForResponse((response) => response.url().endsWith('/api/v1/store/checkout'))
  await buyer.getByRole('button', { name: 'Place Order' }).click()
  const placedResponse = await placed
  expect(placedResponse.ok(), await placedResponse.text()).toBe(true)
  await buyer.waitForURL(/\/store\/checkout\/\d+\/pay/)
  await expect(buyer.getByText(fixture.locationName).first()).toBeVisible()

  const order: { id: number, number: string } = JSON.parse(runFixture('order'))

  // Admin: live toast without a reload.
  await expect(admin.getByText(`New will call order ${order.number}`).first()).toBeVisible({ timeout: 15_000 })

  // Admin marks it ready; the buyer gets a live toast with the pickup code.
  await admin.goto(`/admin/c-r-m/orders/${order.id}`)
  await buyerReady
  await admin.getByRole('button', { name: 'Ready for pickup' }).click()
  await admin.getByRole('dialog').getByRole('button', { name: 'Confirm' }).click()

  const readyToast = buyer.getByRole('status').getByText('Your order is ready for pickup')
  await expect(readyToast).toBeVisible({ timeout: 15_000 })
  const pickupCode = JSON.parse(runFixture('order')).pickupCode as string
  expect(pickupCode).toMatch(/^WC-[A-Z0-9]{4}$/)
  await expect(buyer.getByRole('status')).toContainText(pickupCode)
  await expect(buyer.getByRole('button', { name: /^Notifications, \d+ unread$/ })).toBeVisible()

  // The order page shows the code once the buyer looks again.
  buyerReady = channelReady(buyer)
  await buyer.reload()
  await expect(buyer.getByText(pickupCode)).toBeVisible()
  await buyerReady

  // Admin hands it over with the code; the buyer is told it was collected.
  await admin.getByRole('button', { name: 'Picked up' }).click()
  const handOver = admin.getByRole('dialog')
  await handOver.getByLabel('Collected by').fill('Pat Buyer')
  await handOver.getByLabel('Pickup code').fill(pickupCode)
  await handOver.getByRole('button', { name: 'Mark picked up' }).click()

  await expect(buyer.getByRole('status').getByText(`Order ${order.number} was picked up`)).toBeVisible({ timeout: 15_000 })

  await expectNoSidewaysScroll(buyer)

  await admin.context().close()
  await buyer.context().close()
})
