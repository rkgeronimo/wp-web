# Create gear reservations for course trainees

- Date: 2026-11-11
- Branch: main
- Commits: pending
- Area: backend
- Breaking: no

## Summary

Instructors can create pending gear reservations for all trainees of a
course with one button on the course report page. For R1 courses the
equipment sizes are computed from the measurements trainees enter at sign-up,
replacing the spreadsheet the club used before.

## API changes

New admin AJAX action (WordPress `admin-ajax.php`, not the REST API):

- `POST action=rkg_course_reserve_gear`
  - Request fields: `nonce` (`rkg_admin` nonce, `rkgScript.nonce`),
    `course_id` (course post ID).
  - Requires `edit_courses`, and the caller must be the course organiser or
    have `edit_others_courses`.
  - Success: `{"success": true, "data": {"created": [{"name": string,
    "warnings": string[]}], "skipped": string[], "failed": string[]}}`.
    `skipped` lists trainees who already have a non-deleted reservation for
    the course, `failed` those whose reservation could not be saved.
  - Error: `{"success": false, "data": string}` (bad nonce dies with `-1`).

REST routes are unchanged. `GET` reservation responses from
`Api/EquipmentReservation` now also contain reservations whose `post_id` is
a course: `excursion_id` holds the course ID and `excursion` the course
title.

## Data model changes

none. Reservations are rows in `wp_rkg_excursion_gear` with
`post_id` = course ID, `state` = 0 (pending), `other` = '' and `lead_size`
set. Computed sizes are written to the existing user meta keys `suit_size`,
`boots_size`, `bcd_size`, `fins_size`, `gloves_size` and `lead_size`.

## Migrations

none

## Behaviour changes

- Course report page (`admin.php?page=course_report&post=<id>`) has a
  "Rezerviraj opremu za polaznike" button for users with `edit_courses`.
- R1 courses (course category 1): for each trainee the sizes are computed
  from `wp_rkg_course_signup.height`, `weight` and `shoe_size` and saved to
  the trainee's user meta, overwriting earlier values. The per-item
  "doesn't need it" user meta flags (`mask`, `regulator`, `suit`, `boots`,
  `gloves`, `fins`, `bcd`, `lead`) are deleted, so every item is needed.
  Sizes that can't be computed because a measurement is missing or
  unreadable are deleted from the profile and listed as warnings. The
  profile is only changed after the reservation row is saved.
- Other courses: a reservation is created with the sizes already in the
  trainee's profile, like an excursion reservation.
- Size rules: boots EU→US (34→3, 35→3, 36→4, 37→5, 38→6, 39→7, 40→7, 41→8,
  42→9, 43→10, 44→11, 45→11, 46→12, 47→13); suit is a Mares number 1–9
  (1 XS, 2 S, 3 M, 4 ML, 5 L, 6 XL, 7 2XL, 8 3XL, 9 4XL), the larger of the
  size by height (Mares men's chart: up to 175 cm → 2, 178 → 3, 181 → 4,
  184 → 5, 187 → 6, 190 → 7, 193 → 8, taller → 9; below 175 cm → 1) and the
  size by weight (from 45 kg → 1, 55 → 2, 65 → 3, 71 → 4, 78 → 5, 85 → 6,
  95 → 7, 105 → 8, 115 → 9); BCD and gloves from the suit; fins S/R/XL from
  shoe size; lead = 10% of weight rounded up. See `Helpers/GearSizing.php`
  and `tests/GearSizingTest.php`.
- Suit size lists now offer 1–9 everywhere (`Definitions::defineEquipment()`,
  the profile form and the excursion gear form). They were 2–8 and 2–7, but
  production stock has sizes 1, 8 and 9.

## Action required by other agents

Mobile: reservation lists can now include course reservations. The
`excursion` field then holds a course title; display it as the event name
rather than assuming an excursion.

## Files

- `web/app/plugins/rkg-plugin/lib/Helpers/GearSizing.php`: new, size rules.
- `web/app/plugins/rkg-plugin/lib/Courses.php`: `rkg_course_reserve_gear`
  AJAX handler.
- `web/app/plugins/rkg-plugin/lib/templates/courseReport.twig`: button and
  result area.
- `src/js/plugin/script.js` (built to `web/app/plugins/rkg-plugin/js/script.js`):
  button handler.
- `web/app/plugins/rkg-plugin/tests/GearSizingTest.php`: new, plain-PHP
  check of the size rules (`docker compose exec -T php php
  web/app/plugins/rkg-plugin/tests/GearSizingTest.php`).
- `web/app/plugins/rkg-plugin/lib/Helpers/Definitions.php`,
  `web/app/plugins/rkg-plugin/lib/templates/userMeta.twig`,
  `web/app/themes/rkg-theme/templates/partial/gear.twig`: suit sizes 1–9.
