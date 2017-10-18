<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Collector extends Model
{
    protected $fillable = ['person_id', 'object_id', 'object_type'];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function object()
    {
        return $this->morphTo('object');
    }
}
