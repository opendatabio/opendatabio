<?php

namespace App;

trait IncompleteDate {
    public function getDayAttribute() {
        return (int) substr($this->date,8,2);
    }
    public function getMonthAttribute() {
        return (int) substr($this->date,5,2);
    }
    public function getYearAttribute() {
        return (int) substr($this->date,0,4);
    }
    public function setDate($month, $day, $year) {
        $this->date = $year . "-" . $month . "-" . $day;
    }
    public function getFormatDateAttribute() {
        if ( $this->day)
            return $this->date;
        if ( $this->month)
            return substr($this->date,0,7);
        return substr($this->date,0,4);
    }
    public static function checkDate($month, $day = null, $year = null) {
        if (is_array($month)) {
            $year = $month[2];
            $day = $month[1];
            $month = $month[0];
        }
        // if month is unknown, day must be unknown too
        if ($month == 0 and $day != 0) return false;
        // if date is incomplete, we only require 0 <= $month <= 12
        if ($day == 0 and $month <=12 and $month >= 0) return true;
        // if the date is complete, just run php checkdate
        return checkdate($month, $day, $year);
    }
    public static function beforeOrSimilar($first, $second) {
        // if both dates are complete, returns true if date $first is before $second
        // if one is incomplete, returns true if they are comparable 
        // (eg, the full date for first might have been less than the full date for second)
        // may receive dates as array of [$m, $d, $y] or as a date string formated in Y-m-d
        // NOTE THAT string dates must NOT be incomplete
        if (is_array($first)) // will be bumped down for comparison
            $first = $first[2] . "-" . str_pad($first[0], 2, '0',STR_PAD_LEFT) . "-" . str_pad($first[1], 2, '0',STR_PAD_LEFT);
        if (is_array($second)) { // must be bumped UP for comparison
            if ($second[1] == 0 and $second[0] == 0) {
                $second[2] = $second[2] + 1;
            } elseif ($second[1] == 0) {
                $second[0] = $second[0] + 1;
            }
            $second = $second[2] . "-" . str_pad($second[0], 2, '0',STR_PAD_LEFT) . "-" . str_pad($second[1], 2, '0',STR_PAD_LEFT);
        }
        if (! (is_string($first) and is_string($second)))
            throw new \InvalidArgumentException ("beforeOrSimilar expects string or array arguments");
        return $first <= $second;
    }
}
