<?php
namespace RKGeronimo\Api;

use RKGeronimo\Helpers\Definitions;
use RKGeronimo\Interfaces\InitInterface;
use RKGeronimo\Inventory;
use WP_Error;
use WP_REST_Request;

/**
 * REST reservations for the Android app.
 *
 * @see InitInterface
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class EquipmentReservation implements InitInterface
{
    const FLOOR_CREATED = '2026-09-25 00:00:00';

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
        register_rest_route(
            'rkg/v1',
            '/reservations',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'listReservations'),
                'permission_callback' => array($this, 'permissions'),
            )
        );

        register_rest_route(
            'rkg/v1',
            '/reservations/(?P<id>\d+)',
            array(
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'issue'),
                    'permission_callback' => array($this, 'permissions'),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array($this, 'delete'),
                    'permission_callback' => array($this, 'permissions'),
                ),
            )
        );
    }

    /**
     * Staff only, same capability as the admin screen.
     * Application Passwords authenticate the REST user.
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
     * Valjane rezervacije: created on or after the floor date.
     *
     * @return \WP_REST_Response|WP_Error
     */
    public function listReservations()
    {
        global $wpdb;
        $tableName = $wpdb->prefix.'rkg_excursion_gear';
        $deleted = Definitions::RESERVATION_STATUS_DELETED;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tableName
            WHERE created >= %s AND state != %d
            ORDER BY id DESC",
            self::FLOOR_CREATED,
            $deleted
        ));

        $items = array();
        foreach ($rows as $row) {
            $items[] = $this->present($row);
        }

        return rest_ensure_response($items);
    }

    /**
     * Spremi. Same body as GET. Only equipment.*.returned changes.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response|WP_Error
     */
    public function issue(WP_REST_Request $request)
    {
        $reservationId = intval($request['id']);
        $guard = $this->guard($reservationId);
        if (is_wp_error($guard)) {
            return $guard;
        }

        $data = $this->issueData($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $inventory = new Inventory();
        $result = $inventory->issueReservation($reservationId, $data);
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($this->load($reservationId));
    }

    /**
     * Obriši. Does not pull reservations created before the floor.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response|WP_Error
     */
    public function delete(WP_REST_Request $request)
    {
        $reservationId = intval($request['id']);
        $guard = $this->guard($reservationId);
        if (is_wp_error($guard)) {
            return $guard;
        }

        $inventory = new Inventory();
        $result = $inventory->softDeleteReservation($reservationId);
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(array(
            'deleted' => true,
            'id'      => $reservationId,
        ));
    }

    /**
     * 404 when the row is missing or created before the floor date.
     *
     * @param int $reservationId Reservation id.
     *
     * @return true|WP_Error
     */
    private function guard($reservationId)
    {
        global $wpdb;
        $tableName = $wpdb->prefix.'rkg_excursion_gear';
        $created = $wpdb->get_var($wpdb->prepare(
            "SELECT created FROM $tableName WHERE id = %d",
            $reservationId
        ));

        if (!$created || $created < self::FLOOR_CREATED) {
            return new WP_Error(
                'rkg_not_found',
                'Reservation not found',
                array('status' => 404)
            );
        }

        return true;
    }

    /**
     * @param WP_REST_Request $request Request.
     *
     * @return array|WP_Error
     */
    private function issueData(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        if (!is_array($params) || count($params) === 0) {
            $params = $request->get_body_params();
        }

        $definitions = new Definitions();
        $data = array();
        $allowed = array(0, 1, 3);

        foreach (array_keys($definitions->defineEquipment()) as $type) {
            if (isset($params[$type]) && $params[$type] !== '') {
                $data[$type] = sanitize_text_field($params[$type]);
            }

            $returnKey = $type.'_returned';
            $returned = null;
            if (isset($params[$returnKey]) && $params[$returnKey] !== '') {
                $returned = $params[$returnKey];
            }

            $piece = null;
            if (isset($params['equipment'][$type])
                && is_array($params['equipment'][$type])
            ) {
                $piece = $params['equipment'][$type];
            }
            if (is_array($piece)
                && array_key_exists('returned', $piece)
                && $piece['returned'] !== null
                && $piece['returned'] !== ''
            ) {
                $returned = $piece['returned'];
            }

            if ($returned === null) {
                continue;
            }

            if (is_string($returned) && is_numeric($returned)) {
                $returned = intval($returned);
            }
            if (!in_array($returned, $allowed, true)) {
                return new WP_Error(
                    'rkg_invalid_return',
                    'Invalid return status',
                    array('status' => 400)
                );
            }
            $data[$returnKey] = $returned;
        }

        if (isset($params['other'])) {
            $data['other'] = sanitize_textarea_field($params['other']);
        }

        return $data;
    }

    /**
     * @param int $reservationId Reservation id.
     *
     * @return array|WP_Error
     */
    private function load($reservationId)
    {
        global $wpdb;
        $tableName = $wpdb->prefix.'rkg_excursion_gear';
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tableName WHERE id = %d",
            intval($reservationId)
        ));

        if (!$row) {
            return new WP_Error(
                'rkg_not_found',
                'Reservation not found',
                array('status' => 404)
            );
        }

        return $this->present($row);
    }

    /**
     * @param object $row Gear row.
     *
     * @return array
     */
    private function present($row)
    {
        $definitions = new Definitions();
        $labels = $definitions->getReservationStatusLabels();
        $state = (int) $row->state;
        $user = get_userdata($row->user_id);
        $excursion = $row->post_id ? get_the_title($row->post_id) : null;
        $equipment = array();

        foreach ($definitions->defineEquipment() as $type => $meta) {
            $returnedKey = $type.'_returned';
            $returned = $row->$returnedKey;
            $size = get_user_meta($row->user_id, $type.'_size', true);
            if ($type === 'lead'
                && $row->lead_size !== null
                && $row->lead_size !== ''
            ) {
                $size = $row->lead_size;
            }
            $equipment[$type] = array(
                'label'    => $meta['name'],
                'size'     => ($size === '' || $size === false) ? null : $size,
                'code'     => ($row->$type === null || $row->$type === '')
                    ? null
                    : (string) $row->$type,
                'returned' => ($returned === null || $returned === '')
                    ? null
                    : (int) $returned,
            );
        }

        return array(
            'id'            => (int) $row->id,
            'user_id'       => (int) $row->user_id,
            'user_name'     => $user ? $user->display_name : null,
            'excursion_id'  => $row->post_id ? (int) $row->post_id : null,
            'excursion'     => $excursion ? $excursion : null,
            'state'         => $state,
            'status'        => isset($labels[$state]) ? $labels[$state] : null,
            'comment'       => $row->other,
            'equipment'     => $equipment,
        );
    }
}
