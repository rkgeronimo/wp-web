<?php
namespace RKGeronimo;

use WP_User;
use WP_Error;

/**
 * Class: RKGeronimo
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 */
class RKGeronimo
{
    /**
     * run
     *
     * @return void
     */
    public function run()
    {
        $this->initComponents();
        $this->initHooks();
    }

    /**
     * enqueStyle
     *
     * @return void
     */
    public function enqueStyle()
    {
        wp_enqueue_style(
            'leaflet',
            'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.5.1/leaflet.css'
        );
        wp_enqueue_style(
            'leaflet-gestures',
            'https://unpkg.com/leaflet-gesture-handling@1.1.8'
            .'/dist/leaflet-gesture-handling.min.css',
            array('leaflet')
        );
        wp_enqueue_style(
            'leaflet-geocoder',
            'https://maps.locationiq.com/v2/libs/leaflet-geocoder/1.9.5/'
            .'leaflet-geocoder-locationiq.min.css',
            array('leaflet')
        );
        wp_enqueue_style(
            'slick',
            'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/'
            .'1.9.0/slick.min.css'
        );
        wp_enqueue_style(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css'
        );
    }

    /**
     * enqueScript
     *
     * @return void
     */
    public function enqueScript()
    {
        wp_enqueue_script(
            'leaflet',
            'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.5.1/leaflet.js',
            null,
            null,
            true
        );
        wp_enqueue_script(
            'leaflet-provider',
            'https://cdnjs.cloudflare.com/ajax/libs/leaflet-providers/'
            .'1.5.0/leaflet-providers.min.js',
            array('leaflet'),
            null,
            true
        );
        wp_enqueue_script(
            'leaflet-tile',
            'https://stamen-maps.a.ssl.fastly.net/js/tile.stamen.js?v1.3.0',
            array('leaflet-provider'),
            null,
            true
        );
        wp_enqueue_script(
            'leaflet-gestures',
            'https://unpkg.com/leaflet-gesture-handling@1.1.8/dist/'
            .'leaflet-gesture-handling.min.js',
            array('leaflet'),
            null,
            true
        );
        wp_enqueue_script(
            'leaflet-geocoder',
            'https://maps.locationiq.com/v2/libs/leaflet-geocoder/1.9.5/'
            .'leaflet-geocoder-locationiq.min.js',
            array('leaflet'),
            null,
            true
        );
        wp_enqueue_script(
            'slick',
            'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/'.
            '1.9.0/slick.min.js',
            array('jquery'),
            null,
            true
        );
        wp_enqueue_script(
            'rkg-js',
            WP_PLUGIN_URL.'/rkg-plugin/js/script.js',
            array ('jquery', 'leaflet-tile', 'slick', 'leaflet-gestures'),
            1.1,
            true
        );
        wp_localize_script(
            'rkg-js',
            'rkgScript',
            array('ajaxUrl' => admin_url('admin-ajax.php'))
        );
        wp_enqueue_script(
            'selct2',
            'https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js',
            array('jquery'),
            null,
            true
        );
    }

    /**
     * rkgEmailLogin
     *
     * @param mixed $user
     * @param mixed $email
     * @param mixed $password
     *
     * @return WP_User|WP_Error
     */
    public function emailLogin($user, $email, $password)
    {
        if ($error = $this->emailLoginValidate($email, $password)) {
            return $error;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = get_user_by('email', $email);
        }

        if (!$user) {
            $user = get_user_by('login', $email);
        }

        if (!$user) {
            $error = new WP_Error();
            $error->add(
                'invalid',
                __('<strong>ERROR</strong>: '
                .'Either the email or password you entered is invalid.')
            );

            return $error;
        }

        // phpcs:ignore Zend.NamingConventions.ValidVariableName.NotCamelCaps
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            $error = new WP_Error();
            $error->add(
                'invalid',
                __('<strong>ERROR</strong>: '
                .'Either the email or password you entered is invalid.')
            );

            return $error;
        }

        return $user;
    }

    /**
     * initComponents
     *
     * @return void
     */
    private function initComponents()
    {
        $components = array(
            'Messages',
            'Roles',
            'Users',
            'RkgPost',
            'Courses',
            'Excursions',
            'Blocks',
            'News',
            'Inventory',
        );
        foreach ($components as $component) {
            $namespaceComponent = 'RKGeronimo\\'.$component;
            $object             = new $namespaceComponent();
            $object->init();
        }
    }

    /**
     * initHooks
     *
     * @return void
     */
    private function initHooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueStyle'));
        add_action('wp_enqueue_scripts', array($this, 'enqueScript'));
        add_action('admin_enqueue_scripts', array($this, 'enqueStyle'));
        add_action('admin_enqueue_scripts', array($this, 'enqueScript'));

        remove_filter('authenticate', 'wp_authenticate_username_password', 20);
        add_filter('authenticate', array($this, 'emailLogin'), 20, 3);
    }

    /**
     * emailLoginValidate
     *
     * @param mixed $email
     * @param mixed $password
     *
     * @return WP_Error|null
     */
    private function emailLoginValidate($email, $password)
    {
        if (empty($email) || empty($password)) {
            $error = new WP_Error();

            if (empty($email)) {
                $error->add(
                    'empty_username',
                    __('<strong>ERROR</strong>: Email field is empty.')
                );
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error->add(
                    'invalid_username',
                    __('<strong>ERROR</strong>: Email is invalid.')
                );
            }

            if (empty($password)) {
                $error->add(
                    'empty_password',
                    __('<strong>ERROR</strong>: Password field is empty.')
                );
            }

            return $error;
        }

        return null;
    }
}
