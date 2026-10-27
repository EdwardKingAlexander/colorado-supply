# How to finish the Laravel 13 upgrade

Laravel 13 is already live on cogovsupply.com. This last check confirms
production is configured right. Allow about 10 minutes.

## Already done (2026-10-11)

- The deploy succeeded, and the pre-upgrade tag `pre-laravel-13-upgrade` exists.
- Public pages work: `/`, `/up`, `/admin/login` and `/login` all load, and
  `/store` redirects guests to `/login`. Session cookies are secure.

## Your checklist

Tick each item as you go.

### In Forge (forge.laravel.com, then your server, then the cogovsupply.com site)

- [ ] **1. PHP version.** Server or site settings should show PHP **8.3 or newer**.
- [ ] **2. Deploy script, PHP binary.** Open the site's **Deployments**
      tab and read the deploy script. If it names a specific PHP version,
      such as `php8.2 artisan ...`, it should be 8.3 or newer. A plain
      `php` or `$FORGE_PHP` is fine.
- [ ] **3. Deploy script, cache clear.** The same script should include
      `php artisan optimize:clear` or `php artisan view:clear`, usually near
      the end. If it's missing, add `$FORGE_PHP artisan optimize:clear` as
      the last line.
      *This matters because stale cached views broke the admin MFA code
      boxes during the upgrade.*
- [ ] **4. `.env`.** In the site's **Environment** tab, check that
      `APP_URL=https://cogovsupply.com`. Also check `SANCTUM_STATEFUL_DOMAINS`:
      it must either be missing or include `cogovsupply.com`.
      *If either is wrong, customers can't check out (the checkout gets a
      401 error).*
- [ ] **5. Staging.** Is there a second site on Forge for staging, or is
      production the only one? Just note the answer.

### On the live site

- [ ] **6. Admin login.** Go to https://cogovsupply.com/admin and log in
      **all the way through the MFA code**. Reaching the code screen isn't
      enough; the six digit boxes must be visible and accept input.
- [ ] **7. Checkout.** As a customer account, add a product to the cart and
      check out. You should land on the **Stripe payment page**. You don't
      need to pay; close the tab.

## When you come back

Open Claude Code in this project and say one of these:

- **Everything passed:** "Laravel 13 checklist all passed". Claude will mark
  phase 6 Complete, rename the folder to `laravel-13-upgrade-complete`, and
  update `ai/reference/remaining.md`.
- **Something failed:** "Laravel 13 checklist: item N failed, here's what I
  saw: ..." Paste the error or a screenshot, and Claude will fix it.

Copy-paste the values you see for items 1–5. Leave out secrets such as
passwords and API keys.

## Other things waiting (not part of this checklist)

- `main` has **13 local commits that aren't pushed yet**: NSN sourcing plus the
  product page work. Push when you're ready for all of it to go live. NSN on
  production also needs its own setup: see
  `ai/modules/nsn-sourcing-workflow-complete/RUNBOOK.md`.
- After a PC reboot, start the local PUB LOG catalog database with:
  `powershell -ExecutionPolicy Bypass -File ai\modules\nsn-sourcing-workflow-complete\start-local-mysql.ps1`
