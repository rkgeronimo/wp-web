# Instructor-editable diving profiles admin page

- Date: 2026-10-16
- Branch: feat/instructor-diver-profiles
- Commits: pending
- Area: backend (admin)
- Breaking: no

## Summary

Adds a new admin page, "Ronilački profili", where instructors can edit any
member's diving profile (category, brevet number, certifications) without being
granted the broad `edit_users` capability. The page is gated by `edit_courses`,
so instructors, administrators and the super admin can use it. The existing
WordPress user-profile screen is unchanged.

## API changes

none

## Data model changes

none. The page writes existing user meta keys only: `rc`, `cardNumber`, `log`,
`co`, `nitrox`, `dry_suit`, `DAN_op`.

## Migrations

none

## Behaviour changes

- New top-level admin menu item "Ronilački profili" (slug `rkg_diver_profiles`),
  visible to roles with `edit_courses`.
- The page lists all users; opening a user shows the "Ronilački profil" section
  and saves the fields above. "Ukupno izleta" and "Izleta u zadnjih 6 mjeseci"
  stay read-only (they are computed from excursion sign-ups).
- The save is nonce-protected (`check_admin_referer`) and capability-checked;
  `rc` is validated against the allowed category list and `cardNumber` is
  sanitized. No email, password, role or other profile data is writable here.
- The "Ronilački profil" markup now lives in a shared partial
  (`diverProfile.twig`) used by both the user-profile screen and the new page.

## Action required by other agents

none

## Files

- `web/app/plugins/rkg-plugin/lib/Users.php`: `createDiverProfiles()`,
  `diverProfilesPage()`, `saveDiverProfile()`, `diverCategories()`,
  `excursionCounts()`; `userMeta()` now reuses the two helpers
- `web/app/plugins/rkg-plugin/lib/templates/diverProfile.twig` (new): shared
  "Ronilački profil" fields
- `web/app/plugins/rkg-plugin/lib/templates/diverProfiles.twig` (new): list and
  edit views for the admin page
- `web/app/plugins/rkg-plugin/lib/templates/userMeta.twig`: includes the shared
  partial instead of inline markup
