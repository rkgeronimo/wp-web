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

        // check the "edit others" capability of the listed post type
        // (eg. edit_other_excursions, edit_others_courses)
        $postType = $query->get('post_type');
        $postType = is_string($postType) && $postType ? $postType : 'post';
        $postTypeObject = get_post_type_object($postType);
        $cap = $postTypeObject
            ? $postTypeObject->cap->edit_others_posts
            : 'edit_others_posts';

        if (!current_user_can($cap)) {
            global $user_ID;
            $query->set('author', $user_ID );
        }
        return $query;
    }
}
