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
$tableName       = $wpdb->prefix."rkg_excursion_meta";
$context['meta'] = $wpdb->get_row(
    "SELECT id, leaders, latitude, longitude, canceled, guests_limit"
    .", price, limitation, registered, starttime, endtime, deadline, course FROM "
    .$tableName
    ." WHERE id="
    .$post->ID
);

if (!empty($context['meta']->course)) {
    $user_id = $context['user']->id;
    $course_signup_table = $wpdb->prefix . "rkg_course_signup";
    $course_meta_table = $wpdb->prefix . "rkg_course_meta";
    
    // Check if user is in course OR is the assistant
    $user_in_course = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $course_signup_table WHERE user_id = %d AND course_id = %d",
        $user_id,
        $context['meta']->course
    ));
    
    $is_assistant = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $course_meta_table WHERE id = %d AND assistant = %d",
        $context['meta']->course,
        $user_id
    ));
    
    $context['user_in_reserved_course'] = ($user_in_course > 0 || $is_assistant > 0);
    
    // get total number of people in the reserved course
    $course_participants_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $course_signup_table WHERE course_id = %d",
        $context['meta']->course
    ));
    
    // add 1 for assistant if they exist
    $assistant_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $course_meta_table WHERE id = %d AND assistant IS NOT NULL AND assistant != 0",
        $context['meta']->course
    ));

    // Get list of course participant IDs for template
    $context['course_participant_ids'] = $wpdb->get_col($wpdb->prepare(
        "SELECT user_id FROM $course_signup_table WHERE course_id = %d",
        $context['meta']->course
    ));
    
    $context['course_participants_count'] = $course_participants_count + $assistant_exists;
    
    $course_template_table = $wpdb->prefix . "rkg_course_template";
    $posts_table = $wpdb->prefix . "posts";
    
    $context['reserved_course'] = $wpdb->get_row($wpdb->prepare(
        "SELECT p.post_title, rcm.starttime, rcm.endtime, rct.category 
         FROM $course_meta_table AS rcm 
         INNER JOIN $course_template_table AS rct ON rcm.category = rct.id 
         INNER JOIN $posts_table AS p ON rcm.id = p.id 
         WHERE rcm.id = %d",
        $context['meta']->course
    ));
} else {
    $context['user_in_reserved_course'] = false;
    $context['reserved_course'] = null;
    $context['course_participants_count'] = 0;
}

$join = $wpdb->prefix."posts";
$same = $wpdb->get_col(
    "SELECT m.id FROM "
    .$tableName
    ." AS m "
    ."INNER JOIN ".$join." AS p ON m.id = p.id "
    ." WHERE m.starttime="
    ."'".$context['meta']->starttime."'"
    ." AND p.post_status='publish'"
);

$currentKey = array_search($post->ID, $same);

if (isset($same[$currentKey-1])) {
    $context['prev'] = new Timber\Post($same[$currentKey-1]);
}

if (empty($context['prev'])) {
    $ex = $wpdb->get_var($wpdb->prepare(
            "SELECT m.id FROM ".
            $tableName
            ." AS m "
            ."INNER JOIN ".$join." AS p ON m.id = p.id "
            ." WHERE m.starttime < %s"
            ." AND p.post_status='publish'"
            ." ORDER BY m.starttime DESC LIMIT 1",
            $context['meta']->starttime
        )
    );
    if ($ex) {
        $context['prev'] = new Timber\Post($ex);
    }
}

if (isset($same[$currentKey+1])) {
    $context['next'] = new Timber\Post($same[$currentKey+1]);
}

if (empty($context['next'])) {
    $ex = $wpdb->get_var($wpdb->prepare(
            "SELECT m.id FROM ".
            $tableName
            ." AS m "
            ."INNER JOIN ".$join." AS p ON m.id = p.id "
            ." WHERE m.starttime > %s"
            ." AND p.post_status='publish'"
            ." ORDER BY m.starttime ASC LIMIT 1",
            $context['meta']->starttime
        )
    );
    if ($ex) {
        $context['next'] = new Timber\Post($ex);
    }
}

$tableName = $wpdb->prefix."rkg_excursion_signup";
$participants = $wpdb->get_col(
    "SELECT user_id FROM "
    .$tableName
    ." WHERE post_id="
    .$post->ID
    ." ORDER BY created"
);


foreach ($participants as $value) {
    // Load participants data with complete user info
    $user = new Timber\User($value);
    $context['participants'][] = $user;

    // Check if still needs reserved spots for leaders (dive masters)
    if ($context['meta']->leaders != '0') {
        $is_course_assistant = false;
        // course assisant should not count in leaders needed - he is reserved for the course participants
        // since assistant is usually not used it will not really have implication to counts
        if (!empty($context['meta']->course)) {
            $course_assistant_id = $wpdb->get_var($wpdb->prepare(
                "SELECT assistant FROM {$wpdb->prefix}rkg_course_meta WHERE id = %d",
                $context['meta']->course
            ));
            $is_course_assistant = ($user->ID == $course_assistant_id);
        }
        
        if (in_array($user->rc, array('R3', 'R4', 'I1', 'I2', 'I3')) && !$is_course_assistant) {
            $context['meta']->leaders -= 1;
        }
    }
}

$tableName = $wpdb->prefix."rkg_excursion_waiting";
$replacements = $wpdb->get_col(
    "SELECT user_id FROM "
    .$tableName
    ." WHERE post_id="
    .$post->ID
    ." ORDER BY created"
);

$tableName = $wpdb->prefix."rkg_excursion_gear";
$context['gear'] = $wpdb->get_col(
    "SELECT user_id FROM "
    .$tableName
    ." WHERE post_id="
    .$post->ID
    ." AND user_id="
    .$context['user']->id
);

foreach ($replacements as $value) {
    $context['replacements'][] = new Timber\User($value);
}

$tableName = $wpdb->prefix."rkg_excursion_guest";
$context['guests'] = $wpdb->get_results(
    "SELECT name as display_name, email as user_email, tel FROM "
    .$tableName
    ." WHERE post_id="
    .$post->ID
    ." ORDER BY created"
);

$context['user_waiting_position'] = 0;
if ($context['user']->id) {
    $waitingTableName = $wpdb->prefix."rkg_excursion_waiting";
    // Only compute position if the user actually has a row on the waiting list
    $created = $wpdb->get_var($wpdb->prepare(
        "SELECT created FROM $waitingTableName WHERE post_id = %d AND user_id = %d",
        $post->ID, $context['user']->id
    ));
    if ($created) {
        $context['user_waiting_position'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM $waitingTableName
             WHERE post_id = %d AND created < %s",
            $post->ID, $created
        ));
    } else {
        $context['user_waiting_position'] = 0;
    }
}

$tableName = $wpdb->prefix."rkg_excursion_guest";
$context['myGuests'] = $wpdb->get_results(
    "SELECT name as display_name, email as user_email, tel FROM "
    .$tableName
    ." WHERE post_id="
    .$post->ID
    ." AND user_id="
    .$context['user']->id
    ." ORDER BY created"
);

if (post_password_required($post->ID)) {
    Timber::render('single-password.twig', $context);
} elseif (!current_user_can('edit_excursion')) {
    Timber::render('single-no-pasaran.twig', $context);
} else {
    Timber::render('single-excursion.twig', $context);
}
