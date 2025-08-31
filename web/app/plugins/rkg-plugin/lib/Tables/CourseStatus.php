<?php
namespace RKGeronimo\Tables;

use WP_List_Table;
use RKGeronimo\UserData;
use Timber;

/**
 * Class CourseStatus
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 * @SuppressWarnings("CamelCase")
 */
class CourseStatus extends WP_List_Table
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
            'rkg_name'     => 'Ime i prezime',
            'email'        => 'Email',
            'rkg_gsm'      => 'Mobitel',
            // 'questionaire' => 'Upitnik',
            'health'       => 'Zdravstvena izjava',
            'liability'    => 'Izjava o odgovornosti',
            'brevet'       => 'Slika za brevet',
            'newbrevet'    => 'Novi broj breveta',
            'payed'        => 'Plaćeno',
        );

        $additionalColumns = array(
            'weight'       => 'Težina (kg)',
            'height'       => 'Visina (cm)',
            'shoe_size'       => 'Veličina obuće',
        );

        $courseName = get_the_title($this->post);
        if (stripos($courseName, "Početni ronilački tečaj") !== false
            || stripos($courseName, "R1") !== false)
        {
            $columns = array_merge($columns, $additionalColumns);
        }

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
            // case 'questionaire':
            case 'email':
            case 'health':
            case 'liability':
            case 'brevet':
            case 'newbrevet':
            case 'weight':
            case 'height':
            case 'shoe_size':
            case 'payed':
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

        $tableName = $wpdb->prefix."rkg_course_signup";
        $participants = $wpdb->get_results(
            "SELECT * FROM "
            .$tableName
            ." WHERE course_id="
            .$this->post
            ." ORDER BY created"
        );

        foreach ($participants as $value) {
            $user     = get_userdata($value->user_id);
            $userData = new UserData($value->user_id);
            $templates = array('CourseStatus.twig');

            $tableName = $wpdb->prefix."rkg_course_medical_meta";
            $medicalData = $wpdb->get_row(
                "SELECT pregnancy, medications, older, breathing, allergies, cold,"
                ." lungs, chest, pneumotorax, phobia, behavior, epilepsy, migraine,"
                ." fainting, moving, decompression, back, backoperation, diabetes,"
                ." fracture, exercise, bloodpresure, heart, heartattack, infarction,"
                ." ears, deafness, earpressure, bleeding, hernia, ulcer, colostomy,"
                ." addiction FROM "
                .$tableName
                ." WHERE post_id="
                .$this->post
                ." AND user_id="
                .$value->user_id
                ." ORDER BY created",
                ARRAY_A
            );

            $medical = '&nbsp';
            if ($medicalData) {
                $false   = in_array('1', $medicalData, true);
                $red = null;
                if ($false) {
                    $red = " class='rkg-admin-delete'";
                }
                $medical = "<a".$red." href='"
                    .get_admin_url()
                    ."admin.php?page=course_medical&post="
                    .$this->post
                    ."&user="
                    .$value->user_id
                    ."'>Ispunjeno</a>";
            }

            $tableName = $wpdb->prefix."rkg_course_liability_meta";
            $liabilityData = $wpdb->get_row(
                "SELECT rs1, rs2, rs3, rs4, rs5, rs6, rs7 FROM "
                .$tableName
                ." WHERE post_id="
                .$this->post
                ." AND user_id="
                .$value->user_id
                ." ORDER BY created",
                ARRAY_A
            );

            $liability = '&nbsp';
            if ($liabilityData) {
                $false   = in_array('0', $liabilityData, true);
                $red = null;
                if ($false) {
                    $red = " class='rkg-admin-delete'";
                }
                $liability = "<a".$red." href='"
                    .get_admin_url()
                    ."admin.php?page=course_liability&post="
                    .$this->post
                    ."&user="
                    .$value->user_id
                    ."'>Ispunjeno</a>";
            }

            $brevet = "&nbsp;";
            if (isset($userData->userMeta['brevet']) && $userData->userMeta['brevet']) {
                $brevet = "<img src='".$userData->userMeta['brevet'][0]."' style='max-width: 100%' />";
            }
            $newBrevet = '<input type="text" style="width: 90%" '
                .'value="'.$value->new_card.'" '
                .'name="new_card['.$value->user_id.']"'
                .'>';
            $payed = '<input type="checkbox" name="payed['.$value->user_id.']"';
            if ($value->payed) {
                $payed = $payed.' checked ';
            }
            $payed = $payed.'>';
            $data[] = array(
                'rkg_name'           => $user->data->display_name,
                'email'           => $user->data->user_email,
                'rkg_gsm'            => $userData->showData('tel'),
                // 'questionaire' => 'Upitnik',
                'health'       => $medical,
                'liability'    => $liability,
                'brevet'       => $brevet,
                'newbrevet'    => $newBrevet,
                'weight'       => $value->weight,
                'height'       => $value->height,
                'shoe_size'    => $value->shoe_size,
                'payed'        => $payed,
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
    private function sort_data($a, $b)
    {
        if ($a === $b) {
            return 0;
        }

        return ($a < $b) ? 1 : -1;
    }
}
