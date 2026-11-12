<?php
namespace RKGeronimo\Helpers;

/**
 * Equipment sizes for course trainees, computed from body measurements.
 *
 * Trainees enter height (cm), weight (kg) and EU shoe size when they sign
 * up for an R1 course. Suit ranges are the Mares men's chart (columns
 * 2-8). Signup has no sex, so the lady columns are not used. Sizes 1 and
 * 9 are not printed: 1 is below the chart and 9 is above it.
 *
 * @author Josip Razov <joso.razov@gmail.com>
 */
class GearSizing
{
    /**
     * Mares men's length, cm, as [min, max] per suit number.
     */
    const SUIT_HEIGHT = array(
        2 => array(167, 175),
        3 => array(172, 180),
        4 => array(177, 185),
        5 => array(182, 190),
        6 => array(185, 195),
        7 => array(188, 200),
        8 => array(193, 205),
    );

    /**
     * Mares men's weight, kg, as [min, max]. Null max means no upper bound
     * (the chart's 96+ and 112+).
     */
    const SUIT_WEIGHT = array(
        2 => array(54, 70),
        3 => array(61, 79),
        4 => array(70, 88),
        5 => array(79, 97),
        6 => array(88, 107),
        7 => array(96, null),
        8 => array(112, null),
    );

    /**
     * Dive boot chart: EU shoe size of each US size. A shoe between two
     * rows takes the larger US size. 35 is US 4; 35.5 stays US 4.
     */
    const BOOTS = array(
        array(34, 3),
        array(35.5, 4),
        array(37, 5),
        array(38, 6),
        array(39, 7),
        array(41, 8),
        array(43, 9),
        array(44.5, 10),
        array(45.5, 11),
        array(46, 12),
        array(47, 13),
    );

    /**
     * Float slack when comparing a measurement with a chart value.
     */
    const TOLERANCE = 0.001;

    /**
     * Sizes for one trainee.
     *
     * Each size that can be computed is returned under the user meta key
     * the reservation pages already read (suit_size, boots_size, ...).
     * Missing or unreadable measurements and values outside the charts are
     * listed in warnings.
     *
     * @param mixed $height Height as entered, in cm or m.
     * @param mixed $weight Weight as entered, in kg.
     * @param mixed $shoe   EU shoe size as entered.
     *
     * @return array {sizes: array, warnings: string[]}
     */
    public static function compute($height, $weight, $shoe)
    {
        $sizes    = array();
        $warnings = array();

        $height = self::parseNumber($height);
        if ($height !== null && $height < 3) {
            // Entered in metres, e.g. 1,75
            $height = $height * 100;
        }
        $weight = self::parseNumber($weight);
        $shoe   = self::parseNumber($shoe);

        if ($height === null) {
            $warnings[] = 'nema ispravne visine';
        }
        if ($weight === null) {
            $warnings[] = 'nema ispravne težine';
        }
        if ($shoe === null) {
            $warnings[] = 'nema ispravnog broja obuće';
        }

        if ($height !== null && $weight !== null) {
            $suit = self::suitSize($height, $weight, $warnings);
            $sizes['suit_size']   = (string) $suit;
            $sizes['bcd_size']    = self::bcdSize($suit);
            $sizes['gloves_size'] = self::glovesSize($suit);
        }

        if ($weight !== null) {
            $sizes['lead_size'] = (string) self::leadKg($weight);
        }

        if ($shoe !== null) {
            $sizes['boots_size'] = (string) self::bootsSize($shoe, $warnings);
            $sizes['fins_size']  = self::finsSize($shoe);
        }

        return array(
            'sizes'    => $sizes,
            'warnings' => $warnings,
        );
    }

    /**
     * Suit number from height and weight.
     *
     * Sizes whose height and weight ranges both hold the trainee win; of
     * those, the one whose range centres are closest. Without a common size,
     * each measure takes its smallest fitting size and the larger of the two
     * is used.
     * Outside a chart the size is 1 or 9, with a warning.
     *
     * @param float $height   Height in cm.
     * @param float $weight   Weight in kg.
     * @param array $warnings Out-of-chart notes are appended here.
     *
     * @return int
     */
    public static function suitSize($height, $weight, array &$warnings = array())
    {
        $heightSizes = self::fittingSizes($height, self::SUIT_HEIGHT);
        $weightSizes = self::fittingSizes($weight, self::SUIT_WEIGHT);
        if (!$heightSizes) {
            $warnings[] = 'visina izvan tablice odijela';
        }
        if (!$weightSizes) {
            $warnings[] = 'težina izvan tablice odijela';
        }

        $both = array_intersect($heightSizes, $weightSizes);
        if ($both) {
            return self::closestSize(
                $both,
                array(
                    array($height, self::SUIT_HEIGHT),
                    array($weight, self::SUIT_WEIGHT),
                )
            );
        }

        // No common size: the measure that needs the larger suit sets it,
        // with the smallest size that still fits that measure.
        return max(
            self::smallestSize($heightSizes, $height, self::SUIT_HEIGHT),
            self::smallestSize($weightSizes, $weight, self::SUIT_WEIGHT)
        );
    }

    /**
     * Smallest fitting size, or 1 below the chart and 9 above it.
     *
     * @param int[] $sizes  Fitting sizes, ascending.
     * @param float $value  Measurement.
     * @param array $ranges Size => [min, max|null], in ascending size.
     *
     * @return int
     */
    private static function smallestSize(array $sizes, $value, array $ranges)
    {
        if ($sizes) {
            return $sizes[0];
        }
        $first = reset($ranges);

        return $value < $first[0] ? 1 : 9;
    }

    /**
     * Chart sizes whose range holds the value, in ascending order.
     *
     * @param float $value  Measurement.
     * @param array $ranges Size => [min, max|null], in ascending size.
     *
     * @return int[]
     */
    private static function fittingSizes($value, array $ranges)
    {
        $sizes = array();
        foreach ($ranges as $size => $range) {
            if ($value + self::TOLERANCE < $range[0]) {
                continue;
            }
            if ($range[1] !== null && $value > $range[1] + self::TOLERANCE) {
                continue;
            }
            $sizes[] = (int) $size;
        }

        return $sizes;
    }

    /**
     * Size whose range centres are closest to the measurements. Distance is
     * counted in range widths so cm and kg can be added. A tie takes the
     * larger size.
     *
     * @param int[] $sizes    Candidate sizes, ascending, not empty.
     * @param array $measures List of [value, ranges].
     *
     * @return int
     */
    private static function closestSize(array $sizes, array $measures)
    {
        $best        = null;
        $bestDistance = null;
        foreach ($sizes as $size) {
            $distance = 0;
            foreach ($measures as $measure) {
                list($value, $ranges) = $measure;
                list($min, $max)      = self::rangeBounds($ranges, $size);
                $distance += abs($value - ($min + $max) / 2) / ($max - $min);
            }
            if ($best === null || $distance <= $bestDistance + self::TOLERANCE) {
                $best         = $size;
                $bestDistance = $distance;
            }
        }

        return $best;
    }

    /**
     * Range of one size. An open range (96+) is as wide as the size below.
     *
     * @param array $ranges Size => [min, max|null].
     * @param int   $size   Suit number.
     *
     * @return array [min, max]
     */
    private static function rangeBounds(array $ranges, $size)
    {
        list($min, $max) = $ranges[$size];
        if ($max === null) {
            list($prevMin, $prevMax) = self::rangeBounds($ranges, $size - 1);
            $max = $min + ($prevMax - $prevMin);
        }

        return array($min, $max);
    }

    /**
     * BCD size from the suit number.
     *
     * @param int $suit Suit number.
     *
     * @return string
     */
    public static function bcdSize($suit)
    {
        if ($suit <= 2) {
            return 'S';
        }
        if ($suit <= 5) {
            return 'M';
        }
        if ($suit === 6) {
            return 'L';
        }

        return 'XL';
    }

    /**
     * Glove size from the suit number.
     *
     * @param int $suit Suit number.
     *
     * @return string
     */
    public static function glovesSize($suit)
    {
        if ($suit <= 2) {
            return 'S';
        }
        if ($suit <= 4) {
            return 'M';
        }
        if ($suit <= 6) {
            return 'L';
        }

        return 'XL';
    }

    /**
     * US boot size from an EU shoe size. Half sizes stay (35.5 is US 4).
     * Between two chart rows the larger US size is used.
     *
     * @param float $shoe     EU shoe size.
     * @param array $warnings Out-of-chart notes are appended here.
     *
     * @return int
     */
    public static function bootsSize($shoe, array &$warnings = array())
    {
        if ($shoe < 34 || $shoe > 47) {
            $warnings[] = 'broj obuće izvan tablice čizmica';
            $shoe       = $shoe < 34 ? 34 : 47;
        }

        $boot = self::BOOTS[count(self::BOOTS) - 1][1];
        foreach (self::BOOTS as $row) {
            if ($shoe <= $row[0] + self::TOLERANCE) {
                $boot = $row[1];
                break;
            }
        }

        return $boot;
    }

    /**
     * Fin size from an EU shoe size. Size L is not issued.
     *
     * @param float $shoe EU shoe size.
     *
     * @return string
     */
    public static function finsSize($shoe)
    {
        $shoe = (int) floor($shoe);
        if ($shoe <= 37) {
            return 'S';
        }
        if ($shoe <= 43) {
            return 'R';
        }

        return 'XL';
    }

    /**
     * Lead in kg: 10% of body weight, rounded up.
     *
     * @param float $weight Weight in kg.
     *
     * @return int
     */
    public static function leadKg($weight)
    {
        // Round first so float error can't push e.g. 70 kg to 8 kg
        return (int) ceil(round($weight * 0.1, 4));
    }

    /**
     * Number from free text such as "70 kg" or "1,75", or null.
     *
     * @param mixed $value Value as entered.
     *
     * @return float|null
     */
    public static function parseNumber($value)
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/^\d+(\.\d+)?/', $value, $match)) {
            return null;
        }

        $number = (float) $match[0];

        return $number > 0 ? $number : null;
    }
}
