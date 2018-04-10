<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Response;
use URL;

class ODBFunctions extends BaseController
{
    /**
     * Interprets $value as a value to search at a given table and $class as the class that is associated with the table.
     * If $value has a number or a list of numbers separeted by comma, this method converts this list to an array of numbers.
     * Otherwise, this method search the table for registries that has the field $name with value $value. Additinally,
     * $value could be a string containing a comma separeted list of values, and each value could contains the wildcard '*'.
     */
    public static function asIdList($value, $query, $name, $raw=false)
    {
        if (preg_match("/\A\d+(,\d+)*\z/", $value))
            return explode(',', $value);
        $ids = array();
        ODBFunctions::advancedWhereIn($query, $name, $value, $raw);
        $query = $query->get();
        foreach ($query as $registry)
            array_push($ids, $registry->id);
        return array_unique($ids);
    }

    public static function advancedWhereIn(&$query, $field, $value, $raw=false)
    {
        $values = explode(',', $value, 2);
        if (count($values) == 1)
            ODBFunctions::filter($query, $field, $values[0], $raw);
        else // count($values) == 2
            $query->where(function ($internalQuery) use ($field, $raw, $values) {
                ODBFunctions::filter($internalQuery, $field, $values[0], $raw);
                ODBFunctions::orFilter(
                        $internalQuery,
                        $field,
                        explode(',', $values[1]),
                        $raw);
            });
    }

    // Changes the $query that is recieved adding a where statement filtering the $field with the $value. It suports exact match, as soon as 'LIKE' match if the $value contains '*'. It can receive $raw=true to specify that $field is a sql function to be applied
    private static function filter(&$query, $field, $value, $raw=false)
    {
        $treatedValue = ODBFunctions::treateWildcard($value);
        $op = ($treatedValue === $value) ? '=' : 'LIKE';
        if ($raw)
            $query->whereRaw("$field $op ?", [$treatedValue]);
        else
            $query->where($field, $op, $treatedValue);
    }

    // Changes the $query that is recieved adding a orWhere statement filtering the $field with the $value. It suports exact match, as soon as 'LIKE' match if the $value contains '*'. It can receive $raw=true to specify that $field is a sql function to be applied
    private static function orFilter(&$query, $field, $values, $raw=false)
    {
        foreach ($values as $value) {
            $treatedValue = ODBFunctions::treateWildcard($value);
            $op = ($treatedValue === $value) ? '=' : 'LIKE';
            if ($raw)
                $query->orWhereRaw("$field $op ?", [$treatedValue]);
            else
                $query->orWhere($field, $op, $treatedValue);
        }
    }

    // Replace all '*' to '%'
    private static function treateWildcard($string)
    {
        $pos = strpos($string, '*');
        while ($pos !== FALSE) {
            $string = substr_replace($string, '%', $pos, 1);
            $pos = strpos($string, '*');
        }
        return $string;
    }
}
