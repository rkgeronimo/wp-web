<?php
/* Template Name: courses */
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * To generate specific templates for your pages you can use:
 * /mytheme/views/page-mypage.twig
 * (which will still route through this PHP file)
 * OR
 * /mytheme/page-mypage.php
 * (in which case you'll want to duplicate this file and save to the above path)
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context         = Timber::get_context();
$post            = new TimberPost();
$context['post'] = $post;


$tableName          = $wpdb->prefix."rkg_course_meta";
$firstJoin          = $wpdb->prefix."rkg_course_template";
$secondJoin         = $wpdb->prefix."posts";
$context['courses'] = $wpdb->get_results("SELECT rcm.id, rcm.category AS cat, "
    ."rct.category, rcm.starttime, rcm.endtime, rcm.deadline, rct.name, rct.priority, "
    ."p.post_title, p.post_content FROM ".$tableName." AS rcm "
    ."INNER JOIN ".$firstJoin." AS rct ON rcm.category = rct.id "
    ."INNER JOIN ".$secondJoin." AS p ON rcm.id = p.id "
    ."WHERE rcm.deadline > ".date("Y-m-d")
    ." AND p.post_status='publish'"
    ." ORDER BY rct.priority, rcm.category, rcm.starttime");

foreach ($context['courses'] as $key => $value) {
    $context['courses'][$key]->link = get_permalink($value->id);
}

Timber::render(array('page-'.$post->post_name.'.twig', 'page-courses.twig'), $context);
