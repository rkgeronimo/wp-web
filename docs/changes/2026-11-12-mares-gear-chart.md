# Match course gear sizes to the Mares chart

- Date: 2026-11-12
- Branch: main
- Commits: pending
- Area: backend
- Breaking: no

## Summary

R1 course gear sizing now uses the Mares men's length and weight ranges
and the dive-boot EUR column, instead of the previous stepped ranges.

## API changes

none

## Data model changes

none

## Migrations

none

## Behaviour changes

`RKGeronimo\Helpers\GearSizing` sizes change. Signup has no sex, so
the lady columns are not used. Suit number:

1. Sizes whose height range and weight range both hold the trainee are
   candidates. Of several, the one whose range centres are closest wins.
   Distance is counted in range widths, so cm and kg add up. A tie takes
   the larger size.
2. With no common size, height and weight each take their smallest
   fitting size (1 below a chart, 9 above it) and the larger of the two
   is used.
3. The open weight ranges (96+, 112+) count as wide as the size below
   when the centre is computed.

Examples: 175 cm / 70 kg = 3, 183 cm / 80 kg = 4, 168 cm / 78 kg = 3.

Men's length (cm): 2 = 167-175, 3 = 172-180, 4 = 177-185, 5 = 182-190,
6 = 185-195, 7 = 188-200, 8 = 193-205. Below 167 cm the suit size from
height is 1. Above 205 cm it is 9. Both add the warning
`visina izvan tablice odijela`.

Men's weight (kg): 2 = 54-70, 3 = 61-79, 4 = 70-88, 5 = 79-97,
6 = 88-107, 7 = 96+, 8 = 112+. Below 54 kg the suit size from weight is
1 and the warning is `težina izvan tablice odijela`. 112 kg and above
stays size 8.

Boot US size starts at EU 34 = 3, 35.5 = 4, 37 = 5, 38 = 6, 39 = 7,
41 = 8, 43 = 9, 44.5 = 10, 45.5 = 11, 46 = 12, 47 = 13. A shoe between
two rows takes the larger US size. Half sizes are not rounded down.
Outside 34-47 still warns with `broj obuće izvan tablice čizmica` and
clamps to 3 or 13.

BCD, gloves, fins and lead are unchanged.

## Action required by other agents

none

## Files

- `web/app/plugins/rkg-plugin/lib/Helpers/GearSizing.php`: chart ranges
- `web/app/plugins/rkg-plugin/tests/GearSizingTest.php`: expected sizes
