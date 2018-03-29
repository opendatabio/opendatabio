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

    // Returns a query that is the recieved query plus a where statement filtering the field with the value. It suports exact match, as soon as 'LIKE' match if the $value contains '*'.
    public function filter($query, $field, $value, $raw=false)
    {
        $treatedValue = $this->treateWildcard($value);
        $op = ($treatedValue === $value) ? '=' : 'LIKE';
        if ($raw)
            return $query->whereRaw("$field $op ?", [$treatedValue]);
        return $query->where($field, $op, $treatedValue);
    }

    public function advancedWhereIn($query, $field, $value, $raw=false)
    {
        $values = explode(',', $value, 2);
        if (count($values) == 1)
            return $this->filter($query, $field, $values[0], $raw);
        // count($values) == 2
        return $query->where(function ($internalQuery) use ($field, $raw, $values){
            return $this->orFilter(
                    $this->filter($internalQuery, $field, $values[0], $raw),
                    $field,
                    explode(',', $values[1]),
                    $raw
            );
        });
    }

    protected function orFilter($query, $field, $values, $raw=false)
    {
        if (is_array($values))
            foreach ($values as $value) {
                $treatedValue = $this->treateWildcard($value);
                $op = ($treatedValue === $value) ? '=' : 'LIKE';
                if ($raw)
                    $query = $query->orWhereRaw("$field $op ?", [$treatedValue]);
                else
                    $query = $query->orWhere($field, $op, $treatedValue);
            }
        return $query;
    }

    // Replace all '*' to '%'
    public function treateWildcard($string)
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
