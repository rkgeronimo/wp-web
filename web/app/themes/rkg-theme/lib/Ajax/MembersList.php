<?php
namespace RKGTheme\Ajax;

use Timber;

/**
 * Class: MembersList
 *
 * Generira listu članova
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 */
class MembersList
{
    /**
     * init
     *
     * Čini listu članova dostupnu ajax pozivu
     *
     * @return void
     */
    public function init()
    {
        add_action('wp_ajax_getMembersList', array($this, 'getMembersList'));
        add_action('wp_ajax_nopriv_getMembersList', array($this, 'getMembersList'));
    }

    /**
     * getMembersList
     *
     * Dohvača članove i renderira u listu
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getMembersList()
    {
        $context            = Timber::get_context();
        $context['members'] = get_users(array(
            'role__not_in' => array('user'),
            'orderby'      => 'display_name',
            'role'    => 'member',
        ));
        foreach ($context['members'] as $key => $value) {
            $context['members'][$key]->rkg = get_user_meta($value->ID);
        }
        $templates = array('membersList.twig');
        Timber::render($templates, $context);
        wp_die();
    }
}
