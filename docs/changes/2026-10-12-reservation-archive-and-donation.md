# Archive flag and donation amount for equipment reservations

- Date: 2026-10-12
- Branch: dev
- Commits: fcdb498, 4a66048
- Area: backend, database
- Breaking: no (existing list calls without `state` now return more rows; see Behaviour changes)

## Summary

Equipment reservations get an `archived` flag and a nullable `donation_amount`.
The reservation list no longer filters by creation date. It excludes archived
and deleted reservations instead. A new endpoint archives or restores a
reservation. The donation amount is set on return, through the issue endpoint.

## API changes

- `GET /wp-json/rkg/v1/reservations`
  - `state` is optional. Allowed values are `0` (pending), `1` (active), `2` (completed).
  - Without `state`, all statuses are returned.
  - Always excludes `archived = true` and `state = 3` (deleted).
  - Pagination headers `X-WP-Total` and `X-WP-TotalPages` are unchanged.
- `POST /wp-json/rkg/v1/reservations/{id}/archive` (new)
  - Body: `{"archived": true}` or `{"archived": false}`.
  - Works in any status, including reservations that still have issued equipment.
  - Returns the updated reservation. Returns `404` if the reservation does not exist or is deleted.
- `POST /wp-json/rkg/v1/reservations/{id}` (issue and return)
  - Accepts `donation_amount`: a number, a numeric string with `.` or `,`, or `null` to clear it.
  - Negative or non-numeric values return `400 rkg_invalid_donation`.
  - If the key is absent, the stored value is kept.
- `POST /wp-json/rkg/v1/reservations` (create)
  - Does not accept `donation_amount`. Donation is set on return only.
- Response of every reservation endpoint gains:
  - `archived`: boolean
  - `donation_amount`: number or `null`
- `POST` and `DELETE` on `/reservations/{id}` return `404 rkg_not_found` for archived reservations.

## Data model changes

Table `{prefix}rkg_excursion_gear`:

- `archived` BOOLEAN NOT NULL DEFAULT 0
- `donation_amount` DECIMAL(10,2) NULL DEFAULT NULL

## Migrations

- `database/migrations/2026_10_12_120000_add_archived_to_excursion_gear.php`
- `database/migrations/2026_10_12_120100_add_donation_amount_to_excursion_gear.php`

Run: `php database/migrate.php migrate`. Both are idempotent (they check for the column first).

Fresh installs get the same columns from `Activator.php`.

## Behaviour changes

- Reservations created before 2026-09-25 are no longer hidden by a date filter.
  They appear in the list unless they are archived or deleted.
- Lists that previously relied on the implicit date cutoff now include older reservations.
- Archiving does not change `state` or equipment. It only hides the reservation from the list.
- An archived reservation cannot be edited, returned, or deleted until it is restored.

## Action required by other agents

- Mobile app:
  1. Call `GET /reservations` without `state` to build the Rezervacije list.
  2. The server already excludes archived and deleted reservations. Do not add a date filter on the client.
  3. Add an edit view that posts to `POST /reservations/{id}`, including `donation_amount` on return.
  4. Add an archive action that posts to `POST /reservations/{id}/archive`.
  5. Handle `400 rkg_invalid_donation` and `404 rkg_not_found` in the UI.
- Any client that assumed `state` was required on `GET /reservations` must still send it, or accept all statuses.
- Full endpoint guide (Croatian, pre-dates this process): `docs/mobile-reservations-api.md`.

## Files

- `web/app/plugins/rkg-plugin/lib/Api/EquipmentReservation.php`: routes, list query without date filter, archive endpoint, donation parsing and saving, response fields
- `web/app/plugins/rkg-plugin/lib/Helpers/Donation.php` (new): parses donation input to a float, `null`, or `false`
- `web/app/plugins/rkg-plugin/lib/Activator.php`: new columns in the table definition for fresh installs
- `database/migrations/2026_10_12_120000_add_archived_to_excursion_gear.php` (new)
- `database/migrations/2026_10_12_120100_add_donation_amount_to_excursion_gear.php` (new)
- `docs/mobile-reservations-api.md` (new): Croatian endpoint guide, not updated by this entry
