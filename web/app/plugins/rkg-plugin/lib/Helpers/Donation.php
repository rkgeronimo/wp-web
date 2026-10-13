<?php
namespace RKGeronimo\Helpers;

/**
 * Donation amount of an equipment reservation, in EUR.
 *
 * @author Josip Razov <joso.razov@gmail.com>
 */
class Donation
{
    /**
     * Largest amount the DECIMAL(10,2) column holds.
     */
    const MAX = 99999999.99;

    /**
     * Turns posted input into a donation amount.
     *
     * @param mixed $raw Posted value.
     *
     * @return float|null|false Null for an empty value (no donation), false
     *                          when the value is not a number between 0
     *                          and MAX.
     */
    public static function parse($raw)
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $value = is_string($raw) ? str_replace(',', '.', trim($raw)) : $raw;
        if (!is_numeric($value) || $value < 0) {
            return false;
        }

        // Also catches INF from an exponent such as 1e309
        $amount = round((float) $value, 2);
        if ($amount > self::MAX) {
            return false;
        }

        return $amount;
    }
}
