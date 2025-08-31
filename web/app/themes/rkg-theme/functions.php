<?php
/**
 * Timber starter-theme
 * https://github.com/timber/starter-theme
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

use Timber\Timber;
use Timber\Site;

spl_autoload_register(function ($className) {
    $namespace = 'RKGTheme\\';
    $len       = strlen($namespace);
    if (strncmp($namespace, $className, $len) !== 0) {
        return;
    }
    $relativeClass = substr($className, $len);
    $relativeClass = str_replace("\\", DIRECTORY_SEPARATOR, $relativeClass);
    $file          = plugin_dir_path(__FILE__).'lib/'.$relativeClass.'.php';
    if (file_exists($file)) {
        require $file;
    }
});

$timber = new Timber();

if (!class_exists('Timber')) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>Timber not activated. Make sure you activate '
            .'the plugin in <a href="'.esc_url(admin_url('plugins.php#timber')).
            '">'.esc_url(admin_url('plugins.php')).'</a></p></div>';
    });

    add_filter('template_include', function ($template) {
        return get_stylesheet_directory().'/static/no-timber.html';
    });

    return;
}

if (!class_exists('RKGeronimo\RKGeronimo')) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>RKGeronimo plugin nije aktiviran</p></div>';
    });

    add_filter('template_include', function ($template) {
        return get_stylesheet_directory().'/static/no-rkg.html';
    });

    return;
}

/**
 * Sets the directories (inside your theme) to find .twig files
 */
Timber::$dirname = array( 'templates', 'views' );

/**
 * By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
 * No prob! Just set this value to true
 */
Timber::$autoescape = false;

new RKGTheme\RKGSite();
$functions = new RKGTheme\RKGTheme();
$functions->run();

register_sidebar(array(
    'name' => 'About right sidebar',
    'id' => 'about_right',
    'before_widget' => '<div>',
    'after_widget' => '</div>',
    'before_title' => '<h2 class="rounded">',
    'after_title' => '</h2>',
));

function ajax_check_user_logged_in()
{
    echo is_user_logged_in()?'yes':'no';
    wp_die();
}
add_action('wp_ajax_is_user_logged_in', 'ajax_check_user_logged_in');
add_action('wp_ajax_nopriv_is_user_logged_in', 'ajax_check_user_logged_in');

function rkg_user_additional_details()
{
    $info              = array();
    $info['dob']       = date('Y-m-d', strtotime($_POST['dob']));
    $info['pob']       = $_POST['pob'];
    $info['oib']       = $_POST['oib'];
    $info['tel']       = $_POST['tel'];
    $info['course']    = $_POST['course'];

    $info['weight']    = $_POST['weight'];
    $info['height']    = $_POST['height'];
    $info['shoe_size']    = $_POST['shoe_size'];

    $currentUser = wp_get_current_user();
    update_user_meta(
        $currentUser->ID,
        'pob',
        $info['pob']
    );
    update_user_meta(
        $currentUser->ID,
        'dob',
        $info['dob']
    );
    update_user_meta(
        $currentUser->ID,
        'oib',
        $info['oib']
    );
    update_user_meta(
        $currentUser->ID,
        'tel',
        $info['tel']
    );

    global $wpdb;
    $tableName = $wpdb->prefix."rkg_course_signup";
    $wpdb->insert(
        $tableName,
        array(
            'user_id'   => $currentUser->ID,
            'course_id' => $info['course'],
            'weight' => $info['weight'],
            'height' => $info['height'],
            'shoe_size' => $info['shoe_size'],
            'created'   => date("Y-m-d H:i:s"),
        )
    );

    $tableName = $wpdb->prefix."rkg_course_meta";
    $wpdb->query("UPDATE $tableName SET registered = registered + 1 WHERE id = {$info['course']};");

    echo json_encode(array('status' => 0, 'message' => __('Prijava uspješna')));

    wp_die();
}

add_action('wp_ajax_rkg_user_additional_details', 'rkg_user_additional_details');
add_action('wp_ajax_nopriv_rkg_user_additional_details', 'rkg_user_additional_details');

function rkg_course_interest_signup()
{
    $course      = $_POST['course'];
    $currentUser = wp_get_current_user();

    global $wpdb;
    $tableName = $wpdb->prefix."rkg_course_interest";
    $wpdb->insert(
        $tableName,
        array(
            'user_id'   => $currentUser->ID,
            'course_template_id' => $course,
            'created'   => date("Y-m-d H:i:s"),
        )
    );

    echo json_encode(array('status' => 0, 'message' => __('Prijava uspješna')));

    wp_die();
}

add_action('wp_ajax_course_interest', 'rkg_course_interest_signup');
add_action('wp_ajax_nopriv_course_interest', 'rkg_course_interest_signup');

function rkg_course_not_interest_signup()
{
    $course      = $_POST['course'];
    $currentUser = wp_get_current_user();

    global $wpdb;
    $tableName = $wpdb->prefix."rkg_course_interest";
    $wpdb->delete(
        $tableName,
        array(
            'user_id'   => $currentUser->ID,
            'course_template_id' => $course,
        )
    );

    echo json_encode(array('status' => 0, 'message' => __('Prijava uspješna')));

    wp_die();
}

add_action('wp_ajax_course_not_interest', 'rkg_course_not_interest_signup');
add_action('wp_ajax_nopriv_course_not_interest', 'rkg_course_not_interest_signup');

function rkg_user_excursion_signup()
{
    $info           = array();
    $info['post'] = $_POST['post'];

    global $wpdb;
    $currentUser = wp_get_current_user();

    $tableName   = $wpdb->prefix."rkg_excursion_signup";
    $result = $wpdb->insert(
        $tableName,
        array(
            'user_id'   => $currentUser->ID,
            'post_id' => $info['post'],
        )
    );

    // Delete from waiting list if user is there
    $tableName   = $wpdb->prefix."rkg_excursion_waiting";
    $waiting     = $wpdb->delete(
        $tableName,
        array(
            'user_id'   => $currentUser->ID,
            'post_id' => $info['post'],
        )
    );

    $tableName = $wpdb->prefix."rkg_excursion_meta";
    if ($waiting) {
        $wpdb->query("UPDATE $tableName SET waiting = waiting - 1 WHERE id = {$info['post']};");
    }

    if ($result) {
        $wpdb->query("UPDATE $tableName SET registered = registered + 1 WHERE id = {$info['post']};");
        echo json_encode(array('update'=>true, 'message'=>__('Prijava uspješna')));
        wp_die();
    } 
    
    echo json_encode(array('update'=>true, 'message'=>__('došlo je do greške prilikom prijave')));
    wp_die();
}

add_action('wp_ajax_rkg_user_excursion_signup', 'rkg_user_excursion_signup');

function rkg_user_excursion_signup_waiting()
{
    $info           = array();
    $info['post'] = $_POST['post'];

    global $wpdb;
    $currentUser = wp_get_current_user();
    $tableName   = $wpdb->prefix."rkg_excursion_waiting";
    $wpdb->insert(
        $tableName,
        array(
            'user_id'   => $currentUser->ID,
            'post_id' => $info['post'],
        )
    );

    $tableName = $wpdb->prefix."rkg_excursion_meta";
    $wpdb->query("UPDATE $tableName SET waiting = waiting + 1 WHERE id = {$info['post']};");

    echo json_encode(array('update'=>true, 'message'=>__('Prijava uspješna')));

    wp_die();
}

add_action('wp_ajax_rkg_user_excursion_signup_waiting', 'rkg_user_excursion_signup_waiting');

function rkg_course_signout()
{
    $info           = array();
    $info['course'] = $_POST['course'];

    $currentUser = wp_get_current_user();
    global $wpdb;
    $tableName = $wpdb->prefix."rkg_course_signup";
    $result = $wpdb->delete(
        $tableName,
        array(
            'user_id'   => $currentUser->ID,
            'course_id' => $info['course'],
        )
    );

    if ($result) {
        $tableName = $wpdb->prefix."rkg_course_meta";
        $wpdb->query("UPDATE $tableName SET registered = registered - 1 WHERE id = {$info['course']};");
    
        echo json_encode(array('update'=>true, 'message'=>__('odjava uspješna')));   
        wp_die(); 
    }

    echo json_encode(array('update'=>true, 'message'=>__('došlo je do greške kod odjave')));
    wp_die();
}

add_action('wp_ajax_rkg_course_signout', 'rkg_course_signout');
add_action('wp_ajax_nopriv_rkg_course_signout', 'rkg_course_signout');

function rkg_excursion_signout()
{
    $info           = array();
    $info['post'] = $_POST['post'];

    $currentUser = wp_get_current_user();
    global $wpdb;
    $tableName = $wpdb->prefix."rkg_excursion_signup";
    $result = $wpdb->delete(
        $tableName,
        array(
            'user_id' => $currentUser->ID,
            'post_id' => $info['post'],
        )
    );

    if ($result) {
        $tableName = $wpdb->prefix."rkg_excursion_meta";
        $wpdb->query("UPDATE $tableName SET registered = registered - 1 WHERE id = {$info['post']};");
    
        echo json_encode(array('update'=>true, 'message'=>__('odjava uspješna')));
        wp_die();
    }

    echo json_encode(array('update'=>true, 'message'=>__('došlo je do greške kod odjave')));
    wp_die();
}

add_action('wp_ajax_rkg_excursion_signout', 'rkg_excursion_signout');
add_action('wp_ajax_nopriv_rkg_excursion_signout', 'rkg_excursion_signout');

function rkg_excursion_signout_waiting()
{
    $info           = array();
    $info['post'] = $_POST['post'];

    $currentUser = wp_get_current_user();
    global $wpdb;
    $tableName = $wpdb->prefix."rkg_excursion_waiting";
    $wpdb->delete(
        $tableName,
        array(
            'user_id' => $currentUser->ID,
            'post_id' => $info['post'],
        )
    );

    $tableName = $wpdb->prefix."rkg_excursion_meta";
    $wpdb->query("UPDATE $tableName SET waiting = waiting - 1 WHERE id = {$info['post']};");

    echo json_encode(array('update'=>true, 'message'=>__('odjava uspješna')));

    wp_die();
}

add_action('wp_ajax_rkg_excursion_signout_waiting', 'rkg_excursion_signout_waiting');
add_action('wp_ajax_nopriv_rkg_excursion_signout_waiting', 'rkg_excursion_signout_waiting');

if(is_admin()){
  remove_action("admin_color_scheme_picker", "admin_color_scheme_picker");
}

add_filter('show_admin_bar', '__return_false');

add_action( 'admin_menu', 'Wps_remove_tools', 99 );
function Wps_remove_tools(){

    remove_menu_page( 'index.php' ); //dashboard

}

function dashboard_redirect($url) {
    $url = 'profile.php';
}

add_filter('login_redirect', 'dashboard_redirect');

function hide_update_notice_to_all_but_admin_users()
{
    if (!current_user_can('update_core')) {
        remove_action( 'admin_notices', 'update_nag', 3 );
    }
}
add_action( 'admin_head', 'hide_update_notice_to_all_but_admin_users', 1 );

add_action( 'admin_menu', 'linked_url' );
function linked_url() {
    add_menu_page( 'linked_url', 'Početna stranica', 'read', 'my_slug', '', 'dashicons-admin-home', 1 );
}

add_action( 'admin_menu' , 'linkedurl_function' );
function linkedurl_function() {
    global $menu;
    $menu[1][2] = get_home_url();
}

/********************************************************/
// Adding Dashicons in WordPress Front-end
/********************************************************/
add_action( 'wp_enqueue_scripts', 'load_dashicons_front_end' );
function load_dashicons_front_end() {
  wp_enqueue_style( 'dashicons' );
}

add_action( 'admin_menu', 'remove_menu_links' );
function remove_menu_links() {
    remove_menu_page('upload.php'); //remove media
}

add_action( 'admin_menu', 'change_menu_icon' );
function change_menu_icon() {
    global $menu;
    foreach ( $menu as $key => $val ) {
        if ( __('Novosti') == $val[0] ) {
            $menu[$key][6] = 'dashicons-edit';
        }
    }
}

add_action('save_post', 'wpds_check_thumbnail');
add_action('admin_notices', 'wpds_thumbnail_error');

function wpds_check_thumbnail($post_id) {

    // change to any custom post type
    if(get_post_type($post_id) != 'post')
        return;

    if ( !has_post_thumbnail( $post_id ) ) {
        // set a transient to show the users an admin message
        set_transient( "has_post_thumbnail", "no" );
        // unhook this function so it doesn't loop infinitely
        remove_action('save_post', 'wpds_check_thumbnail');
        // update the post set it to draft
        wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));

        add_action('save_post', 'wpds_check_thumbnail');
    } else {
        delete_transient( "has_post_thumbnail" );
    }
}

function wpds_thumbnail_error()
{
    // check if the transient is set, and display the error message
    if ( get_transient( "has_post_thumbnail" ) == "no" ) {
        echo "&lt;div id='message' class='error'&gt;&lt;p&gt;&lt;strong&gt;You must select Featured Image. Your Post is saved but it can not be published.&lt;/strong&gt;&lt;/p&gt;&lt;/div&gt;";
        delete_transient( "has_post_thumbnail" );
    }

}

add_filter( 'post_row_actions', 'my_disable_quick_edit', 10, 2 );
add_filter( 'page_row_actions', 'my_disable_quick_edit', 10, 2 );

function my_disable_quick_edit( $actions = array(), $post = null ) {

    // Remove the Quick Edit link
    if ( isset( $actions['inline hide-if-no-js'] ) ) {
        unset( $actions['inline hide-if-no-js'] );
    }

    // Return the set of links without Quick Edit
    return $actions;

}

function excursionCalendar()
{
    $calendar = new RKGeronimo\Calendar();
    echo $calendar->show();

    wp_die();
}

add_action('wp_ajax_excursion_calendar', 'excursionCalendar');
add_action('wp_ajax_nopriv_excursion_calendar', 'excursionCalendar');

function customize_post_admin_menu_labels() {
    global $menu;
    // global $submenu;
    $menu[3][0] = 'Pisanje objava';
    $menu[26][0] = 'Organizacija izleta';
    $menu[27][0] = 'Organizacija tečajeva';
    //$menu[70][0] = 'Administracija korisnika';
    unset($menu[25]);
    unset($menu[5]);
    echo '';
}
add_action( 'admin_menu', 'customize_post_admin_menu_labels' );

function foo_move_deck() {
        # Get the globals:
        global $post, $wp_meta_boxes;

        # Output the "advanced" meta boxes:
        do_meta_boxes( get_current_screen(), 'prenormal', $post );

        # Remove the initial "advanced" meta boxes:
        unset($wp_meta_boxes['excursion']['prenormal']);
    }

add_action('edit_form_after_title', 'foo_move_deck');

function ajaxChangeRoles()
{
    echo json_encode(array('update' => true, 'message' => __('Spremljeno')));
    wp_die();
}

add_action('wp_ajax_rkg_change_role', 'ajaxChangeRoles');

function hide_publishing_actions(){
        $postType = array('excursion', 'course');
        global $post;
        if(in_array($post->post_type, $postType)){
            echo '
                <style type="text/css">
                    #visibility{
                        display:none;
                    }
                </style>
            ';
        } else {
            echo '
                <style type="text/css">
                    #visibility-radio-password,
                    #visibility-radio-password+label,
                    #visibility-radio-password+label+br
                    {
                        display:none;
                    }
                </style>
            ';
        }
}
add_action('admin_head-post.php', 'hide_publishing_actions');
add_action('admin_head-post-new.php', 'hide_publishing_actions');

function translate_and_override_slugs( $args, $post_type ) {

    if ('course' === $post_type) {
        $args['rewrite']['slug'] = 'tecaj';
    } else if ('excursion' === $post_type) {
        $args['rewrite']['slug'] = 'izlet';
    } else if ('rkg-post' === $post_type) {
        $args['rewrite']['slug'] = 'vijest';
    } 

    return $args;
}
add_filter('register_post_type_args', 'translate_and_override_slugs', 10, 2);


// SEO stuff
function get_meta_and_og_tags() {
    global $post;

    echo '<meta property="og:site_name" content="' .get_bloginfo(). '">';
    echo '<meta property="og:url" content="' .get_permalink(). '">';

    if ( is_front_page() ) {
        echo '<meta property="og:title" content="' .get_bloginfo(). '">';
        echo '<meta name="og:description" content="' . get_bloginfo('description') . '" />' . "\n";
        $imgurl = 'app/themes/rkg-theme/assets/img/geronimo-meta.png';
        echo '<meta property="og:image" content="' .get_permalink().$imgurl.'">';
    } else {
        echo '<meta property="og:title" content="'.trim(wp_title(' ', false, 'right'))." - ".get_bloginfo(). '">';
    }
    
    if ( is_front_page() ) {

    }

    else if ( is_singular() ) {
        $des_post = strip_tags($post->post_content);
        $des_post = strip_shortcodes($post->post_content);
        $des_post = str_replace(array("\n", "\r", "\t"), ' ', $des_post);
        $des_post = strip_tags($des_post);
        $descriptionLength = strlen($des_post);
        $des_post = mb_substr($des_post, 0, 260, 'utf8' );
        if ($descriptionLength > 260) $des_post= $des_post . "...";

        echo '<meta name="description" content="' . $des_post . '" />' . "\n";
        echo '<meta property="og:description" content="' . $des_post . '">';
    }

    else if ( is_category() ) {
        $des_cat = strip_tags(category_description());
        echo '<meta name="description" content="' . $des_cat . '" />' . "\n";
        echo '<meta name="og:description" content="' . $des_cat . '" />' . "\n";
    }
}

add_action('wp_head', 'get_meta_and_og_tags', 2);

add_filter('login_url', 'login_url_to_homepage', 10, 3);

function login_url_to_homepage($login_url, $redirect, $force_reauth) {
	$login_url = site_url();
	if ( ! empty( $redirect ) ) {
		$login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );
	}
	if ( $force_reauth ) {
		$login_url = add_query_arg( 'reauth', '1', $login_url );
	}
	return $login_url;
}


// Disable admin notification when any user changes password
add_filter( 'send_password_change_email', '__return_false' );