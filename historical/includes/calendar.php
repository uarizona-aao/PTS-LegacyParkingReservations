<?php

/*
 * calendar.php
 * Generates an XML calendar for a given year and month.
 *
 *
 *
 *
 *   AH!!!!!!!!!!!!!!!!!!!  This class uses     xsl/special_elements.xsl   and   css/main.css
 *
 *
 *
 *
 *
 */

class calendar {
    private $xml;

    // The optional third parameter specifies a callback function for highlighting days.
    // This function should return the link, the link class (CSS), and the pop-up hint text.
    function __construct($year, $month, $day_func = null){
        if($day_func and !function_exists($day_func))
            throw new Exception("Function $day_func is not defined");
        $first_of_month = mktime (0,0,0, $month, 1, $year);
        $maxdays   = date('t', $first_of_month); // number of days in the month
        $date_info = getdate($first_of_month);   // get info about the first day of the month
        $month     = $date_info['mon'];
        $year      = $date_info['year'];

        $next_year = $year;
        $next_month = $month+1;
        if($next_month == 13) {
            $next_month = 1;
            $next_year++;
        }

        $prev_year = $year;
        $prev_month = $month-1;
        if($prev_month == 0) {
            $prev_month = 12;
            $prev_year--;
        }

        $url = $_SERVER['PHP_SELF'];

        $title = "$date_info[month] $year";
        $calendar  = "<calendar title=\"$title\" prev=\"$url?month=$prev_month&amp;year=$prev_year\" next=\"$url?month=$next_month&amp;year=$next_year\"><week>";

        // The first day of the month
        $weekday = $date_info['wday'];
        $day = 1;
        $calendar .= str_repeat("<day> </day>", $weekday);

        $same_month = ($month == date('m') and $year == date('Y'));
        $today = date('j');

        while ($day <= $maxdays){
            // Start a new week
            if($weekday == 7){
                $calendar .= "</week>\n<week>";
                $weekday = 0;
            }

            $calendar .= "<day";
            if($day_func) {
                $oradate = strtoupper(date('d-M-y', mktime(0,0,0,$month,$day,$year)));
                list($link, $class, $title) = $day_func($month, $day, $year, $oradate);
                if($link) $calendar .= " href=\"$link?date=$oradate\"";
                if($title) $calendar .= " title=\"$title\"";
            }

            // Mark today specially
            if($same_month and $day == $today) $class .= ' today';

            if($class) $calendar .= " class=\"$class\"";
            $calendar .= ">$day</day>";

            $day++;
            $weekday++;
        }

        if($weekday != 7) $calendar .= str_repeat("<day> </day>", 7-$weekday);
        $this->xml = $calendar . "</week></calendar>";
    }

    function get_xml() {
        return $this->xml;
    }
}
?>
