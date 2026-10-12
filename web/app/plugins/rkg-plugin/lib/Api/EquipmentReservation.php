<?php
namespace RKGeronimo\Api;

use RKGeronimo\Helpers\Definitions;
use RKGeronimo\Helpers\Donation;
use RKGeronimo\Helpers\EquipmentSize;
use RKGeronimo\Interfaces\InitInterface;
use RKGeronimo\Inventory;
use WP_Error;
use WP_REST_Request;

/**
 * REST reservations for the Android app.
 *
 * @author Josip Razov <joso.razov@gmail.com>
 * @see InitInterface
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class EquipmentReservation extends ApiEndpoint implements InitInterface
{
    /**
     * Equipment definitions, built once per request for present().
     *
     * @var array|null
     */
    private $equipmentMeta;

    /**
     * Reservation status labels, built once per request for present().
     *
     * @var array|null
     */
    private $reservationLabels;

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
            '/users',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'listUsers'),
                'permission_callback' => array($this, 'permissions'),
            )
        );

        register_rest_route(
            'rkg/v1',
            '/reservations',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array($this, 'listReservations'),
                    'permission_callback' => array($this, 'permissions'),
                    'args'                => array(
                        'state' => array(
                            'description' => 'Reservation status id. Omit for all statuses.',
                            'type'        => 'integer',
                            'enum'        => array(
                                Definitions::RESERVATION_STATUS_PENDING,
                                Definitions::RESERVATION_STATUS_ACTIVE,
                                Definitions::RESERVATION_STATUS_COMPLETED,
                            ),
                        ),
                        'per_page' => array(
                            'description' => 'Maximum number of items.',
                            'type'        => 'integer',
                            'default'     => 100,
                            'minimum'     => 1,
                            'maximum'     => 200,
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
                    'callback'            => array($this, 'create'),
                    'permission_callback' => array($this, 'permissions'),
                ),
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

        register_rest_route(
            'rkg/v1',
            '/reservations/(?P<id>\d+)/archive',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'archive'),
                'permission_callback' => array($this, 'permissions'),
                'args'                => array(
                    'archived' => array(
                        'description' => 'true archives, false restores.',
                        'type'        => 'boolean',
                        'required'    => true,
                    ),
                ),
            )
        );
    }

    /**
     * Members, plus attendees of a current R1 course.
     * Same picker as Izdavanje bez rezervacije.
     *
     * @return \WP_REST_Response
     */
    public function listUsers()
    {
        $users = get_users(array(
            'role'    => 'member',
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ));
        $byId  = array();
        foreach ($users as $user) {
            $byId[(int) $user->ID] = $user;
        }
        foreach ($this->courseAttendeeIds() as $id) {
            if (isset($byId[$id])) {
                continue;
            }
            $user = get_user_by('id', $id);
            if ($user) {
                $byId[(int) $user->ID] = $user;
            }
        }

        $items = array();
        foreach ($byId as $user) {
            $items[] = array(
                'id'        => (int) $user->ID,
                'user_name' => $user->display_name,
            );
        }
        usort(
            $items,
            function ($left, $right) {
                return strcasecmp($left['user_name'], $right['user_name']);
            }
        );

        return rest_ensure_response($items);
    }

    /**
     * New reservation without an excursion. Same as the admin form.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response|WP_Error
     */
    public function create(WP_REST_Request $request)
    {
        global $wpdb;
        $params = $this->jsonParams($request);

        $userId = isset($params['user_id']) ? intval($params['user_id']) : 0;
        if (!$userId || !get_userdata($userId)) {
            return new WP_Error(
                'rkg_invalid',
                'Invalid user',
                array('status' => 400)
            );
        }

        $data = $this->issueData($request);
        if (is_wp_error($data)) {
            return $data;
        }

        // Single source of truth for the comment. `comment` is the app's
        // field name and wins over the `other` alias issueData() may have
        // read; both the reservation row and $data use this one value.
        $comment = '';
        if (isset($params['comment'])) {
            $comment = sanitize_textarea_field($params['comment']);
        } elseif (isset($params['other'])) {
            $comment = sanitize_textarea_field($params['other']);
        }
        $data['other'] = $comment;

        // The reservation row and the issued inventory rows commit or roll
        // back together, so a failure cannot leave an orphan reservation
        // or a phantom equipment claim.
        $wpdb->query('START TRANSACTION');
        $tableName = $this->gearTable();
        $inserted  = $wpdb->insert(
            $tableName,
            array(
                'user_id' => $userId,
                'other'   => $comment,
                'state'   => Definitions::RESERVATION_STATUS_PENDING,
            )
        );
        if (!$inserted) {
            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'rkg_create_failed',
                'Failed to create reservation',
                array('status' => 500)
            );
        }

        $reservationId = (int) $wpdb->insert_id;
        $inventory = new Inventory();
        $result    = $inventory->issueReservation($reservationId, $data);
        if (is_wp_error($result)) {
            $wpdb->query('ROLLBACK');

            return $result;
        }

        $wpdb->query('COMMIT');

        return rest_ensure_response($this->load($reservationId));
    }

    /**
     * User ids signed up to an R1 course that has not ended.
     *
     * @return int[]
     */
    private function courseAttendeeIds()
    {
        global $wpdb;
        $signup = $wpdb->prefix.'rkg_course_signup';
        $meta   = $wpdb->prefix.'rkg_course_meta';
        $posts  = $wpdb->posts;
        $ids    = $wpdb->get_col($wpdb->prepare(
            "SELECT signup.user_id
            FROM $signup AS signup
            LEFT JOIN $meta AS meta ON signup.course_id = meta.id
            LEFT JOIN $posts AS posts ON posts.ID = meta.id
            WHERE meta.endtime >= %s
            AND posts.post_title LIKE %s",
            current_time('Y-m-d'),
            '%R1%'
        ));
        if (!is_array($ids)) {
            return array();
        }

        return array_map('intval', $ids);
    }

    /**
     * Reservations that are not archived and not deleted. With `state`
     * only that status is returned: 0 Na čekanju, 1 Aktivno, 2 Vraćeno.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response
     */
    public function listReservations(WP_REST_Request $request)
    {
        $state = $request->get_param('state');

        return $this->listByState(
            $state === null ? null : intval($state),
            intval($request->get_param('per_page')),
            intval($request->get_param('page'))
        );
    }

    /**
     * Valjane rezervacije: not archived, not deleted, optionally one status.
     *
     * @param int|null $state   Reservation state, null for all statuses.
     * @param int      $perPage Items per page.
     * @param int      $page    One-based page.
     *
     * @return \WP_REST_Response
     */
    private function listByState($state = null, $perPage = 100, $page = 1)
    {
        global $wpdb;
        $tableName = $this->gearTable();
        $perPage   = max(1, min(200, $perPage));
        $page      = max(1, $page);
        $offset    = ($page - 1) * $perPage;

        $where = "archived = 0 AND state != ".Definitions::RESERVATION_STATUS_DELETED;
        if ($state !== null) {
            $where .= $wpdb->prepare(' AND state = %d', $state);
        }

        $total = intval($wpdb->get_var("SELECT COUNT(*) FROM $tableName WHERE $where"));

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tableName
            WHERE $where
            ORDER BY id DESC
            LIMIT %d OFFSET %d",
            $perPage,
            $offset
        ));

        $items = array();
        foreach ($rows as $row) {
            $items[] = $this->present($row);
        }

        return $this->paginatedResponse($items, $total, $perPage);
    }

    /**
     * Spremi. Full reservation writes ciphers. Returned-only body
     * updates return status.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response|WP_Error
     */
    public function issue(WP_REST_Request $request)
    {
        global $wpdb;
        $reservationId = intval($request['id']);
        $guard         = $this->guard($reservationId);
        if (is_wp_error($guard)) {
            return $guard;
        }

        $data = $this->issueData($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $donation = $this->donationData($this->jsonParams($request));
        if (is_wp_error($donation)) {
            return $donation;
        }

        // The availability check locks inventory rows with FOR UPDATE, which
        // only holds until commit inside a transaction. Without it two
        // requests could claim the same piece.
        $wpdb->query('START TRANSACTION');
        $inventory = new Inventory();
        $result    = $inventory->issueReservation($reservationId, $data);
        if (is_wp_error($result)) {
            $wpdb->query('ROLLBACK');

            return $result;
        }

        if (!$this->saveDonation($reservationId, $donation)) {
            $wpdb->query('ROLLBACK');

            return new WP_Error(
                'rkg_update_failed',
                'Failed to save donation',
                array('status' => 500)
            );
        }
        $wpdb->query('COMMIT');

        return rest_ensure_response($this->load($reservationId));
    }

    /**
     * Obriši. Archived reservations are not found.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response|WP_Error
     */
    public function delete(WP_REST_Request $request)
    {
        global $wpdb;
        $reservationId = intval($request['id']);
        $guard         = $this->guard($reservationId);
        if (is_wp_error($guard)) {
            return $guard;
        }

        // Releasing the equipment and marking the reservation deleted
        // commit or roll back together, so a failed delete cannot leave
        // an active reservation whose pieces are back in stock.
        $wpdb->query('START TRANSACTION');
        $inventory = new Inventory();
        $result    = $inventory->softDeleteReservation($reservationId);
        if (is_wp_error($result)) {
            $wpdb->query('ROLLBACK');

            return $result;
        }
        $wpdb->query('COMMIT');

        return rest_ensure_response(array(
            'deleted' => true,
            'id'      => $reservationId,
        ));
    }

    /**
     * Archive or restore. Works in any status.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response|WP_Error
     */
    public function archive(WP_REST_Request $request)
    {
        global $wpdb;
        $reservationId = intval($request['id']);
        $archived      = rest_sanitize_boolean($request->get_param('archived'));
        $tableName     = $this->gearTable();
        $state         = $wpdb->get_var($wpdb->prepare(
            "SELECT state FROM $tableName WHERE id = %d",
            $reservationId
        ));

        if ($state === null || (int) $state === Definitions::RESERVATION_STATUS_DELETED) {
            return new WP_Error(
                'rkg_not_found',
                'Reservation not found',
                array('status' => 404)
            );
        }

        $result = $wpdb->update(
            $tableName,
            array('archived' => $archived ? 1 : 0),
            array('id' => $reservationId)
        );
        if ($result === false) {
            return new WP_Error(
                'rkg_update_failed',
                'Failed to update reservation',
                array('status' => 500)
            );
        }

        return rest_ensure_response($this->load($reservationId));
    }

    /**
     * Donation from the request body. Empty array when the key is absent,
     * so the stored value stays as it is.
     *
     * @param array $params Request body.
     *
     * @return array|WP_Error
     */
    private function donationData($params)
    {
        if (!array_key_exists('donation_amount', $params)) {
            return array();
        }

        $amount = Donation::parse($params['donation_amount']);
        if ($amount === false) {
            return new WP_Error(
                'rkg_invalid_donation',
                'Invalid donation amount',
                array('status' => 400)
            );
        }

        return array('donation_amount' => $amount);
    }

    /**
     * @param int   $reservationId Reservation id.
     * @param array $donation      Result of donationData().
     *
     * @return bool False when the write failed.
     */
    private function saveDonation($reservationId, $donation)
    {
        global $wpdb;
        if (empty($donation)) {
            return true;
        }

        return $wpdb->update(
            $this->gearTable(),
            $donation,
            array('id' => $reservationId)
        ) !== false;
    }

    /**
     * 404 when the row is missing or archived.
     *
     * @param int $reservationId Reservation id.
     *
     * @return true|WP_Error
     */
    private function guard($reservationId)
    {
        global $wpdb;
        $tableName = $this->gearTable();
        $found     = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tableName WHERE id = %d AND archived = 0",
            $reservationId
        ));

        if (!$found) {
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
        $params = $this->jsonParams($request);

        $definitions = new Definitions();
        $data        = array();
        $allowed     = array(
            Definitions::EQUIPMENT_STATUS_AVAILABLE,
            Definitions::EQUIPMENT_STATUS_ISSUED,
            Definitions::EQUIPMENT_STATUS_LOST,
        );

        foreach (array_keys($definitions->defineEquipment()) as $type) {
            if (isset($params[$type]) && $params[$type] !== '') {
                $data[$type] = sanitize_text_field($params[$type]);
            }

            $returnKey = $type.'_returned';
            $returned  = null;
            if (isset($params[$returnKey]) && $params[$returnKey] !== '') {
                $returned = $params[$returnKey];
            }

            $piece = null;
            if (isset($params['equipment'][$type])
                && is_array($params['equipment'][$type])
            ) {
                $piece = $params['equipment'][$type];
            }
            if (is_array($piece)) {
                if (isset($piece['code'])
                    && $piece['code'] !== null
                    && $piece['code'] !== ''
                ) {
                    $data[$type] = sanitize_text_field($piece['code']);
                }
                if (array_key_exists('returned', $piece)
                    && $piece['returned'] !== null
                    && $piece['returned'] !== ''
                ) {
                    $returned = $piece['returned'];
                }
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
        $tableName = $this->gearTable();
        $row       = $wpdb->get_row($wpdb->prepare(
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
        if ($this->equipmentMeta === null) {
            $definitions             = new Definitions();
            $this->equipmentMeta     = $definitions->defineEquipment();
            $this->reservationLabels = $definitions->getReservationStatusLabels();
        }
        $labels      = $this->reservationLabels;
        $state       = (int) $row->state;
        $user        = get_userdata($row->user_id);
        $excursion   = $row->post_id ? get_the_title($row->post_id) : null;
        $equipment   = array();

        foreach ($this->equipmentMeta as $type => $meta) {
            $returnedKey      = $type.'_returned';
            $returned         = $row->$returnedKey;
            $size             = EquipmentSize::resolve($row, $row->user_id, $type);
            $declined         = get_user_meta($row->user_id, $type, true);
            $equipment[$type] = array(
                'label'    => $meta['name'],
                'size'     => $size,
                'code'     => ($row->$type === null || $row->$type === '')
                    ? null
                    : (string) $row->$type,
                'needed'   => ($declined === '' || $declined === false),
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
            'archived'      => (bool) $row->archived,
            'donation_amount' => $row->donation_amount === null
                ? null
                : (float) $row->donation_amount,
            'comment'       => $row->other,
            'equipment'     => $equipment,
        );
    }
}
