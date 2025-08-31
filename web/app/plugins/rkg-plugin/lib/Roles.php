<?php
namespace RKGeronimo;

use RKGeronimo\Interfaces\InitInterface;

/**
 * Class: Roles
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @see Init
 */
class Roles implements InitInterface
{
    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        add_filter('pre_get_posts', array($this, 'seeMyPosts'));
    }

    /**
     * seeMyPosts
     *
     * @param WP_Query $query
     *
     * @return WP_Query
     *
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function seeMyPosts($query)
    {
        global $pagenow;

        if ('edit.php' != $pagenow || !$query->is_admin)
            return $query;

        if (!current_user_can('edit_others_posts')) {
            global $user_ID;
            $query->set('author', $user_ID );
        }
        return $query;
    }
}
