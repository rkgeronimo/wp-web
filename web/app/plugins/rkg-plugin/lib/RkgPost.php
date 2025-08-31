<?php
namespace RKGeronimo;

use RKGeronimo\Interfaces\InitInterface;
use Timber;

/**
 * Class: RkgPost
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @see InitInterface
 */
class RkgPost implements InitInterface
{

    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        add_action('init', array($this, 'createRkgPostPosttype'));
    }

    /**
     * createRkgPostPosttype
     *
     * @return void
     */
    public function createRkgPostPosttype()
    {
        $labels = array(
            'name'          => __('Novosti'),
            'singular_name' => __('Novost'),
            'add_new'       => __('Dodaj novu'),
            'add_new_item'  => __('Dodaj novu'),
            'edit_item'     => __('Uredi vijest'),
            'new_item'      => __('Dodaj novu vijesti'),
            'view_item'     => __('Pregledaj vijest'),
            'search_items'  => __('Pretraži vijesti'),
        );
        register_post_type(
            'rkg-post',
            array(
                'labels'   => $labels,
                'public'   => true,
                'menu_position' => 2,
                'menu_icon'            => 'dashicons-edit',
                'taxonomies'           => array('category'),
                'supports' => array(
                    'title',
                    'editor',
                    'author',
                    'thumbnail',
                    'revisions',
                ),
                // 'register_meta_box_cb' => array($this, 'createCourseMetaboxes'),
                'capability_type' => array('rkgPost', 'rkgPosts'),
            )
        );
    }
}
