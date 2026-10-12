# Donation and archive controls in the admin reservations list

- Date: 2026-10-12
- Branch: dev
- Commits: this commit (see `git log`)
- Area: backend (admin)
- Breaking: no

## Summary

The admin equipment reservations list (Izdavanje opreme) gets two editable
columns: donation amount and archived flag. Saving a row also saves these
two values. The script URL now changes when the bundle is rebuilt, so browsers
do not keep an old copy.

## API changes

None. The REST API is unchanged. The admin AJAX action `edit_reservation`
accepts two new fields:

- `donation_amount`: number, numeric string with `.` or `,`, or empty to clear.
  Invalid values return HTTP 400 with a message.
- `archived`: `1` or `0`.

If a field is not sent, its stored value is kept.

## Data model changes

None. Uses the `archived` and `donation_amount` columns added by the earlier
migrations.

## Migrations

None in this commit. Requires the migrations from `2026-10-12-reservation-archive-and-donation.md`.

## Behaviour changes

- The admin list shows archived reservations too, with the checkbox checked.
  The list filter is unchanged.
- The plugin script is enqueued with the file's modification time as its
  version, instead of the fixed value `1.1`.

## Action required by other agents

None.

## Files

- `web/app/plugins/rkg-plugin/lib/Tables/Reservations.php`: donation and archived columns for active rows
- `web/app/plugins/rkg-plugin/lib/Inventory.php`: `editReservation` validates and saves donation and archived flag
- `src/js/plugin/script.js`: sends donation and archived values with Spremi
- `web/app/plugins/rkg-plugin/lib/RKGeronimo.php`: script version from file modification time
