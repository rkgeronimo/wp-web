<?php
/* Template Name: course-interest */
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

$tableName          = $wpdb->prefix."rkg_course_template";
$context['coursePlaceholder'] = $wpdb->get_row(
    "SELECT * "
    ." FROM "
    .$tableName
    ." WHERE id=".$context['request']->get['id']
    ." ORDER BY priority"
        );


global $wpdb;
$tableName = $wpdb->prefix."rkg_course_interest";
$students = $wpdb->get_col(
    "SELECT user_id FROM "
    .$tableName
    ." WHERE course_template_id="
    .$context['request']->get['id']
    ." ORDER BY created"
);

foreach ($students as $value) {
    $context['students'][] = new Timber\User($value);
}


$currentUser = wp_get_current_user();

$tableName = $wpdb->prefix."rkg_course_interest";
$context['interested'] = $wpdb->get_var(
    "SELECT id FROM "
    .$tableName
    ." WHERE course_template_id="
    .$context['request']->get['id']
    ." AND user_id = "
    .$currentUser->ID
);

Timber::render(array('page-'.$post->post_name.'.twig', 'page-course-interest.twig'), $context);
