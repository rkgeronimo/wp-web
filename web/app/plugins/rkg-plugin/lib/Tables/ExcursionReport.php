<?php
namespace RKGeronimo\Tables;

use WP_List_Table;
use RKGeronimo\UserData;

/**
 * Class ExcursionReport
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 * @SuppressWarnings("CamelCase")
 */
class ExcursionReport extends WP_List_Table
{
    /**
     * post
     *
     * @var mixed
     */
    public $post = null;

    /**
     * __construct
     *
     * @param int $post
     *
     * @return void
     */
    public function __construct($post)
    {
        parent::__construct(array(
            'singular' => __('Customer', 'sp'),
            'plural'   => __('Customers', 'sp'),
            'ajax'     => false,

        ));

        $this->post = $post;
    }

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns  = $this->get_columns();
        $hidden   = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort($data, array(&$this, "sort_data"));

        $perPage     = 100;
        $currentPage = $this->get_pagenum();
        $totalItems  = count($data);

        $this->set_pagination_args(array(
            'total_items' => $totalItems,
            'per_page'    => $perPage,
        ));

        $data = array_slice($data, (($currentPage-1)*$perPage), $perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items           = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'rkg_name'       => 'Ime',
            'rkg_gsm'        => 'Mobitel',
            'category'       => 'Kategorija',
            'excursionsLast' => 'Izleta u 6mj',
            'excursionsAll'  => 'Izleta ukupno',
            'specialty'      => 'Specijalnost',
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array();
    }

    /**
     * column_default
     *
     * @param array  $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'rkg_name':
            case 'rkg_gsm':
            case 'category':
            case 'excursionsLast':
            case 'excursionsAll':
            case 'specialty':
                return $item[$column_name];

            default:
                return print_r($item, true);
        }
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $data = array();
        global $wpdb;

        $tableName    = $wpdb->prefix."rkg_excursion_signup";
        $participants = $wpdb->get_col(
            "SELECT user_id FROM "
            .$tableName
            ." WHERE post_id       = "
            .$this->post
            ." ORDER BY created"
        );

        foreach ($participants as $value) {
            $user     = get_userdata($value);
            $userData = new UserData($value);

            $data[] = array(
                'rkg_name'           => $user->data->display_name,
                'rkg_gsm'            => $userData->showData('tel'),
                'category'       => $userData->showData('rc'),
                'excursionsLast' => $userData->showData('excursionsLast'),
                'excursionsAll'  => $userData->showData('excursionsAll'),
                'specialty'      => $userData->showData('specialties'),
            );
        }

        return $data;
    }

    /**
     * sort_data
     *
     * @param mixed $aIn
     * @param mixed $bIn
     *
     * @return int
     */
    private function sort_data($aIn, $bIn)
    {
        $a = $this->rateParticipant($aIn);
        $b = $this->rateParticipant($bIn);
        if ($a === $b) {
            return 0;
        }

        return ($a < $b) ? 1 : -1;
    }

    private function rateParticipant($participant)
    {
        switch ($participant['category']) {
            case "I3":
                $score = 9;
                break;
            case "I2":
                $score = 8;
                break;
            case "I1":
                $score = 7;
                break;
            case "R4":
                $score = 6;
                break;
            case "R3":
                $score = 5;
                break;
            case "R2":
                $score = 4;
                break;
            case "R2T":
                $score = 3;
                break;
            case "R1":
                $score = 2;
                break;
            case "R1T":
                $score = 1;
                break;
            default:
                $score = 0;
        }

        if (!empty($participant['log'])) {
            $score += 100;
        }

        return $score;
    }
}
