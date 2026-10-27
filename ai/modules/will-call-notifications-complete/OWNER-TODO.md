# Will Call & Notifications: your to-do list

Everything left for the owner on this module, in order. Written 2026-10-12.
Step-by-step detail for each item is in [RUNBOOK.md](RUNBOOK.md); section
numbers below point there.

## Where things stand

- **Built:** all 7 phases. Phases 1–6 are committed (last commit `fd1672b6`).
- **Phase 7** (browser tests, hardening, runbook) is built and tested but **not
  committed**. It is waiting for your OK.
- **Text alerts** are built but switched **off** until Twilio approves you.
- **Production:** nothing is deployed or configured yet.

---

## 1. Approve Phase 7 (2 minutes, in Claude Code)

- [ ] Tell Claude **"continue"**. Claude will:
  - commit the Phase 7 files
  - mark the module complete
  - rename the folder to `ai/modules/will-call-notifications-complete`
  - update `ai/reference/remaining.md`

  Uncommitted Phase 7 files:
  - `tests/Browser/will-call.spec.ts`, `tests/Browser/support/will-call-fixture.php`
  - `playwright.config.ts`, `tests/Browser/product-page.spec.ts` (one-line fix)
  - `tests/Feature/Notifications/NotificationHardeningTest.php`
  - `app/Notifications/AppNotification.php` (retries)
  - `routes/web.php` (rate limit on "mark all read" and import order)

## 2. Production setup on Forge (about 1–2 hours)

Do these in order. Each step ends with a check in the runbook.

- [ ] **Deploy and migrate** (RUNBOOK §1).
- [ ] **Queue worker and scheduler running** (RUNBOOK §2). Without a worker,
      *no* notification of any kind goes out, not even emails.
- [ ] **Turn on pickup at your counters**: Admin → Locations → Will call
      section. Fill in the address, phone, hours and instructions (RUNBOOK §3).
- [ ] **Live bell (Reverb)** (RUNBOOK §4):
  - [ ] Forge site → Overview → turn on **Laravel Reverb** and accept the
        hostname `ws.<your-domain>`.
  - [ ] DNS: add an `A` record for `ws.<your-domain>` that points at the
        server.
  - [ ] SSL: issue a certificate covering both `<your-domain>` and
        `ws.<your-domain>`.
  - [ ] Environment: `BROADCAST_CONNECTION=reverb`, the `REVERB_*` values and
        the `VITE_REVERB_*` values (fresh, not copied from your local `.env`).
  - [ ] Redeploy so the site is rebuilt with the new settings.
- [ ] **Web push** (RUNBOOK §5):
  - [ ] Generate production VAPID keys **once** and save them somewhere safe.
        Changing them later breaks everyone's push.
  - [ ] Add `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` and
        `VAPID_SUBJECT=mailto:...` to the environment.
- [ ] **Smoke test the whole flow** (RUNBOOK §7): place a will-call order,
      watch the admin alert, mark it ready, then mark it picked up.

## 3. Text alerts: Twilio (days to weeks, mostly waiting on carriers)

Leave `SMS_ENABLED=false` until step 7 below.

- [ ] **Publish a privacy policy page.** The site has none. Twilio's A2P
      reviewers usually require one, and it should say mobile numbers and
      text consent are not sold or shared with third parties. Claude can build
      the page if you give it the wording, or ask your attorney. (`/sms-terms`
      already exists and covers the text program itself.)
- [ ] Create a Twilio account, upgrade it from trial, and buy a local
      (Colorado) number.
- [ ] Register your **A2P 10DLC Brand** (legal name and EIN exactly as on IRS
      records).
- [ ] Register the **Campaign** (Customer Care). Suggested description, sample
      messages, opt-in wording and links are in RUNBOOK §6.
- [ ] Wait for approval.
- [ ] Set the number's incoming-message webhook to
      `https://<your-domain>/api/v1/twilio/sms` (HTTP POST).
- [ ] Add `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM` (or
      `TWILIO_SMS_SERVICE_SID`) and `SMS_ENABLED=true` to the environment.
      Then run `config:cache` and `queue:restart`.
- [ ] Test it:
  - [ ] Sign up your own phone on `/notifications` and mark an order ready.
  - [ ] Reply **HELP**, then **STOP**, then **START**.
- [ ] Admins who want texts: sign up under **My notifications → Text
      alerts**.

## 4. Tell your team

- [ ] Each admin gets every store alert by default. Show them **My
      notifications** (user menu, top right of the admin panel) to mute alert
      types or channels and to turn on push or texts.
- [ ] Show the counter staff the will-call workflow. On the order screen:
  - **Ready for pickup** creates the code and tells the buyer.
  - **Picked up** needs the collector's name plus their code. If they don't
    have the code, staff check ID and tick the override.
  - **Not ready** undoes a ready mark.

## 5. Decisions for later (nothing breaks if you skip these)

These were left out on purpose. Each needs a decision from you before Claude
builds it.

- [ ] **"Back in stock" alert.** Not wired up, because the store has no
      backorder records yet. Decide when backorders become real.
- [ ] **Link in "your quote is ready".** The notification has no link because
      buyers have no quote page to land on. Decide whether to build one.
- [ ] **Old quote form.** Quotes sent through the legacy `/api/v1/store/quote`
      endpoint don't alert admins (the product-page quote button does). Decide
      whether to add the alert or retire that endpoint.
- [ ] **"Text me when it's ready" box at checkout.** Left out because texting
      requires confirming the number with a code first. Revisit after launch
      if buyers ask for it.
- [ ] **Twilio "recipient opted out" errors (21610).** These are ignored
      today, and the STOP webhook covers normal opt-outs. Optionally also mark
      the number as opted out when Twilio returns this error.

## Local development reminders

- Run `composer run dev`. It now also starts Reverb and the queue worker that
  live alerts need.
- Run the browser tests with `npm run test:browser`. It starts its own app
  server and Reverb, and cleans up after itself.
- Web push on Windows Herd needs the environment variable
  `OPENSSL_CONF=C:/Users/babyd/.config/herd/openssl.cnf`.
