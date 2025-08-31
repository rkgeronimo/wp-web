<?php
namespace RKGeronimo\Helpers;

/**
 * Class Definitions
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 */
class Definitions
{
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
