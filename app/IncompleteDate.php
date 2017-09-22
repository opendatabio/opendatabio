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
}
