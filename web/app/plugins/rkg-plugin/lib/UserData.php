<?php
namespace RKGeronimo;

/**
 * Class UserData
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 */
class UserData
{
    /**
     * id
     *
     * @var int
     */
    public $id;

    /**
     * userMeta
     *
     * @var array
     */
    public $userMeta;

    /**
     * __construct
     *
     * @param mixed $id
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id       = $id;
        $this->userMeta = get_user_meta($id);
    }

    /**
     * showData
     *
     * @param string $data
     *
     * @return mixed
     */
    public function showData($data)
    {
        switch ($data) {
            case 'specialties':
                $output = $this->specialties();
                break;
            case 'excursionsLast':
                $output = $this->excursionsLast();
                break;
            case 'excursionsAll':
                $output = $this->excursionsAll();
                break;
            default:
                $output = $this->userMeta[$data][0];
                break;
        }

        if (!empty($output)) {

            return $output;
        }

        return '&nbsp;';
    }

    /**
     * specialties
     *
     * @return string
     */
    public function specialties()
    {
        $specialities = array();
        if ($this->userMeta['co'][0]) {
            $specialities[] = 'Kompresorist';
        }
        if ($this->userMeta['nitrox'][0]) {
            $specialities[] = 'NITROX';
        }
        if ($this->userMeta['dry_suit'][0]) {
            $specialities[] = 'Suho odijelo';
        }
        if ($this->userMeta['DAN_op'][0]) {
            $specialities[] = 'DAN oxygen provider';
        }

        return implode(", ", $specialities);
    }

    /**
     * excursionsLast
     *
     * @return int
     */
    public function excursionsLast()
    {
        global $wpdb;
        $tableName = $wpdb->prefix."rkg_excursion_signup";
        $join = $wpdb->prefix."rkg_excursion_meta";
        $excursionsLast = $wpdb->get_var(
            "SELECT COUNT(*) FROM "
            .$tableName
            ." AS s "
            ."INNER JOIN ".$join." AS m ON s.post_id = m.id "
            ." WHERE s.user_id = "
            .$this->id.
            " AND m.endtime > '"
            .date("Y-m-d", strtotime("-6 months"))
            ."'"
        );

        return $excursionsLast;
    }

    /**
     * excursionsAll
     *
     * @return int
     */
    public function excursionsAll()
    {
        global $wpdb;
        $tableName = $wpdb->prefix."rkg_excursion_signup";
            $excursionsAll = $wpdb->get_var(
                "SELECT COUNT(*) FROM "
                .$tableName.
                " WHERE user_id = ".$this->id
            );

            return $excursionsAll;
    }
}
