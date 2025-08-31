<?php
/* Template Name: excursions */
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

global $wpdb;
$context               = Timber::get_context();
$post                  = new TimberPost();
$tableName             = $wpdb->prefix."rkg_excursion_meta";
$firstJoin             = $wpdb->prefix."users";
$secondJoin            = $wpdb->prefix."posts";
$thirdJoin             = $wpdb->prefix."rkg_excursion_signup";

$where = "WHERE rem.endtime >= ".date("'Y-m-d'");
$join  = null;
$group = null;

if (!empty($_GET['godina'])
    || !empty($_GET['naziv'])
    || !empty($_GET{'organizator'})
    || !empty($_GET{'prijavljeni'})
) {
    $where = array();
    if (!empty($_GET['godina'])) {
        $where[] = "rem.endtime > '".$_GET['godina']."-01-01'";
        if ($_GET['godina'] == date("Y")) {
            $where[] = "rem.starttime < ".date("'Y-m-d'");
        } else {
            $where[] = "rem.starttime < '".$_GET['godina']."-12-31'";
        }
    } else {
        $where[] = "rem.endtime < ".date("'Y-m-d'");
    }
    if (!empty($_GET['naziv'])) {
        $where[] = "p.post_title LIKE '%".$_GET['naziv']."%'";
    }
    if (!empty($_GET['organizator'])) {
        $tableNameUsers = $wpdb->prefix."users";
        $organiser = $wpdb->get_col(
            "SELECT id FROM ".$tableNameUsers
            ." WHERE display_name LIKE '%".$_GET['organizator']."%'"
        );

        if ($organiser) {
            $where[] = "p.post_author IN (".implode(',', $organiser).")";
        } else {
            $where[] = "1 = 0";
        }
    }
    if (!empty($_GET['prijavljeni'])) {
        $tableNameUsers = $wpdb->prefix."users";
        $participant = $wpdb->get_col(
            "SELECT id FROM ".$tableNameUsers
            ." WHERE display_name LIKE '%".$_GET['prijavljeni']."%'"
        );

        $join = "LEFT JOIN ".$thirdJoin." AS s ON rem.id = s.post_id ";
        if ($participant) {
            $where[] = "s.user_id IN (".implode(',', $participant).")";
        } else {
            $where[] = "1 = 0";
        }
        $group = " GROUP BY s.post_id";
    }
    $where = "WHERE ".implode(" AND ", $where);

    $context ['search'] = $_GET;
}

$context['excursions'] = $wpdb->get_results(
    "SELECT rem.id, "
    ."rem.latitude, rem.longitude, p.guid, rem.canceled, rem.registered, "
    ."rem.starttime, rem.endtime, rem.deadline, rem.limitation, p.post_title, "
    ."u.display_name FROM ".$tableName." AS rem "
    ."INNER JOIN ".$secondJoin." AS p ON rem.id = p.id "
    ."INNER JOIN ".$firstJoin." AS u ON p.post_author = u.id "
    .$join
    .$where
    ." AND p.post_status='publish'"
    .$group
    ." ORDER BY rem.starttime ASC"
);

$calendar = new RKGeronimo\Calendar();
$context['calendar'] = $calendar->show();

foreach ($context['excursions'] as $key => $value) {
    $context['excursions'][$key]->link = get_permalink($value->id);
}

if (!current_user_can('member_access')) {
    Timber::render('single-no-pasaran.twig', $context);
} else {
    Timber::render(array('page-'.$post->post_name.'.twig', 'page-excursions.twig'), $context);
}
