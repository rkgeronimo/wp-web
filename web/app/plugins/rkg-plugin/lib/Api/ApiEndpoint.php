<?php
namespace RKGeronimo\Api;

use WP_Error;
use WP_REST_Request;

/**
 * Shared REST plumbing for the app-facing endpoints.
 *
 * Endpoints are staff only, the REST user authenticates with
 * Application Passwords, and access requires the same
 * manage_equipment capability as the admin screens.
 *
 * @author Josip Razov <joso.razov@gmail.com>
 */
abstract class ApiEndpoint
{
    /**
     * Staff only, same capability as the admin screens.
     *
     * @return true|WP_Error
     */
    public function permissions()
    {
        if (!current_user_can('manage_equipment')) {
            return new WP_Error(
                'rest_forbidden',
                'Forbidden',
                array('status' => 403)
            );
        }

        return true;
    }

    /**
     * JSON body, falling back to form data and then to an empty array.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return array
     */
    protected function jsonParams(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        if (!is_array($params) || count($params) === 0) {
            $params = $request->get_body_params();
        }
        if (!is_array($params)) {
            $params = array();
        }

        return $params;
    }

    /**
     * Reservations table name with the site prefix.
     *
     * @return string
     */
    protected function gearTable()
    {
        global $wpdb;

        return $wpdb->prefix.'rkg_excursion_gear';
    }

    /**
     * Inventory table name with the site prefix.
     *
     * @return string
     */
    protected function inventoryTable()
    {
        global $wpdb;

        return $wpdb->prefix.'rkg_inventory';
    }
}
