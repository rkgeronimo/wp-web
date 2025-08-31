<?php
/* Template Name: news */
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

$category = isset($_GET['cat']) ? $_GET['cat'] : null;

$args['numberposts'] = 30;
$args['post_type']   = 'rkg-post';
$args['post_status']   = array('publish', 'private');
if ($category) {
    $args['category'] = $category;
}

$context['news'] = Timber::get_posts($args);

foreach ($context['news'] as $key => $value) {
    $context['news'][$key]->categories = get_the_category(
        $context['news'][$key]->ID
    );

    $coverImageId = $context['news'][$key]->cover_image;

    $context['news'][$key]->cover_image = new Timber\Image($coverImageId);
}

$context['categories']   = get_categories();
$context['categorie_id'] = $category;

$context['rkg_side'] = Timber::get_widgets('about_right');

Timber::render(array('page-'.$post->post_name.'.twig', 'page-news.twig'), $context);
