<?php
namespace RKGeronimo\Helpers;

/**
 * Resolves the equipment size shown for one user on a reservation.
 *
 * The reservation row can override the user's stored size, which the
 * reservation form writes for the lead belt.
 *
 * @author Josip Razov <joso.razov@gmail.com>
 */
class EquipmentSize
{
    /**
     * Size for one equipment type, or null when nothing is stored.
     *
     * @param object|null $row    Reservation row, null when unavailable.
     * @param int         $userId User id.
     * @param string      $type   Equipment type key.
     *
     * @return string|null
     */
    public static function resolve($row, $userId, $type)
    {
        $size = get_user_meta($userId, $type.'_size', true);
        if ($type === 'lead'
            && $row !== null
            && property_exists($row, 'lead_size')
            && $row->lead_size !== null
            && $row->lead_size !== ''
        ) {
            $size = $row->lead_size;
        }

        if ($size === null || $size === '' || $size === false) {
            return null;
        }

        return (string) $size;
    }
}
