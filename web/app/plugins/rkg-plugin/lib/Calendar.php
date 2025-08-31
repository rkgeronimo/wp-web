<?php
namespace RKGeronimo;

/**
 * Class: Calendar
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * Stvaranje kalendara koji se prikazuje na popisu izleta
 */
class Calendar
{

    /**
     * dayLabels
     *
     * @var array
     *
     * nazivi dana
     */
    private $dayLabels = array("Pon", "Uto", "Sri", "Čet", "Pet", "Sub", "Ned");

    /**
     * currentMonth
     *
     * @var int
     *
     * trenutni mjesec koji se prikazuje
     */
    private $currentMonth = 0;

    /**
     * currentDay
     *
     * @var int
     *
     * trenutni dan (ime) koji se prikazuje
     */
    private $currentDay = 0;

    /**
     * currentDate
     *
     * @var mixed
     *
     * trenutni datum koji se prikazuje
     */
    private $currentDate = null;

    /**
     * daysInMonth
     *
     * @var int
     *
     * broj dana u trenutnom mjesecu
     */
    private $daysInMonth = 0;

    /**
     * naviHref
     *
     * @var mixed
     *
     * Stalni dio navigacijskog linka
     */
    private $naviHref = null;

    /**
     * excursions
     *
     * @var mixed
     *
     * Izleti za prikaz
     */
    private $excursions = null;

    /**
     * currentExcursions
     *
     * @var mixed
     *
     * Izleti koji su u tijeku
     */
    private $currentExcursions = null;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->naviHref = htmlentities($_SERVER['PHP_SELF']);
    }

    /**
     * show
     *
     * @return string
     *
     * Generiranje html kalendara
     */
    public function show()
    {
        $year  = null;
        $month = null;

        if (null === $year && isset($_GET['year'])) {
            $year = $_GET['year'];
        } elseif (null === $year) {
            $year = date("Y", time());
        }

        if (null === $month && isset($_GET['month'])) {
            $month = $_GET['month'];
        } elseif (null === $month) {
            $month = date("m", time());
        }

        $this->currentYear  = $year;
        $this->currentMonth = $month;
        $this->daysInMonth  = $this->_daysInMonth($month, $year);
        $this->excursions   = $this->excursions();

        $content  = '<div id="calendar">'.
            '<div class="box">'.
            $this->_createNavi().
            '</div>'.
            '<div class="box-content">'.
            '<ul class="label">'.$this->_createLabels().'</ul>';
        $content .= '<div class="clear"></div>';
        $content .= '<ul class="dates">';

        $weeksInMonth = $this->_weeksInMonth($month, $year);
        // Create weeks in a month
        for ($i = 0; $i < $weeksInMonth; $i++) {
            //Create days in a week
            for ($j = 1; $j <= 7; $j++) {
                $content .= $this->_showDay($i*7+$j);
            }
        }

        $content .= '</ul>';
        $content .= '<div class="clear"></div>';
        $content .= '</div>';
        $content .= '</div>';

        return $content;
    }

    /**
     * excursions
     *
     * @return array
     */
    private function excursions()
    {
        global $wpdb;
        $tableName  = $wpdb->prefix."rkg_excursion_meta";
        $firstJoin  = $wpdb->prefix."users";
        $secondJoin = $wpdb->prefix."posts";
        $excursions = $wpdb->get_results(
            "SELECT rem.id, "
            ."rem.starttime, rem.endtime, rem.deadline, rem.limitation, "
            ."p.guid, p.post_title, "
            ."u.display_name FROM ".$tableName." AS rem "
            ."INNER JOIN ".$secondJoin." AS p ON rem.id = p.id "
            ."INNER JOIN ".$firstJoin." AS u ON p.post_author = u.id "
            ."WHERE rem.endtime >= '"
            .$this->currentYear."-".($this->currentMonth)."-1'"
            ." AND rem.starttime < '"
            .$this->currentYear."-".($this->currentMonth)."-"
            .$this->daysInMonth."'"
            ." AND rem.canceled=0"
            ." AND p.post_status='publish'"
            ." ORDER BY rem.starttime ASC LIMIT 20"
        );

        return $excursions;
    }

    /**
    * create the li element for ul
    */
    private function _showDay($cellNumber){

        if($this->currentDay==0){

            $firstDayOfTheWeek = date('N',strtotime($this->currentYear.'-'.$this->currentMonth.'-01'));

            if(intval($cellNumber) == intval($firstDayOfTheWeek)){

                $this->currentDay=1;

            }
        }

        if( ($this->currentDay!=0)&&($this->currentDay<=$this->daysInMonth) ){

            $this->currentDate = date('Y-m-d',strtotime($this->currentYear.'-'.$this->currentMonth.'-'.($this->currentDay)));

            $cellContent = $this->currentDay;

            $this->currentDay++;

        }else{

            $this->currentDate =null;

            $cellContent=null;
        }

        $this->currentExcursions = null;
        if ($this->currentDate && $this->excursions) {
            foreach ($this->excursions as $value) {
                if (
                    ($value->starttime <= $this->currentDate) &&
                    ($value->endtime >= $this->currentDate)
                ) {
                    $this->currentExcursions[] = $value;
                }

            }
        }

        $class = null;
        $div = null;
        if ($this -> currentExcursions) {
            if (count($this->currentExcursions) > 1) {
                $class = 'calendar-excursion multiple';
            } else {
                $class = 'calendar-excursion single';
            }

            $div = '<div class="date-excursions"><div class="date-excursions-flex">'
                .'<div class="rkg-info-close"></div>';
            foreach ($this->currentExcursions as $value) {
                $div .= '<a href="'.$value->guid.'">'
                    .$value->post_title
                    .'</a>';
            }
            $div .= '</div></div>';
        }

        $cell = '<li id="li-'.$this->currentDate.
            '" class="'.$class;
        $cell .= ($cellNumber%7==1?' start ':($cellNumber%7==0?' end ':' '))
                .($cellContent==null?'mask':'').'">'.$div.$cellContent.'</li>';

        return $cell;
    }

    /**
    * create navigation
    */
    private function _createNavi(){

        $nextMonth = $this->currentMonth==12?1:intval($this->currentMonth)+1;

        $nextYear = $this->currentMonth==12?intval($this->currentYear)+1:$this->currentYear;

        $preMonth = $this->currentMonth==1?12:intval($this->currentMonth)-1;

        $preYear = $this->currentMonth==1?intval($this->currentYear)-1:$this->currentYear;

        return
            '<div class="header">'.
                '<a class="rkg-cal-prev" href="'.$this->naviHref.'?month='.sprintf('%02d',$preMonth).'&year='.$preYear.'">&lt;</a>'.
                    '<span class="title">'.date('Y M',strtotime($this->currentYear.'-'.$this->currentMonth.'-1')).'</span>'.
                '<a class="rkg-cal-next" href="'.$this->naviHref.'?month='.sprintf("%02d", $nextMonth).'&year='.$nextYear.'">&gt;</a>'.
            '</div>';
    }

    /**
    * create calendar week labels
    */
    private function _createLabels(){

        $content='';

        foreach($this->dayLabels as $index=>$label){

            $content.='<li class="'.($label==6?'end title':'start title').' title">'.$label.'</li>';

        }

        return $content;
    }



    /**
    * calculate number of weeks in a particular month
    */
    private function _weeksInMonth($month=null,$year=null){

        if( null==($year) ) {
            $year =  date("Y",time());
        }

        if(null==($month)) {
            $month = date("m",time());
        }

        // find number of days in this month
        $daysInMonths = $this->_daysInMonth($month,$year);

        $numOfweeks = ($daysInMonths%7==0?0:1) + intval($daysInMonths/7);

        $monthEndingDay= date('N',strtotime($year.'-'.$month.'-'.$daysInMonths));

        $monthStartDay = date('N',strtotime($year.'-'.$month.'-01'));

        if($monthEndingDay<$monthStartDay){

            $numOfweeks++;

        }

        return $numOfweeks;
    }

    /**
    * calculate number of days in a particular month
    */
    private function _daysInMonth($month=null,$year=null){

        if(null==($year))
            $year =  date("Y",time());

        if(null==($month))
            $month = date("m",time());
        return date('t',strtotime($year.'-'.$month.'-01'));
    }

}
