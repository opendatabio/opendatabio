<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Location;
use App\Models\Individual;
use App\Models\Identification;
use DB;

class IndividualLocation extends Model
{
    protected $table = 'individual_location';

    //protected $appends = ['latitude','location_name','taxon_name','taxon_family','project_name'];


    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function individual()
    {
        return $this->belongsTo(Individual::class);
    }

    public function identification()
    {
      return $this->hasOne(Identification::class, 'object_id', 'identification_id')->where('identifications.object_type',Individual::class);
    }

    public function getlocationWithGeomAttribute()
    {
      return $this->location()->withGeom()->first();
    }

    public function getLocationNameAttribute()
    {
      return $this->locationWithGeom->name;
    }

    public function getLocationFullnameAttribute()
    {
      return $this->locationWithGeom->higherGeography;
    }

    public function getLatitudeAttribute()
    {
      return (float) $this->locationWithGeom->decimalLatitude;
    }

    public function getLongitudeAttribute()
    {
      return (float) $this->locationWithGeom->decimalLongitude;
    }



    public function newQuery($excludeDeleted = true)
    {
        // This uses the explicit list to avoid conflict due to global scope
        // maybe check http://lyften.com/journal/user-settings-using-laravel-5-eloquent-global-scopes.html ???
        return parent::newQuery($excludeDeleted)->select(
            'individual_location.id',
            'individual_location.location_id',
            'individual_location.individual_id',
            'individual_location.date_time',
            'individual_location.notes',
            'individual_location.altitude',
            'individual_location.first',
            DB::raw('ST_AsText(relative_position) as relativePosition')
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

    /* dwc terms */
    public function getOrganismIDAttribute()
    {
      return $this->individual->fullname;
    }
    public function getRecordedDateAttribute()
    {
      return $this->date_time;
    }
    public function getDecimalLatitudeAttribute()
    {
      return (float) $this->locationWithGeom->decimalLatitude;
    }

    public function getDecimalLongitudeAttribute()
    {
      return (float) $this->locationWithGeom->decimalLongitude;
    }
    public function getHigherGeographyAttribute()
    {
      return $this->locationWithGeom->higherGeography;
    }
    public function getGeoreferenceRemarksAttribute()
    {
      return $this->locationWithGeom->georeferenceRemarks;
    }
    public function getOccurrenceRemarksAttribute()
    {
      return $this->notes;
    }
    public function getOrganismRemarksAttribute()
    {
      return $this->individual->organismRemarks;
    }
    public function getMinimumElevationAttribute()
    {
      return $this->altitude;
    }
    public function getDatasetNameAttribute()
    {
      return $this->individual->datasetName;
    }
    public function getBibliographicCitationAttribute()
    {
      return $this->individual->bibliographicCitation;
    }
    public function getAccessRightsAttribute()
    {
      return $this->individual->accessRights;
    }

    public function getBasisOfRecordAttribute()
    {
      return 'Occurrence';
    }
    public function getScientificNameAttribute()
    {
      return $this->individual->scientificName;
    }
    public function getFamilyAttribute()
    {
      return $this->individual->family;
    }
    public function getOccurrenceIDAttribute()
    {
      return $this->individual->tag.":".$this->individual->normalizedAbbreviation.":".strtotime($this->date_time);
    }
    public function getLicenseAttribute()
    {
      return $this->individual->license;
    }
}
