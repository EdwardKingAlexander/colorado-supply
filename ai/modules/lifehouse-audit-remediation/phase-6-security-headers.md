# Phase 6 - Security Headers

## Goal

Address high-severity best-practice findings for missing CSP, HSTS, COOP, and
Trusted Types in a staged way.

## Scope

- Determine the correct owner for production headers:
  Laravel middleware, Apache `.htaccess`, reverse proxy, CDN, or hosting panel.
- Add baseline security headers:
  - `Strict-Transport-Security` only when HTTPS deployment is confirmed.
  - `Cross-Origin-Opener-Policy` with the least disruptive policy that still
    satisfies the audit.
  - `Content-Security-Policy` initially in report-only mode if possible.
- Build a CSP allowlist for:
  - First-party Vite assets.
  - Google Fonts if still used.
  - Google Tag Manager/Analytics.
  - Google reCAPTCHA.
  - PDF iframe for the capabilities statement.
  - Images loaded from first-party origins after Phase 3.
- Evaluate Trusted Types:
  - Add `require-trusted-types-for 'script'` only if compatible.
  - If incompatible with current libraries, document the blocker and decide
    whether a weaker CSP is acceptable.

## Likely Files to Create/Modify

- `bootstrap/app.php` or a new middleware if Laravel owns headers.
- `app/Http/Middleware/**` if a middleware is created.
- `public/.htaccess` if Apache owns headers in this deployment.
- Environment/deployment documentation in `STATE.md` if headers are external.

## Tests to Write / Run

- Feature test for expected headers on the public landing page if Laravel owns
  them.
- Browser verification that:
  - Landing page loads.
  - Vite assets load.
  - Contact/repair reCAPTCHA still works.
  - Capabilities PDF iframe still opens.
  - No CSP violations block required functionality.
- Re-run Lighthouse best-practices audit.

## Success Criteria

- Lighthouse no longer reports missing HSTS/COOP/CSP where repo-owned.
- CSP is either safely enforced or tracked as report-only with a clear next
  decision.
- No production flow is broken by header restrictions.

**STOP. Await phase approval.**
