<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Lang;

class TraitObject extends Model
{
    protected $fillable = ['trait_id', 'object_type'];

    public function odbtrait()
    {
        return $this->belongsTo(ODBTrait::class, 'trait_id');
    }

    public function getNameAttribute()
    {
        return Lang::get('classes.'.$this->object_type);
    }
}
