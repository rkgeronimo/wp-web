<?php
namespace RKGTheme\Ajax;

use WP_Error;

/**
 * Class: Login
 *
 * Omogučuje login, registraciju i promjenu lozinke preko ajaxa
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 */
class Login
{
    /**
     * init
     *
     * Metode postaju dostupne wp ajax-u
     *
     * @return void
     */
    public function init()
    {
        add_action('wp_ajax_sendPasswordReset', array($this, 'sendPasswordReset'));
        add_action(
            'wp_ajax_nopriv_sendPasswordReset',
            array($this, 'sendPasswordReset')
        );
        add_action('wp_ajax_nopriv_register_user', array($this, 'ajaxRegistration'));

        // login moguć ako korisnik nije prijavljen
        if (!is_user_logged_in()) {
            add_action('init', array($this, 'ajaxLoginInit'));
        }
    }

    /**
     * ajaxLoginInit
     *
     * Login dostupan ajaxu
     *
     * @return void
     */
    public function ajaxLoginInit()
    {
        add_action('wp_ajax_nopriv_ajaxlogin', array($this, 'ajaxLogin'));
    }

    /**
     * ajaxLogin
     *
     *  provjera podataka i prijava korisnika ukoliko su točni
     *
     * @return void
     */
    public function ajaxLogin()
    {
        // First check the nonce, if it fails the function will break
        check_ajax_referer('ajax-login-nonce', 'security');

        // Nonce is checked, get the POST data and sign user on
        $info                  = array();
        $info['user_login']    = $_POST['username'];
        $info['user_password'] = $_POST['password'];
        $info['remember']      = true;

        $userSignon = wp_signon($info, is_ssl());
        if (is_wp_error($userSignon)) {
            echo json_encode(array(
                'status' => 1,
                'message' => __('Pogrešno korisničko ime, e-mail ili lozinka.'),
            ));
            wp_die();
        }

        echo json_encode(array(
            'status' => 0,
            'message' => __('Prijava uspješna'),
        ));
        wp_die();
    }

    /**
     * sendPasswordReset
     *
     * Slanje e-maila sa linkom za promjenu lozinke
     *
     * @return null
     */
    public function sendPasswordReset()
    {
        $error = null;

        if (empty($_POST['lost_username']) || !is_string($_POST['lost_username'])) {
            $error = 'Unesi korisničko ime ili E-mail adresu';
        } elseif (strpos($_POST['lost_username'], '@')) {
            $userData = get_user_by(
                'email',
                trim(wp_unslash($_POST['lost_username']))
            );
            if (empty($userData)) {
                $error = "Ne postoji takvo korisničko ime ili e-mail";
            }
        } else {
            $login    = trim($_POST['lost_username']);
            $userData = get_user_by('login', $login);
        }

        /**
         * Fires before errors are returned from a password reset request.
         *
         * @since 2.1.0
         * @since 4.4.0 Added the `$errors` parameter.
         *
         * @param WP_Error $errors A WP_Error object containing any errors generated
         *                         by using invalid credentials.
         */
        do_action('lostpassword_post', $errors);

        if ($error) {
            echo json_encode(array(
                'status' => 1,
                'message' => $error,
            ));

            wp_die();
        }

        if (!$userData) {
            $error = "Ne postoji takvo korisničko ime ili e-mail";

            echo json_encode(array(
                'status' => 1,
                'message' => $error,
            ));

            wp_die();
        }

        // Redefining user_login ensures we return the right case in the email.
        $userLogin = $userData->user_login;
        $userEmail = $userData->user_email;
        $key       = get_password_reset_key($userData);

        if (is_wp_error($key)) {
            return $key;
        }

        if (is_multisite()) {
            $siteName = get_network()->site_name;
        } else {
            /*
             * The blogname option is escaped with esc_html on the way into the
             * database in sanitize_option we want to reverse this for the plain
             * text arena of emails.
             */
            $siteName = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        }

        $message = "Bok,"
            ."\r\n\r\n"
            ."Zatražena je promjena lozinke za tvoj profil."
            ."\r\n\r\n"
            ."Prati ovaj link za upis nove lozinke:\r\n";
        $message .= network_site_url(
            "wp-login.php?action=rp&key=$key&login=".rawurlencode($userLogin),
            'login'
        );
        $message .= "\r\n\r\n"
            ."Tvoj,\r\n"
            ."RKG web";

        $title = "RKG web - zaboravljena lozinka";

        /**
         * Filters the subject of the password reset email.
         *
         * @since 2.8.0
         * @since 4.4.0 Added the `$userLogin` and `$userData` parameters.
         *
         * @param string  $title      Default email title.
         * @param string  $userLogin The username for the user.
         * @param WP_User $userData  WP_User object.
         */
        $title = apply_filters(
            'retrieve_password_title',
            $title,
            $userLogin,
            $userData
        );

        /**
         * Filters the message body of the password reset mail.
         *
         * If the filtered message is empty,
         * the password reset email will not be sent.
         *
         * @since 2.8.0
         * @since 4.1.0 Added `$userLogin` and `$userData` parameters.
         *
         * @param string  $message    Default mail message.
         * @param string  $key        The activation key.
         * @param string  $userLogin The username for the user.
         * @param WP_User $userData  WP_User object.
         */
        $message = apply_filters(
            'retrieve_password_message',
            $message,
            $key,
            $userLogin,
            $userData
        );

        if ($message
            && !wp_mail($userEmail, wp_specialchars_decode($title), $message)
        ) {
            echo json_encode(array(
                'status' => 1,
                'message' => 'Slanje neuspješno',
            ));

            wp_die();
        }

        echo json_encode(array(
            'status' => 0,
            'message' => 'Poslan ti je mail s linkom za promjenu lozinke!',
        ));

        wp_die();
    }

    /**
     * ajaxRegistration
     *
     * Provjera, obrada i unos podataka kod registracije korisnika
     *
     * @return void
     */
    public function ajaxRegistration()
    {

        // Verify nonce
        if (!isset($_POST['nonce'])
            || !wp_verify_nonce($_POST['nonce'], 'vb_new_user')) {
            echo json_encode(array(
                'status' => 1,
                'message' => 'Nešto je pošlo po zlu, molimo da pokušaš kasnije',
            ));

            wp_die();
        }

        // Post values
        $password  = $_POST['pass'];
        $email     = $_POST['mail'];
        $firstname = $_POST['firstname'];
        $lastname  = $_POST['lastname'];

        /**
         * IMPORTANT: You should make server side validation here!
         *
         */

        $username = mb_substr($firstname, 0, 1).$lastname;

        $userCheck = username_exists($username);

        if (!empty($userCheck)) {
            $sufix = 1;
            while (!empty($userCheck)) {
                $username  = $username.$sufix;
                $userCheck = username_exists($username);
                $sufix     = $sufix + 1;
            }
        }

        //$username = $username.$sufix;

        $userdata = array(
            'user_login' => $username,
            'user_pass'  => $password,
            'user_email' => $email,
            'first_name'    =>   $firstname,
            'last_name'     =>   $lastname,
        );

        $userId = wp_insert_user($userdata) ;

        // Return
        if (!is_wp_error($userId)) {
            $user = get_user_by('id', $userId);
            if ($user) {
                wp_set_current_user($userId, $user->user_login);
                wp_set_auth_cookie($userId);
                $user->set_role('user');
                do_action('wp_login', $user->user_login, $user);
                update_user_meta($userId, 'gdprOk', date("Y-m-d H:i:s"));
            }
            echo json_encode(array(
                'status' => 0,
                'message' => 'Registracija uspješna',
            ));

            wp_die();
        }

        echo json_encode(array(
            'status' => 1,
            'message' => $userId->get_error_message(),
        ));
        wp_die();
    }
}
