<?php
namespace RKGeronimo\Helpers;

/**
 * Class: Superglobals
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class Superglobals
{
    /**
     * getCookies
     *
     * @return array
     */
    public function getCookies()
    {
        return $_COOKIE;
    }
}
