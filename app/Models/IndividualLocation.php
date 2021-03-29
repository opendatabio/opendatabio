<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Location;
use DB;

class IndividualLocation extends Model
{
    protected $table = 'individual_location';

    //protected $appends = ['latitude','location_name','taxon_name','taxon_family','project_name'];


    public function location()
    {
        return $this->belongsTo(Location::class);
    }


    public function getLocationWithGeomAttribute()
    {
      return $this->location()->withGeom()->first();
    }

    public function getLocationNameAttribute()
    {
      return $this->location()->first()->name;
    }

    public function getLocationFullnameAttribute()
    {
      return $this->location()->first()->fullname;
    }

    public function getLatitudeAttribute()
    {
      $centroid = $this->locationwithgeom->centroid_raw;
      $point = substr($centroid, 6, -1);
      $pos = strpos($point, ' ');
      $lat = substr($point, $pos + 1);
      return (float) $lat;
    }

    public function getLongitudeAttribute()
    {
      $centroid = $this->locationwithgeom->centroid_raw;
      $point = substr($centroid, 6, -1);
      $pos = strpos($point, ' ');
      $long = substr($point, 0, $pos);
      return (float) $long;
    }



    public function newQuery($excludeDeleted = true)
    {
        // This uses the explicit list to avoid conflict due to global scope
        // maybe check http://lyften.com/journal/user-settings-using-laravel-5-eloquent-global-scopes.html ???
        return parent::newQuery($excludeDeleted)->select(
            'individual_location.id',
            'individual_location.location_id',
            'individual_location.date_time',
            'individual_location.notes',
            'individual_location.altitude',
            'individual_location.first',
            DB::raw('AsText(relative_position) as relativePosition')
        );
    }

    // getters for the Relative Position
    public function getXAttribute()
    {
        if ($this->attributes['relativePosition'] == "") {
          return;
        }
        $point = substr($this->attributes['relativePosition'], 6, -1);
        $pos = strpos($point, ' ');

        return substr($point, $pos + 1);
    }

    public function getYAttribute()
    {
        if ($this->attributes['relativePosition'] == "") {
          return;
        }
        $point = substr($this->attributes['relativePosition'], 6, -1);
        $pos = strpos($point, ' ');

        return substr($point, 0, $pos);
    }

    public function getAngleAttribute()
    {
        $x = $this->getXAttribute();
        if (null == $x) {
            return ;
        }
        $y = $this->getYAttribute();
        $angle = (180 / M_PI) * atan2((float) $y, (float) $x);
        if ($angle<0) {
          $angle = $angle+360;
        }
        return round($angle,2);
    }

    public function getDistanceAttribute()
    {
        $x = $this->getXAttribute();
        if (null == $x) {
            return ;
        }
        $y = $this->getYAttribute();
        $distance = sqrt((float) $x * (float) $x + (float) $y * (float) $y);
        return round($distance,2);
    }


}
