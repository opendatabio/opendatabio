<?php

namespace App;

trait IncompleteDate {
    public function getDayAttribute() {
        return date("d",strtotime($this->date));
    }
    public function getMonthAttribute() {
        return date("m",strtotime($this->date));
    }
    public function setDate($month, $day, $year) {
        $this->date = $year . "-" . $month . "-" . $day;
    }
}
