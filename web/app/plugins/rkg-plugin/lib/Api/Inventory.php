<?php
namespace RKGeronimo\Api;

use RKGeronimo\Helpers\Definitions;
use RKGeronimo\Interfaces\InitInterface;
use WP_Error;
use WP_REST_Request;

/**
 * REST inventory list for the Android app.
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
        add_action('rest_api_init', array($this, 'registerRoutes'));
    }

    /**
     * registerRoutes
     *
     * @return void
     */
    public function registerRoutes()
    {
        $definitions = new Definitions();
        $states = array(
            Definitions::EQUIPMENT_STATUS_AVAILABLE,
            Definitions::EQUIPMENT_STATUS_ISSUED,
            Definitions::EQUIPMENT_STATUS_DAMAGED,
            Definitions::EQUIPMENT_STATUS_LOST,
            Definitions::EQUIPMENT_STATUS_WRITTEN_OFF,
            Definitions::EQUIPMENT_STATUS_DELETED,
        );

        register_rest_route(
            'rkg/v1',
            '/inventory',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'listItems'),
                'permission_callback' => array($this, 'permissions'),
                'args'                => array(
                    'type'  => array(
                        'description' => 'Equipment type key.',
                        'type'        => 'string',
                        'required'    => false,
                        'enum'        => array_keys(
                            $definitions->defineEquipment()
                        ),
                    ),
                    'state' => array(
                        'description' => 'Inventory status id.',
                        'type'        => 'integer',
                        'required'    => false,
                        'enum'        => $states,
                    ),
                ),
            )
        );

        register_rest_route(
            'rkg/v1',
            '/inventory/(?P<id>[A-Za-z0-9]+)',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'updateItem'),
                'permission_callback' => array($this, 'permissions'),
            )
        );
    }

    /**
     * Staff only, same capability as the inventory screen.
     *
     * @return true|WP_Error
     */
    public function permissions()
    {
        if (!current_user_can('manage_equipment')) {
            return new WP_Error(
                'rkg_forbidden',
                'Unauthorized',
                array('status' => 401)
            );
        }

        return true;
    }

    /**
     * Inventory rows. Deleted (state 5) are omitted unless state=5.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response
     */
    public function listItems(WP_REST_Request $request)
    {
        global $wpdb;
        $tableName = $wpdb->prefix.'rkg_inventory';
        $where = array();
        $args = array();

        $type = $request->get_param('type');
        if ($type) {
            $where[] = 'type = %s';
            $args[] = $type;
        }

        $state = $request->get_param('state');
        if ($state === null || $state === '') {
            $where[] = 'state != %d';
            $args[] = Definitions::EQUIPMENT_STATUS_DELETED;
        } else {
            $where[] = 'state = %d';
            $args[] = intval($state);
        }

        $sql = "SELECT * FROM $tableName WHERE "
            .implode(' AND ', $where)
            .' ORDER BY id ASC';
        $rows = $wpdb->get_results($wpdb->prepare($sql, $args));

        $items = array();
        foreach ($rows as $row) {
            $items[] = $this->present($row);
        }

        return rest_ensure_response($items);
    }

    /**
     * Edit one item. Same fields as the inventory edit form:
     * type, size, state, note. Id stays in the URL.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response|WP_Error
     */
    public function updateItem(WP_REST_Request $request)
    {
        global $wpdb;
        $id = sanitize_text_field($request['id']);
        $row = $this->find($id);
        if (!$row) {
            return new WP_Error(
                'rkg_not_found',
                'Inventory item not found',
                array('status' => 404)
            );
        }

        $update = $this->updateFields($request);
        if (is_wp_error($update)) {
            return $update;
        }

        $tableName = $wpdb->prefix.'rkg_inventory';
        $result = $wpdb->update(
            $tableName,
            $update,
            array('id' => $id)
        );
        if ($result === false) {
            return new WP_Error(
                'rkg_update_failed',
                'Failed to update inventory item',
                array('status' => 500)
            );
        }

        return rest_ensure_response($this->present($this->find($id)));
    }

    /**
     * @param string $id Inventory id.
     *
     * @return object|null
     */
    private function find($id)
    {
        global $wpdb;
        $tableName = $wpdb->prefix.'rkg_inventory';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tableName WHERE id = %s",
            $id
        ));
    }

    /**
     * Fields the edit form saves. Omitted keys stay unchanged.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return array|WP_Error
     */
    private function updateFields(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            $params = $request->get_body_params();
        }
        if (!is_array($params)) {
            $params = array();
        }

        $definitions = new Definitions();
        $types = array_keys($definitions->defineEquipment());
        $states = array(
            Definitions::EQUIPMENT_STATUS_AVAILABLE,
            Definitions::EQUIPMENT_STATUS_ISSUED,
            Definitions::EQUIPMENT_STATUS_DAMAGED,
            Definitions::EQUIPMENT_STATUS_LOST,
            Definitions::EQUIPMENT_STATUS_WRITTEN_OFF,
            Definitions::EQUIPMENT_STATUS_DELETED,
        );
        $update = array();

        if (array_key_exists('type', $params)) {
            $type = sanitize_text_field($params['type']);
            if (!in_array($type, $types, true)) {
                return new WP_Error(
                    'rkg_invalid',
                    'Invalid equipment type',
                    array('status' => 400)
                );
            }
            $update['type'] = $type;
        }

        if (array_key_exists('size', $params)) {
            $size = sanitize_text_field((string) $params['size']);
            if (strlen($size) > 60) {
                return new WP_Error(
                    'rkg_invalid',
                    'Invalid size',
                    array('status' => 400)
                );
            }
            $update['size'] = $size;
        }

        if (array_key_exists('state', $params)) {
            $state = $params['state'];
            if (is_string($state) && is_numeric($state)) {
                $state = intval($state);
            }
            if (!in_array($state, $states, true)) {
                return new WP_Error(
                    'rkg_invalid',
                    'Invalid inventory status',
                    array('status' => 400)
                );
            }
            $update['state'] = $state;
        }

        if (array_key_exists('note', $params)) {
            $note = $params['note'];
            $update['note'] = ($note === null)
                ? ''
                : sanitize_textarea_field($note);
        }

        if (count($update) === 0) {
            return new WP_Error(
                'rkg_invalid',
                'Nothing to update',
                array('status' => 400)
            );
        }

        return $update;
    }

    /**
     * @param object $row Inventory row.
     *
     * @return array
     */
    private function present($row)
    {
        $definitions = new Definitions();
        $types = $definitions->defineEquipment();
        $labels = $definitions->getEquipmentStatusLabels();
        $state = (int) $row->state;
        $user = $row->user_id ? get_userdata($row->user_id) : null;
        $type = $row->type;

        return array(
            'id'         => (string) $row->id,
            'type'       => $type,
            'type_label' => isset($types[$type]) ? $types[$type]['name'] : $type,
            'size'       => ($row->size === '' || $row->size === null)
                ? null
                : (string) $row->size,
            'state'      => $state,
            'status'     => isset($labels[$state]) ? $labels[$state] : null,
            'user_id'    => $row->user_id ? (int) $row->user_id : null,
            'user_name'  => $user ? $user->display_name : null,
            'issue_date' => $row->issue_date ? $row->issue_date : null,
            'note'       => ($row->note === null || $row->note === '')
                ? null
                : $row->note,
        );
    }
}
