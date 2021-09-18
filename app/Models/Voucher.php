<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Auth;
use Lang;
use DB;
use App\Models\IndividualLocation;
use App\Models\Location;
use App\Models\Dataset;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;


class Voucher extends Model
{
    use IncompleteDate, LogsActivity;

    protected $fillable = [
      'individual_id', 'biocollection_id', 'biocollection_type',
      'number', 'date', 'notes', 'dataset_id','biocollection_number'];

    //add attributes for automatic use in datatabe
    //protected $appends = ['location_id'];
    //activity log trait (parent, uc and geometry are logged in controller)
    protected static $logName = 'voucher';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at'];
    protected static $logFillable = true;
    //$logAttributes = ['name','altitude','adm_level','datum','x','y','startx','starty','notes'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;


    public function rawLink($addId = false)
    {
        $text = "<a href='".url('vouchers/'.$this->id)."'>".htmlspecialchars($this->fullname).'</a>';
        if ($addId) {
            if ($this->identification) {
                $text .= ' ('.$this->identification->rawLink().')';
            } else {
                $text .= ' '.Lang::get('messages.unidentified');
            }
        }
        return $text;
    }

    // for use when receiving this as part of a morph relation
    public function getTypenameAttribute()
    {
        return 'vouchers';
    }


    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('datasetScope', function (Builder $builder) {
            // first, the easy cases. No logged in user?
            if (is_null(Auth::user())) {
                return $builder->whereRaw('((vouchers.dataset_id IS NULL) OR vouchers.id IN (SELECT p1.id FROM vouchers AS p1 JOIN datasets ON datasets.id = p1.dataset_id WHERE datasets.privacy >='.Dataset::PRIVACY_PUBLIC.'))');
            }
            // superadmins see everything
            if (User::ADMIN == Auth::user()->access_level) {
                return $builder;
            }
            // now the complex case: the regular user
            return $builder->whereRaw('((vouchers.dataset_id IS NULL) OR vouchers.dataset_id IN (SELECT dts.id FROM datasets as dts JOIN dataset_user as dtu ON dtu.dataset_id=dts.id WHERE (dts.privacy >='.Dataset::PRIVACY_REGISTERED.') OR dtu.user_id='.Auth::user()->id.'))');
          });
    }


    //get individual the voucher belongs to
    public function individual()
    {
      return $this->belongsTo(Individual::class);
    }


    //voucher location is the location of the individual (the closes in date)
    //this direct functions are to GET data only, not to set the location for a voucher
    public function locations()
    {
      return $this->hasMany(
          IndividualLocation::class,
          'individual_id',
          'individual_id'
        );
    }

    /* get closest location if many */
    public function location_first()
    {
      if ($this->locations->count()==0) {
        return null; //this should not exists;
      }
      $date = isset($this->date) ? $this->date : $this->individual->date;
      $date = explode("-",$date);
      $valid_date = checkdate($date[1],$date[2],$date[0]);
      if ($this->locations->count()==1 or !$valid_date) {
        return $this->locations()->where('first',1);
      }
      return $this->locations()->orderByRaw(" ABS(DATEDIFF(date_time, '".$this->date."')), id" )->limit(1);
    }

    // with access to the location geom field
    public function getLocationWithGeomAttribute()
    {
        if ($this->location_first->count()) {
          $id = $this->location_first->first()->location_id;
          return Location::withGeom()->addSelect('id', 'name')->find($id);
        }
        return;
    }

    public function getLocationDisplayAttribute()
    {
      $location = $this->location_first->first()->location_fullname;
      $coordinates = $this->locationWithGeom->coordinatesSimple;
      $altitude = $this->location_first->first()->altitude ? $this->location_first->first()->altitude : $this->location_first->first()->location->altitude;
      $altitude = ($altitude != "" and null != $altitude) ? "<br>".$altitude."m.a.s.l." : null;
      return $location."<br>".$coordinates.$altitude;
    }

    public function getCoordinatesPrecisionAttribute()
    {
      return strip_tags($this->location_first->first()->location->precision);
    }


    //IDENTIFICATION -similarly, vouchers identification is that of the inidividual
    public function identification()
    {
      return $this->hasOneThrough(
                    'App\Models\Identification',
                    'App\Models\Individual',
                    'identification_individual_id', // Foreign key on individual table...
                    'object_id', // Foreign key on identification table...
                    'individual_id', // Local key on voucher table...
                    'id' // Local key on individual table...
                    )->where('object_type', 'App\Models\Individual');
    }



    public function newQuery($excludeDeleted = true)
    {
        // This uses the explicit list to avoid conflict due to global scope
        return parent::newQuery($excludeDeleted)->addSelect(
            'vouchers.id',
            'vouchers.number',
            'vouchers.individual_id',
            'vouchers.dataset_id',
            'vouchers.date',
            'vouchers.notes',
            'vouchers.biocollection_id',
            'vouchers.biocollection_type',
            'vouchers.biocollection_number',
            DB::raw('odb_voucher_fullname(vouchers.id,vouchers.number,vouchers.individual_id,vouchers.biocollection_id,vouchers.biocollection_number) as fullname')
        );
    }

    public function measurements()
    {
        return $this->morphMany(Measurement::class, 'measured');
    }




    /*  VOUCHER COLLECTOR AND UNIQUE IDENTIFIERS
      ** vouchers may have it own collector (SELF)
      ** OR is the same of that of the individual (INDIVIDUAL COLLECTOR AND NUMBER)
    */
      //GET ONLY - RELATIONSHIPS
      public function collectors()
      {
          return $this->morphMany(Collector::class, 'object')->with('person');
      }

      public function collector_main()
      {
          return $this->collectors()->where('main',1);
      }

    /* DATASET */
    public function dataset()
    {
        return $this->belongsTo(Dataset::class);
    }
    public function getDatasetNameAttribute()
    {
        if ($this->dataset) {
            return $this->dataset->name;
        }
        return 'Unknown dataset';
    }

    /* BIOCOLLECTION RELATIONS */
      public function biocollection()
      {
        return $this->belongsTo(Biocollection::class);
      }



      public function getIsTypeAttribute()
      {
        if ($this->biocollection_type>0) {
          return Lang::get('levels.vouchertype.1');
        } else {
          return Lang::get('levels.vouchertype.0');
        }
      }


      public function getOrganismIDAttribute()
      {
        return $this->individual->organismID;
      }

    /*
      * MEDIA FILES
      * image vouchers are linked to individuals with voucher_id added to
      * custom_properties
    */
    public function media()
    {
        $searchStr = 'voucher_id":'.$this->id;
        return $this->individual
          ->media()
          ->where('custom_properties','like','%'.$searchStr.'%');
    }



    public static function getTableName()
    {
        return (new self())->getTable();
    }



    /* NAME Used in ActivityDataTable change display */
    public function identifiableName()
    {
        return $this->fullname;
    }

    /*dwc terms */
    public function getOccurrenceIDAttribute()
    {
      return $this->fullname;
    }

    public function getCollectionCodeAttribute()
    {
      return $this->biocollection->acronym;
    }
    public function getCatalogNumberAttribute()
    {
      return $this->biocollection_number;
    }
    public function getRecordedByMainAttribute()
    {
        if ($this->collectors()->count()) {
            return $this->collector_main()->first()->person->abbreviation;
        }
        return $this->individual->recordedByMain;
    }

    public function getRecordNumberAttribute()
    {
      if ($this->collectors()->count()) {
          return $this->number;
      }
      return $this->individual->tag;
    }


    //full list of collectors for display and exports, pipe delimited
    public function getRecordedByAttribute()
    {
        if ($this->collectors()->count()) {
            $collectors = $this->collectors();
            $persons = $collectors->cursor()->map(function($q) { return $q->person->abbreviation;})->toArray();
            $persons = implode(' | ',$persons);
        } else {
            $persons = $this->individual->recordedBy;
        }
        return $persons;
    }


    public function getRecordedDateAttribute()
    {
      if ($this->collectors()->count()) {
        return $this->formatDate;
      } else {
        return $this->individual->formatDate;
      }
    }

    /* IDENTIFICATION ATTRIBUTES ASSESSORS */
    public function getScientificNameAttribute()
    {
        if ($this->identification and $this->identification->taxon) {
            return $this->identification->taxon->fullname;
        }
        return Lang::get('messages.unidentified');
    }
    public function getFamilyAttribute()
    {
        if ($this->identification) {
            return $this->identification->taxon->family;
        }
        return Lang::get('messages.unidentified');
    }
    public function getGenusAttribute()
    {
        if ($this->identification) {
            return $this->identification->taxon->genus;
        }
        return Lang::get('messages.unidentified');
    }
    public function getIdentificationQualifierAttribute()
    {
      $modifier = null;
      if ($this->identification) {
        $modifier = $this->identification->modifier;
        if ($modifier>0) {
          $modifier = Lang::get('levels.modifier.'.$modifier);
        }
      }
      return $modifier;
    }
    public function getScientificNameWithAuthorAttribute()
    {
        if ($this->identification) {
            return $this->identification->taxon->getFullnameWithAuthor();
        }
        return Lang::get('messages.unidentified');
    }
    public function getDateIdentifiedAttribute()
    {
      if ($this->identification) {
          return $this->identification->date;
      }
      return null;
    }
    public function getIdentifiedByAttribute()
    {
      if ($this->identification) {
          return $this->identification->person->abbreviation;
      }
      return Lang::get('messages.unidentified');
    }
    public function getIdentificationRemarksAttribute()
    {
      if ($this->identification) {
          $text = "";
          if ($this->identification->biocollection_id) {
            $text = Lang::get('messages.identification_based_on')." ".Lang::get('messages.voucher')." #".$this->identification->biocollection_reference." @".$this->identification->biocollection->acronym.". ";
          }
          return $text.$this->identification->notes;
      }
      return "";
    }
    public function getLocationNameAttribute()
    {
      return $this->location_first->first()->location_name;
    }

    public function getHigherGeographyAttribute()
    {
      return $this->individual->higherGeography;
    }

    public function getDecimalLongitudeAttribute()
    {
      return (float) $this->individual->decimalLongitude;
    }
    public function getDecimalLatitudeAttribute()
    {
      return (float) $this->individual->decimalLatitude;
    }
    public function getGeoreferenceRemarksAttribute()
    {
      return $this->individual->georeferenceRemarks;
    }


    public function getOccurrenceRemarksAttribute()
    {
      $notes = [];
      if ($this->notes) {
        $notes[]= $this->notes;
      }
      if (isset($this->individual->notes)) {
        $notes[] = $this->individual->notes;
      }
      return implode(" | ",$notes);
    }

    public function getTypeStatusAttribute()
    {
      return Lang::get('levels.vouchertype.'.$this->biocollection_type);
    }

    public function getAssociatedMediaAttribute()
    {
      //return null;
      $media = $this->media;
      if ($media->count()) {
      $result = $media->map(function($v){
                return url('media/'.$v->id);
      })->toArray();
      return implode(" | ",$result);
      }
      return null;
    }
    public function getAccessRightsAttribute()
    {
      if ($this->dataset) {
        return $this->dataset->accessRights;
      }
      return "Open access";
    }
    public function getBibliographicCitationAttribute()
    {
      if ($this->dataset) {
        return $this->dataset->bibliographicCitation;
      }
      return null;
    }
    public function getBasisOfRecordAttribute()
    {
      return 'PreservedSpecimens';
    }
    public function getLicenseAttribute()
    {
      if ($this->dataset) {
        return $this->dataset->dwcLicense;
      }
      return null;
    }

}
