<?php
namespace RKGeronimo;

/**
 * Class: Activator
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * Izvršava se kod uključivanja ili isključivanja plugina
 */
class Activator
{
    /**
     * activate
     *
     * @return void
     *
     * Izvršava se prilikom aktivacije
     */
    public static function activate()
    {
        self::alterDatabase();
        self::changeRoles();
    }

    /**
     * deactivate
     *
     * @return void
     *
     * Izvršava se prilikom deaktivacije
     */
    public static function deactivate()
    {
        self::removeRkgRoles();
    }

    /**
     * alterDatabase
     *
     * @return void
     *
     * Promjene na bazi
     */
    private static function alterDatabase()
    {
        include_once ABSPATH.'wp-admin/includes/upgrade.php';
        self::alterCoursesTables();
        self::alterExcursionsTables();
        self::alterMembersTable();
        self::alterGuestTable();
        self::alterInventoryTable();
    }

    /**
     * alterCoursesTables
     *
     * @return void
     *
     * Stvaranje i promjena tablica tečajeva
     */
    private static function alterCoursesTables()
    {
        global $wpdb;
        $tableName      = $wpdb->prefix."rkg_course_template";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            category text NOT NULL,
            priority int(3) NOT NULL,
            name text NOT NULL,
            location text NOT NULL,
            terms text NOT NULL,
            price text NOT NULL,
            limitation int(3) NULL,
            special BOOLEAN,
            temp_categorie text NULL,
            finish_categorie text NOT NULL,
            payment_desc text NOT NULL,
            payment_price text NOT NULL,
            description text NOT NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);

        $tableName      = $wpdb->prefix."rkg_course_meta";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL UNIQUE PRIMARY KEY,
            category mediumint(9) NOT NULL,
            organiser mediumint(9) NOT NULL,
            location text NOT NULL,
            terms text NOT NULL,
            price text NOT NULL,
            limitation int(3) NOT NULL,
            registered int(3) DEFAULT '0' NOT NULL,
            starttime date DEFAULT '0000-00-00' NOT NULL,
            endtime date DEFAULT '0000-00-00' NOT NULL,
            deadline date DEFAULT '0000-00-00' NOT NULL,
            exam date NULL,
            assistant text NULL,
            delegate text NULL,
            locked BOOLEAN,
            completed BOOLEAN,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);

        $tableName      = $wpdb->prefix."rkg_course_signup";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id mediumint(9) NOT NULL,
            course_id mediumint(9) NOT NULL,
            new_card text NULL,
            payed BOOLEAN,
            finished BOOLEAN,
            weight text NULL,
            height text NULL,
            shoe_size text NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);
        $wpdb->query("ALTER TABLE $tableName ADD UNIQUE (`user_id`, `course_id`);");

        $tableName      = $wpdb->prefix."rkg_course_interest";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id mediumint(9) NOT NULL,
            course_template_id mediumint(9) NOT NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);
        $wpdb->query(
            "ALTER TABLE $tableName "
            ."ADD UNIQUE (`user_id`, `course_template_id`);"
        );

        $tableName      = $wpdb->prefix."rkg_course_medical_meta";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL  AUTO_INCREMENT PRIMARY KEY,
            user_id mediumint(9) NOT NULL,
            post_id mediumint(9) NOT NULL,
            pregnancy BOOLEAN,
            medications BOOLEAN,
            older BOOLEAN,
            breathing BOOLEAN,
            allergies BOOLEAN,
            cold BOOLEAN,
            lungs BOOLEAN,
            chest BOOLEAN,
            pneumotorax BOOLEAN,
            phobia BOOLEAN,
            behavior BOOLEAN,
            epilepsy BOOLEAN,
            migraine BOOLEAN,
            fainting BOOLEAN,
            moving BOOLEAN,
            decompression BOOLEAN,
            back BOOLEAN,
            backoperation BOOLEAN,
            diabetes BOOLEAN,
            fracture BOOLEAN,
            exercise BOOLEAN,
            bloodpresure BOOLEAN,
            heart BOOLEAN,
            heartattack BOOLEAN,
            infarction BOOLEAN,
            ears BOOLEAN,
            deafness BOOLEAN,
            earpressure BOOLEAN,
            bleeding BOOLEAN,
            hernia BOOLEAN,
            ulcer BOOLEAN,
            colostomy BOOLEAN,
            addiction BOOLEAN,
            parent text,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);

        $wpdb->query("ALTER TABLE $tableName ADD UNIQUE (`user_id`, `post_id`);");

        $tableName      = $wpdb->prefix."rkg_course_liability_meta";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL  AUTO_INCREMENT PRIMARY KEY,
            user_id mediumint(9) NOT NULL,
            post_id mediumint(9) NOT NULL,
            rs1 BOOLEAN,
            rs2 BOOLEAN,
            rs3 BOOLEAN,
            rs4 BOOLEAN,
            rs5 BOOLEAN,
            rs6 BOOLEAN,
            rs7 BOOLEAN,
            rs8 text,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);

        $wpdb->query("ALTER TABLE $tableName ADD UNIQUE (`user_id`, `post_id`);");
    }

    /**
     * alterExcursionsTables
     *
     * @return void
     *
     * Stvaranje i promjena tablica vezanih za izlete
     */
    private static function alterExcursionsTables()
    {
        global $wpdb;
        $tableName      = $wpdb->prefix."rkg_excursion_meta";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL PRIMARY KEY,
            leaders int(3) NULL,
            log BOOLEAN,
            limitation int(3) NULL,
            waiting int(3) NOT NULL,
            registered int(3) NOT NULL,
            guests BOOLEAN,
            guests_limit BOOLEAN,
            r1_adavanced BOOLEAN,
            r2 BOOLEAN,
            nitrox BOOLEAN,
            price text NULL,
            latitude text NOT NULL,
            longitude text NOT NULL,
            canceled BOOLEAN,
            starttime date DEFAULT '0000-00-00' NOT NULL,
            endtime date DEFAULT '0000-00-00' NOT NULL,
            deadline date DEFAULT '0000-00-00' NOT NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);

        $tableName      = $wpdb->prefix."rkg_excursion_signup";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id mediumint(9) NOT NULL,
            post_id mediumint(9) NOT NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);

        $wpdb->query("ALTER TABLE $tableName ADD UNIQUE (`user_id`, `post_id`);");

        $tableName      = $wpdb->prefix."rkg_excursion_waiting";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id mediumint(9) NOT NULL,
            post_id mediumint(9) NOT NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);

        $wpdb->query("ALTER TABLE $tableName ADD UNIQUE (`user_id`, `post_id`);");

        $tableName      = $wpdb->prefix."rkg_excursion_gear";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id mediumint(9) NOT NULL,
            post_id mediumint(9),
            mask text NULL,
            regulator text NULL,
            suit text NULL,
            boots text NULL,
            gloves text NULL,
            fins text NULL,
            bcd text NULL,
            lead text NULL,
            lead_size text NULL,
            other text NULL,
            other_admin text NULL,
            mask_returned BOOLEAN,
            regulator_returned BOOLEAN,
            suit_returned BOOLEAN,
            boots_returned BOOLEAN,
            gloves_returned BOOLEAN,
            fins_returned BOOLEAN,
            bcd_returned BOOLEAN,
            lead_returned BOOLEAN,
            other_returned BOOLEAN,
            lead_size_returned tinyint(1) NOT NULL,
            state tinyint(1) NOT NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);

        $wpdb->query("ALTER TABLE $tableName ADD UNIQUE (`user_id`, `post_id`);");
    }

    /**
     * alterGuestTable
     *
     * @return void
     *
     * Tablica gostiju na izletu
     */
    private static function alterGuestTable()
    {
        global $wpdb;

        $tableName      = $wpdb->prefix."rkg_excursion_guest";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id mediumint(9) NOT NULL,
            post_id mediumint(9) NOT NULL,
            name text NOT NULL,
            email varchar(255) NOT NULL,
            tel text NOT NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);

        $wpdb->query(
            "ALTER TABLE $tableName ADD UNIQUE (`user_id`, `post_id`, `email`);"
        );
    }

    /**
     * alterMembersTable
     *
     * @return void
     *
     * Tablica članova
     */
    private static function alterMembersTable()
    {
        global $wpdb;
        $tableName      = $wpdb->prefix."rkg_member_subscription";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user int(3) NOT NULL,
            year int(4) NOT NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);

        $sql = "ALTER TABLE $tableName
            ADD CONSTRAINT user_year UNIQUE ('user', 'year'); ";
        dbDelta($sql);
    }

    /**
     * alterInventoryTable
     *
     * @return void
     *
     * Tablice inventure
     */
    private static function alterInventoryTable()
    {
        global $wpdb;
        $tableName      = $wpdb->prefix."rkg_inventory";
        $charsetCollate = $wpdb->get_charset_collate();
        $sql            = "CREATE TABLE $tableName (
            id varchar(255) NOT NULL UNIQUE PRIMARY KEY,
            type text NOT NULL,
            size text NOT NULL,
            thickness text NOT NULL,
            state tinyint(1) NOT NULL,
            user_id mediumint(9) NULL,
            issue_date DATETIME NULL,
            note text NOT NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charsetCollate;";
        dbDelta($sql);

        $sql = "ALTER TABLE $tableName
            ADD CONSTRAINT user_year UNIQUE ('user', 'year'); ";
        dbDelta($sql);
    }

    /**
     * changeRoles
     *
     * @return void
     *
     * Promjena uloga kod uključivanja plugina
     */
    private static function changeRoles()
    {
        $user = wp_get_current_user();
        self::addGod();
        $user->set_role('god');
        remove_role('administrator');
        remove_role('editorInstructor');
        remove_role('instructor');
        remove_role('editor');
        remove_role('author');
        remove_role('contributor');
        remove_role('subscriber');

        self::addAdministrator();
        self::addInstructor();
        self::addEditor();
        self::addManager();
        self::addMember();
        self::addUser();
        self::addEquipmentManager();


        add_role(
            'honorMember',
            __('Počasni član'),
            array()
        );
    }

    /**
     * addGod
     *
     * @return void
     *
     * Definiranje uloge i prava super administratora
     */
    private static function addGod()
    {
        add_role(
            'god',
            __('Super administrator')
        );
        $god = get_role('god');
        $god->add_cap('activate_plugins');
        $god->add_cap('create_users');
        $god->add_cap('delete_plugins');
        $god->add_cap('delete_themes');
        $god->add_cap('delete_users');
        $god->add_cap('edit_files');
        $god->add_cap('edit_plugins');
        $god->add_cap('edit_theme_options');
        $god->add_cap('edit_themes');
        $god->add_cap('edit_users');
        $god->add_cap('export');
        $god->add_cap('import');
        $god->add_cap('install_plugins');
        $god->add_cap('install_themes');
        $god->add_cap('list_users');
        $god->add_cap('manage_options');
        $god->add_cap('promote_users');
        $god->add_cap('remove_users');
        $god->add_cap('switch_themes');
        $god->add_cap('update_core');
        $god->add_cap('update_plugins');
        $god->add_cap('update_themes');
        $god->add_cap('edit_dashboard');
        $god->add_cap('customize');
        $god->add_cap('delete_site');
        $god->add_cap('moderate_comments');
        $god->add_cap('manage_categories');
        $god->add_cap('manage_links');
        $god->add_cap('edit_others_posts');
        $god->add_cap('edit_pages');
        $god->add_cap('edit_others_pages');
        $god->add_cap('edit_published_pages');
        $god->add_cap('publish_pages');
        $god->add_cap('delete_pages');
        $god->add_cap('delete_others_pages');
        $god->add_cap('delete_published_pages');
        $god->add_cap('delete_others_posts');
        $god->add_cap('delete_private_posts');
        $god->add_cap('edit_private_posts');
        $god->add_cap('read_private_posts');
        $god->add_cap('delete_private_pages');
        $god->add_cap('edit_private_pages');
        $god->add_cap('read_private_pages');
        $god->add_cap('unfiltered_html');
        $god->add_cap('edit_published_posts');
        $god->add_cap('upload_files');
        $god->add_cap('publish_posts');
        $god->add_cap('delete_published_posts');
        $god->add_cap('edit_posts');
        $god->add_cap('delete_posts');
        $god->add_cap('read');
        //RKG capabilities
        $god->add_cap('member_access');
        $god->add_cap('edit_excursion');
        $god->add_cap('edit_excursions');
        $god->add_cap('edit_other_excursions');
        $god->add_cap('publish_excursions');
        $god->add_cap('read_excursions');
        $god->add_cap('read_private_excursions');
        $god->add_cap('delete_excursions');
        $god->add_cap('publish_courses');
        $god->add_cap('edit_courses');
        $god->add_cap('edit_others_courses');
        $god->add_cap('delete_courses');
        $god->add_cap('delete_others_courses');
        $god->add_cap('read_private_courses');
        $god->add_cap('edit_course');
        $god->add_cap('delete_course');
        $god->add_cap('read_course');
        $god->add_cap('manage_equipment');
        $god->add_cap('edit_other_excursions');
        $god->add_cap('edit_others_rkgPosts');
        $god->add_cap('publish_rkgPosts');
        $god->add_cap('edit_private_rkgPosts');
        $god->add_cap('read_private_rkgPosts');
    }

    /**
     * addAdministrator
     *
     * @return void
     *
     * Definiranje uloge i prava administratora
     */
    private static function addAdministrator()
    {
        add_role(
            'administrator',
            __('Administrator'),
            array(
                'create_users'            => true,
                'delete_users'            => true,
                'edit_files'              => true,
                'edit_users'              => true,
                'list_users'              => true,
                'promote_users'           => true,
                'remove_users'            => true,
                'manage_categories'       => true,
                'manage_links'            => true,
                'edit_pages'              => true,
                'edit_others_pages'       => true,
                'edit_published_pages'    => true,
                'publish_pages'           => true,
                'delete_pages'            => true,
                'delete_others_pages'     => true,
                'delete_published_pages'  => true,
                'delete_others_posts'     => true,
                'delete_private_posts'    => true,
                'edit_private_posts'      => true,
                'read_private_posts'      => true,
                'delete_private_pages'    => true,
                'edit_private_pages'      => true,
                'read_private_pages'      => true,
                'unfiltered_html'         => true,
                'edit_published_posts'    => true,
                'upload_files'            => true,
                'publish_posts'           => true,
                'delete_published_posts'  => true,
                'edit_posts'              => true,
                'delete_posts'            => true,
                'read'                    => true,
                //RKG capabilities
                'member_access'           => true,
                'edit_excursion'          => true,
                'publish_excursions'      => true,
                'read_excursions'         => true,
                'read_private_excursions' => true,
                'delete_excursions'       => true,
                'manage_equipment'             => true,
                'publish_rkgPosts'        => true,
                'edit_others_rkgPosts'     => true,
                'edit_rkgPosts'            => true,
                'read_gallery_courses'    => true,
                'edit_course' => true,
                'read_course' => true,
                'delete_course' => true,
                'edit_courses' => true,
                'edit_others_courses' => true,
                'publish_courses' => true,
                'read_private_courses' => true,
                'delete_courses' => true,
                'delete_private_courses' => true,
                'delete_published_courses' => true,
                'delete_others_courses' => true,
                'edit_private_courses' => true,
                'edit_published_courses' => true,
            )
        );
    }

    /**
     * addEquipmentManager
     *
     * @return void
     *
     * Definiranje uloge i prava oružara
     */
    private static function addEquipmentManager()
    {
        add_role(
            'equipmentManager',
            __('Oružar'),
            array(
                'manage_equipment'             => true,
            )
        );
    }

    /**
     * addInstructor
     *
     * @return void
     *
     * Definiranje uloge i prava instruktora
     */
    private static function addInstructor()
    {
        add_role(
            'instructor',
            __('Instruktor'),
            array(
                'publish_courses'       => true,
                'edit_courses'          => true,
                'delete_courses'        => true,
                'read_private_courses'  => true,
                'edit_course'           => true,
                'delete_course'         => true,
                'read_course'           => true,
            )
        );
    }

    /**
     * addEditor
     *
     * @return void
     *
     * Definiranje uloge i prava urednika (objave)
     */
    private static function addEditor()
    {
        add_role(
            'editor',
            __('Urednik'),
            array(
                'publish_rkgPosts'        => true,
                'edit_others_rkgPosts'     => true,
                'edit_rkgPosts'            => true,
                'manage_categories'        => true,
                'edit_posts'              => true,
                'edit_others_posts' => true
            )
        );
    }

    /**
     * addManager
     *
     * @return void
     *
     * Definiranje uloge i prava upravitelja korisnicima
     */
    private static function addManager()
    {
        add_role(
            'manager',
            __('Upravitelj korisnika'),
            array(
                'edit_users'    => true,
                'delete_users'  => true,
                'create_users'  => true,
                'list_users'    => true,
                'remove_users'  => true,
                'promote_users' => true,
            )
        );
    }

    /**
     * addMember
     *
     * @return void
     *
     * Definiranje uloge i prava člana
     */
    private static function addMember()
    {
        add_role(
            'member',
            __('Član'),
            array(
                'manage_links'            => true,
                'unfiltered_html'         => true,
                'upload_files'            => true,
                'read'                    => true,
                //RKG capabilities
                'member_access'           => true,
                'edit_excursion'          => true,
                'read_excursion'          => true,
                'delete_excursion'        => true,
                'edit_excursions'         => true,
                'read_private_excursions' => true,
                'publish_excursions'      => true,
                'edit_rkgPost'            => true,
                'delete_rkgPost'          => true,
                'edit_rkgPosts'           => true,
                'read_private_rkgPosts'   => true,
                'edit_private_rkgPosts'   => true,
                'read_excursions' => true,
            )
        );
    }

    /**
     * addUser
     *
     * @return void
     *
     * Definiranje uloge i prava korisnika
     */
    private static function addUser()
    {
        add_role(
            'user',
            __('Korisnik'),
            array(
                'read'                   => true,
                'read_rkgPost'            => true,
            )
        );
    }

    /**
     * removeRkgRoles
     *
     * @return void
     *
     * Briše stvorene uloge kod deaktivacije plugina
     */
    private static function removeRkgRoles()
    {
        remove_role('user');
        remove_role('member');
        remove_role('editor');
        remove_role('instructor');
        remove_role('equipmentManager');
        remove_role('armorer');
        remove_role('manager');
        remove_role('administrator');
    }
}
