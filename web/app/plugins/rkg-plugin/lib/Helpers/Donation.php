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
     * Turns posted input into a donation amount.
     *
     * @param mixed $raw Posted value.
     *
     * @return float|null|false Null for an empty value (no donation), false
     *                          when the value is not a non-negative number.
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

        return round((float) $value, 2);
    }
}
