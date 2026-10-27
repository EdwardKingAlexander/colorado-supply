# Phase 2 - Navigation and Layout Shells

## Goal

Make public and authenticated navigation consistently touch-friendly,
safe-area aware, and accessible on phones while preserving desktop navigation
and existing destinations.

## Files to Create/Modify

- `resources/js/Components/Navigation.vue`
- `resources/js/Components/NavLink.vue`
- `resources/js/Components/ResponsiveNavLink.vue`
- `resources/js/Layouts/AppLayout.vue`
- `resources/js/Layouts/AuthenticatedLayout.vue`
- `resources/js/Layouts/GuestLayout.vue`
- `resources/js/Layouts/BrandedAuthLayout.vue` only after the active auth
  redesign phase is validated.
- `resources/js/Components/FrontEnd/Footer.vue`
- `tests/Browser/mobile-navigation.spec.ts`
- Existing route/auth feature tests where assertions need coverage for retained
  destinations.

## Implementation Requirements

- Use a 64 px top bar plus top safe-area inset and 48 px menu/account actions.
- Mobile navigation rows are at least 52 px high with 16 px labels.
- Public mobile navigation uses a modal right drawer no wider than 360 px,
  falling back to full width on smaller viewports; include a visible scrim.
- Both public and authenticated drawers trap focus, close on Escape/scrim/route
  activation, restore focus to the trigger, lock background scroll, and expose
  correct `aria-expanded`, labels, and current-page states.
- Fixed/sticky navigation does not obscure anchor targets or keyboard focus.
- Apply `safe-area-inset-left/right/top` fallbacks to headers and drawers.
- Footer links become 48 px rows on mobile without making desktop footer spacing
  unnecessarily tall.
- Do not change route destinations or implement the separate navigation
  restructure plan in this phase.

## Tests

- At 320/390/430 px, open and close each menu by pointer, keyboard, Escape, and
  link activation; assert focus return and background scroll lock.
- Assert header/menu targets and mobile link rows meet their dimensions.
- Assert no overlap between fixed header and first focusable page content.
- Verify guest, normal-user, and admin-authenticated non-admin navigation
  branches retain their current destinations.
- Desktop checks at 1280 px confirm the desktop navigation remains visible and
  drawers remain hidden.
- `npm run build`, SSR build, targeted auth/navigation feature tests, and Pint
  pass.

## Success Criteria

- Every non-admin page inherits a usable mobile header and page offset.
- Navigation is fully keyboard and touch operable with no scroll/focus leak.
- No admin/Filament layout is modified.

**STOP. Await phase approval before implementation.**
