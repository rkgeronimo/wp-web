<?php
namespace RKGeronimo\Helpers;

/**
 * Equipment sizes for course trainees, computed from body measurements.
 *
 * Trainees enter height (cm), weight (kg) and EU shoe size when they sign
 * up for an R1 course. The rules below replace the spreadsheet the club
 * used to size their rental equipment.
 *
 * @author Josip Razov <joso.razov@gmail.com>
 */
class GearSizing
{
    /**
     * Mares suit number (1 XS, 2 S, 3 M, 4 ML, 5 L, 6 XL, 7 2XL, 8 3XL,
     * 9 4XL) by height: the tallest height (cm) each size fits, from the
     * Mares men's chart. 9 continues the chart's 3 cm steps. Anyone below
     * 175 cm can fit size 1, so their suit is set by weight.
     */
    const SUIT_HEIGHT_MAX = array(
        1 => 174.99,
        2 => 175,
        3 => 178,
        4 => 181,
        5 => 184,
        6 => 187,
        7 => 190,
        8 => 193,
        9 => 196,
    );

    /**
     * Suit number by weight: the lightest weight (kg) of each size. A weight
     * on a boundary takes the larger size. The Mares chart has no weights;
     * these are the club's ranges, with M and L split into M, ML and L.
     */
    const SUIT_WEIGHT_MIN = array(
        1 => 45,
        2 => 55,
        3 => 65,
        4 => 71,
        5 => 78,
        6 => 85,
        7 => 95,
        8 => 105,
        9 => 115,
    );

    /**
     * Upper end of the weight chart (kg).
     */
    const SUIT_WEIGHT_MAX = 125;

    /**
     * Heights (cm) below this are probably typos and get a warning.
     */
    const SUIT_HEIGHT_MIN = 140;

    /**
     * EU shoe size to US boot size. Where the chart overlaps, the smaller
     * boot is used.
     */
    const BOOTS = array(
        34 => 3,
        35 => 3,
        36 => 4,
        37 => 5,
        38 => 6,
        39 => 7,
        40 => 7,
        41 => 8,
        42 => 9,
        43 => 10,
        44 => 11,
        45 => 11,
        46 => 12,
        47 => 13,
    );

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
     * Suit number from height and weight: the larger of the size by height
     * and the size by weight. Outside the charts the size is 1 or 9, with a
     * warning.
     *
     * @param float $height   Height in cm.
     * @param float $weight   Weight in kg.
     * @param array $warnings Out-of-chart notes are appended here.
     *
     * @return int
     */
    public static function suitSize($height, $weight, array &$warnings = array())
    {
        $byHeight = 9;
        foreach (self::SUIT_HEIGHT_MAX as $size => $max) {
            if ($height <= $max) {
                $byHeight = $size;
                break;
            }
        }
        if ($height < self::SUIT_HEIGHT_MIN
            || $height > self::SUIT_HEIGHT_MAX[9]
        ) {
            $warnings[] = 'visina izvan tablice odijela';
        }

        $byWeight = 1;
        foreach (self::SUIT_WEIGHT_MIN as $size => $min) {
            if ($weight >= $min) {
                $byWeight = $size;
            }
        }
        if ($weight < self::SUIT_WEIGHT_MIN[1]
            || $weight > self::SUIT_WEIGHT_MAX
        ) {
            $warnings[] = 'težina izvan tablice odijela';
        }

        return max($byHeight, $byWeight);
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
     * US boot size from an EU shoe size. Half sizes round down.
     *
     * @param float $shoe     EU shoe size.
     * @param array $warnings Out-of-chart notes are appended here.
     *
     * @return int
     */
    public static function bootsSize($shoe, array &$warnings = array())
    {
        $shoe = (int) floor($shoe);
        if ($shoe < 34 || $shoe > 47) {
            $warnings[] = 'broj obuće izvan tablice čizmica';
            $shoe       = $shoe < 34 ? 34 : 47;
        }

        return self::BOOTS[$shoe];
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
