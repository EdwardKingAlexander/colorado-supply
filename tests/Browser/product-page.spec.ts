import AxeBuilder from '@axe-core/playwright'
import { execFileSync } from 'node:child_process'
import { randomBytes } from 'node:crypto'
import { expect, test, type Page } from '@playwright/test'

// Product detail page QA (ai/modules/product-detail-page, phase 5).
// The fixture creates a throwaway buyer and E2E-PDP-* products in the database
// the Playwright server uses, and removes them afterwards. The password is
// random per run and never stored.
const password = randomBytes(18).toString('hex')
// Each worker (including one restarted after a failure) owns its own records.
const token = randomBytes(4).toString('hex')
let fixture: { email: string, sku: string, full: string, bare: string, substitute: string }

const runFixture = (command: 'setup' | 'teardown') => execFileSync(
  'php',
  ['tests/Browser/support/product-page-fixture.php', command],
  { env: { ...process.env, E2E_PASSWORD: password, E2E_TOKEN: token }, encoding: 'utf8' },
)

test.beforeAll(() => {
  fixture = JSON.parse(runFixture('setup'))
})

test.afterAll(() => {
  runFixture('teardown')
})

const login = async (page: Page) => {
  await page.goto('/login')
  // A fresh browser gets the cookie-consent banner, which covers the form.
  const essentialOnly = page.getByRole('button', { name: 'Essential only' })
  if (await essentialOnly.isVisible()) {
    await essentialOnly.click()
  }
  await page.locator('#email').fill(fixture.email)
  await page.locator('#password').fill(password)
  await page.getByRole('button', { name: 'Log in' }).click()
  await page.waitForURL((url) => !url.pathname.startsWith('/login'))
}

const axeScan = async (page: Page) => {
  const results = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
    // The cookie-consent banner is shared site chrome, audited separately.
    .exclude('[aria-label="Privacy choices"]')
    .analyze()

  return results.violations.map((violation) => `${violation.id}: ${violation.nodes.map((node) => node.target.join(' ')).join(' | ')}`)
}

const viewports = [
  { name: 'compact', width: 320, height: 568 },
  { name: 'standard', width: 390, height: 844 },
  { name: 'large', width: 430, height: 932 },
  { name: 'desktop', width: 1280, height: 900 },
]

for (const viewport of viewports) {
  test.describe(`${viewport.name} ${viewport.width}px`, () => {
    test.use({ viewport: { width: viewport.width, height: viewport.height } })

    test('full product page reflows with usable controls and no accessibility violations', async ({ page }) => {
      await login(page)
      await page.goto(fixture.full)
      await expect(page.getByRole('heading', { level: 1 })).toContainText('E2E Hex Bolt')

      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)
      expect(overflow, 'no horizontal scroll').toBeLessThanOrEqual(1)

      // Every visible button in the page content is a comfortable touch target.
      const buttons = page.locator('main button:visible, [aria-labelledby="buy-box-heading"] button:visible')
      for (let index = 0; index < await buttons.count(); index += 1) {
        const box = await buttons.nth(index).boundingBox()
        const label = await buttons.nth(index).getAttribute('aria-label') ?? await buttons.nth(index).innerText()
        expect(box!.height, `button "${label}" is at least 44px tall`).toBeGreaterThanOrEqual(43.5)
      }

      const quantity = page.locator('#buy-quantity')
      const fontSize = await quantity.evaluate((element) => Number.parseFloat(getComputedStyle(element).fontSize))
      expect(fontSize, 'quantity input avoids iOS zoom').toBeGreaterThanOrEqual(16)

      expect(await axeScan(page)).toEqual([])
    })

    test('bare product shows only what exists', async ({ page }) => {
      await login(page)
      await page.goto(fixture.bare)

      // .first(): on small screens the sticky bar repeats the price.
      await expect(page.getByText('Price on request').first()).toBeVisible()
      await expect(page.getByRole('button', { name: 'Request a Quote' }).first()).toBeVisible()
      await expect(page.getByRole('button', { name: 'Add to Cart' })).toHaveCount(0)
      for (const heading of ['Description', 'Specifications', 'Substitutes']) {
        await expect(page.getByRole('heading', { name: heading })).toHaveCount(0)
      }
      await expect(page.locator('body')).not.toContainText(/No (description|technical|specifications)|N\/A/)

      expect(await axeScan(page)).toEqual([])
    })
  })
}

test.describe('interactions at 390px', () => {
  test.use({ viewport: { width: 390, height: 844 }, hasTouch: true })

  test('quantity, tiers, backorder notice and add to cart work by keyboard', async ({ page }) => {
    await login(page)
    await page.goto(fixture.full)

    const quantity = page.locator('#buy-quantity')
    await expect(quantity).toHaveValue('5') // starts at the minimum order

    await quantity.fill('2')
    await quantity.press('Tab')
    await expect(quantity).toHaveValue('5') // cannot go below the minimum

    // Prices update while typing, before the field loses focus.
    await quantity.fill('')
    await quantity.pressSequentially('30')
    await expect(quantity).toBeFocused()
    await expect(page.getByText('27 will be backordered')).toBeVisible()
    await expect(page.locator('[aria-labelledby="buy-box-heading"]')).toContainText('$270.00')
    await quantity.press('Tab')

    await page.getByRole('button', { name: 'Add to Cart' }).focus()
    await page.keyboard.press('Enter')
    await expect(page.getByText('Added 30 to cart')).toBeVisible()

    const cart = await page.evaluate(() => JSON.parse(localStorage.getItem('colorado-supply.cart') ?? '[]'))
    expect(cart[0]).toMatchObject({ quantity: 30, price: 9, sku: fixture.sku, url: fixture.full })
  })

  test('cart links back to the product', async ({ page }) => {
    await login(page)
    await page.goto(fixture.full)
    await page.getByRole('button', { name: 'Add to Cart' }).click()
    await page.goto('/store/cart')

    await page.getByRole('link', { name: 'E2E Hex Bolt, Grade 8, 1/4-20 x 1 in' }).click()
    await expect(page).toHaveURL(new RegExp(`${fixture.full}$`))
  })

  test('sticky bar appears once the buy box scrolls away and returns to it', async ({ page }) => {
    await login(page)
    await page.goto(fixture.full)

    const stickyBuy = page.getByRole('button', { name: 'Buy', exact: true })

    // Hidden while the buy box is on screen.
    await page.locator('#buy-quantity').scrollIntoViewIfNeeded()
    await expect(stickyBuy).toHaveCount(0)

    await page.locator('#specs-heading').scrollIntoViewIfNeeded()
    await expect(stickyBuy).toBeVisible()

    await stickyBuy.click()
    await expect(page.locator('#buy-quantity')).toBeInViewport()
    await expect(page.locator('#buy-quantity')).toBeFocused()
  })

  test('gallery thumbnails, swipe and zoom viewer', async ({ page }) => {
    await login(page)
    await page.goto(fixture.full)

    const mainImage = page.getByRole('button', { name: /^Zoom image/ }).locator('img')
    await expect(mainImage).toHaveAttribute('alt', 'E2E Hex Bolt, Grade 8, 1/4-20 x 1 in')

    await page.getByRole('button', { name: 'Show image 2 of 2' }).click()
    await expect(mainImage).toHaveAttribute('alt', 'Side view')

    // Swipe right goes back to the first image.
    await page.getByRole('button', { name: /^Zoom image/ }).evaluate((element) => {
      const touch = (x: number) => new Touch({ identifier: 1, target: element, clientX: x, clientY: 200 })
      element.dispatchEvent(new TouchEvent('touchstart', { bubbles: true, touches: [touch(100)] }))
      element.dispatchEvent(new TouchEvent('touchend', { bubbles: true, changedTouches: [touch(260)] }))
    })
    await expect(mainImage).toHaveAttribute('alt', 'E2E Hex Bolt, Grade 8, 1/4-20 x 1 in')

    await page.getByRole('button', { name: /^Zoom image/ }).click()
    const viewer = page.getByRole('dialog', { name: /image viewer/ })
    await expect(viewer).toBeVisible()
    await page.keyboard.press('ArrowRight')
    await expect(viewer.locator('img')).toHaveAttribute('alt', 'Side view')
    await page.keyboard.press('Escape')
    await expect(viewer).toHaveCount(0)
  })

  test('request a quote by keyboard creates a draft quote', async ({ page }) => {
    await login(page)
    await page.goto(fixture.full)

    await page.getByRole('button', { name: 'Request a Quote' }).first().click()
    const modal = page.getByRole('dialog')
    await expect(modal.getByLabel(/^Quantity/)).toHaveValue('5')
    await modal.getByLabel(/^Notes/).fill('E2E check')
    await modal.getByRole('button', { name: 'Send request' }).click()
    await expect(page.getByRole('status')).toContainText(/Q-\d{8}-[A-Z0-9]{4}/)

    expect(await axeScan(page)).toEqual([])

    await page.getByRole('button', { name: 'Done' }).click()
    await expect(page.getByRole('dialog')).toHaveCount(0)
  })

  test('copy buttons copy the part number', async ({ page, context }) => {
    await context.grantPermissions(['clipboard-read', 'clipboard-write'])
    await login(page)
    await page.goto(fixture.full)

    await page.getByRole('button', { name: 'Copy NSN 5305-00-269-3238' }).click()
    expect(await page.evaluate(() => navigator.clipboard.readText())).toBe('5305-00-269-3238')
  })
})
