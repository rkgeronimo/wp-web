<?php
namespace RKGeronimo\Api;

use RKGeronimo\Helpers\Definitions;
use RKGeronimo\Interfaces\InitInterface;
use WP_Error;
use WP_REST_Request;

/**
 * REST inventory list for the Android app.
 *
 * @author Josip Razov <joso.razov@gmail.com>
 * @see InitInterface
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class Inventory extends ApiEndpoint implements InitInterface
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
        $states      = $definitions->getEquipmentStatusValues();

        register_rest_route(
            'rkg/v1',
            '/inventory',
            array(
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
                        'per_page' => array(
                            'description' => 'Maximum number of items.',
                            'type'        => 'integer',
                            'default'     => 500,
                            'minimum'     => 1,
                            'maximum'     => 500,
                        ),
                        'page' => array(
                            'description' => 'One-based page of items.',
                            'type'        => 'integer',
                            'default'     => 1,
                            'minimum'     => 1,
                        ),
                    ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'createItem'),
                    'permission_callback' => array($this, 'permissions'),
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
     * See ApiEndpoint::permissions().
     */

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
        $tableName = $this->inventoryTable();
        $where     = array();
        $args      = array();

        $type = $request->get_param('type');
        if ($type) {
            $where[] = 'type = %s';
            $args[]  = $type;
        }

        $state = $request->get_param('state');
        if ($state === null || $state === '') {
            $where[] = 'state != %d';
            $args[]  = Definitions::EQUIPMENT_STATUS_DELETED;
        } else {
            $where[] = 'state = %d';
            $args[]  = intval($state);
        }

        $filters = implode(' AND ', $where);
        $total   = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tableName WHERE $filters",
            $args
        )));

        $perPage = max(1, min(500, intval($request->get_param('per_page'))));
        $page    = max(1, intval($request->get_param('page')));
        $args[]  = $perPage;
        $args[]  = ($page - 1) * $perPage;
        $rows    = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tableName WHERE $filters
            ORDER BY id ASC
            LIMIT %d OFFSET %d",
            $args
        ));

        $items = array();
        foreach ($rows as $row) {
            $items[] = $this->present($row);
        }

        return $this->paginatedResponse($items, $total, $perPage);
    }

    /**
     * Add one item. Same fields as the inventory add form:
     * id, type, size, plus an optional note. New items start available.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response|WP_Error
     */
    public function createItem(WP_REST_Request $request)
    {
        global $wpdb;
        $params = $this->jsonParams($request);
        $id     = isset($params['id']) && is_scalar($params['id'])
            ? trim((string) $params['id'])
            : '';
        if (!preg_match('/^[A-Za-z0-9]{1,60}$/', $id)) {
            return new WP_Error(
                'rkg_invalid',
                'Invalid inventory id',
                array('status' => 400)
            );
        }
        if (!array_key_exists('type', $params)) {
            return new WP_Error(
                'rkg_invalid',
                'Invalid equipment type',
                array('status' => 400)
            );
        }

        $fields = $this->updateFields($request);
        if (is_wp_error($fields)) {
            return $fields;
        }

        if ($this->find($id)) {
            return new WP_Error(
                'rkg_exists',
                'Inventory item already exists',
                array('status' => 409)
            );
        }

        $fields['state'] = Definitions::EQUIPMENT_STATUS_AVAILABLE;
        $result          = $wpdb->insert(
            $this->inventoryTable(),
            array_merge(
                array(
                    'id'        => $id,
                    'size'      => '',
                    'thickness' => '',
                    'note'      => '',
                ),
                $fields
            )
        );
        if (!$result) {
            return new WP_Error(
                'rkg_create_failed',
                'Failed to create inventory item',
                array('status' => 500)
            );
        }

        $response = rest_ensure_response($this->present($this->find($id)));
        $response->set_status(201);

        return $response;
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
        $id  = sanitize_text_field($request['id']);
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

        $tableName = $this->inventoryTable();
        $result    = $wpdb->update(
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
        $tableName = $this->inventoryTable();

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
        $params = $this->jsonParams($request);

        $definitions = new Definitions();
        $types       = array_keys($definitions->defineEquipment());
        $states      = $definitions->getEquipmentStatusValues();
        $update      = array();

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
            $note           = $params['note'];
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
        $types       = $definitions->defineEquipment();
        $labels      = $definitions->getEquipmentStatusLabels();
        $state       = (int) $row->state;
        $user        = $row->user_id ? get_userdata($row->user_id) : null;
        $type        = $row->type;

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
