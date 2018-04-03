<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Response;
use URL;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    // Wraps the data to be sent with key "data" and adds metadata about the server and request
    public function wrap_response($data)
    {
        return Response::json([
            'meta' => [
                'odb_version' => config('app.version'),
                'api_version' => 'v0',
                'server' => url('/'),
                'full_url' => URL::full(),
            ],
            'data' => $data,
        ]);
    }

    /**
     * Interprets $value as a value to search at a given table and $class as the class that is associated with the table.
     * If $value has a number or a list of numbers separeted by comma, this method converts this list to an array of numbers.
     * Otherwise, this method search the table for registries that has the field $name with value $value. Additinally,
     * $value could be a string containing a comma separeted list of values, and each value could contains the wildcard '*'.
     */
    public function asIdList($value, $class, $name, $raw=false)
    {
        if (preg_match("/\A\d+(,\d+)*\z/", $value))
            return explode(',', $value);
        $ids = array();
        $found = $this->advancedWhereIn($class::select('id'), $name, $value, $raw)->get();
        foreach ($found as $registry)
            array_push($ids, $registry->id);
        return array_unique($ids);
    }

    public function advancedWhereIn(&$query, $field, $value, $raw=false)
    {
        $values = explode(',', $value, 2);
        if (count($values) == 1)
            $this->filter($query, $field, $values[0], $raw);
        else // count($values) == 2
            $query->where(function ($internalQuery) use ($field, $raw, $values) {
                $this->filter($internalQuery, $field, $values[0], $raw);
                $this->orFilter(
                        $internalQuery,
                        $field,
                        explode(',', $values[1]),
                        $raw);
            });
    }

    // Changes the $query that is recieved adding a where statement filtering the $field with the $value. It suports exact match, as soon as 'LIKE' match if the $value contains '*'. It can receive $raw=true to specify that $field is a sql function to be applied
    private function filter(&$query, $field, $value, $raw=false)
    {
        $treatedValue = $this->treateWildcard($value);
        $op = ($treatedValue === $value) ? '=' : 'LIKE';
        if ($raw)
            $query->whereRaw("$field $op ?", [$treatedValue]);
        else
            $query->where($field, $op, $treatedValue);
    }

    // Changes the $query that is recieved adding a orWhere statement filtering the $field with the $value. It suports exact match, as soon as 'LIKE' match if the $value contains '*'. It can receive $raw=true to specify that $field is a sql function to be applied
    private function orFilter(&$query, $field, $values, $raw=false)
    {
        foreach ($values as $value) {
            $treatedValue = $this->treateWildcard($value);
            $op = ($treatedValue === $value) ? '=' : 'LIKE';
            if ($raw)
                $query->orWhereRaw("$field $op ?", [$treatedValue]);
            else
                $query->orWhere($field, $op, $treatedValue);
        }
    }

    // Replace all '*' to '%'
    private function treateWildcard($string)
    {
        $pos = strpos($string, '*');
        while ($pos !== FALSE) {
            $string = substr_replace($string, '%', $pos, 1);
            $pos = strpos($string, '*');
        }
        return $string;
    }

    // Filters the designated fields in the collection to be returned
    public function setFields($collection, $fields, $simple)
    {
        // Special keyword "all", returns the collection untransformed
        if ('all' == $fields) {
            return $collection;
        }
        // Special keyword "simple", returns a default listing of fields
        if ('simple' == $fields) {
            $fields = $simple;
        } else {
            $fields = explode(',', $fields);
        }

        $collection = $collection->map(function ($obj) use ($fields) {
            foreach ($fields as $field) {
                // appends custom accessors to the JSON response
                if ($obj->hasGetMutator($field)) {
                    $obj->append($field);
                }
            }

            return collect($obj->toArray())
                ->only($fields)
                ->all();
        });

        return $collection;
    }
}
