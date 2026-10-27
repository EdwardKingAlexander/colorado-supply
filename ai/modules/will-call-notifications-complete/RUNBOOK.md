# Will Call & Notifications — Production Runbook

Checklist for turning on will call, the live bell, web push and text alerts on
the Forge server. Work top to bottom. Each section ends with a check that
proves it works. If anything goes wrong, see **Rollback**: every channel can
be switched off on its own, and email always keeps working.

Assumes the code from phases 1–7 is deployed (migrations included).

---

## 1. Deploy and migrate

1. Deploy the branch on Forge. The deploy script must run
   `php artisan migrate --force`. New tables and columns: will-call columns on
   `locations` and `orders`, `admin_notification_preferences`,
   `push_subscriptions`, `sms_recipients`. All migrations are additive.
2. Composer installs `laravel/reverb`, `laravel-notification-channels/webpush`
   and `laravel-notification-channels/twilio`. No PHP extensions are needed
   beyond the usual `openssl`, `gmp` (recommended for push speed) and `curl`.

**Check:** `php artisan migrate:status` shows no pending migrations.

## 2. Queue worker and scheduler (required)

Every notification is queued, one job per channel. Without a worker, nothing
is sent: no emails, bell entries, live toasts, pushes or texts.

1. Forge → server → **Daemons** (or site → **Queue**): make sure a worker runs
   `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`.
   Notifications cap their own retries at 3, with 1- and 5-minute backoff.
2. The deploy script should end with `php artisan queue:restart`, so workers
   pick up new code.
3. Forge → server → **Scheduler**: make sure `php artisan schedule:run` runs
   every minute. The module schedules these jobs (America/Denver time):
   - `will-call:remind` daily at 09:00: pickup reminders for buyers at 3 and
     7 days and the admin "uncollected" alert at 10 days
     (`config/will_call.php`).
   - `notifications:prune` daily at 03:30: deletes old read bell entries.

**Check:** `php artisan schedule:list` shows both jobs. After any test alert,
`select count(*) from jobs` drops back to 0 within seconds.

## 3. Pickup counters

1. Admin → **Locations** → edit each counter that hands out orders → **Will
   call** section: turn on pickup, then fill in the address, phone, hours and
   pickup instructions.
2. Buyers only see counters that belong to their company (admins and buyers
   with no company see them all).

**Check:** on `/store/checkout`, with something in the cart, **Will Call**
appears and lists the counter.

## 4. Live bell (Laravel Reverb)

### Turn it on in Forge
1. Site → **Overview** → application panel → turn on **Laravel Reverb**.
   Forge creates the Reverb daemon, tunes the server, and adds an Nginx server
   block that proxies the public hostname to the daemon port.
2. Public hostname: accept the default `ws.<your-domain>` (or choose another),
   with port **8080** (internal).
3. DNS: add an `A` record for that hostname that points at the server.
4. Site → **SSL**: issue a certificate that covers the Reverb hostname (for
   example Let's Encrypt for `<your-domain>, ws.<your-domain>`). Forge
   installs it on the Reverb server block, which gives `wss://`. If Reverb was
   switched on before the certificate existed, request it from the SSL tab
   afterwards.
5. Forge appends `php artisan reverb:restart` to the deploy script if it's
   missing. Keep it.

### Environment (site → Environment)
```
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<random number>
REVERB_APP_KEY=<random string>
REVERB_APP_SECRET=<random string>
REVERB_HOST=ws.<your-domain>
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```
- Forge usually fills `REVERB_*` in for you. Generate fresh production values;
  never copy the local ones.
- The `VITE_*` values are baked into the JavaScript bundle at build time. The
  deploy script must run `npm ci && npm run build` **after** these are set.
  After changing them, redeploy.
- The CSP `connect-src` is built from `REVERB_HOST`, `REVERB_PORT` and
  `REVERB_SCHEME` (`SecurityHeaders`), so it needs no separate change.
- Run `php artisan config:cache` after editing the environment (the deploy
  script normally does this).

**Check:**
1. Log in as a buyer, then open the browser dev tools → Network → WS. A
   connection to `wss://ws.<your-domain>/app/...` should show
   `pusher_internal:subscription_succeeded` for `private-App.Models.User.<id>`.
2. In another window, as an admin, open an order and use **Ready for pickup**
   (or `php artisan tinker` →
   `User::find(<id>)->notify(new App\Notifications\OrderStatusUpdated(...))`).
   A toast appears on the buyer's page without a reload, and the bell count
   goes up.
3. In the admin panel, a new store order shows a Filament toast and the bell
   updates live. Without Reverb, the bell still refreshes every 60 seconds.

## 5. Web push

1. Generate production VAPID keys **once**:
   `php artisan webpush:vapid --show` (on the server, or locally; on Windows
   Herd it only works with `OPENSSL_CONF=C:/Users/babyd/.config/herd/openssl.cnf`
   set). Never reuse the local keys, and never rotate the production keys
   casually: changing them silently breaks every existing subscription.
2. Environment:
   ```
   VAPID_PUBLIC_KEY=<public>
   VAPID_PRIVATE_KEY=<private>
   VAPID_SUBJECT=mailto:orders@<your-domain>
   ```
3. HTTPS is required (already true in production). `public/sw.js` must be
   served from the site root (it is, as a static file).
4. iPhone and iPad: push only works after the buyer adds the site to the Home
   Screen (iOS 16.4+). The opt-in card explains this.

**Check:** on `/notifications`, click **Turn on** in the push card and allow
it in the browser. Then trigger any alert for that buyer: a system
notification appears even with the tab closed. Admins test from
**My notifications → Push**. Dead devices (the push service returns 404 or
410) are removed automatically.

## 6. Text alerts (Twilio): only after A2P approval

SMS stays **off** (`SMS_ENABLED=false`) until carriers approve the campaign.
Texting US numbers from a 10-digit number without A2P 10DLC registration gets
messages blocked and can draw fines.

### Register (one time, takes days to weeks)
1. Create a Twilio account, upgrade it from trial, and buy a local number
   (Colorado area code).
2. Twilio Console → **Messaging → Regulatory compliance → A2P 10DLC**:
   1. **Brand**: legal business name, EIN, address and website, exactly as on
      IRS records.
   2. **Campaign**: use case *Customer Care* (or *Account Notifications*).
      Description: "Order status, will-call pickup ready and pickup reminder
      texts to customers who opt in on our website, and new-order alerts to
      our staff."
      - Sample messages: copy two real ones, for example "Colorado Supply:
        Order COGOV-202610-00004 is ready at Front Range Will Call. Pickup
        code WC-7KQ4. Reply STOP to opt out."
      - Opt-in description: "Customers enter their mobile number on their
        account's Notifications page, tick an unchecked consent box, and
        confirm with a 6-digit code we text them. Terms:
        https://<your-domain>/sms-terms".
      - Opt-out: STOP. Help: HELP.
      - Link the Messaging Service to the number.
   3. Wait for approval.
3. Number → **Messaging configuration** → *A message comes in* → Webhook
   `https://<your-domain>/api/v1/twilio/sms` (HTTP POST). This handles STOP,
   START and HELP. Twilio signs each request, and the app rejects unsigned
   ones. The URL must match exactly, including https and no trailing slash.

### Environment (after approval)
```
TWILIO_ACCOUNT_SID=AC...
TWILIO_AUTH_TOKEN=...
TWILIO_FROM=+1303XXXXXXX        # or TWILIO_SMS_SERVICE_SID=MG... for the Messaging Service
SMS_ENABLED=true
# optional: SMS_QUIET_START=21:00 SMS_QUIET_END=08:00 SMS_TIMEZONE=America/Denver
```
Then run `php artisan config:cache` and `php artisan queue:restart`.

**Check:**
1. As a buyer on `/notifications`: enter your mobile number, tick consent, and
   enter the code you receive. The card shows **On**.
2. Mark one of that buyer's will-call orders **Ready for pickup**. The text
   arrives, unless it's between 21:00 and 08:00 Denver time; then it arrives
   at 08:00.
3. Reply **HELP**: the help text comes back. Reply **STOP**: Twilio confirms
   and the card shows opted out. Reply **START** to resume.

## 7. Post-deploy smoke test (the whole flow)

1. A buyer places a Will Call order, choosing a counter.
2. Every admin gets "New will call order …" live in the admin bell, by email,
   and (if subscribed) by push or text.
3. An admin marks it **Ready for pickup**. The buyer gets a toast, an email
   with the pickup code, and a push or text if they're on.
4. The admin marks it **Picked up**, with the collector's name and the code.
   The buyer is told the order was collected.
5. `failed_jobs` is empty, and `storage/logs/laravel.log` has no
   notification errors.

The same flow runs automatically in `tests/Browser/will-call.spec.ts`
(`npm run test:browser`, local only).

## Rollback

Each channel switches off on its own through the environment. Run
`php artisan config:cache && php artisan queue:restart` after any change.

| Problem | Switch | Effect |
|---|---|---|
| Reverb down or misbehaving | `BROADCAST_CONNECTION=log` (and stop the daemon if needed) | No live toasts; the buyer bell updates on page loads and the admin bell polls every 60 s. Email unaffected. |
| Push errors | Remove `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` | Push opt-in hides; no pushes sent. Subscriptions are kept for when keys return (use the **same** keys). |
| Texts going wrong | `SMS_ENABLED=false` | Nothing is texted and sign-up hides. Consent records are kept. |
| Will call itself | Admin → Locations: turn off pickup at every counter | Checkout shows shipping only. Existing will-call orders keep working in the admin. |

A code rollback (redeploying the previous commit) is safe without reverting
migrations. The new columns and tables are unused by older code. Don't run
`migrate:rollback` in production: it drops pickup codes and consent records.

## Reference
- Channels per alert type: `app/Notifications/NotificationType.php`.
- Admin mutes: **My notifications** (user menu), stored in
  `admin_notification_preferences`. A missing row means on.
- Forge Reverb docs: https://forge.laravel.com/docs/sites/laravel
- Laravel Reverb docs: https://laravel.com/docs/reverb
