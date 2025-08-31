<?php
namespace RKGeronimo;

use RKGeronimo\Interfaces\InitInterface;
use RKGeronimo\Helpers\Definitions;
use RKGeronimo\Tables\Reservations;
use Timber;

/**
 * Class: Inventory
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @see InitInterface
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class Inventory implements InitInterface
{
    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        add_action('admin_menu', array($this, 'addInventoryPage'));
        add_action('admin_menu', array($this, 'addInventoryNew'));
        add_action('admin_menu', array($this, 'addReservations'));
        add_action('admin_menu', array($this, 'addNewReservations'));

        add_action('wp_ajax_check_inventory', array($this, 'checkInventory'));
        add_action('wp_ajax_edit_reservation', array($this, 'editReservation'));
        add_action('wp_ajax_add_custom_reservation', array($this, 'addCustomReservation'));
    }

    /**
     * addInventoryPage
     *
     * @return void
     */
    public function addInventoryPage()
    {
        add_menu_page(
            'Upravljanje opremom',
            'Upravljanje opremom',
            'manage_equipment',
            'inventory_managment',
            array($this, 'showInventoryPage'),
            'dashicons-sos',
            6
        );
    }

    /**
     * showInventoryPage
     *
     * @return void
     */
    public function showInventoryPage()
    {
        $context = Timber::get_context();
        if (array_key_exists("edit", $context['request']->get)) {
            $this->showInventoryEdit();

            return;
        };

        $this->showInventoryList();
    }

    /**
     * addInventoryNew
     *
     * @return void
     */
    public function addInventoryNew()
    {
        add_submenu_page(
            'inventory_managment',
            'Dodavanje opreme',
            'Dodaj novi',
            'manage_equipment',
            'inventory_new',
            array($this, 'showInventoryNew')
        );
    }

    /**
     * showInventoryNew
     *
     * @return void
     */
    public function showInventoryNew()
    {
        $context                    = Timber::get_context();
        $context['typeTranslation'] = $this->translateTypes();
        $context['error'] = false;

        $post = $context['request']->post;
        if (!empty($post)) {
            global $wpdb;
            $tableName = $wpdb->prefix."rkg_inventory";
            $result = $wpdb->insert(
                $tableName,
                array(
                    'id' => $post['id'],
                    'type' => $post['type'],
                    'size' => $post['size'],
                )
            );
            $context['error'] = !!!$result;
        }

        $templates = array( 'inventoryNew.twig' );
        Timber::render($templates, $context);
    }

    /**
     * addReservation
     *
     * @return void
     */
    public function addReservations()
    {
        add_submenu_page(
            'inventory_managment',
            'Izdavanje opreme',
            'Izdavanje opreme',
            'manage_equipment',
            'reservations',
            array($this, 'showReservations')
        );
    }

    /**
     * addNewReservation
     *
     * @return void
     */
    public function addNewReservations()
    {
        add_submenu_page(
            'inventory_managment',
            'Izdavanje bez rezervacije',
            'Izdavanje bez rezervacije',
            'manage_equipment',
            'reservations_new',
            array($this, 'showNewReservations')
        );
    }

    private function saveReservation($reservationId, $typeTranslations, $data) {
        global $wpdb;
        $tableName = $wpdb->prefix."rkg_excursion_gear";
        $tableName2 = $wpdb->prefix."rkg_inventory";

        $originalReservation = $wpdb->get_row(
            "
            SELECT *
            FROM $tableName
            WHERE id = '$reservationId'
            "
        );

        // Add other data types that can be edited
        $allDataKeys = $typeTranslations + array('other' => 'komentar');

        $state = 0;
        foreach ($allDataKeys as $key => $value) {
            $returnKey = $key.'_returned';

            // New type of inventory is being rented (not only status change for existing entries)
            if (!empty($data[$key])) {
                // Server-side validation to prevent overwriting inventory rent 
                if ($this->isInventoryAvailable($data[$key], $key)) {
                    // Update reservations data
                    $wpdb->update(
                        $tableName,
                        array(
                            $key => $data[$key],
                        ),
                        array('id' => $reservationId)
                    );
                    // Update inventory status
                    $wpdb->update(
                        $tableName2,
                        array(
                            'state' => 1,
                            'issue_date' => date("Y-m-d H:i:s"),
                            'user_id' => $data['user_id'],
                        ),
                        array(
                            'id' => $data[$key],
                        )
                    );
                }
                $state = 1;
            }

            if (isset($data[$returnKey])) {
                // Update reservations data
                $wpdb->update(
                    $tableName,
                    array(
                        $returnKey => $data[$returnKey],
                    ),
                    array('id' => $reservationId)
                );
                // Update inventory status
                $wpdb->update(
                    $tableName2,
                    array(
                        'state' => $data[$returnKey],
                        'issue_date' => null,
                        'user_id' => null,
                    ),
                    array(
                        'id' => $originalReservation->$key,
                    )
                );
            }
        }
        $wpdb->update(
            $tableName,
            array(
                'state' => $state,
            ),
            array('id' => $reservationId)
        );
    }

    /**
     * Should be also called when "Save" button for reservations is used
     *
     * @return void
     */
    public function showReservations()
    {
        global $wpdb;
        $definitions                 = new Definitions();
        $context                     = Timber::get_context();
        $tableName                   = $wpdb->prefix."rkg_excursion_gear";
        $context['equipment']        = $definitions->defineEquipment();
        $context['typeTranslation']  = $this->translateTypes();
        $context['stateTranslation'] = $this->translateState();

        $where = "";
        if (isset($context['request']->get['type'])) {
            $where = "WHERE type = '".$context['request']->get['type']."'";
        }
        $context['reservations'] = $wpdb->get_results(
            "
            SELECT *
            FROM $tableName
            $where
            ORDER BY id desc
            "
        );

        foreach ($context['typeTranslation'] as $key => $value) {
            $context[$key.'s'] = $this->getAvailableInventory($key);
        }

        $context['masks']['items'] = $this->getAvailableInventory('mask');
        foreach ($context['reservations'] as $key => $value) {
            $context['reservations'][$key]->user =
                new Timber\User($context['reservations'][$key]->user_id);
            $context['reservations'][$key]->post =
                new Timber\Post($context['reservations'][$key]->post_id);
        }

        $listTable = new Reservations();
        $listTable->prepare_items();
        $context['table'] = $listTable;

        $templates = array( 'inventoryReservations.twig' );
        Timber::render($templates, $context);
    }

    public function editReservation()
    {
        if (!current_user_can('manage_equipment')) {
            status_header(401);
            wp_die();
        }

        global $wpdb;
        $tableName                   = $wpdb->prefix."rkg_excursion_gear";
        $context                     = Timber::get_context();
        $context['typeTranslation']  = $this->translateTypes();

        // Editing already made reservation by user
        $this->saveReservation(
            $context['request']->post['reservation'],
            $context['typeTranslation'],
            $context['request']->post
        );

        return json_encode("ok");
    }

    public function addCustomReservation() {
        if (!current_user_can('manage_equipment')) {
            status_header(401);
            wp_die();
        }

        global $wpdb;
        $context                     = Timber::get_context();
        $context['typeTranslation']  = $this->translateTypes();
        $tableName                   = $wpdb->prefix."rkg_excursion_gear";

        if (!empty($context['request']->post)) {
            // Creating new custom reservation by admin
            if (empty($context['request']->post['reservation'])) {
                $result = $wpdb->insert(
                    $tableName,
                    array(
                        'user_id'   => $context['request']->post['user_id'],
                    )
                );
                if (!$result) {
                    return false;
                }

                $this->saveReservation(
                    $wpdb->insert_id,
                    $context['typeTranslation'],
                    $context['request']->post
                );
            }
        }
    }

    
    // Used when adding new reservation from admin panel (not requested by user)
    public function showNewReservations()
    {
        global $wpdb;
        $definitions                 = new Definitions();
        $context                     = Timber::get_context();
        $context['equipment']        = $definitions->defineEquipment();
        $context['typeTranslation']  = $this->translateTypes();
        $context['stateTranslation'] = $this->translateState();

        foreach ($context['typeTranslation'] as $key => $value) {
            $context[$key.'s'] = $this->getAvailableInventory($key);
        }

        $context['masks']['items'] = $this->getAvailableInventory('mask');

        // Get members/users
        $context['userList'] = get_users(array(
            'role'    => 'member',
        ));
        // Expand list of users with course attendees (non-members)
        $courseAttendees = $wpdb->get_results("
            SELECT user_id from wp_rkg_course_signup
            LEFT JOIN wp_rkg_course_meta meta ON course_id = meta.id
            LEFT JOIN wp_posts posts ON posts.id = meta.id
            WHERE endtime >= ".date("'Y-m-d'")
            . ' AND post_title LIKE "%R1%"'
        );
        foreach ($courseAttendees as $attendee) {
           array_push($context['userList'], get_user_by("id", $attendee->user_id));
        }

        $templates = array( 'inventoryReservationsNew.twig' );
        Timber::render($templates, $context);
    }

    public function checkInventory()
    {
        $id        = $_POST['id'];
        $type      = $_POST['type'];
        

        $result = $this->isInventoryAvailable($id, $type);

        $json = "no";
        if ($result) {
            $json = "ok";
        }
        echo json_encode($json);
        wp_die();
    }

    private function isInventoryAvailable($id, $type) {
        if (in_array($type, array('lead', 'lead_belt', 'other', 'comment'))) {
            return true;
        }
        
        global $wpdb;
        $tableName                   = $wpdb->prefix."rkg_inventory";
        $result = $wpdb->get_row(
            "
            SELECT *
            FROM $tableName
            WHERE id = '$id' AND state = 0
            AND type = '$type'
            "
        );

        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * translateTypes
     *
     * @return array
     */
    private function translateTypes()
    {
        $types = array(
            "mask"      => 'Maska i disalica',
            "regulator" => 'Regulator',
            "suit"      => 'Odijelo',
            "boots"     => 'Čižmice',
            "gloves"    => 'Rukavice',
            "fins"      => 'Peraje',
            "bcd"       => 'KPL',
            "lead" => 'Pojas za olovo',
        );

        return $types;
    }


    /**
     * translateState
     *
     * @return array
     */
    private function translateState()
    {
        $types = array('Na stanju', 'Izdano', 'Neispravno', 'Izgubljeno', 'Otpisano');

        return $types;
    }

    /**
     * showInventoryEdit
     *
     * @return void
     */
    private function showInventoryEdit()
    {
        if (!current_user_can('manage_equipment')) {
            status_header(401);
            wp_die();
        }

        global $wpdb;
        $context                     = Timber::get_context();
        $tableName                   = $wpdb->prefix."rkg_inventory";
        $context['typeTranslation']  = $this->translateTypes();
        $context['stateTranslation'] = $this->translateState();


        $post = $context['request']->post;
        if (!empty($post)) {
            $wpdb->update(
                $tableName,
                array(
                    'type' => $post['type'],
                    'size' => $post['size'],
                    'state' => $post['status'],
                    'note' => $post['note'],
                ),
                array(
                    'id' => $post['id'],
                )
            );
        }

        $context['itemEdit']         = $wpdb->get_row(
            "SELECT * FROM "
            .$tableName
            ." WHERE id = '".$context['request']->get['edit']."'"
        );

        $tableName                   = $wpdb->prefix."rkg_excursion_gear";
        $users         = $wpdb->get_results(
            "SELECT user_id, updated FROM "
            .$tableName
            ." WHERE "
            .$context['itemEdit']->type.
            " = '".$context['request']->get['edit']."'"
            ." LIMIT 10"
        );

        foreach ($users as $k => $value) {
            $user = new Timber\User($value->user_id);
            $context['lenters'][] = ['display_name' => $user->display_name, 'date_updated' => $value->updated];
        }

        $templates = array('inventoryEdit.twig');
        Timber::render($templates, $context);
    }

    /**
     * showInventoryList
     *
     * @return void
     */
    private function showInventoryList()
    {
        if (!current_user_can('manage_equipment')) {
            status_header(401);
            wp_die();
        }

        global $wpdb;
        $context                     = Timber::get_context();
        $tableName                   = $wpdb->prefix."rkg_inventory";
        $context['typeTranslation']  = $this->translateTypes();
        $context['stateTranslation'] = $this->translateState();
        $context['inventoryCount']   = $wpdb->get_results(
            "SELECT COUNT(*) as num
            FROM $tableName"
        );
        $context['typeCount']        = $wpdb->get_results(
            "SELECT type, COUNT(*) as num
            FROM $tableName
            GROUP BY type"
        );

        if (isset($context['request']->get['action'])
            && !empty($context['request']->get['ids'])
            && $context['request']->get['action'] >= 0) {
            $ids = implode("', '", $context['request']->get['ids']);
            $query = "UPDATE $tableName
                SET state = {$context['request']->get['action']}
                WHERE id IN ('$ids')
                ";
            $wpdb->query($query);
        }

        $where = "";
        if (isset($context['request']->get['type'])) {
            $where = "WHERE type = '".$context['request']->get['type']."'";
        }
        $context['stateCount'] = $wpdb->get_results(
            "SELECT state, COUNT(*) as num
            FROM $tableName
            $where
            GROUP BY state"
        );

        $where     = "";
        $wherePart = array();
        if (isset($context['request']->get['type'])) {
            $wherePart[] = "type = '".$context['request']->get['type']."'";
        }
        if (isset($context['request']->get['state'])) {
            $wherePart[] = "state = '".$context['request']->get['state']."'";
        }
        if (isset($context['request']->get['id'])) {
            $wherePart[] = "id = '".$context['request']->get['id']."'";
        }
        if (!empty($wherePart)) {
            $where = "WHERE ".implode(" AND ", $wherePart);
        }

        $context['orderby'] = empty($context['request']->get['orderby'])
            ? 'id' : $context['request']->get['orderby'];
        $context['order']   = empty($context['request']->get['order'])
            ? 'asc' : $context['request']->get['order'];

        $context['inventoryItems'] = $wpdb->get_results(
            "
            SELECT *
            FROM $tableName
            $where
            ORDER BY {$context['orderby']} {$context['order']}
            "
        );


        foreach ($context['inventoryItems'] as $key => $value) {
            $context['inventoryItems'][$key]->user =
                new Timber\User($value->user_id);
        }

        $templates = array('inventory.twig');
        Timber::render($templates, $context);
    }

    /**
     * getAvailableInventory
     *
     * @param mixed $type
     *
     * @return array
     */
    private function getAvailableInventory($type)
    {
        global $wpdb;

        $definitions = new Definitions();
        $equipment   = $definitions->defineEquipment();
        $tableName   = $wpdb->prefix."rkg_inventory";

        if (isset($equipment[$type]) && !$equipment[$type]['size']) {
            return $wpdb->get_results(
                "
            SELECT *
            FROM $tableName
            WHERE type = '$type' AND STATE = 0
            ORDER BY id
            "
            );
        }

        $result = null;
        if (isset($equipment[$type]) && isset($equipment[$type]['size'])) {
            foreach ($equipment[$type]['size'] as $value) {
                $result[$value] = $wpdb->get_results(
                    "
                SELECT *
                FROM $tableName
                WHERE type = '$type' AND STATE = 0 and size = '$value'
                ORDER BY id
                "
                );
            }
        }

        return $result;
    }
}
