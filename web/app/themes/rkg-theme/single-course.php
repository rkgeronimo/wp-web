<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 *
 * @subpackage  Timber
 *
 * @since    Timber 0.1
 */

global $wpdb;
$context         = Timber::get_context();
$post            = Timber::query_post();
$context['post'] = $post;
$tableName       = $wpdb->prefix."rkg_course_meta";
$context['meta'] = $wpdb->get_row(
    "SELECT id, category, "
    ."organiser, location, terms, price, limitation, locked, registered, starttime, "
    ."endtime, deadline FROM "
    .$tableName
    ." WHERE id="
    .$post->ID
);

$context['organiser'] = new Timber\User($context['meta']->organiser);

// Drugi termini
$context['courseTerms'] = $wpdb->get_results(
    "SELECT orig.id AS id, category, "
    ."starttime, endtime, deadline FROM "
    .$tableName . " orig"
    ." LEFT JOIN wp_posts wps ON orig.id = wps.id"
    ." WHERE deadline > "
    .date("'Y-m-d'")
    ." AND category = "
    .$context['meta']->category
    ." AND orig.id != " . $post->ID
    ." AND post_type = 'course'"
    ." AND post_status = 'publish'"
    ." ORDER BY deadline"
);

foreach ($context['courseTerms'] as $key => $value) {
    $context['courseTerms'][$key]->link = get_permalink($value->id);
}

$tableName               = $wpdb->prefix."rkg_course_template";
$context['metaTemplate'] = $wpdb->get_row(
    "SELECT * FROM "
    .$tableName
    ." WHERE id="
    .$context['meta']->category
);

$context['metaTemplate']->hub3_price =
    str_replace(",", "", $context['metaTemplate']->payment_price);
$context['metaTemplate']->hub3_price =
    str_pad($context['metaTemplate']->hub3_price, 15, "0", STR_PAD_LEFT);

$tableName = $wpdb->prefix."rkg_course_signup";
$students = $wpdb->get_results(
    "SELECT user_id, payed FROM "
    .$tableName
    ." WHERE course_id="
    .$post->ID
    ." ORDER BY created"
);

foreach ($students as $value) {
    $student = new Timber\User($value->user_id);
    $context['students'][] = $student;
    if ($student->id === $context['user']->id) {
        $context['user']->payed = !!$value->payed;
    }
}

if ($context['user']) {
    $tableName = $wpdb->prefix."rkg_course_medical_meta";
    $context['helthsurvey'] = $wpdb->get_row(
        "SELECT * FROM "
        .$tableName
        ." WHERE post_id="
        .$post->ID
        ." AND user_id="
        .$context['user']->id
        ." ORDER BY created"
    );
    $tableName = $wpdb->prefix."rkg_course_liability_meta";
    $context['responsibilitysurvey'] = $wpdb->get_row(
        "SELECT * FROM "
        .$tableName
        ." WHERE post_id="
        .$post->ID
        ." AND user_id="
        .$context['user']->id
        ." ORDER BY created"
    );
}

if (post_password_required($post->ID)) {
    Timber::render('single-password.twig', $context);
} else {
    Timber::render('single-course.twig', $context);
}
