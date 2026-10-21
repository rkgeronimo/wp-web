<?php
namespace RKGeronimo\Tables;

use WP_List_Table;

/**
 * List table of members for the "Ronilački profili" admin page. Mirrors the
 * native WordPress users list (searchable, paginated) and shows membership
 * info alongside the diving category.
 *
 * @author Josip Razov <joso.razov@gmail.com>
 *
 * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 * @SuppressWarnings("CamelCase")
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class DiverProfiles extends WP_List_Table
{
    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'diver_profile',
            'plural'   => 'diver_profiles',
            'ajax'     => false,
        ));
    }

    /**
     * Columns, in display order. The first column is the primary one.
     *
     * @return array
     */
    public function get_columns()
    {
        return array(
            'name'         => 'Ime i prezime',
            'rc'           => 'Ronilačka kategorija',
            'member_since' => 'Član od',
            'last_year'    => 'Zadnja aktivna godina',
            'fee_current'  => 'Članarina za '.date('Y'),
        );
    }

    /**
     * @return array
     */
    public function get_sortable_columns()
    {
        return array();
    }

    /**
     * Prepare rows, with pagination.
     *
     * @return void
     */
    public function prepare_items()
    {
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );

        $data        = $this->table_data();
        $perPage     = 50;
        $currentPage = $this->get_pagenum();

        $this->set_pagination_args(array(
            'total_items' => count($data),
            'per_page'    => $perPage,
        ));

        $this->items = array_slice($data, (($currentPage - 1) * $perPage), $perPage);
    }

    /**
     * Fallback renderer: every value here is plain text, so escape it.
     *
     * @param array  $item        Row.
     * @param string $column_name Column key.
     *
     * @return string
     */
    public function column_default($item, $column_name)
    {
        return esc_html(isset($item[$column_name]) ? $item[$column_name] : '');
    }

    /**
     * Primary column: name links to the edit view, with an "Uredi" row action.
     *
     * @param array $item Row.
     *
     * @return string
     */
    public function column_name($item)
    {
        $url  = admin_url('admin.php?page=rkg_diver_profiles&user_id='.$item['ID']);
        $link = '<a href="'.esc_url($url).'">'.esc_html($item['name']).'</a>';

        return '<strong>'.$link.'</strong>'.$this->row_actions(array(
            'edit' => '<a href="'.esc_url($url).'">Uredi</a>',
        ));
    }

    /**
     * Shown when there are no members.
     *
     * @return void
     */
    public function no_items()
    {
        echo 'Nema članova.';
    }

    /**
     * Members plus their category and membership summary. Search matches the
     * name, login or email, like the native users list.
     *
     * @return array
     */
    private function table_data()
    {
        global $wpdb;

        $search = isset($_REQUEST['s'])
            ? sanitize_text_field(wp_unslash($_REQUEST['s']))
            : '';

        $args = array('orderby' => 'display_name', 'order' => 'ASC');
        if ($search !== '') {
            $args['search']         = '*'.$search.'*';
            $args['search_columns'] = array('display_name', 'user_login', 'user_email');
        }
        $users = get_users($args);
        if (empty($users)) {
            return array();
        }

        $ids = array();
        foreach ($users as $user) {
            $ids[] = (int) $user->ID;
        }
        $idList = implode(',', $ids);

        // Diving category for every listed user in one query.
        $rcMap = array();
        $rcRows = $wpdb->get_results(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta}
            WHERE meta_key = 'rc' AND user_id IN ($idList)"
        );
        foreach ($rcRows as $row) {
            $rcMap[(int) $row->user_id] = $row->meta_value;
        }

        // Membership summary for every listed user in one query.
        $subTable = $wpdb->prefix.'rkg_member_subscription';
        $year     = (int) date('Y');
        $subMap   = array();
        $subRows  = $wpdb->get_results($wpdb->prepare(
            "SELECT user,
                MIN(created) AS member_since,
                MAX(year)    AS last_year,
                MAX(CASE WHEN year = %d THEN 1 ELSE 0 END) AS paid_current
            FROM $subTable
            WHERE user IN ($idList)
            GROUP BY user",
            $year
        ));
        foreach ($subRows as $row) {
            $subMap[(int) $row->user] = $row;
        }

        $data = array();
        foreach ($users as $user) {
            $id  = (int) $user->ID;
            $sub = isset($subMap[$id]) ? $subMap[$id] : null;

            $data[] = array(
                'ID'           => $id,
                'name'         => $user->display_name,
                'rc'           => isset($rcMap[$id]) ? $rcMap[$id] : '',
                'member_since' => ($sub && $sub->member_since)
                    ? date('d.m.Y.', strtotime($sub->member_since))
                    : '—',
                'last_year'    => ($sub && $sub->last_year) ? $sub->last_year : '—',
                'fee_current'  => ($sub && $sub->paid_current) ? 'Da' : 'Ne',
            );
        }

        return $data;
    }
}
