<?php
/**
 * Class: RKGeronimoExcursion
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class RKGeronimoExcursion
{
    private $post;
    private $wpdb;

    /**
     * __construct
     *
     * @SuppressWarnings(PHPMD)
     *
     * @return null
     */
    public function __construct()
    {
        global $wpdb;
        add_action('init', array($this, 'rkgCreateExcursionPosttype'));
        add_action('save_post', array($this, 'rkgSaveExcursionData'));
        add_shortcode('rkg-map', array($this, 'rkgExcursion'));
        add_action('wp_ajax_excursion_search', array($this, 'excursionSearch'));
        add_filter('manage_excursion_posts_columns', array($this, 'setExcursionColumns'));
        add_action('manage_excursion_posts_custom_column', array($this, 'customExcursionColumn'), 10, 2 );
        $this->post = $_POST;
        $this->wpdb = $wpdb;
    }

    /**
     * rkgCreateExcursionPosttype
     *
     * @return null
     */
    public function rkgCreateExcursionPosttype()
    {
        $labels = array(
            'name'          => __('Izleti'),
            'singular_name' => __('Izlet'),
            'add_new'       => __('Dodaj novi izlet'),
            'add_new_item'  => __('Dodaj novi izlet'),
            'edit_item'     => __('Uredi izlet'),
            'new_item'      => __('Dodaj novi izlet'),
            'view_item'     => __('Pregledaj izlet'),
            'search_items'  => __('Pretraži izlete'),
        );
        register_post_type(
            'excursion',
            array(
                'labels' => $labels,
                'public' => true,
                // 'has_archive' => true,
                'supports' => array(
                    'title',
                    'editor',
                    'author',
                    'thumbnail',
                    'revisions',
                ),
                'menu_icon' => 'dashicons-calendar-alt',
                'taxonomies' => array( 'excursion' ),
                'register_meta_box_cb' => array(
                    $this,
                    'rkgCreateExcursionMetaboxes',
                ),
                'capability_type' => array('excursion', 'excursions'),
                'capabilities' => array(
                    'edit_post' => 'edit_excursion',
                    'edit_posts' => 'edit_excursions',
                    'edit_others_posts' => 'edit_other_excursions',
                    'publish_posts' => 'publish_excursions',
                    'read_post' => 'read_excursions',
                    'read_private_posts' => 'read_private_excursions',
                    'delete_post' => 'delete_excursions'
                ),
            )
        );
    }

    /**
     * rkgCreateExcursionMetaboxes
     *
     * @return null
     */
    public function rkgCreateExcursionMetaboxes()
    {
        add_meta_box(
            'rkg_excursion_data_metabox',
            'Informacije o izletu',
            array($this, 'rkgShowExcursionData'),
            'excursion',
            'advanced',
            'high'
        );
    }

    /**
     * rkgShowExcursionData
     *
     * @return bool/string
     */
    public function rkgShowExcursionData()
    {
        global $post;
        wp_nonce_field(basename(__FILE__), 'rkg_excursion_nounce');
        $context = Timber::get_context();
        if ($post->ID) {
            $tableName       = $this->wpdb->prefix . "rkg_excursion_meta";
            $context['meta'] = $this->wpdb->get_row("SELECT id, leaders, log, ".
                "price, latitude, longitude, limitation, guests, guests_limit, canceled, starttime, "
                ."endtime, deadline, course FROM "
                .$tableName
                ." WHERE id="
                .$post->ID);
        }

        if (current_user_can('edit_course')) {
            $user = wp_get_current_user();

            global $wpdb;
            $tableName          = $wpdb->prefix."rkg_course_meta";
            $firstJoin          = $wpdb->prefix."rkg_course_template";
            $secondJoin         = $wpdb->prefix."posts";

            $whereClause = "WHERE rcm.endtime > '".date("Y-m-d")."' ";
            
            // Allow admins to see all courses
            // regular users only see their own courses
            if (!current_user_can('edit_others_courses')) {
                $whereClause .= "AND rcm.organiser = ".$user->ID." ";
            }
            
            $query = "SELECT rcm.id, rcm.category AS cat, "
                ."rct.category, rcm.starttime, rcm.endtime, rcm.deadline, rct.name, rct.priority, "
                ."p.post_title, p.post_content FROM ".$tableName." AS rcm "
                ."INNER JOIN ".$firstJoin." AS rct ON rcm.category = rct.id "
                ."INNER JOIN ".$secondJoin." AS p ON rcm.id = p.id "
                .$whereClause
                ."AND p.post_status='publish'"
                ." ORDER BY rct.priority, rcm.category, rcm.starttime";
            
            $context['courses'] = $wpdb->get_results($query);

            foreach ($context['courses'] as $key => $course) {
                $course_signup_table = $wpdb->prefix . "rkg_course_signup";
                $course_meta_table = $wpdb->prefix . "rkg_course_meta";
                
                // count course participants
                $participants_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $course_signup_table WHERE course_id = %d",
                    $course->id
                ));
                
                // add assistant if exists
                $assistant_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $course_meta_table WHERE id = %d AND assistant IS NOT NULL AND assistant != 0",
                    $course->id
                ));
                
                $context['courses'][$key]->participants_count = $participants_count + $assistant_exists;
            }
        }

        $context['applicants'] = get_users(array(
            'fields' => 'all_with_meta',
            'meta_key' => "application-".$post->ID,
            'meta_value' => 'applied'
        ));

        $context['userList'] = get_users(array(
            'role'    => 'member',
        ));

        $templates = array('rkgExcursionMeta.twig');
        Timber::render($templates, $context);
    }

    /**
     * rkgSaveExcursionData
     *
     * @param mixed $postId
     * @return null
     */
    public function rkgSaveExcursionData($postId)
    {
        if ("excursion" != get_post_type($postId)) {
            return $postId;
        }

        if ($this->rkgValidateExcursionData($postId) == $postId) {
            return $postId;
        }

        if (!empty($this->post)) {
            $tableName = $this->wpdb->prefix . "rkg_excursion_meta";

            $log = false;
            if (!empty($this->post['log'])) {
                $log = true;
            }

            $guests = false;
            if (!empty($this->post['guests'])) {
                $guests = true;
            }

            $guests_limit = false;
            if (!empty($this->post['guests_limit'])) {
                $guests_limit = true;
            }

            $canceled = false;
            if (!empty($this->post['canceled'])) {
                $canceled = true;
            }

            $latitude = $this->post['latitude'];
            $longitude = $this->post['longitude'];

            $load_registered = $this->wpdb->get_var(
                "SELECT registered FROM "
                .$tableName
                ." WHERE id = "
                .$this->post['post_ID']
            );
            // Count organizer as registered since added as participant
            $registered = empty($load_registered) ? 1 : $load_registered;

            // Make sure that registered counter is still ok with guests
            if (true) {
                $guestsNumber = $this->wpdb->get_var(
                    "SELECT COUNT(*) FROM "
                    .$this->wpdb->prefix."rkg_excursion_guest"
                    ." WHERE post_id="
                    .$this->post['post_ID']
                );

                $participantsNumber = $this->wpdb->get_var(
                    "SELECT COUNT(*) FROM "
                    .$this->wpdb->prefix."rkg_excursion_signup"
                    ." WHERE post_id="
                    .$this->post['post_ID']
                );

                if (empty($guests_limit)) {
                    // Excude guests now from registered
                    $registered = $participantsNumber;
                } else {
                    // Include guests now in registered
                    $registered = $guestsNumber + $participantsNumber;
                }
            }

            $course = !empty($this->post['excursion-course']) ? $this->post['excursion-course'] : null;

            $sql = $this->wpdb->prepare(
                "INSERT INTO $tableName (id, leaders, log, limitation, registered, guests, guests_limit, ".
                "price, latitude, longitude, canceled, starttime, endtime, deadline, course) ".
                "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s) ".
                "ON DUPLICATE KEY UPDATE leaders = %s, log = %s, limitation = %s, registered = %s, ".
                "guests = %s, guests_limit = %s, price = %s, latitude = %s, longitude = %s, ".
                "canceled = %s, starttime = %s, endtime = %s, deadline = %s, course = %s",
                $this->post['post_ID'],
                $this->post['leaders'],
                $log,
                $this->post['limitation'],
                $registered,
                $guests,
                $guests_limit,
                $this->post['price'],
                $latitude,
                $longitude,
                $canceled,
                $this->post['starttime'],
                $this->post['endtime'],
                $this->post['deadline'],
                $course,
                $this->post['leaders'],
                $log,
                $this->post['limitation'],
                $registered,
                $guests,
                $guests_limit,
                $this->post['price'],
                $latitude,
                $longitude,
                $canceled,
                $this->post['starttime'],
                $this->post['endtime'],
                $this->post['deadline'],
                $course
            );
            $result = $this->wpdb->query($sql);

            $metaTableName = $this->wpdb->prefix."rkg_excursion_meta";

            // When creating new excursion, add organizer
            if ($result) {
                $tableName   = $this->wpdb->prefix."rkg_excursion_signup";
                // set author, in case admin has put organizer as someone else
                $author = isset($this->post['post_author_override']) ? $this->post['post_author_override'] : $this->post['post_author'];
                $sql = $this->wpdb->prepare(
                    // since this does not check if the author is already added
                    // IGNORE will prevent duplicate error on update of excursion
                    "INSERT IGNORE INTO $tableName (post_id, user_id) VALUES (%s, %s) ",
                    $this->post['post_ID'],
                    $author
                );
                $result = $this->wpdb->query($sql);
                if ($result > 0) {
                    $this->wpdb->query("UPDATE $metaTableName SET registered = registered + 1 WHERE id = {$this->post['post_ID']};");
                }
            }

            // Add predprijave
            $tableName = $this->wpdb->prefix."rkg_excursion_signup";

            if ($this->post['pr1']) {
                $userId = $this->post['pr1'];
                $postId = $this->post['post_ID'];

                // Check if user can be moved from waiting to registered
                if ($this->moveUserFromWaitingToRegistered($userId, $postId)) {
                    $sql = $this->wpdb->prepare(
                        "INSERT IGNORE INTO $tableName (user_id, post_id) ".
                        "VALUES (%s, %s) ",
                        $userId,
                        $postId
                    );
                    $result = $this->wpdb->query($sql);

                    if ($result > 0) {
                        $this->wpdb->query("UPDATE $metaTableName SET registered = registered + 1 WHERE id = {$postId};");
                    }
                }
            }
            if ($this->post['pr2']) {
                $userId = $this->post['pr2'];
                $postId = $this->post['post_ID'];

                if ($this->moveUserFromWaitingToRegistered($userId, $postId)) {
                    $sql = $this->wpdb->prepare(
                        "INSERT IGNORE INTO $tableName (user_id, post_id) ".
                        "VALUES (%s, %s) ",
                        $userId,
                        $postId
                    );
                    $result = $this->wpdb->query($sql);

                    if ($result > 0) {
                        $this->wpdb->query("UPDATE $metaTableName SET registered = registered + 1 WHERE id = {$postId};");
                    }
                }
            }
            if ($this->post['pr3']) {
                $userId = $this->post['pr3'];
                $postId = $this->post['post_ID'];

                if ($this->moveUserFromWaitingToRegistered($userId, $postId)) {
                    $sql = $this->wpdb->prepare(
                        "INSERT IGNORE INTO $tableName (user_id, post_id) ".
                        "VALUES (%s, %s) ",
                        $userId,
                        $postId
                    );
                    $result = $this->wpdb->query($sql);

                    if ($result > 0) {
                        $this->wpdb->query("UPDATE $metaTableName SET registered = registered + 1 WHERE id = {$postId};");
                    }
                }
            }
            if ($this->post['pr4']) {
                $userId = $this->post['pr4'];
                $postId = $this->post['post_ID'];

                if ($this->moveUserFromWaitingToRegistered($userId, $postId)) {
                    $sql = $this->wpdb->prepare(
                        "INSERT IGNORE INTO $tableName (user_id, post_id) ".
                        "VALUES (%s, %s) ",
                        $userId,
                        $postId
                    );
                    $result = $this->wpdb->query($sql);

                    if ($result > 0) {
                        $this->wpdb->query("UPDATE $metaTableName SET registered = registered + 1 WHERE id = {$postId};");
                    }
                }
            }
        }
    }

    /**
     * rkgValidateExcursionData
     *
     * @param mixed $postId
     * @return null
     */
    private function rkgValidateExcursionData($postId)
    {
        if (!empty($this->post['rkg_excursion_nounce'])
            && !wp_verify_nonce(
                $this->post['rkg_excursion_nounce'],
                basename(__FILE__)
            )
        ) {
            return $postId;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $postId;
        }

        if (!empty($this->post['rkg_excursion_nounce'])
            && ('excursion' === $this->post['post_type'])) {
            if (!current_user_can('edit_excursion', $postId)) {
                return $postId;
            }
        }

        return null;
    }

    /**
     * rkgExcursion
     *
     * @return bool/string
     */
    public function rkgExcursion()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . "rkg_excursion_meta";
        $context   = Timber::get_context();
        $templates = array( 'rkgExcursion.twig' );

        $tableName  = $this->wpdb->prefix."rkg_excursion_meta";
        $firstJoin  = $this->wpdb->prefix."users";
        $secondJoin = $this->wpdb->prefix."posts";

        $context['rkgExcursionNow'] = $this->wpdb->get_results("SELECT rem.id, "
            ."rem.latitude, rem.longitude, "
            ."rem.starttime, rem.endtime, rem.deadline, rem.limitation, "
            ."rem.registered, p.post_title, u.display_name, rem.canceled FROM ".
            $tableName." AS rem "
            ."INNER JOIN ".$secondJoin." AS p ON rem.id = p.id "
            ."INNER JOIN ".$firstJoin." AS u ON p.post_author = u.id "
            ."WHERE rem.starttime <= ".date("'Y-m-d'")
            ." AND rem.endtime >= ".date("'Y-m-d'")
            ." AND rem.canceled=0"
            ." AND p.post_status='publish'"
            ." ORDER BY rem.starttime ASC");

        foreach ($context['rkgExcursionNow'] as $key => $value) {
            $context['rkgExcursionNow'][$key]->link = get_permalink($value->id);
        }

        $context['rkgExcursionNew'] = $this->wpdb->get_results("SELECT rem.id, "
            ."rem.latitude, rem.longitude, "
            ."rem.starttime, rem.endtime, rem.deadline, rem.limitation, rem.registered, p.post_title, "
            ."u.display_name, rem.canceled FROM ".$tableName." AS rem "
            ."INNER JOIN ".$secondJoin." AS p ON rem.id = p.id "
            ."INNER JOIN ".$firstJoin." AS u ON p.post_author = u.id "
            ."WHERE rem.starttime > ".date("'Y-m-d'")
            ." AND rem.canceled=0"
            ." AND p.post_status='publish'"
            ." ORDER BY rem.starttime ASC");

        foreach ($context['rkgExcursionNew'] as $key => $value) {
            $context['rkgExcursionNew'][$key]->link = get_permalink($value->id);
        }

        $context['rkgExcursionOld'] = $this->wpdb->get_results("SELECT rem.id, "
            ."rem.latitude, rem.longitude, "
            ."rem.starttime, rem.endtime, rem.deadline, rem.limitation, rem.registered, p.post_title, "
            ."u.display_name, rem.canceled FROM ".$tableName." AS rem "
            ."INNER JOIN ".$secondJoin." AS p ON rem.id = p.id "
            ."INNER JOIN ".$firstJoin." AS u ON p.post_author = u.id "
            ."WHERE rem.endtime < ".date("'Y-m-d'")
            ." AND rem.canceled=0"
            ." AND p.post_status='publish'"
            ." ORDER BY rem.starttime DESC LIMIT 15");

        foreach ($context['rkgExcursionOld'] as $key => $value) {
            $context['rkgExcursionOld'][$key]->link = get_permalink($value->id);
        }

        return Timber::compile($templates, $context);
    }

    /**
     * rkgExcursionPage
     *
     * @return bool/string
     */
    public function rkgExcursionPage()
    {
        global $wpdb;
        $tableName             = $wpdb->prefix . "rkg_excursion";
        $context               = Timber::get_context();
        $context['rkgForm']    = null;
        $context['rkgMapEdit'] = null;
        if (isset($this->post['submit']) && $this->post['submit'] == 'Save') {
            if (empty($this->post['id'])) {
                $sql = $wpdb->prepare(
                    "INSERT INTO $tableName (name, organizer, starttime, endtime,"
                    ." deadline, limitation, price, latitude, longitude) "
                    ."VALUES (%s, %s, %s, %s, %s, %d, %s, %s, %s)",
                    $this->post['name'],
                    $this->post['organizer'],
                    $this->post['starttime'],
                    $this->post['endtime'],
                    $this->post['deadline'],
                    $this->post['limitation'],
                    $this->post['price'],
                    $this->post['latitude'],
                    $this->post['longitude']
                );

                $context['rkgForm'] = 'added';
            } else {
                $sql = $wpdb->prepare(
                    "UPDATE $tableName SET name = %s, organizer = %s,"
                    ." starttime = %s, endtime = %s, deadline = %s, limitation = %d,"
                    ." price = %s, latitude = %s, longitude = %s WHERE id = %d",
                    $this->post['name'],
                    $this->post['organizer'],
                    $this->post['starttime'],
                    $this->post['endtime'],
                    $this->post['deadline'],
                    $this->post['limitation'],
                    $this->post['price'],
                    $this->post['latitude'],
                    $this->post['longitude'],
                    $this->post['id']
                );

                $context['rkgForm'] = 'updated';
            }
            $wpdb->query($sql);
        } elseif (isset($this->post['submit']) && $this->post['submit'] == 'Edit') {
            $context['rkgMapEdit'] = $wpdb->get_row(
                "SELECT id, name, organizer, starttime, endtime, deadline,"
                ." limitation, price, latitude, longitude FROM "
                .$tableName.
                " WHERE id = ". $this->post['id']
            );
        } elseif (isset($this->post['submit'])
            && $this->post['submit'] == 'Repeat') {
            $context['rkgMapEdit'] = $wpdb->get_row(
                "SELECT name, organizer, limitation, price, latitude,"
                ." longitude FROM "
                .$tableName.
                " WHERE id = ". $this->post['id']
            );
        } elseif (isset($this->post['submit'])
            && $this->post['submit'] == 'Delete') {
            $wpdb->delete($tableName, array( 'id' => $this->post['id'] ));
            $context['rkgForm'] = 'deleted';
        }

        $context['rkgExcursion'] = $wpdb->get_results(
            "SELECT id, name, organizer, starttime, endtime, deadline, limitation,".
            " price, latitude, longitude FROM "
            .$tableName.
            " ORDER BY starttime DESC LIMIT 10"
        );

        $templates = array( 'rkgExcursionMenu.twig' );
        Timber::render($templates, $context);
    }

    public function setExcursionColumns($columns)
    {
        $columns = $this->array_insert_after('title', $columns, 'start', 'Od');
        $columns = $this->array_insert_after('start', $columns, 'end', 'Do');
        $columns = $this->array_insert_after('end', $columns, 'participants', 'Prijavljenih');

        return $columns;
    }

    public function customExcursionColumn($column, $postId)
    {
        $tableName  = $this->wpdb->prefix."rkg_excursion_meta";
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

                if (!$registered) {
                    $registered = "0";
                }
                echo $registered;
                break;
        }
    }

    public function excursionSearch()
    {
        global $wpdb;
        $context    = Timber::get_context();
        $tableName  = $wpdb->prefix."rkg_excursion_meta";
        $firstJoin  = $wpdb->prefix."users";
        $secondJoin = $wpdb->prefix."posts";
        $thirdJoin  = $wpdb->prefix."rkg_excursion_signup";

        if (!empty($context['request']->post['godina'])
            || !empty($context['request']->post['naziv'])
            || !empty($context['request']->post['organizator'])
            || !empty($context['request']->post['prijavljeni'])
        ) {
            $where = array();
            if (!empty($context['request']->post['godina'])) {
                $where[] = "rem.endtime > '".$context['request']->post['godina']."-01-01'";
                if ($context['request']->post['godina'] == date("Y")) {
                    $where[] = "rem.starttime < ".date("'Y-m-d'");
                } else {
                    $where[] = "rem.starttime < '".$context['request']->post['godina']."-12-31'";
                }
            } else {
                $where[] = "rem.endtime < ".date("'Y-m-d'");
            }
            if (!empty($context['request']->post['naziv'])) {
                $where[] = "p.post_title LIKE '%".$context['request']->post['naziv']."%'";
            }
            if (!empty($context['request']->post['organizator'])) {
                $tableNameUsers = $wpdb->prefix."users";
                $organiser = $wpdb->get_col(
                    "SELECT id FROM ".$tableNameUsers
                    ." WHERE display_name LIKE '%".$context['request']->post['organizator']."%'"
                );

                if ($organiser) {
                    $where[] = "p.post_author IN (".implode(',', $organiser).")";
                } else {
                    $where[] = "1 = 0";
                }
            }
            if (!empty($context['request']->post['prijavljeni'])) {
                $tableNameUsers = $wpdb->prefix."users";
                $participant = $wpdb->get_col(
                    "SELECT id FROM ".$tableNameUsers
                    ." WHERE display_name LIKE '%".$context['request']->post['prijavljeni']."%'"
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

            foreach ($context['excursions'] as $key => $value) {
                $context['excursions'][$key]->link = get_permalink($value->id);
            }

            $templates = array( 'ajax/rkgExcursionAjax.twig' );
            $data = array(
                "html" => Timber::compile($templates, $context),
                "cords" => $context['excursions'],
            );
            echo json_encode($data);

        }
        wp_die();
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

    /**
     * Remove user from waiting list and add to registered list
     *
     * @param int $userId
     * @param int $postId
     * @return bool
     */
    private function moveUserFromWaitingToRegistered($userId, $postId)
    {
        $waitingTableName = $this->wpdb->prefix . "rkg_excursion_waiting";
        $signupTableName = $this->wpdb->prefix . "rkg_excursion_signup";
        $metaTableName = $this->wpdb->prefix . "rkg_excursion_meta";
        
        // Check if user is already registered in signup table
        $existingSignup = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $signupTableName WHERE user_id = %d AND post_id = %d",
            $userId,
            $postId
        ));
        
        // If user is already registered, don't process
        if ($existingSignup > 0) {
            return false;
        }
        
        // Delete from waiting list if user is there
        $waiting = $this->wpdb->delete(
            $waitingTableName,
            array(
                'user_id' => $userId,
                'post_id' => $postId
            ),
            array('%d', '%d')
        );
        
        // Update waiting counter if user was on waiting list
        if ($waiting) {
            $this->wpdb->query("UPDATE $metaTableName SET waiting = waiting - 1 WHERE id = {$postId};");
        }
        
        return true;
    }

}
