# Report excursions attended while gear is out

- Date: 2026-11-19
- Branch: main
- Commits: pending
- Area: backend
- Breaking: no

## Summary

Reservation responses now say how many excursions the member has been on
since the equipment was issued, so whoever takes the gear back can see that
the member kept it for, for example, 3 excursions.

## API changes

New response field on every reservation object returned by
`GET /rkg/v1/reservations`, `POST /rkg/v1/reservations`,
`POST /rkg/v1/reservations/(?P<id>\d+)` and
`POST /rkg/v1/reservations/(?P<id>\d+)/archive`:

- `excursions_while_out`: `int|null`. The number of distinct excursions the
  member is signed up for (`wp_rkg_excursion_signup`) that are not canceled
  (`wp_rkg_excursion_meta.canceled` 0 or NULL), are published, and whose
  `starttime` is between the date of the earliest issue of a piece still out
  and today, both inclusive. The reservation's own excursion (`post_id`)
  also counts when it started before the issue date, as long as the member
  is signed up and it has started by today; it does not widen the range.
  Example: gear issued on Thursday 2026-01-01 for the Saturday excursion on
  2026-01-03, member then went on 3 more Saturday excursions without
  returning it, so the value is 4.
  - `null` when `state` is not 1 (Aktivno), when the reservation is
    archived (`archived` true), when no piece is still out, or
    when no issue date is recorded for the pieces still out.

Request fields and status codes are unchanged.

## Data model changes

none. The issue date comes from `wp_rkg_inventory.issue_date` of the pieces
still out (`state` = 1 and `user_id` = the reservation's user or NULL for
rows saved before the holder was stored), converted from UTC to the site
time zone. That column
is cleared on return, so the count is live only and is not stored.

## Migrations

none

## Behaviour changes

- The count is computed per reservation on each response, while the
  reservation is active. After the last piece is returned the field is
  `null`.
- When a piece is swapped or re-issued, its issue date resets, so the count
  starts from the earliest piece that is still out.
- A reservation that is active only because of pieces with a return status
  other than 1 (for example 2, damaged) has no piece out, so the value is
  `null`.

## Action required by other agents

Mobile: show `excursions_while_out` on the return screen of an active
reservation (for example "Went on 3 excursions since issue"). Treat `null` as
"no data" and hide it.

## Files

- `web/app/plugins/rkg-plugin/lib/Api/EquipmentReservation.php`: new
  `excursionsWhileOut()` and `excursions_while_out` in `present()`.
