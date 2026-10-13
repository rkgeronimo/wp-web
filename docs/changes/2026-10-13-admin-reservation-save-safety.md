# Harden reservation donation, archive and admin save handling

- Date: 2026-10-13
- Branch: dev
- Commits: pending
- Area: backend
- Breaking: no

## Summary

Fixes from the review of the donation and archive work. The admin save in
Izdavanje opreme is now atomic and sends only changed donation and archive
values. The REST API rejects donation amounts the column cannot hold and
answers `rkg_not_found` for deleted reservations. Supersedes the
`edit_reservation` details in
`2026-10-12-admin-donation-and-archive-controls.md`.

## API changes

- `POST /wp-json/rkg/v1/reservations/{id}` (issue and return)
  - `donation_amount` above `99999999.99` returns `400 rkg_invalid_donation`.
    Before, it was stored clamped or as infinity. The same limit applies to
    the admin AJAX action.
  - A deleted reservation returns `404 rkg_not_found`. Before, it returned
    `404 rkg_deleted`.
- `DELETE /wp-json/rkg/v1/reservations/{id}`: unchanged. Deleting an already
  deleted reservation still returns `200` with `{"deleted": true, "id": <id>}`.
- `POST /wp-json/rkg/v1/reservations/{id}/archive`: unchanged.
- Admin AJAX action `edit_reservation`:
  - `donation_amount` and `archived` are sent by the admin page only when the
    user changed them. The server rule is unchanged: a field that is not sent
    keeps its stored value.
  - A failed write returns HTTP 500 with
    `{"success":false,"data":{"message":"Spremanje rezervacije nije uspjelo."}}`.
    Before, the handler always answered as if the save succeeded.

## Data model changes

none

## Migrations

No new migrations. The `down` callbacks of
`2026_10_12_120000_add_archived_to_excursion_gear.php` and
`2026_10_12_120100_add_donation_amount_to_excursion_gear.php` were fixed, see
Behaviour changes. Nothing needs to be re-run.

## Behaviour changes

- Equipment, donation and archive flag of one admin save are written in a
  single transaction. If any write fails, or a piece was taken in the
  meantime, nothing is saved and the admin sees an error.
- An invalid value in the donation input blocks the save in the browser
  instead of clearing the stored donation.
- A donation or archive flag set through the REST API after the admin page
  was loaded is no longer overwritten when the admin saves other fields of
  that row.
- A failed admin save shows the server's message as plain text instead of
  raw JSON, and the row's Spremi button is enabled again so the save can be
  retried without reloading.
- `php database/migrate.php rollback` of the two `2026_10_12_*` migrations
  now works on MySQL. Their `down` callbacks used `DROP COLUMN IF EXISTS`,
  which is MariaDB-only; on MySQL the rollback was recorded while the column
  stayed. The `up` callbacks and the schema are unchanged.
- The admin save and the REST API write the donation and archive flag through
  one method, `Inventory::saveReservationExtras()`.

## Action required by other agents

- Mobile app: `404 rkg_not_found` on `POST /reservations/{id}` now also
  covers deleted reservations. Remove any separate handling of `rkg_deleted`.
  Limit the donation input to `99999999.99` or handle
  `400 rkg_invalid_donation`.

## Files

- `web/app/plugins/rkg-plugin/lib/Inventory.php`: `editReservation` uses a transaction, checks write results, returns 500 on failure; new `saveReservationExtras()`
- `web/app/plugins/rkg-plugin/lib/Api/EquipmentReservation.php`: `guard()` also rejects deleted reservations and is used by `archive()`; donation and archive writes go through `Inventory::saveReservationExtras()`
- `web/app/plugins/rkg-plugin/lib/Helpers/Donation.php`: upper bound `Donation::MAX`
- `web/app/plugins/rkg-plugin/lib/Tables/Reservations.php`: wrapped long lines, no output change
- `src/js/plugin/script.js`: validates the donation input, sends donation and archived only when changed, shows the error message and re-enables Spremi on failure
- `database/migrations/2026_10_12_120000_add_archived_to_excursion_gear.php`: `down` checks INFORMATION_SCHEMA before `DROP COLUMN archived`
- `database/migrations/2026_10_12_120100_add_donation_amount_to_excursion_gear.php`: `down` checks INFORMATION_SCHEMA before `DROP COLUMN donation_amount`
- `docs/changes/2026-10-12-admin-donation-and-archive-controls.md`: `Commits` set to `cd463d3`
