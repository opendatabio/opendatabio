<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

trait IncompleteDate
{
    private function _zeroPad($what, $howmany = 2)
    {
        return str_pad($what, $howmany, '0', STR_PAD_LEFT);
    }

    public function getDayAttribute()
    {
        return (int) substr($this->date, 8, 2);
    }

    public function getMonthAttribute()
    {
        return (int) substr($this->date, 5, 2);
    }

    public function getYearAttribute()
    {
        return (int) substr($this->date, 0, 4);
    }

    // This can be used to set the date if you have separate numbers
    public function setDate($month, $day = null, $year = null)
    {
        //for some reason sometimes issues is array even when not (not clear why.)
        if (!is_array($month)) {
          if (strlen($month) > 2) {
            return $this->setDateFromString($month);
          }
        }
        $year = (int) $year;
        $month = (int) $month;
        $day = (int) $day;
        $this->date = $this->_zeroPad($year, 4).'-'.$this->_zeroPad($month).'-'.$this->_zeroPad($day);
    }

    // This can be used to set the date from a string
    public function setDateFromString(string $string)
    {
        try {
            $firstdash = strpos($string, '-', 0);
            $seconddash = strpos($string, '-', $firstdash + 1);
        } catch (\ErrorException $e) {
            return $this->date = null;
        }
        $this->setDate(
            // month
            substr($string, $firstdash + 1, $seconddash - $firstdash - 1),
            // day
            substr($string, $seconddash + 1, strlen($string) - $seconddash - 1),
            // year
            substr($string, 0, $firstdash));
    }

    public function getFormatDateAttribute()
    {
        if ($this->day) {
            return $this->date;
        }
        if ($this->month) {
            return substr($this->date, 0, 7);
        }

        return substr($this->date, 0, 4);
    }

    public static function checkDate($month, $day = null, $year = null)
    {
        if (is_array($month)) {
            $year = $month[2];
            $day = $month[1];
            $month = $month[0];
        }
        $year = (int) $year;
        $month = (int) $month;
        $day = (int) $day;
        // if month is unknown, day must be unknown too
        if (0 == $month and 0 != $day) {
            return false;
        }
        // if date is incomplete, we only require 0 <= $month <= 12
        // TODO: MUST ALLOW ONLY year BUT is required so add or (0==$day and 0==$month and $year>0
        //otherwise will fail)
        if (0 == $day and $month <= 12 and $month >= 0) {
            return true;
        }
        // if the date is complete, just run php checkdate
        return checkdate($month, $day, $year);
    }

    public static function beforeOrSimilar($first, $second)
    {
        // if both dates are complete, returns true if date $first is before $second
        // if one is incomplete, returns true if they are comparable
        // (eg, the full date for first might have been less than the full date for second)
        // may receive dates as array of [$m, $d, $y] or as a date string formated in Y-m-d
        // NOTE THAT string dates must NOT be incomplete
        if (is_array($first)) { // will be bumped down for comparison
            $first = $first[2].'-'.str_pad($first[0], 2, '0', STR_PAD_LEFT).'-'.str_pad($first[1], 2, '0', STR_PAD_LEFT);
        }
        if (is_array($second)) { // must be bumped UP for comparison
            if (0 == $second[1] and 0 == $second[0]) {
                $second[2] = $second[2] + 1;
            } elseif (0 == $second[1]) {
                $second[0] = $second[0] + 1;
            }
            $second = $second[2].'-'.str_pad($second[0], 2, '0', STR_PAD_LEFT).'-'.str_pad($second[1], 2, '0', STR_PAD_LEFT);
        }
        if (!(is_string($first) and is_string($second))) {
            throw new \InvalidArgumentException('beforeOrSimilar expects string or array arguments');
        }

        return $first <= $second;
    }
}
