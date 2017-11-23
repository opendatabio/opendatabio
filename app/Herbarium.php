<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Herbarium extends Model
{
    protected $fillable = ['name', 'acronym', 'irn'];

    public function persons()
    {
        return $this->hasMany(Person::class);
    }

    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class)->withPivot('herbarium_number');
    }

    // For Revisionable
    public function identifiableName()
    {
        return $this->acronym;
    }
}
