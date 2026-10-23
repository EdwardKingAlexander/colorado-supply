import AxeBuilder from '@axe-core/playwright'
import { execFileSync } from 'node:child_process'
import { randomBytes } from 'node:crypto'
import { expect, test, type Page } from '@playwright/test'

// Customer portal rebrand QA (ai/modules/customer-portal-ui-rebrand-complete, phase 6).
// The fixture creates a throwaway company, two locations and a buyer in the
// database the Playwright server uses, and removes them afterwards.
// Screenshots land in the module's qa/ folder for review.
const password = randomBytes(18).toString('hex')
const token = randomBytes(4).toString('hex')
const qaDir = 'ai/modules/customer-portal-ui-rebrand-complete/qa'

let buyerEmail: string

// Local dev serves images from the Vite dev server over Herd's self-signed certificate.
test.use({ ignoreHTTPSErrors: true })

const runFixture = (command: 'setup' | 'teardown') => execFileSync(
  'php',
  ['tests/Browser/support/customer-portal-fixture.php', command],
  { env: { ...process.env, E2E_PASSWORD: password, E2E_TOKEN: token }, encoding: 'utf8' },
)

test.beforeAll(() => {
  buyerEmail = JSON.parse(runFixture('setup')).buyerEmail
})

test.afterAll(() => {
  runFixture('teardown')
})

const login = async (page: Page) => {
  await page.goto('/login')
  // The consent banner mounts after hydration and covers the form on a fresh browser.
  const essentialOnly = page.getByRole('button', { name: 'Essential only' })
  if (await essentialOnly.waitFor({ timeout: 3_000 }).then(() => true, () => false)) {
    await essentialOnly.click()
  }
  await page.locator('#email').fill(buyerEmail)
  await page.locator('#password').fill(password)
  await page.getByRole('button', { name: 'Log in' }).click()
  await page.waitForURL((url) => !url.pathname.startsWith('/login'))
}

const setTheme = async (page: Page, theme: 'light' | 'dark') => {
  await page.evaluate((value) => window.localStorage.setItem('cs-theme', value), theme)
}

const accountPages = [
  { path: '/account', heading: 'Purchasing overview', tab: 'Overview' },
  { path: '/account/reports', heading: 'Purchasing reports', tab: 'Reports' },
  { path: '/account/notifications', heading: 'Notifications', tab: 'Notifications' },
  { path: '/account/company', heading: /E2E Portal Company/, tab: 'Company' },
  { path: '/account/profile', heading: 'Profile', tab: 'Profile' },
]

test.describe('desktop', () => {
  test.use({ viewport: { width: 1440, height: 1000 } })

  test('account pages share the branded shell and account nav', async ({ page }) => {
    await login(page)

    for (const account of accountPages) {
      await page.goto(account.path)
      await expect(page.getByRole('heading', { level: 1 })).toHaveText(account.heading)
      const logo = page.locator('header img[src*="logo-cleansed"]:visible')
      await expect(logo).toBeVisible()
      await expect.poll(() => logo.evaluate((image: HTMLImageElement) => image.naturalWidth)).toBeGreaterThan(0)
      await expect(page.getByRole('navigation', { name: 'Account' }).getByRole('link', { name: account.tab })).toHaveAttribute('aria-current', 'page')
      await expect(page.locator('svg[viewBox="0 0 316 316"]')).toHaveCount(0)
    }
  })

  test('the theme choice applies immediately, persists, and leaves public pages dark', async ({ page }) => {
    await login(page)
    await page.goto('/account')
    await expect(page.locator('html')).toHaveClass(/dark/)

    await page.getByRole('button', { name: 'E2E Portal Buyer' }).click()
    await page.getByRole('menuitem', { name: /^Light theme/ }).click()
    await expect(page.locator('html')).not.toHaveClass(/dark/)

    await page.reload()
    await expect(page.locator('html')).not.toHaveClass(/dark/)
    await expect(page.locator('html')).toHaveAttribute('data-portal-theme', '')

    await page.goto('/')
    await expect(page.locator('html')).toHaveClass(/dark/)
  })

  for (const theme of ['light', 'dark'] as const) {
    test(`account pages pass axe in ${theme} mode`, async ({ page }) => {
      await login(page)
      await setTheme(page, theme)

      for (const account of accountPages) {
        await page.goto(account.path)
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
        const results = await new AxeBuilder({ page }).analyze()
        const serious = results.violations.filter((violation) => ['critical', 'serious'].includes(violation.impact ?? ''))
        expect(serious, `${account.path}: ${serious.map((violation) => `${violation.id}: ${violation.help} (${violation.nodes.map((node) => node.target.join(' ')).slice(0, 3).join(', ')})`).join('\n')}`).toEqual([])
      }
    })

    test(`desktop screenshots in ${theme} mode`, async ({ page }) => {
      await login(page)
      await setTheme(page, theme)

      for (const path of ['/account', '/account/profile', '/account/company']) {
        await page.goto(path)
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
        await page.screenshot({ path: `${qaDir}/desktop-${theme}${path.replace('/account', '').replaceAll('/', '-') || '-dashboard'}.png`, fullPage: true })
      }
    })
  }
})

test.describe('mobile', () => {
  test.use({ viewport: { width: 390, height: 844 } })

  for (const theme of ['light', 'dark'] as const) {
    test(`account pages reflow at 390px in ${theme} mode`, async ({ page }) => {
      await login(page)
      await setTheme(page, theme)

      for (const account of accountPages) {
        await page.goto(account.path)
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)
        expect(overflow, `${account.path} scrolls sideways`).toBeLessThanOrEqual(1)
      }

      await page.goto('/account')
      await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
      await page.screenshot({ path: `${qaDir}/mobile-${theme}-dashboard.png`, fullPage: true })
    })
  }

  test('the mobile drawer carries the theme switch', async ({ page }) => {
    await login(page)
    await page.goto('/account')
    await page.getByRole('button', { name: 'Open account menu' }).click()
    await page.getByRole('radio', { name: 'Light' }).check({ force: true })
    await expect(page.locator('html')).not.toHaveClass(/dark/)
    await page.getByRole('radio', { name: 'System' }).check({ force: true })
    expect(await page.evaluate(() => window.localStorage.getItem('cs-theme'))).toBe('system')
  })
})
