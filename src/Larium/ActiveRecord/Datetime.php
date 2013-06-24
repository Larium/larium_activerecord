<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord;

class Datetime extends \Datetime
{

    public function getYear()
    {
        return $this->format("Y");
    }

    public function getMonth()
    {
        return $this->format("m");
    }

    public function getDay()
    {
        return $this->format("d");
    }

    public function toDb()
    {
        return $this->format("Y-m-d H:i:s");
    }

    public static function now()
    {
        return new self();
    }

    public static function create($time)
    {
        return new self($time);
    }

    public static function createFromMktime($time)
    {
        $string_time = date("Y-m-d H:i:s", $time);
        return self::createFromFormat('Y-m-d H:i:s', $string_time);
    }

    public static function months()
    {
        return array(
            1 => "January",
            2 => "February",
            3 => "March",
            4 => "April",
            5 => "May",
            6 => "June",
            7 => "July",
            8 => "August",
            9 => "September",
            10 => "October",
            11 => "November",
            12 => "December"
        );
    }

    public static function abbrMonths()
    {
        return array(
            1 => "Jan",
            2 => "Feb",
            3 => "Mar",
            4 => "Apr",
            5 => "May",
            6 => "Jun",
            7 => "Jul",
            8 => "Aug",
            9 => "Sep",
            10 => "Oct",
            11 => "Nov",
            12 => "Dec"
        );
    }

}
