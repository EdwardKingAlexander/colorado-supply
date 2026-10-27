# Phase 1 — Dedicated Pages for About, Capabilities, Contact

## Goal

Give `About`, `Capabilities`, and `Contact` their own routes/pages, mirroring
the existing `RepairServices/Index.vue` pattern exactly. No homepage or nav
changes yet — Welcome.vue keeps its current sections for now (Phase 2 removes
them).

## Files to Create/Modify

- `resources/js/Pages/About/Index.vue` — `AppLayout` + `<Head>` (own
  title/meta) + `<About />` + `<Footer />`.
- `resources/js/Pages/Capabilities/Index.vue` — same pattern with
  `<Capabilities />`.
- `resources/js/Pages/Contact/Index.vue` — same pattern with `<Contact />`.
  (This is the full existing contact section/form component — the page just
  gives it its own URL. The existing `POST /contact` endpoint
  (`ContactController@store`) is unchanged; `Contact.vue` already posts
  there.)
- `app/Http/Controllers/PageController.php` (new) — simple controller with
  `about()`, `capabilities()`, `contact()` methods, each returning
  `Inertia::render(...)`. (A single small controller for these three static
  marketing pages avoids three near-empty single-method controllers.)
- `routes/web.php`:
  ```php
  Route::get('/about', [PageController::class, 'about'])->name('about.index');
  Route::get('/capabilities', [PageController::class, 'capabilities'])->name('capabilities.index');
  Route::get('/contact', [PageController::class, 'contact'])->name('contact.index');
  ```
  Public, no auth middleware — matches `/repair-services`.
- `routes/web.php` — add `->name('home')` to the existing `/` closure route
  so `Navigation.vue`'s "Home" link can use `route('home')` in Phase 3.

## Out of Scope

- Homepage content changes (Phase 2).
- Nav/footer link updates (Phase 3).
- Any rewrite of `About.vue`/`Capabilities.vue`/`Contact.vue` content —
  reused verbatim.

## Success Criteria

- `GET /about`, `/capabilities`, `/contact` each return 200, unauthenticated,
  rendering the correct Inertia component with working section content
  (verify manually or with a throwaway test).
- `npm run build` succeeds.
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**
