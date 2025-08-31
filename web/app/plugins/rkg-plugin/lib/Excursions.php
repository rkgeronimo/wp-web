<?php
namespace RKGeronimo;

use RKGeronimo\Interfaces\InitInterface;
use RKGeronimo\Tables\ExcursionReport;
use Timber;

/**
 * Class: Courses
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @see InitInterface
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class Excursions implements InitInterface
{
    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        add_filter('post_row_actions', array($this, 'changeExcursionsRowActions'), 10, 2);
        add_action('admin_menu', array($this, 'addExcursionReport'));
    }


    public function changeExcursionsRowActions($actions, $post)
    {
        if ($post->post_type=='excursion')
        {
            //$actions['sendMail'] = '<a href="#" title="" rel="permalink">Duplicate</a>';
            $allEmails = $this->getEmails($post->ID);

            $currentUser = wp_get_current_user();
            $sendToEmails = array_diff($allEmails, array($currentUser->user_email));

            $actions['sendMail'] = "<a href='mailto:".implode(';', $sendToEmails).
            "'>Pošalji e-mail prijavljenima (".count($sendToEmails).")</a>";

            $actions['report'] = "<a href='"
                .get_admin_url()
                ."admin.php?page=excursion_report&post="
                .$post->ID
                ."'>Status izleta</a>";
        }
        unset($actions['delete']);

        return $actions;
    }

    private function getEmails($id)
    {
        global $wpdb;
        $emails = array();
        $tableName = $wpdb->prefix."rkg_excursion_signup";
        $participants = $wpdb->get_col(
            "SELECT user_id FROM "
            .$tableName
            ." WHERE post_id="
            .$id
            ." ORDER BY created"
        );

        foreach ($participants as $value) {
            $user = new Timber\User($value);
            $emails[] = $user->user_email;
        }

        return $emails;
    }

    public function addExcursionReport()
    {
        add_submenu_page(
            'admin.php',
            'Izlet - status',
            'Izlet - status',
            'edit_excursion',
            'excursion_report',
            array($this, 'showExcursionReport')
        );
    }

    public function showExcursionReport()
    {
        apply_filters('admin_body_class', 'rkg-printable');
        global $wpdb;
        $context = Timber::get_context();

        $id = $context['request']->get['post'];
        $context['post'] = new Timber\Post($id);

        $tableName = $wpdb->prefix."rkg_excursion_meta";
        $context['excursionMeta'] = $wpdb->get_row(
            "SELECT * FROM "
            .$tableName
            ." WHERE id="
            .$id
        );

        // Get guests and related data

        $context['guests'] = array();
        $tableName = $wpdb->prefix."rkg_excursion_guest";
        $guests = $wpdb->get_results(
            "SELECT user_id, name, email, tel FROM "
            .$tableName
            ." WHERE post_id="
            .$id
            ." ORDER BY created"
        );
        foreach ($guests as $key => $value) {
            $user      = new Timber\User($value->user_id);
            $guests[$key]->invited_by =  $user->display_name;
        }
        $context['guests'] = $guests;

        // Get excursion participants and related data

        $context['participants'] = array();
        $tableName = $wpdb->prefix."rkg_excursion_signup";
        $participants = $wpdb->get_col(
            "SELECT user_id FROM "
            .$tableName
            ." WHERE post_id="
            .$id
            ." ORDER BY created"
        );

        foreach ($participants as $value) {
            $user      = new Timber\User($value);
            $tableName = $wpdb->prefix."rkg_excursion_signup";
            $join = $wpdb->prefix."rkg_excursion_meta";
            $user->excursionsLast = $wpdb->get_var(
                "SELECT COUNT(*) FROM "
                .$tableName
                ." AS s "
                ."INNER JOIN ".$join." AS m ON s.post_id = m.id "
                ." WHERE s.user_id = "
                .$user->ID .
                " AND m.endtime > '"
                .date("Y-m-d", strtotime("-6 months"))
                ."'"
            );
            $user->excursionsAll = $wpdb->get_var(
                "SELECT COUNT(*) FROM "
                .$tableName.
                " WHERE user_id = ".$user->ID
            );

            $context['participants'][] = $user;
        }
        usort($context['participants'], array($this, "sortParticipants"));

        $exampleListTable = new ExcursionReport($id);
        $exampleListTable->prepare_items();

        $context['table'] = $exampleListTable;

        $templates = array('excursionReport.twig');
        Timber::render($templates, $context);
    }

    private function sortParticipants($aIn, $bIn)
    {
        $a = $this->rateParticipant($aIn);
        $b = $this->rateParticipant($bIn);
        if ($a == $b) {
            return 0;
        }

        return ($a < $b) ? 1 : -1;
    }

    private function rateParticipant($participant)
    {
        switch ($participant->rc) {
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

        if (!empty($participant->log)) {
            $score += 100;
        }

        return $score;
    }
}
