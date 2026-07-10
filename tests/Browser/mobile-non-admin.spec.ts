import AxeBuilder from '@axe-core/playwright'
import { expect, test } from '@playwright/test'

const mobileViewports = [
  { name: 'compact', width: 320, height: 568 },
  { name: 'standard', width: 390, height: 844 },
  { name: 'large', width: 430, height: 932 },
]

const publicRoutes = ['/', '/repair-services', '/login', '/register']

for (const viewport of mobileViewports) {
  test.describe(`${viewport.name} mobile viewport`, () => {
    test.use({ viewport: { width: viewport.width, height: viewport.height } })

    for (const path of publicRoutes) {
      test(`${path} reflows without undersized form controls`, async ({ page }) => {
        await page.goto(path)

        const dimensions = await page.evaluate(() => ({
          viewport: document.documentElement.clientWidth,
          scrollWidth: document.documentElement.scrollWidth,
          bodyFont: Number.parseFloat(getComputedStyle(document.body).fontSize),
        }))

        expect(dimensions.scrollWidth).toBeLessThanOrEqual(dimensions.viewport + 1)
        expect(dimensions.bodyFont).toBeGreaterThanOrEqual(16)

        const controls = page.locator('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):visible, select:visible, textarea:visible, button:visible')
        for (let index = 0; index < await controls.count(); index += 1) {
          const control = controls.nth(index)
          const box = await control.boundingBox()
          expect(box, `control ${index} on ${path} has a box`).not.toBeNull()
          expect(box!.height, `control ${index} on ${path} is at least 48px tall`).toBeGreaterThanOrEqual(47.5)
        }

        const textControls = page.locator('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):visible, select:visible, textarea:visible')
        for (let index = 0; index < await textControls.count(); index += 1) {
          const fontSize = await textControls.nth(index).evaluate((element) => Number.parseFloat(getComputedStyle(element).fontSize))
          expect(fontSize, `field ${index} on ${path} uses readable text`).toBeGreaterThanOrEqual(16)
        }

        const choiceControls = page.locator('input[type="checkbox"]:visible, input[type="radio"]:visible')
        for (let index = 0; index < await choiceControls.count(); index += 1) {
          const labelBox = await choiceControls.nth(index).locator('xpath=ancestor::label[1]').boundingBox()
          expect(labelBox, `choice ${index} on ${path} has a label target`).not.toBeNull()
          expect(labelBox!.height, `choice ${index} on ${path} has a 48px label target`).toBeGreaterThanOrEqual(47.5)
        }
      })
    }
  })
}

test.describe('mobile interaction and accessibility', () => {
  test.use({ viewport: { width: 390, height: 844 } })

  test('public navigation drawer has full-size rows and closes cleanly', async ({ page }) => {
    await page.goto('/')
    const trigger = page.getByRole('button', { name: 'Open main menu' })
    await trigger.click()

    const drawer = page.locator('#public-mobile-menu')
    await expect(drawer).toBeVisible()

    const links = drawer.getByRole('link')
    for (let index = 0; index < await links.count(); index += 1) {
      const box = await links.nth(index).boundingBox()
      expect(box).not.toBeNull()
      expect(box!.height).toBeGreaterThanOrEqual(47.5)
    }

    const navigationRows = drawer.locator('.space-y-1 > a')
    for (let index = 0; index < await navigationRows.count(); index += 1) {
      const box = await navigationRows.nth(index).boundingBox()
      expect(box).not.toBeNull()
      expect(box!.height).toBeGreaterThanOrEqual(51.5)
    }

    await page.getByRole('button', { name: 'Close menu' }).click()
    await expect(drawer).toBeHidden()
    await expect(trigger).toBeFocused()
  })

  test('homepage hero leaves the next section discoverable', async ({ page }) => {
    await page.goto('/')
    const aboutTop = await page.locator('#about').evaluate((element) => element.getBoundingClientRect().top)
    expect(aboutTop).toBeLessThan(844)
  })

  for (const path of publicRoutes) {
    test(`${path} has no critical or serious axe violations`, async ({ page }) => {
      await page.goto(path)
      const results = await new AxeBuilder({ page }).analyze()
      const serious = results.violations.filter((violation) => ['critical', 'serious'].includes(violation.impact ?? ''))
      expect(serious, serious.map((violation) => `${violation.id}: ${violation.help}`).join('\n')).toEqual([])
    })
  }
})
