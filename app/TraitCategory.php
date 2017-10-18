<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class TraitCategory extends Model
{
    use Translatable;

    protected $fillable = ['trait_id', 'rank'];

    public function odbtrait()
    {
        return $this->belongsTo(ODBTrait::class, 'trait_id');
    }
}
