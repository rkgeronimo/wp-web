<?php
/**
 * Checks the trainee gear size rules in RKGeronimo\Helpers\GearSizing.
 *
 * GearSizing has no WordPress dependencies, so this runs with plain PHP:
 *
 *     docker compose exec -T php php web/app/plugins/rkg-plugin/tests/GearSizingTest.php
 *
 * Prints each failing case and exits with 1 when any case fails.
 *
 * @author Josip Razov <joso.razov@gmail.com>
 */

require __DIR__.'/../lib/Helpers/GearSizing.php';

use RKGeronimo\Helpers\GearSizing;

$failures = 0;
$checks   = 0;

/**
 * Compares a result with the expected value and reports a mismatch.
 *
 * @param string $label    Case description.
 * @param mixed  $expected Expected value.
 * @param mixed  $actual   Actual value.
 *
 * @return void
 */
function check($label, $expected, $actual)
{
    global $failures, $checks;
    $checks++;
    if ($expected !== $actual) {
        $failures++;
        echo 'FAIL '.$label.': expected '.var_export($expected, true)
            .', got '.var_export($actual, true)."\n";
    }
}

// Suit: height (cm), weight (kg), expected size, expects a warning.
// A size that fits both measures wins; of several, the closest to the
// range centres. Otherwise the larger of the smallest fitting sizes. 1 is
// below the chart, 9 above it.
$suits = array(
    array(165, 50, 1, true),    // both below the men's chart
    array(165, 60, 2, true),    // height below, weight is size 2
    array(167, 54, 2, false),   // both on the size 2 minimum
    array(175, 60, 2, false),   // only size 2 fits both
    array(176, 68, 3, false),
    array(178, 68, 3, false),   // 178 cm is in 3 and 4, 68 kg in 2 and 3
    array(180, 68, 3, false),
    array(172, 71, 3, false),   // only size 3 fits both
    array(175, 70, 3, false),   // 2 and 3 fit; 3 is closer to the centres
    array(172, 78, 3, false),
    array(183, 80, 4, false),   // 4 and 5 fit; 4 is closer
    array(185, 80, 4, false),   // 4 and 5 fit; 4 is closer
    array(182, 90, 5, false),   // only size 5 fits both
    array(188, 100, 6, false),  // 6 and 7 fit; 6 is closer
    array(193, 112, 7, false),  // 7 and 8 fit; 7 is closer
    array(205, 130, 8, false),  // 112+ has no upper weight
    array(168, 78, 3, false),   // no common size: smallest weight size
    array(165, 75, 3, true),    // height below, smallest weight size
    array(206, 90, 9, true),    // taller than 205 cm
    array(170, 40, 2, true),    // lighter than 54 kg
    array(130, 60, 2, true),    // height below the chart
);
foreach ($suits as $case) {
    list($height, $weight, $expected, $warns) = $case;
    $warnings = array();
    $label    = "suit {$height} cm / {$weight} kg";
    check($label, $expected, GearSizing::suitSize($height, $weight, $warnings));
    check($label.' warning', $warns, !empty($warnings));
}

// BCD and gloves follow the suit
$bcd    = array(1 => 'S', 'S', 'M', 'M', 'M', 'L', 'XL', 'XL', 'XL');
$gloves = array(1 => 'S', 'S', 'M', 'M', 'L', 'L', 'XL', 'XL', 'XL');
foreach ($bcd as $suit => $expected) {
    check("bcd for suit {$suit}", $expected, GearSizing::bcdSize($suit));
    check(
        "gloves for suit {$suit}",
        $gloves[$suit],
        GearSizing::glovesSize($suit)
    );
}

// Boots: EU shoe size, expected US boot, expects a warning.
// Between two chart rows the larger US size is used.
$boots = array(
    array(34, 3, false),
    array(35, 4, false),
    array(35.5, 4, false),
    array(36, 5, false),
    array(37.5, 6, false),
    array(40, 8, false),
    array(41, 8, false),
    array(42.5, 9, false),
    array(43, 9, false),
    array(44.5, 10, false),
    array(45, 11, false),
    array(45.5, 11, false),
    array(46, 12, false),
    array(47, 13, false),
    array(33, 3, true),
    array(48, 13, true),
);
foreach ($boots as $case) {
    list($shoe, $expected, $warns) = $case;
    $warnings = array();
    check("boots for {$shoe}", $expected, GearSizing::bootsSize($shoe, $warnings));
    check("boots for {$shoe} warning", $warns, !empty($warnings));
}

// Fins
$fins = array(36 => 'S', 37 => 'S', 38 => 'R', 43 => 'R', 44 => 'XL', 46 => 'XL');
foreach ($fins as $shoe => $expected) {
    check("fins for {$shoe}", $expected, GearSizing::finsSize($shoe));
}
check('fins for 43.5', 'R', GearSizing::finsSize(43.5));

// Lead: 10 % of weight, rounded up
$lead = array(60 => 6, 70 => 7, 72 => 8, 75 => 8, 80 => 8, 81 => 9);
foreach ($lead as $weight => $expected) {
    check("lead for {$weight} kg", $expected, GearSizing::leadKg($weight));
}

// Free-text measurements
$numbers = array(
    array('1,75', 1.75),
    array('70 kg', 70.0),
    array('42.5', 42.5),
    array(' 180 ', 180.0),
    array('abc', null),
    array('', null),
    array('0', null),
    array(null, null),
);
foreach ($numbers as $case) {
    check('parse '.var_export($case[0], true), $case[1], GearSizing::parseNumber($case[0]));
}

// Whole computation, as the course button uses it
$result = GearSizing::compute('1,75', '70 kg', '42.5');
check(
    'compute 1,75 m / 70 kg / 42.5',
    array(
        'suit_size'   => '3',
        'bcd_size'    => 'M',
        'gloves_size' => 'M',
        'lead_size'   => '7',
        'boots_size'  => '9',
        'fins_size'   => 'R',
    ),
    $result['sizes']
);
check('compute 1,75 m / 70 kg / 42.5 warnings', array(), $result['warnings']);

$result = GearSizing::compute('', '70', 'abc');
check('compute with missing values', array('lead_size' => '7'), $result['sizes']);
check(
    'compute with missing values warnings',
    array('nema ispravne visine', 'nema ispravnog broja obuće'),
    $result['warnings']
);

echo ($checks - $failures).'/'.$checks." checks passed\n";
exit($failures > 0 ? 1 : 0);
