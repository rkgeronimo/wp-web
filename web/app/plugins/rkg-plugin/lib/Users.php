<?php
namespace RKGeronimo;

use RKGeronimo\Interfaces\InitInterface;
use RKGeronimo\Helpers\OIB;
use Timber;

/**
 * Class: Users
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @see InitInterface
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class Users implements InitInterface
{
    private $validationErrors;

    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        add_action('admin_menu', array($this, 'createMemberRegistry'));
        
        add_action('show_user_profile', array($this, 'userMeta'));
        add_action('edit_user_profile', array($this, 'userMeta'));

        add_action('personal_options_update', array($this, 'userMetaUpdateAdmin'));
        add_action('edit_user_profile_update', array($this, 'userMetaUpdateAdmin'));

        add_action('show_user_profile', array($this, 'instructorMeta'));
        add_action('edit_user_profile', array($this, 'instructorMeta'));

        add_action('personal_options_update', array($this, 'instructorMetaUpdate'));
        add_action('edit_user_profile_update', array($this, 'instructorMetaUpdate'));

        add_action('user_profile_update_errors', array($this, 'validateUserInputs'), 10, 3);

        add_action('wp_ajax_brevet_upload', array($this, 'brevetUpload'));
        add_action('wp_ajax_nopriv_brevet_upload', array($this, 'brevetUpload'));

        add_action('wp_ajax_health_survey', array($this, 'helthSurvey'));
        add_action('wp_ajax_nopriv_helth_survey', array($this, 'helthSurvey'));

        add_action('profile_update', array($this, 'updateUserName'));

        add_action(
            'wp_ajax_responsibility_survey',
            array($this, 'responsibilitySurvey')
        );
        add_action(
            'wp_ajax_nopriv_responsibility_survey',
            array($this, 'responsibilitySurvey')
        );

        add_action('wp_ajax_gear_reserve', array($this, 'gearReserve'));
        add_action('wp_ajax_nopriv_gear_reserve', array($this, 'gearReserve'));

        add_action('wp_ajax_gear_reserve_no', array($this, 'gearReserveNo'));
        add_action('wp_ajax_nopriv_gear_reserve_no', array($this, 'gearReserveNo'));

        add_action('wp_ajax_guest_invite', array($this, 'guestInvite'));
        add_action('wp_ajax_nopriv_guest_invite', array($this, 'guestInvite'));

        add_action('wp_ajax_guest_uninvite', array($this, 'guestUninvite'));
        add_action('wp_ajax_nopriv_guest_uninvite', array($this, 'guestUninvite'));
    }

    public function createMemberRegistry()
    {
        add_users_page(
            'Registar članova',
            'Registar članova',
            'edit_users',
            'member_registry',
            array(
                $this,
                'memberRegistryPage'
            )
        );
    }

    public function updateUserName($userId)
    {
        if (!isset($_POST['first_name']) && !isset($_POST['last_name'])) {
            // Not updating profile with user data, maybe a password change? skip.
            return;
        }

        global $wpdb;
        $user = get_userdata($userId);
        $name = sanitize_text_field($_POST['first_name']) . " " . sanitize_text_field($_POST['last_name']);
        $metaName = get_user_meta($userId, "display_name", true);
        if ($name != $user->display_name || $name != $metaName) {
            // update_user_meta($userId, 'display_name', $name);
            $tableName = $wpdb->prefix."users";
            $sql = $wpdb->prepare(
                "UPDATE $tableName SET display_name = %s WHERE id = %d",
                $name, $userId
            );
            $wpdb->query($sql);
        }
    }

    /**
     * userMeta
     *
     * @param mixed $user
     *
     * @return void
     */
    public function userMeta($user)
    {
        $context                          = Timber::get_context();
        $context['profileMeta']['memberNumber']    = get_user_meta($user->ID, "memberNumber", true);
        $context['profileMeta']['dob']    = get_user_meta($user->ID, "dob", true);
        $context['profileMeta']['pob']    = get_user_meta($user->ID, "pob", true);
        $context['profileMeta']['oib']    = get_user_meta($user->ID, "oib", true);
        $context['profileMeta']['tel']    = get_user_meta($user->ID, "tel", true);
        $context['profileMeta']['brevet'] = get_user_meta($user->ID, "brevet", true);
        $context['profileMeta']['id']     = $user->ID;

        $context['profileMeta']['mask']        = get_user_meta(
            $user->ID,
            "mask",
            true
        );

        $context['profileMeta']['regulator']   = get_user_meta(
            $user->ID,
            "regulator",
            true
        );
        $context['profileMeta']['suit_size']   = get_user_meta(
            $user->ID,
            "suit_size",
            true
        );
        $context['profileMeta']['suit']        = get_user_meta(
            $user->ID,
            "suit",
            true
        );
        $context['profileMeta']['boots_size']  = get_user_meta(
            $user->ID,
            "boots_size",
            true
        );
        $context['profileMeta']['boots']       = get_user_meta(
            $user->ID,
            "boots",
            true
        );
        $context['profileMeta']['gloves_size'] = get_user_meta(
            $user->ID,
            "gloves_size",
            true
        );
        $context['profileMeta']['gloves']      = get_user_meta(
            $user->ID,
            "gloves",
            true
        );
        $context['profileMeta']['fins_size']   = get_user_meta(
            $user->ID,
            "fins_size",
            true
        );
        $context['profileMeta']['fins']        = get_user_meta(
            $user->ID,
            "fins",
            true
        );
        $context['profileMeta']['bcd_size']    = get_user_meta(
            $user->ID,
            "bcd_size",
            true
        );
        $context['profileMeta']['bcd']         = get_user_meta(
            $user->ID,
            "bcd",
            true
        );
        $context['profileMeta']['lead_size']   = get_user_meta(
            $user->ID,
            "lead_size",
            true
        );
        $context['profileMeta']['lead']        = get_user_meta(
            $user->ID,
            "lead",
            true
        );

        $context['profileMeta']['rc']       = get_user_meta(
            $user->ID,
            "rc",
            true
        );
        $context['profileMeta']['cardNumber']       = get_user_meta(
            $user->ID,
            "cardNumber",
            true
        );
        $context['profileMeta']['log']      = get_user_meta(
            $user->ID,
            "log",
            true
        );
        $context['profileMeta']['co']       = get_user_meta(
            $user->ID,
            "co",
            true
        );
        $context['profileMeta']['nitrox']   = get_user_meta(
            $user->ID,
            "nitrox",
            true
        );
        $context['profileMeta']['dry_suit'] = get_user_meta(
            $user->ID,
            "dry_suit",
            true
        );
        $context['profileMeta']['DAN_op']   = get_user_meta(
            $user->ID,
            "DAN_op",
            true
        );


        global $wpdb;
        $tableName                     = $wpdb->prefix."rkg_excursion_signup";
        $context['excursions_all'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM "
            .$tableName.
            " WHERE user_id = ".$user->ID
        );
        $context['excursions_last'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM "
            .$tableName.
            " WHERE user_id = ".$user->ID
            ." AND created > DATE_SUB(now(), INTERVAL 6 MONTH)"
        );

        $templates = array( 'userMeta.twig' );
        Timber::render($templates, $context);
    }

    /**
     * userMetaUpdateAdmin
     *
     * @param mixed $userId
     *
     * @return void
     */
    public function userMetaUpdateAdmin($userId)
    {
        $this->userMetaUpdate($userId);
    }

    /**
     * userMetaUpdate
     *
     * @param mixed $userId
     * @param bool  $invert
     *
     * @return void
     */
    public function userMetaUpdate($userId, $invert = true)
    {
        if (current_user_can('edit_users')) {
            $this->updateMetaValue($userId, 'memberNumber');
        }
        $this->updateMetaValue($userId, 'dob');
        $this->updateMetaValue($userId, 'pob');
        $this->updateMetaValue($userId, 'oib');
        $this->updateMetaValue($userId, 'tel');
        $this->updateMetaValue($userId, 'suit_size');
        $this->updateMetaValue($userId, 'boots_size');
        $this->updateMetaValue($userId, 'gloves_size');
        $this->updateMetaValue($userId, 'fins_size');
        $this->updateMetaValue($userId, 'bcd_size');
        $this->updateMetaValue($userId, 'lead_size');
        $this->updateMetaValueCheckbox($userId, 'mask', $invert);
        $this->updateMetaValueCheckbox($userId, 'regulator', $invert);
        $this->updateMetaValueCheckbox($userId, 'suit', $invert);
        $this->updateMetaValueCheckbox($userId, 'boots', $invert);
        $this->updateMetaValueCheckbox($userId, 'gloves', $invert);
        $this->updateMetaValueCheckbox($userId, 'fins', $invert);
        $this->updateMetaValueCheckbox($userId, 'bcd', $invert);
        $this->updateMetaValueCheckbox($userId, 'lead', $invert);

        $this->updateMetaValue($userId, 'rc');
        $this->updateMetaValue($userId, 'cardNumber');
        $this->updateMetaValueCheckbox($userId, 'log');
        $this->updateMetaValueCheckbox($userId, 'co');
        $this->updateMetaValueCheckbox($userId, 'nitrox');
        $this->updateMetaValueCheckbox($userId, 'dry_suit');
        $this->updateMetaValueCheckbox($userId, 'DAN_op');
    }

    /**
     * brevetUpload
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function brevetUpload()
    {
        // phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
        global $current_user;
        get_currentuserinfo();
        $userLogin = $current_user->user_login;
        $userId    = $current_user->ID;
        // phpcs:enable Zend.NamingConventions.ValidVariableName.NotCamelCaps

        $data = $_POST['image'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, array('jpg', 'jpeg', 'gif', 'png'))) {
                throw new \Exception('invalid image type');
            }

            $data = base64_decode($data);

            if (false === $data) {
                throw new \Exception('base64_decode failed');
            }

            $filename       = $userLogin.'.'.$type;
            $hashedFilename = md5($filename.microtime()).'_'.$filename;
            $uploadDir      = wp_upload_dir();
            $uploadPath     = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $uploadDir['path']
            ).DIRECTORY_SEPARATOR;
            file_put_contents($uploadPath.$hashedFilename, $data);
            $this->updateMetaValue(
                $userId,
                'brevet',
                $uploadDir['url'].'/'.basename($hashedFilename)
            );
            echo $uploadDir['url'].'/'.basename($hashedFilename);
            wp_die();
        }
        throw new \Exception('did not match data URI with image data');

        wp_die();
    }

    /**
     * helthSurvey
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function helthSurvey()
    {
        global $wpdb;
        $userId    = get_current_user_id();
        $tableName = $wpdb->prefix."rkg_course_medical_meta";
        $sql       = $wpdb->prepare(
            "INSERT INTO $tableName (user_id, post_id, pregnancy, "
            ."medications, older, breathing, allergies, cold, lungs, chest, "
            ."pneumotorax, phobia, behavior, epilepsy, migraine, fainting, "
            ."moving, decompression, back, backoperation, diabetes, fracture, "
            ."exercise, bloodpresure, heart, heartattack, infarction, ears, "
            ."deafness, earpressure, bleeding, hernia, ulcer, colostomy, addiction, "
            ."parent) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, "
            ."%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, "
            ."%s, %s, %s, %s, %s, %s, %s, %s) "
            ."ON DUPLICATE KEY UPDATE user_id = %s, post_id = %s, "
            ."pregnancy = %s, medications = %s, older = %s, breathing = %s, "
            ."allergies = %s, cold = %s, lungs = %s, chest = %s, "
            ."pneumotorax = %s, phobia = %s, behavior = %s, epilepsy = %s, "
            ."migraine = %s, fainting = %s, moving = %s, decompression = %s, "
            ."back = %s, backoperation = %s, diabetes = %s, fracture = %s, "
            ."exercise = %s, bloodpresure = %s, heart = %s, heartattack = %s, "
            ."infarction = %s, ears = %s, deafness = %s, earpressure = %s, "
            ."bleeding = %s, hernia = %s, ulcer = %s, colostomy = %s, "
            ."addiction = %s, parent = %s",
            $userId,
            $_POST['course'],
            $_POST['pregnancy'],
            $_POST['medications'],
            $_POST['older'],
            $_POST['breathing'],
            $_POST['allergies'],
            $_POST['cold'],
            $_POST['lungs'],
            $_POST['chest'],
            $_POST['pneumotorax'],
            $_POST['phobia'],
            $_POST['behavior'],
            $_POST['epilepsy'],
            $_POST['migraine'],
            $_POST['fainting'],
            $_POST['moving'],
            $_POST['decompression'],
            $_POST['back'],
            $_POST['backoperation'],
            $_POST['diabetes'],
            $_POST['fracture'],
            $_POST['exercise'],
            $_POST['bloodpresure'],
            $_POST['heart'],
            $_POST['heartattack'],
            $_POST['infarction'],
            $_POST['ears'],
            $_POST['deafness'],
            $_POST['earpressure'],
            $_POST['bleeding'],
            $_POST['hernia'],
            $_POST['ulcer'],
            $_POST['colostomy'],
            $_POST['addiction'],
            $_POST['parent'],
            $userId,
            $_POST['course'],
            $_POST['pregnancy'],
            $_POST['medications'],
            $_POST['older'],
            $_POST['breathing'],
            $_POST['allergies'],
            $_POST['cold'],
            $_POST['lungs'],
            $_POST['chest'],
            $_POST['pneumotorax'],
            $_POST['phobia'],
            $_POST['behavior'],
            $_POST['epilepsy'],
            $_POST['migraine'],
            $_POST['fainting'],
            $_POST['moving'],
            $_POST['decompression'],
            $_POST['back'],
            $_POST['backoperation'],
            $_POST['diabetes'],
            $_POST['fracture'],
            $_POST['exercise'],
            $_POST['bloodpresure'],
            $_POST['heart'],
            $_POST['heartattack'],
            $_POST['infarction'],
            $_POST['ears'],
            $_POST['deafness'],
            $_POST['earpressure'],
            $_POST['bleeding'],
            $_POST['hernia'],
            $_POST['ulcer'],
            $_POST['colostomy'],
            $_POST['addiction'],
            $_POST['parent']
        );
        $wpdb->query($sql);
        wp_die();
    }

    /**
     * responsibilitySurvey
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function responsibilitySurvey()
    {
        global $wpdb;
        $userId    = get_current_user_id();
        $tableName = $wpdb->prefix."rkg_course_liability_meta";
        $sql       = $wpdb->prepare(
            "INSERT INTO $tableName (user_id, post_id, rs1, rs2, "
            ."rs3, rs4, rs5, rs6, rs7, "
            ."rs8) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s) "
            ."ON DUPLICATE KEY UPDATE user_id = %s, post_id = %s, "
            ."rs1 = %s, rs2 = %s, rs3 = %s, "
            ."rs4 = %s, rs5 = %s, rs6 = %s, rs7 = %s, rs8 = %s",
            $userId,
            $_POST['course'],
            $_POST['rs1'],
            $_POST['rs2'],
            $_POST['rs3'],
            $_POST['rs4'],
            $_POST['rs5'],
            $_POST['rs6'],
            $_POST['rs7'],
            $_POST['rs8'],
            $userId,
            $_POST['course'],
            $_POST['rs1'],
            $_POST['rs2'],
            $_POST['rs3'],
            $_POST['rs4'],
            $_POST['rs5'],
            $_POST['rs6'],
            $_POST['rs7'],
            $_POST['rs8']
        );
        $wpdb->query($sql);
        wp_die();
    }

    /**
     * gearReserve
     *
     * @return void
     */
    public function gearReserve()
    {

        $currentUser = wp_get_current_user();
        $userId = $currentUser->ID;
        // Update only gear things from profile
        $this->updateMetaValue($userId, 'suit_size');
        $this->updateMetaValue($userId, 'boots_size');
        $this->updateMetaValue($userId, 'gloves_size');
        $this->updateMetaValue($userId, 'fins_size');
        $this->updateMetaValue($userId, 'bcd_size');
        $this->updateMetaValue($userId, 'lead_size');
        $this->updateMetaValueCheckbox($userId, 'mask', true);
        $this->updateMetaValueCheckbox($userId, 'regulator', true);
        $this->updateMetaValueCheckbox($userId, 'suit', true);
        $this->updateMetaValueCheckbox($userId, 'boots', true);
        $this->updateMetaValueCheckbox($userId, 'gloves', true);
        $this->updateMetaValueCheckbox($userId, 'fins', true);
        $this->updateMetaValueCheckbox($userId, 'bcd', true);
        $this->updateMetaValueCheckbox($userId, 'lead', true);

        global $wpdb;
        $tableName = $wpdb->prefix."rkg_excursion_gear";
        $wpdb->replace(
            $tableName,
            array(
                'user_id'   => $currentUser->ID,
                'post_id' => $_POST['post'],
                'other' => $_POST['other'],
            )
        );

        echo json_encode(array('update' => true, 'message' => __('Spremljeno')));

        wp_die();
    }

    /**
     * gearReserveNo
     *
     * @return void
     */
    public function gearReserveNo()
    {
        $currentUser = wp_get_current_user();

        global $wpdb;
        $tableName = $wpdb->prefix."rkg_excursion_gear";
        $wpdb->delete(
            $tableName,
            array(
                'user_id'   => $currentUser->ID,
                'post_id' => $_POST['post'],
            )
        );

        echo json_encode(array('update' => true, 'message' => __('Spremljeno')));

        wp_die();
    }

    /**
     * guestInvite
     *
     * @return void
     */
    public function guestInvite()
    {
        global $wpdb;

        $excursionTableName = $wpdb->prefix."rkg_excursion_meta";
        $excursionStatus = $wpdb->get_row("SELECT  guests_limit, limitation, registered FROM "
                .$excursionTableName
                ." WHERE id="
                .$_POST['post']);
        
        // If limit applies to guests, check availability
        if (!empty($excursionStatus->guests_limit) && $excursionStatus->registered+1 > $excursionStatus->limitation) {
            echo json_encode(array('update' => true, 'message' => __('Izlet je popunjen.')));
            wp_die();
        }
                
        $currentUser = wp_get_current_user();

        $tableName = $wpdb->prefix."rkg_excursion_guest";
        $result = $wpdb->replace(
            $tableName,
            array(
                'user_id' => $currentUser->ID,
                'post_id' => $_POST['post'],
                'name'    => $_POST['name'],
                'email'   => $_POST['email'],
                'tel'     => $_POST['tel'],
            )
        );

        // If guests count in limit, update registered number (increase)
        if (!empty($excursionStatus->guests_limit)) {
            $wpdb->query("UPDATE $excursionTableName SET registered = registered + 1 WHERE id = {$_POST['post']};");
        }

        if (!empty($result)) {
            echo json_encode(array('update' => true, 'message' => __('Spremljeno')));
        } else {
            echo json_encode(array('update' => true, 'message' => __('Dogodila se pogreška')));
        }

        wp_die();
    }

    /**
     * guestUninvite
     *
     * @return
     */
    public function guestUninvite()
    {

        $currentUser = wp_get_current_user();

        global $wpdb;
        $tableName = $wpdb->prefix."rkg_excursion_guest";
        $result = $wpdb->delete(
            $tableName,
            array(
                'user_id' => $currentUser->ID,
                'post_id' => $_POST['post'],
                'email'   => $_POST['email'],
            )
        );

        $excursionTableName = $wpdb->prefix."rkg_excursion_meta";
        $excursionStatus = $wpdb->get_row("SELECT  guests_limit, registered FROM "
                .$excursionTableName
                ." WHERE id="
                .$_POST['post']);

        // If guests count in limit, update registered number (decrease)
        if (!empty($excursionStatus->guests_limit)) {
            $wpdb->query("UPDATE $excursionTableName SET registered = registered - 1 WHERE id = {$_POST['post']};");
        }

        if (!empty($result)) {
            echo json_encode(array('update' => true, 'message' => __('Spremljeno')));
        } else {
            echo json_encode(array('update' => true, 'message' => __('Dogodila se pogreška')));
        }

        wp_die();
    }

    /**
     * instructorMeta
     *
     * @param mixed $user
     *
     * @return void
     */
    public function instructorMeta($user)
    {
        $context = Timber::get_context();

        if (current_user_can('promote_users', $user->ID)) {
            global $wpdb;
            $tableName              = $wpdb->prefix."rkg_course_template";
            $context['instructors'] = $wpdb->get_results(
                "SELECT id, name, category FROM "
                .$tableName
                ." ORDER BY priority"
            );

            foreach ($context['instructors'] as $key => $value) {
                $context['instructors'][$key]->assigned = get_the_author_meta(
                    "instructor-".$value->id,
                    $user->ID
                );
            }
            $templates = array( 'instructorMeta.twig' );
            Timber::render($templates, $context);
        }
    }

    /**
     * instructorMetaUpdate
     *
     * @param mixed $userId
     *
     * @return void
     */
    public function instructorMetaUpdate($userId)
    {
        if (!current_user_can('edit_user', $userId)) {
            return false;
        }

        global $wpdb;
        $tableName   = $wpdb->prefix."rkg_course_template";
        $instructors = $wpdb->get_results(
            "SELECT id, category FROM "
            .$tableName
            ." ORDER BY priority"
        );

        foreach ($instructors as $value) {
            $this->updateMetaValueCheckbox($userId, "instructor-".$value->id);
        }
    }

    public function validateUserInputs($errors, $isExistingUser, $user) {
        foreach($this->validationErrors as $key => $value) {
            $errors->add($key, $value);
        }
    }

    public function memberRegistryPage()
    {
        $context            = Timber::get_context();
        if(!current_user_can('edit_users')) {
            Timber::render('single-no-pasaran.twig', $context);
        }

        global $wpdb;
        $tableName = $wpdb->prefix."rkg_member_subscription";
        $allMemberIds = $wpdb->get_results(
            "SELECT DISTINCT user FROM "
            .$tableName
        );
        $allMemberIds = array_map(function($item) { return $item->user; }, $allMemberIds);
        $users = get_users(['include' => $allMemberIds]);
        foreach($users as $index => $user){
            $users[$index]->memberNumber    = get_user_meta($user->ID, "memberNumber", true);
            $users[$index]->dob             = get_user_meta($user->ID, "dob", true);
            $users[$index]->oib             = get_user_meta($user->ID, "oib", true);
            
            
            $subscriptions = $wpdb->get_results(
                "SELECT year, created FROM "
                .$tableName
                ." WHERE user = ".$user->ID
                ." ORDER BY year ASC"
            );
            if (count($subscriptions) > 0) {
                $users[$index]->firstYear = $subscriptions[0]->created;
                $users[$index]->lastYear = $subscriptions[count($subscriptions)-1]->year;
                $users[$index]->allYears = $subscriptions;
            }
        }

        $context['data'] = $users;

        $templates = array( 'memberRegistry.twig' );
        Timber::render($templates, $context);        
    }

    /**
     * updateMetaValue
     *
     * @param mixed $userId
     * @param mixed $meta
     * @param mixed $value
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function updateMetaValue($userId, $meta, $value = null)
    {
        $data = $value ? $value : $_POST[$meta];
        if (!$this->isValidInput($meta, $data)) {
            return;
        }

        if (!empty($_POST[$meta]) || !empty($value)) {
            $value = $value ? $value : $_POST[$meta];
            update_user_meta($userId, $meta, $value);
            return;
        }
        delete_user_meta($userId, $meta);
    }

    private function isValidInput($meta, $value = null) {
        if ($meta === 'oib') {
            $isValid = OIB::validate($value);
            if (!$isValid) {
                $this->validationErrors['oib_error'] = __('Nevažeći OIB.');
                return false;
            }
        }
        return true;
    }

    /**
     * updateMetaValueCheckbox
     *
     * @param mixed $userId
     * @param mixed $meta
     * @param bool  $invert
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function updateMetaValueCheckbox($userId, $meta, $invert = false)
    {
        if (((true === $invert) && empty($_POST[$meta])) ||
            ((false === $invert) && !empty($_POST[$meta]))) {
            update_user_meta($userId, $meta, true);

            return;
        }
        delete_user_meta($userId, $meta);
    }
}
