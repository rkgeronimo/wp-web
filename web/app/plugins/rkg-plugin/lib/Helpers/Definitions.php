<?php
namespace RKGeronimo\Helpers;

/**
 * Class Definitions
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 */
class Definitions
{
    // Reservation status constants
    const RESERVATION_STATUS_PENDING = 0;   // Created but no equipment issued yet
    const RESERVATION_STATUS_ACTIVE = 1;    // Equipment issued, waiting return
    const RESERVATION_STATUS_COMPLETED = 2; // All equipment returned
    const RESERVATION_STATUS_DELETED = 3;   // Soft deleted

    // Equipment status constants (for inventory items)
    const EQUIPMENT_STATUS_AVAILABLE = 0;   // Available
    const EQUIPMENT_STATUS_ISSUED = 1;      // Issued
    const EQUIPMENT_STATUS_DAMAGED = 2;     // Damaged
    const EQUIPMENT_STATUS_LOST = 3;        // Lost
    const EQUIPMENT_STATUS_WRITTEN_OFF = 4; // Written off
    const EQUIPMENT_STATUS_DELETED = 5;     // Soft deleted

    /**
     * Get reservation status labels
     *
     * @return array
     */
    public function getReservationStatusLabels()
    {
        return array(
            self::RESERVATION_STATUS_PENDING   => 'Na čekanju',    // Pending
            self::RESERVATION_STATUS_ACTIVE    => 'Aktivno',       // Active
            self::RESERVATION_STATUS_COMPLETED => 'Vraćeno',       // Completed
            self::RESERVATION_STATUS_DELETED   => 'Obrisano',      // Deleted
        );
    }

    /**
     * Get equipment status labels
     *
     * @return array
     */
    public function getEquipmentStatusLabels()
    {
        return array(
            self::EQUIPMENT_STATUS_AVAILABLE   => 'Na stanju',     // Available
            self::EQUIPMENT_STATUS_ISSUED      => 'Izdano',        // Issued
            self::EQUIPMENT_STATUS_DAMAGED     => 'Neispravno',    // Damaged
            self::EQUIPMENT_STATUS_LOST        => 'Izgubljeno',    // Lost
            self::EQUIPMENT_STATUS_WRITTEN_OFF => 'Otpisano',      // Written off
            self::EQUIPMENT_STATUS_DELETED     => 'Obrisano',      // Deleted
        );
    }

    /**
     * defineEquipment
     *
     * @return array
     */
    public function defineEquipment()
    {
        $equipment = array(
            'mask'      => array(
                'name'  => 'Maska i disalica',
                'size'  => null,
                'prefix' => 'MD',
            ),
            'regulator' => array(
                'name'  => 'Regulator',
                'size' => null,
                'prefix' => array('RM', 'RA'),
            ),
            'suit'      => array(
                'name'  => 'Odijelo',
                'size' => array(2, 3, 4, 5, 6, 7, 8),
                'prefix' => array('', 'A'),
            ),
            'boots'     => array(
                'name'  => 'Čižmice',
                'size' => array(5, 6, 7, 8, 9, 10, 11, 12, 13),
                'prefix' => 'B',
            ),
            'gloves'    => array(
                'name'  => 'Rukavice',
                'size' => array('S', 'M', 'L', 'XL', 'XXL'),
                'prefix' => array('GS', 'GM','GL','GXL','G2XL'),
            ),
            'fins'      => array(
                'name'  => 'Peraje',
                'size' => array('S', 'R', 'L', 'XL'),
                'prefix' => array('S', 'R', 'L', 'XL'),
            ),
            'bcd'       => array(
                'name'  => 'KPL',
                'size' => array('XS', 'S', 'M', 'L', 'XL', 'XXL'),
                'prefix' => 'J',
            ),
            'lead' => array(
                'name'  => 'Pojas za olovo',
                'size' => null,
                'prefix' => 'O',
            ),
        );

        return $equipment;
    }
}
