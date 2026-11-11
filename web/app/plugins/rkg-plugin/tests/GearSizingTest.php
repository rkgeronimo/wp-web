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

// Suit: height (cm), weight (kg), expected size, expects a warning
$suits = array(
    array(165, 50, 1, false),   // below 175 cm, XS weight
    array(165, 60, 2, false),   // below 175 cm, sized by weight
    array(175, 60, 2, false),
    array(176, 68, 3, false),
    array(178, 68, 3, false),   // height on the M limit
    array(178.5, 68, 4, false),
    array(180, 68, 4, false),   // ML by height
    array(172, 71, 4, false),   // ML by weight, boundary
    array(172, 74, 4, false),
    array(175, 70, 3, false),
    array(172, 78, 5, false),   // L by weight, boundary
    array(183, 80, 5, false),
    array(186, 80, 6, false),
    array(182, 90, 6, false),   // weight wins
    array(189, 100, 7, false),
    array(192, 110, 8, false),
    array(195, 118, 9, false),
    array(200, 90, 9, true),    // taller than the chart
    array(170, 40, 1, true),    // lighter than the chart
    array(170, 130, 9, true),   // heavier than the chart
    array(130, 60, 2, true),    // implausible height
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

// Boots: EU shoe size, expected US boot, expects a warning
$boots = array(
    array(34, 3, false),
    array(35, 3, false),
    array(36, 4, false),
    array(37.5, 5, false),      // half size rounds down
    array(40, 7, false),
    array(41, 8, false),
    array(45, 11, false),
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
