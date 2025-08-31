<?php
namespace RKGTheme;

use Timber\Timber;
use Timber\Site;

/**
 * Class: RKGSite
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @see \Timber\Site
 *
 * We're going to configure our theme inside of a subclass of Timber\Site
 * You can move this to its own file and include here via php's include("MySite.php")
 */
class RKGSite extends \Timber\Site
{
    /** Add timber support. */
    public function __construct()
    {
        add_action('after_setup_theme', array($this, 'themeSupports'));
        add_filter('timber_context', array($this, 'addToContext'));
        add_filter('get_twig', array($this, 'addToTwig'));
        add_action('init', array($this, 'registerPostTypes'));
        add_action('init', array($this, 'registerTaxonomies'));
        add_action('init', array($this, 'registerMenus'));
        parent::__construct();
    }

    /**
     * registerPostTypes
     *
     * @return void
     *
     * This is where you can register custom post types.
     */
    public function registerPostTypes()
    {
    }

    /**
     * registerTaxonomies
     *
     * @return void
     *
     * This is where you can register custom taxonomies.
     */
    public function registerTaxonomies()
    {
    }

    /**
     * addToContext
     *
     * @param string $context context['this'] Being the Twig's {{ this }}.
     *
     * @return array
     *
     * This is where you add some context
     */
    public function addToContext($context)
    {
        $context['signups']                   = $this->getUserSignups(
            $context['user']
        );
        $context['actual_courses']            = $this->getActualCourses(
            $context['signups']['courses'] ?? null
        );
        $context['actual_excursions']         = $this->getActualExcursions(
            $context['signups']['excursions'] ?? null
        );
        $context['actual_excursions_waiting'] = $this->getActualExcursions(
            $context['signups']['excursions_waiting'] ?? null
        );
        $context['menu']                      = new \Timber\Menu();
        $context['rkg_user_menu']             = new \Timber\Menu(
            'rkg-user-menu'
        );
        $context['site']                      = $this;

        if ($context['user']
            && !array_key_exists('member', (array) $context['user']->roles)
        ) {
            $context['memberDebth'] = date('Y');;
        }
        // Check if membership paid for the next year
        else if ($context['user']
            && array_key_exists('member', (array) $context['user']->roles)
        ) {
            $now   = strtotime("now");
            $start = strtotime(date('Y')."-11-01");
            $end   = strtotime((date('Y')+1)."-03-01");

            if (($now >= $start) && ($now < $end)) {
                $context['memberDebth'] = $this->checkMembership($context['user']);
            }
        }

        return $context;
    }

    /**
     * themeSupports
     *
     * @return void
     */
    public function themeSupports()
    {
        // Add default posts and comments RSS feed links to head.
        add_theme_support('automatic-feed-links');

        /*
         * Let WordPress manage the document title.
         * By adding theme support, we declare that this theme does not use a
         * hard-coded <title> tag in the document head, and expect WordPress to
         * provide it for us.
         */
        add_theme_support('title-tag');

        /*
         * Enable support for Post Thumbnails on posts and pages.
         *
         * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
         */
        add_theme_support('post-thumbnails', array('post', 'rkg-post'));
        add_post_type_support('rkgPost', 'thumbnail');
        /*
         * Switch default core markup for search form, comment form, and comments
         * to output valid HTML5.
         */
        add_theme_support(
            'html5',
            array(
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            )
        );

        /*
         * Enable support for Post Formats.
         *
         * See: https://codex.wordpress.org/Post_Formats
         */
        add_theme_support(
            'post-formats',
            array(
                'aside',
                'image',
                'video',
                'quote',
                'link',
                'gallery',
                'audio',
            )
        );

        add_theme_support('menus');
        add_theme_support('responsive-embeds');
    }

    /**
     * registerMenus
     *
     * @return void
     */
    public function registerMenus()
    {
        register_nav_menus(
            array(
                'rkg-user-menu' => __('RKG User Menu'),
            )
        );
    }

    /** This is where you can add your own functions to twig.
     *
     */
    /**
     * addToTwig
     *
     * @param Twig\Environment $twig get extension.
     *
     * @return Twig\Environment
     *
     * This is where you can add your own functions to twig.
     */
    public function addToTwig($twig)
    {
        $twig->addExtension(new \Twig_Extension_StringLoader());
        $twig->addFilter(new \Twig_SimpleFilter('myfoo', array( $this, 'myfoo' )));

        return $twig;
    }

    /**
     * getUserSignups
     *
     * @param Timber\User $user
     *
     * @return array
     */
    private function getUserSignups($user)
    {
        global $wpdb;
        if (!$user) {
            return false;
        }

        $signups            = array();
        $tableName          = $wpdb->prefix."rkg_course_signup";
        $signups['courses'] = $wpdb->get_col(
            "SELECT course_id FROM "
            .$tableName.
            " WHERE user_id = ".$user->ID
        );

        $tableName             = $wpdb->prefix."rkg_excursion_signup";
        $signups['excursions'] = $wpdb->get_col(
            "SELECT post_id FROM "
            .$tableName.
            " WHERE user_id = ".$user->ID
        );

        $tableName                     = $wpdb->prefix."rkg_excursion_waiting";
        $signups['excursions_waiting'] = $wpdb->get_col(
            "SELECT post_id FROM "
            .$tableName.
            " WHERE user_id = ".$user->ID
        );

        return $signups;
    }

    /**
     * getActualCourses
     *
     * @param array $signups
     *
     * @return array
     */
    private function getActualCourses($signups)
    {
        global $wpdb;
        if (!$signups) {
            return false;
        }
        $date    = date("Y-m-d H:i:s");
        $courses = array();
        foreach ($signups as $value) {
            $tableName  = $wpdb->prefix."rkg_course_meta";
            $coursePost = $wpdb->get_row(
                "SELECT id, category, "
                ."organiser, location, terms, price, limitation, starttime, "
                ."endtime, deadline FROM "
                .$tableName
                ." WHERE id="
                .$value
            );
            if ($coursePost && $coursePost->endtime > $date) {
                $courses[] = array(
                    'post' => get_post($value),
                    'meta' => $coursePost,
                );
            }
        }

        return $courses;
    }

    /**
     * getActualExcursions
     *
     * @param array $signups
     *
     * @return array
     */
    private function getActualExcursions($signups)
    {
        global $wpdb;
        if (!$signups) {
            return false;
        }
        $date       = date("Y-m-d H:i:s");
        $excursions = array();
        foreach ($signups as $value) {
            $tableName     = $wpdb->prefix."rkg_excursion_meta";
            $excursionPost = $wpdb->get_row(
                "SELECT id, starttime, endtime, deadline FROM "
                .$tableName
                ." WHERE id="
                .$value
            );
            if ($excursionPost && $excursionPost->endtime > $date) {
                $excursions[] = array(
                    'post' => get_post($value),
                    'meta' => $excursionPost,
                );
            }
        }

        return $excursions;
    }

    /**
     * checkMembership
     *
     * @param mixed $user
     *
     * @return int
     */
    private function checkMembership($user)
    {
        global $wpdb;
        $now     = strtotime("now");
        $newYear = strtotime((date('Y')+1)."-01-01");
        $year    = date('Y');

        if ($now < $newYear) {
            $year = $year+1;
        }

        $tableName = $wpdb->prefix."rkg_member_subscription";
        $payed     = $wpdb->get_row(
            "SELECT * FROM "
            .$tableName
            ." WHERE user = ".$user->id." AND year = '".$year."'"
        );

        if ($payed) {
            return null;
        }

        return $year;
    }
}
