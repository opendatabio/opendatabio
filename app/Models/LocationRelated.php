<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Location;

class LocationRelated extends Model
{
    protected $table = 'location_related';
    protected $fillable = ['location_id', 'related_id'];

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function relatedLocation()
    {
        return $this->belongsTo(Location::class, 'related_id');
    }

}
