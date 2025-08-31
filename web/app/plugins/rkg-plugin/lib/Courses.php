<?php
namespace RKGeronimo;

use RKGeronimo\Interfaces\InitInterface;
use RKGeronimo\Tables\CourseStatus;
use RKGeronimo\UserData;
use Timber;
use ZipArchive;

/**
 * Class: Courses
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @see InitInterface
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class Courses implements InitInterface
{
    /**
     * post
     *
     * @var array
     */
    private $post;

    /**
     * wpdb
     *
     * @var object
     */
    private $wpdb;

    /**
     * __construct
     *
     * @SuppressWarnings(PHPMD)
     *
     * @return void
     */
    public function __construct()
    {
        global $wpdb;
        $this->post = $_POST;
        $this->wpdb = $wpdb;
    }

    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        add_action('admin_menu', array( $this, 'createCourseTemplatePage' ));
        add_action('init', array($this, 'createCoursePosttype'));
        add_action('save_post', array($this, 'saveCourseData'));
        add_shortcode('rkg-cou', array($this, 'courseBlock'));
        add_filter(
            'post_row_actions',
            array($this, 'changeCoursesRowActions'),
            10,
            2
        );
        add_action('admin_menu', array($this, 'addCourseReport'));
        add_action('admin_menu', array($this, 'addMedicalReport'));
        add_action('admin_menu', array($this, 'addLiabilityReport'));
        add_action('admin_menu', array($this, 'addBrevetDownload'));
        add_action('admin_menu', array($this, 'addHRSReport'));
        add_filter('manage_course_posts_columns', array($this, 'setCourseColumns'));
        add_action(
            'manage_course_posts_custom_column',
            array($this, 'customCourseColumn'),
            10,
            2
        );

        add_action('trashed_post', array($this, 'onDelete'));

        add_filter('months_dropdown_results', array($this, 'my_remove_date_filter'));

        add_action('admin_menu', array($this, 'courseInterests'));

        add_filter('bulk_actions-edit-course', '__return_empty_array', 100);
    }

    function my_remove_date_filter( $months ) {
        global $typenow; // use this to restrict it to a particular post type
        if ( $typenow == 'course' ) {
            return null; // return an empty array
        }
        return $months; // otherwise return the original for other post types
    }

    /**
     * addCourseReport
     *
     * @return void
     */
    public function addCourseReport()
    {
        add_submenu_page(
            'admin.php',
            'Tečaj - izvještaj',
            'Tečaj - izvještaj',
            'read_private_courses',
            'course_report',
            array($this, 'showCourseReport')
        );
    }

    /**
     * showCourseReport
     *
     * @return void
     */
    public function showCourseReport()
    {
        apply_filters('admin_body_class', 'rkg-printable');
        global $wpdb;
        $context = Timber::get_context();

        $id = $context['request']->get['post'];
        $context['post'] = new Timber\Post($id);

        $context['participants'] = array();
        $tableName = $wpdb->prefix."rkg_course_signup";
        $participants = $wpdb->get_results(
            "SELECT * FROM "
            .$tableName
            ." WHERE course_id="
            .$id
            ." ORDER BY created"
        );

        $tableName      = $wpdb->prefix."rkg_course_meta";
        if ($context['request']->post) {
            // TODO: Remove locked & completed
            $locked = 0;
            if ($context['request']->post['locked']) {
                $locked = 1;
            }
            $completed = 0;
            if ($context['request']->post['completed']) {
                $completed = 1;
            }
            $wpdb->update(
                $tableName,
                array(
                    'exam' => $context['request']->post['exam'],
                    'assistant' => $context['request']->post['assistant'],
                    'delegate' => $context['request']->post['delegate'],
                    'locked' => $locked,
                    'completed' => $completed,
                ),
                array(
                    'id' => $id,
                )
            );
        }
        $context['meta'] = $wpdb->get_row(
            "SELECT * FROM "
            .$tableName
            ." WHERE id="
            .$id
            ." ORDER BY created"
        );

        $context['userList'] = get_users();

        $context['allMedical'] = "<a href='"
            .get_admin_url()
            ."admin.php?page=course_medical&post="
            .$id
            ."'>Sve zdravstvene izjave</a>";

        $context['allLiability'] = "<a href='"
            .get_admin_url()
            ."admin.php?page=course_liability&post="
            .$id
            ."'>Sve izjave o odgovornosti</a>";
        $context['allBrevet'] = "<a href='"
            .get_admin_url()
            ."admin.php?page=course_brevet_zip&post="
            .$id
            ."'>Preuzimanje svih breveta</a>";
        $context['hrsReport'] = "<a href='"
            .get_admin_url()
            ."admin.php?page=course_report_hrs&post="
            .$id
            ."'>Izvještaj HRS-a</a>";

        $participantsEmails = array();
        $tableName = $wpdb->prefix."rkg_course_signup";
        foreach ($participants as $value) {
            $user = new Timber\User($value->user_id);
            $user->payed = $value->payed;
            array_push($participantsEmails, $user->user_email);
            if ($context['request']->post) {
                $payed = 0;
                if ($context['request']->post['payed'][$value->user_id]) {
                    $payed = 1;
                }
                // Update course data
                $wpdb->update(
                    $tableName,
                    array(
                        'new_card' => $context['request']->post['new_card'][$value->user_id],
                        'payed' => $payed,
                    ),
                    array(
                        'course_id' => $id,
                        'user_id' => $value->user_id,
                    )
                );
                $user->payed = $payed;
                // Update user data with new card number
                if ($context['request']->post['new_card'][$value->user_id]) {
                    update_user_meta($value->user_id, 'cardNumber', $context['request']->post['new_card'][$value->user_id]);
                }
            }

            $context['participants'][] = $user;
        }

        $courseStatusTable = new CourseStatus($id);
        $courseStatusTable->prepare_items();

        $context['table'] = $courseStatusTable;

        $context['sendEmails'] = "<a href='mailto:".implode(';', $participantsEmails).
        "'>Pošalji e-mail prijavljenima (".count($participantsEmails).")</a>";

        $templates = array( 'courseReport.twig' );
        Timber::render($templates, $context);
    }

    /**
     * addMedicalReport
     *
     * @return void
     */
    public function addMedicalReport()
    {
        $hook = add_submenu_page(
            null,
            'Tečaj - izvještaj',
            'Tečaj - izvještaj',
            'edit_courses',
            'course_medical',
            function () {
            }
        );
        add_action('load-'.$hook, function () {

            add_action('wp_enqueue_scripts', array($this, 'enqueueScriptsAdmin'));
            global $wpdb;
            $context = Timber::get_context();

            $id = $context['request']->get['post'];
            $context['post'] = new Timber\Post($id);

            $where = " WHERE post_id=".$id;
            if ($context['request']->get['user']) {
                $where .= " AND user_id=".$context['request']->get['user'];
            }

            $context['surveys'] = array();
            $tableName = $wpdb->prefix."rkg_course_medical_meta";
            $context['surveys'] = $wpdb->get_results(
                "SELECT * FROM "
                .$tableName
                .$where
                ." ORDER BY created"
            );

            foreach ($context['surveys'] as $key => $value) {
                $context['surveys'][$key]->user = new Timber\User($value->user_id);
            }
            $templates = array( 'medicalReport.twig' );
            Timber::render($templates, $context);
            exit;
        });
    }

    /**
     * addLiabilityReport
     *
     * @return
     */
    public function addLiabilityReport()
    {
        $hook = add_submenu_page(
            null,
            'Tečaj - izjava o odgovornosti',
            'Tečaj - izjava o odgovornosti',
            'edit_courses',
            'course_liability',
            function () {
            }
        );
        add_action('load-'.$hook, function () {

            add_action('wp_enqueue_scripts', array($this, 'enqueueScriptsAdmin'));
            global $wpdb;
            $context = Timber::get_context();

            $id = $context['request']->get['post'];
            $context['post'] = new Timber\Post($id);

            $where = " WHERE post_id=".$id;
            if ($context['request']->get['user']) {
                $where .= " AND user_id=".$context['request']->get['user'];
            }

            $context['surveys'] = array();
            $tableName = $wpdb->prefix."rkg_course_liability_meta";
            $context['surveys'] = $wpdb->get_results(
                "SELECT * FROM "
                .$tableName
                .$where
                ." ORDER BY created"
            );

            foreach ($context['surveys'] as $key => $value) {
                $context['surveys'][$key]->user = new Timber\User($value->user_id);
            }
            $templates = array( 'liabilityReport.twig' );
            Timber::render($templates, $context);
            exit;
        });
    }

    /**
     * addHRSReport
     *
     * @return void
     */
    public function addHRSReport()
    {
        $hook = add_submenu_page(
            null,
            'Tečaj - izvještaj HRS-u',
            'Tečaj - izvještaj HRS-u',
            'edit_courses',
            'course_report_hrs',
            function () {
            }
        );
        add_action('load-'.$hook, function () {

            add_action('wp_enqueue_scripts', array($this, 'enqueueScriptsAdmin'));
            global $wpdb;
            $context = Timber::get_context();

            $id = $context['request']->get['post'];
            $context['post'] = new Timber\Post($id);

            $and = "";
            if ($context['request']->get['students']) {
                $context['generate'] = true;
                $studentsImp = implode(',', $context['request']->get['students']);
                $and = " AND user_id IN ($studentsImp)";
            }

            $tableName = $wpdb->prefix."rkg_course_signup";
            $students = $wpdb->get_col(
                "SELECT user_id FROM "
                .$tableName
                ." WHERE course_id="
                .$id
                .$and
                ." ORDER BY created"
            );

            $context['totalStudents'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM "
                .$tableName
                ." WHERE course_id="
                .$id
            );

            $context['students'] = [];
            foreach ($students as $value) {
                $context['students'][] = new Timber\User($value);
            }


            foreach ($context['students'] as $key => $value) {
                $tableName = $wpdb->prefix."rkg_course_signup";
                $context['students'][$key]->signup = $wpdb->get_row(
                    "SELECT * FROM "
                    .$tableName
                    ." WHERE course_id="
                    .$id
                    ." AND user_id="
                    .$context['students'][$key]->id
                );
            }

            $tableName       = $wpdb->prefix."rkg_course_meta";
            $context['meta'] = $wpdb->get_row(
                "SELECT * FROM "
                .$tableName
                ." WHERE id="
                .$id
            );

            $context['assistant'] = new Timber\User($context['meta']->assistant);

            $tableName               = $wpdb->prefix."rkg_course_template";
            $context['metaTemplate'] = $wpdb->get_row(
                "SELECT * FROM "
                .$tableName
                ." WHERE id="
                .$context['meta']->category
            );

            $context['organiser'] = new Timber\User($context['meta']->organiser);

            $where = " WHERE post_id=".$id;
            if ($context['request']->get['user']) {
                $where .= " AND user_id=".$context['request']->get['user'];
            }

            $templates = array( 'HRSReportSpecialty.twig' );
            if (in_array($context['metaTemplate']->finish_categorie, array('R0', 'R1', 'R2', 'R3', 'I1'))) {
                $templates = array( 'HRSReport.twig' );
            }
            
            Timber::render($templates, $context);
            exit;
        });
    }

    /**
     * addBrevetDownload
     *
     * @return
     */
    public function addBrevetDownload()
    {
        $hook = add_submenu_page(
            null,
            'Tečaj - breveti',
            'Tečaj - breveti',
            'edit_courses',
            'course_brevet_zip',
            function () {
            }
        );
        add_action('load-'.$hook, function () {
            $context = Timber::get_context();
            global $wpdb;

            $id = $context['request']->get['post'];
            $tableName = $wpdb->prefix."rkg_course_signup";
            $participants = $wpdb->get_results(
                "SELECT * FROM "
                .$tableName
                ." WHERE course_id="
                .$id
                ." ORDER BY created"
            );
            $brevet = array();
            foreach ($participants as $value) {
                $userData = new UserData($value->user_id);
                if (isset($userData->userMeta['brevet']) && $userData->userMeta['brevet']) {
                    $brevet[] = array(
                        'file' => $userData->userMeta['brevet'][0],
                        'user' => $userData->userMeta['first_name'][0].'_'
                        .$userData->userMeta['last_name'][0],
                    );
                }
            }

            $uploadDir = wp_upload_dir()['path'];
            $tmpFile = wp_tempnam(uniqid(), $uploadDir);
            $zip = new ZipArchive();
            if($zip->open($tmpFile, ZipArchive::CREATE) !== TRUE ) {
                exit("cannot open <$tmpFile>\n");
            }

            foreach ($brevet as $file) {
                $ext = pathinfo($file['file'], PATHINFO_EXTENSION);
                $downloadFile = file_get_contents($file['file']);
                $filename = $file['user'].'.'.$ext;

                $zip->addFromString($filename, $downloadFile);
            }

            $zip->close();

            if (file_exists($tmpFile)) {
                header('Content-disposition: attachment; filename=breveti.zip');
                header('Content-type: application/zip');
                header('Content-Length: ' . filesize($tmpFile));
                readfile($tmpFile);
                exit;
            }
            exit("Error ocurred. Are there any photos?");
        });
    }

    /**
     * enqueueScriptsAdmin
     *
     * @return void
     */
    public function enqueueScriptsAdmin()
    {
        wp_register_style(
            'google_fonts',
            'https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700&display=swap'
        );
        wp_enqueue_style(
            'google_fonts'
        );
        wp_register_style(
            'Font_Awesome',
            'https://use.fontawesome.com/releases/v5.12.1/css/all.css'
        );
        wp_enqueue_style('rkg_css');
        wp_register_script(
            'cropie',
            'https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.4/croppie.min.js',
            array('jquery'),
            null,
            true
        );
        wp_register_style(
            'rkg_css-survey',
            get_template_directory_uri().'/style-survey.css'
        );
        wp_enqueue_style('rkg_css-survey');
    }

    /**
     * changeCoursesRowActions
     *
     * @param mixed $actions
     * @param mixed $post
     *
     * @return array
     */
    public function changeCoursesRowActions($actions, $post)
    {
        if ($post->post_type=='course')
        {
            $actions['report'] = "<a href='"
                .get_admin_url()
                ."admin.php?page=course_report&post="
                .$post->ID
                ."'>Status tečaja</a>";
        }
        unset($actions['trash']);

        return $actions;
    }

    /**
     * createCourseTemplatePage
     *
     * @return void
     */
    public function createCourseTemplatePage()
    {
        add_submenu_page(
            'edit.php?post_type=course',
            'Predložak tečajeva',
            'Predložak Tečajeva',
            'edit_courses',
            'rkg-course-template',
            array(
                $this,
                'courseTemplatePage',
            )
        );
    }

    public function courseInterests()
    {
        add_submenu_page(
            'edit.php?post_type=course',
            'Zainteresirani',
            'Zainteresirani',
            'edit_courses',
            'course_interests',
            array(
                $this,
                'courseInterestPage'
            )
        );
    }

    

    /**
     * courseTemplatePage
     *
     * @return void
     */
    public function courseTemplatePage()
    {
        $tableName          = $this->wpdb->prefix."rkg_course_template";
        $context            = Timber::get_context();
        $context['rkgForm'] = null;
        $context['rkgEdit'] = null;

        if (isset($this->post['submit']) && $this->post['submit'] === 'Save') {
            $context['rkgForm'] = $this->courseTemplateSave($tableName);
        } elseif (isset($this->post['submit']) && $this->post['submit'] === 'Edit') {
            $context['rkgEdit'] = $this->wpdb->get_row(
                "SELECT id, category, priority, name, location, terms, price,".
                " limitation, special, temp_categorie, finish_categorie,"
                ." payment_desc, payment_price, description FROM "
                .$tableName.
                " WHERE id = ".$this->post['id']
            );
        } elseif (isset($this->post['submit'])
            && $this->post['submit'] === 'Repeat') {
            $context['rkgEdit'] = $this->wpdb->get_row(
                "SELECT id, category, priority, name, location, terms, price,".
                " limitation, special, temp_categorie, finish_categorie,".
                " payment_desc, payment_price, description FROM "
                .$tableName.
                " WHERE id = ".$this->post['id']
            );
        } elseif (isset($this->post['submit'])
            && $this->post['submit'] === 'Delete') {
            $this->wpdb->delete($tableName, array('id' => $this->post['id']));
            $context['rkgForm'] = 'deleted';
        }

        $context['rkgCT'] = $this->wpdb->get_results(
            "SELECT id, category, priority, name, location, terms, price,".
            " limitation, special, temp_categorie, finish_categorie, payment_desc,".
            " payment_price, description FROM "
            .$tableName.
            " ORDER BY priority DESC LIMIT 10"
        );

        $templates = array( 'courseTemplateMenu.twig' );
        Timber::render($templates, $context);
    }

    public function courseInterestPage()
    {
        $context            = Timber::get_context();
        if(!current_user_can('edit_course')) {
            Timber::render('single-no-pasaran.twig', $context);
        }

        $tableName          = $this->wpdb->prefix."rkg_course_interest";
        $userTable          = $this->wpdb->prefix."users";
        $courseTable                  = $this->wpdb->prefix."rkg_course_template";

        $context['data'] = $this->wpdb->get_results(
            "SELECT i.id, u.display_name, u.user_email, c.name as course_name, i.created"
            ." FROM "
            .$tableName." AS i " 
            ."INNER JOIN ".$userTable." AS u ON i.user_id = u.id "
            ."INNER JOIN ".$courseTable." AS c ON i.course_template_id = c.id "
            ." ORDER BY i.created DESC"
        );

        $templates = array( 'courseInterest.twig' );
        Timber::render($templates, $context);
    }

    /**
     * createCoursePosttype
     *
     * @return void
     */
    public function createCoursePosttype()
    {
        $labels = array(
            'name'          => __('Tečajevi'),
            'singular_name' => __('Tečaj'),
            'add_new'       => __('Dodaj novi tečaj'),
            'add_new_item'  => __('Dodaj novi tečaj'),
            'edit_item'     => __('Uredi tečaj'),
            'new_item'      => __('Dodaj novi tečaj'),
            'view_item'     => __('Pregledaj tečaj'),
            'search_items'  => __('Pretraži tečajeve'),
        );
        register_post_type(
            'course',
            array(
                'labels'   => $labels,
                'public'   => true,
                'supports' => array(
                    'title',
                    'editor',
                    'author',
                    'thumbnail',
                    'revisions',
                ),
                'menu_icon'            => 'dashicons-clipboard',
                'taxonomies'           => array( 'courses' ),
                'register_meta_box_cb' => array($this, 'createCourseMetaboxes'),
                'capability_type' =>  array('course', 'courses'),
            )
        );
    }

    /**
     * createCourseMetaboxes
     *
     * @return void
     */
    public function createCourseMetaboxes()
    {
        add_meta_box(
            'rkg_course_data_metabox',
            'Informacije o tečaju',
            array($this, 'showCourseData'),
            'course',
            'prenormal',
            'high'
        );
    }

    /**
     * showCourseData
     *
     * @return void
     */
    public function showCourseData()
    {
        global $post;
        wp_nonce_field(basename(__FILE__), 'rkg_course_nounce');

        $context                    = Timber::get_context();
        $tableName                  = $this->wpdb->prefix."rkg_course_template";
        $context['courseTemplates'] = $this->wpdb->get_results(
            "SELECT id, category, priority, name, location, terms, price,"
            ." limitation, description FROM "
            .$tableName
            ." ORDER BY priority"
        );

        $instructors = get_users(array(
            'fields' => 'ID',
            'role'    => 'instructor',
            'orderby' => 'display_name',
            'order' => 'DESC',
        ));

        foreach ($instructors as $value) {
            $context['instructors'][] =  new Timber\User($value);
        }

        if ($post->ID) {
            $tableName       = $this->wpdb->prefix."rkg_course_meta";
            $context['meta'] = $this->wpdb->get_row(
                "SELECT id, category, "
                ."organiser, location, terms, price, limitation, starttime, "
                ."endtime, deadline FROM "
                .$tableName
                ." WHERE id="
                .$post->ID
            );
        }

        $context['applicants'] = get_users(array(
            'fields' => 'all_with_meta',
            'meta_key' => "application-".$post->ID,
            'meta_value' => 'applied',
        ));

        $templates = array('courseMeta.twig');
        Timber::render($templates, $context);
    }

    /**
     * saveCourseData
     *
     * @param mixed $postId
     *
     * @return void
     */
    public function saveCourseData($postId)
    {
        if ("course" !== get_post_type($postId)) {
            return $postId;
        }

        if ($this->validateCourseData($postId) === $postId) {
            return $postId;
        }

        if (!empty($this->post)) {
            $tableName = $this->wpdb->prefix."rkg_course_meta";

            $arg = array(
                'ID' => $postId,
                'post_author' => $this->post['organiser'],
            );

            remove_action('save_post', array($this, 'saveCourseData'));
            wp_update_post($arg);
            add_action('save_post', array($this, 'saveCourseData'));

            $sql = $this->wpdb->prepare(
                "INSERT INTO $tableName (id, category, organiser, ".
                "location, terms, price, limitation, starttime, endtime, deadline) ".
                "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s) ".
                "ON DUPLICATE KEY UPDATE category = %s, organiser = %s, ".
                "location = %s, terms = %s, price = %s, limitation = %s, ".
                "starttime = %s, endtime = %s, deadline = %s",
                $this->post['post_ID'],
                $this->post['category'],
                $this->post['organiser'],
                $this->post['location'],
                $this->post['terms'],
                $this->post['price'],
                $this->post['limitation'],
                $this->post['starttime'],
                $this->post['endtime'],
                $this->post['deadline'],
                $this->post['category'],
                $this->post['organiser'],
                $this->post['location'],
                $this->post['terms'],
                $this->post['price'],
                $this->post['limitation'],
                $this->post['starttime'],
                $this->post['endtime'],
                $this->post['deadline']
            );
            $this->wpdb->query($sql);
        }
    }

    /**
     * courseBlock
     *
     * @return bool/string
     */
    public function courseBlock()
    {
        $context = Timber::get_context();

        $currentUser = wp_get_current_user();
        $tableName = $this->wpdb->prefix."rkg_course_signup";
        $context['signups'] = $this->wpdb->get_col(
            "SELECT course_id FROM "
            .$tableName.
            " WHERE user_id = ".$currentUser->ID
        );

        $tableName = $this->wpdb->prefix."rkg_course_interest";
        $context['interested'] = $currentUser->ID ? $this->wpdb->get_col(
            "SELECT course_template_id FROM "
            .$tableName.
            " WHERE user_id = ".$currentUser->ID
        ) : [];

        $tableName          = $this->wpdb->prefix."rkg_course_meta";
        $firstJoin          = $this->wpdb->prefix."rkg_course_template";
        $secondJoin         = $this->wpdb->prefix."posts";
        $context['courses'] = $this->wpdb->get_results(
            "SELECT rcm.id, "
            ."rct.category, rcm.starttime, rcm.endtime, rcm.deadline, rct.name, "
            ."p.post_title, p.post_content, rct.id as 'rctid' FROM "
            .$tableName." AS rcm "
            ."INNER JOIN ".$firstJoin." AS rct ON rcm.category = rct.id "
            ."INNER JOIN ".$secondJoin." AS p ON rcm.id = p.id "
            ."WHERE rcm.deadline > ".date("'Y-m-d'")
            ." AND p.post_status='publish'"
            ." GROUP BY rcm.category ORDER BY rct.priority"
        );

        $exsist = null;
        foreach ($context['courses'] as $value) {
            $exsist[] = "'".$value->category."'";
        }

        $where = null;
        if (!empty($exsist)) {
            $where = " WHERE category NOT IN (".implode(",", $exsist).")";
        }

        $context['coursePlaceholders'] = $this->wpdb->get_results(
            "SELECT id, category, name, description "
            ." FROM "
            .$firstJoin
            .$where
            ." ORDER BY priority"
        );

        foreach ($context['courses'] as $key => $value) {
            $context['courses'][$key]->link  = get_permalink($value->id);
            $context['courses'][$key]->terms = $this->wpdb->get_results(
                "SELECT rcm.id, rct.name, p.post_title, p.post_content, "
                ."rcm.starttime, rcm.endtime, rcm.deadline "
                ."FROM ".$tableName." AS rcm "
                ."INNER JOIN ".$firstJoin." AS rct ON rcm.category = rct.id "
                ."INNER JOIN ".$secondJoin." AS p ON rcm.id = p.id "
                ."WHERE rct.id = '".$value->rctid
                ."' AND rcm.deadline > ".date("'Y-m-d'")
                ." AND p.post_status='publish'"
                ." ORDER BY rcm.deadline"
            );
            foreach ($context['courses'][$key]->terms as $key2 => $value2) {
                $context['courses'][$key]->terms[$key2]->link = get_permalink(
                    $value2->id
                );
            }
        }
        $templates = array('courseBlock.twig');

        return Timber::compile($templates, $context);
    }

    public function setCourseColumns($columns)
    {
        $columns = $this->array_insert_after('title', $columns, 'start', 'od');
        $columns = $this->array_insert_after('start', $columns, 'end', 'do');
        $columns = $this->array_insert_after('end', $columns, 'participants', 'Prijavljenih');

        return $columns;
    }

    public function customCourseColumn($column, $postId)
    {
        $tableName  = $this->wpdb->prefix."rkg_course_meta";
        switch ($column) {
            case 'start' :
                $date = $this->wpdb->get_var(
                    "SELECT starttime FROM "
                    .$tableName
                    ." WHERE id = "
                    .$postId
                );
                echo date("d.m.Y", strtotime($date));
                break;
            case 'end' :
                $date = $this->wpdb->get_var(
                    "SELECT endtime FROM "
                    .$tableName
                    ." WHERE id = "
                    .$postId
                );
                echo date("d.m.Y", strtotime($date));
                break;
            case 'participants' :
                $registered = $this->wpdb->get_var(
                    "SELECT registered FROM "
                    .$tableName
                    ." WHERE id = "
                    .$postId
                );
                echo $registered;
                break;
        }
    }


    /**
     * courseTemplateSave
     *
     * @param mixed $tableName
     *
     * @return void
     */
    private function courseTemplateSave($tableName)
    {

        $special = false;
        if (!empty($this->post['guests'])) {
            $special = true;
        }
        if (empty($this->post['id'])) {
            $sql = $this->wpdb->prepare(
                "INSERT INTO ".$tableName." (category, priority, name,".
                " location, terms, price, limitation, description) ".
                "VALUES (%s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s)",
                $this->post['category'],
                $this->post['priority'],
                $this->post['name'],
                $this->post['location'],
                $this->post['terms'],
                $this->post['price'],
                $this->post['limitation'],
                $special,
                $this->post['temp_categorie'],
                $this->post['finish_categorie'],
                $this->post['payment_desc'],
                $this->post['payment_price'],
                $this->post['description']
            );
            $this->wpdb->query($sql);

            return 'added';
        }
        $sql = $this->wpdb->prepare(
            "UPDATE ".$tableName." SET category = %s, priority = %s,".
            " name = %s, location = %s, terms = %s, price = %s,".
            " limitation = %d, special = %s, temp_categorie = %s,"
            ." finish_categorie = %s, payment_desc = %s, payment_price = %s,"
            ." description = %s WHERE id = %d",
            $this->post['category'],
            $this->post['priority'],
            $this->post['name'],
            $this->post['location'],
            $this->post['terms'],
            $this->post['price'],
            $this->post['limitation'],
            $special,
            $this->post['temp_categorie'],
            $this->post['finish_categorie'],
            $this->post['payment_desc'],
            $this->post['payment_price'],
            $this->post['description'],
            $this->post['id']
        );

        $this->wpdb->query($sql);

        return 'updated';
    }

    /**
     * validateCourseData
     *
     * @param mixed $postId id of wp post
     *
     * @return string/null
     */
    private function validateCourseData($postId)
    {
        if (!empty($this->post['rkg_course_nounce'])
            && !wp_verify_nonce(
                $this->post['rkg_course_nounce'],
                basename(__FILE__)
            )
        ) {
            return $postId;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $postId;
        }

        if (!empty($this->post['rkg_course_nounce'])
            && ('course' === $this->post['post_type'])) {
            if (!current_user_can('edit_course', $postId)) {
                return $postId;
            }
        }

        return null;
    }


    /**
     * getEmails
     *
     * @param mixed $id
     *
     * @return string
     */
    private function getEmails($id)
    {
        global $wpdb;
        $emails = array();
        $tableName = $wpdb->prefix."rkg_course_signup";
        $participants = $wpdb->get_col(
            "SELECT user_id FROM "
            .$tableName
            ." WHERE course_id="
            .$id
            ." ORDER BY created"
        );

        foreach ($participants as $value) {
            $user = new Timber\User($value);
            $emails[] = $user->user_email;
        }

        return "<a href='mailto:".implode(';', $emails).
            "'>Pošalji e-mail prijavljenima (".count($emails).")</a>";
    }

    private function array_insert_after($key, array &$array, $new_key, $new_value)
    {
        if (array_key_exists($key, $array)) {
            $new = array();
            foreach ($array as $k => $value) {
                $new[$k] = $value;
                if ($k === $key) {
                    $new[$new_key] = $new_value;
                }
            }
            return $new;
        }
        return false;
    }


    public function onDelete($postId) {
        if ("course" !== get_post_type($postId)) {
            return $postId;
        }

        global $wpdb;
        $tableName = $wpdb->prefix."rkg_course_meta";
        $this->wpdb->delete($tableName, array('id' => $postId));
    }

}
