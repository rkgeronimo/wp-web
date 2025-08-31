<?php
/**
 * Class: RkGeronimoDep
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 */
class RkGeronimoDep
{
    private $excursion;
    private $course;

    /**
     * __construct
     *
     * @return
     */
    public function __construct()
    {
        $this->excursion = new RKGeronimoExcursion;

    }

    /*
     * Actions perform on loading of menu pages
     */
    function rkg_admin_page()
    {
        $context   = Timber::get_context();
        $templates = array( 'rkg-admin-menu.twig' );
        Timber::render($templates, $context);
    }
}
