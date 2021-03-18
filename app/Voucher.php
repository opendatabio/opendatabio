<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Auth;
use Lang;
use DB;
use App\IndividualLocation;
use App\Location;
use App\Project;

use Spatie\Activitylog\Traits\LogsActivity;

class Voucher extends Model
{
    use IncompleteDate, LogsActivity;

    protected $fillable = ['individual_id', 'biocollection_id', 'biocollection_type', 'number', 'date', 'notes', 'project_id','biocollection_number'];

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

        static::addGlobalScope('projectScope', function (Builder $builder) {
            // first, the easy cases. No logged in user?
            if (is_null(Auth::user())) {
                return $builder->whereRaw('vouchers.id IN
(SELECT p1.id FROM vouchers AS p1
JOIN projects ON (projects.id = p1.project_id)
WHERE projects.privacy = 2)');
            }
            // superadmins see everything
            if (User::ADMIN == Auth::user()->access_level) {
                return $builder;
            }
            // now the complex case: the regular user
            return $builder->whereRaw('vouchers.id IN
(SELECT p1.id FROM vouchers AS p1
JOIN projects ON (projects.id = p1.project_id)
WHERE projects.privacy > 0
UNION
SELECT p1.id FROM vouchers AS p1
JOIN projects ON (projects.id = p1.project_id)
JOIN project_user ON (projects.id = project_user.project_id)
WHERE projects.privacy = 0 AND project_user.user_id = '.Auth::user()->id.'
)');
        });
    }


    //get individual the voucher belongs to
    public function individual()
    {
      return $this->belongsTo(Individual::class);
    }


    //voucher location is the location of the individual it relates
    //this direct functions are to GET data only, not to set the location for a voucher
    public function locations()
    {
      return $this->hasMany(
          IndividualLocation::class,
          'individual_id',
          'individual_id'
        );
      /*
      return $this->hasManyThrough(
                  Location::class,
                  IndividualLocation::class,
                  'individual_id', // Foreign key on the individual_location table...
                  'id', // Foreign key on the locations table...
                  'individual_id', // Local key on the vouchers table...
                  'location_id' // Local key on the individual_location table...
            );
        */

    }
    public function location_first()
    {
      return $this->locations()->where('first',1);
      /*
      return $this->locations()->addSelect([
            'date_time',
            'individual_location.notes as locationNote',
            'individual_location.altitude as locationAltitude',
            'odb_ind_relativePosition(relative_position)',
            ])
            ->where('individual_location.first',1);
      */
    }

    public function getLongitudeAttribute()
    {
      return (float) $this->locationWithGeom->centroid["x"];
    }
    public function getLatitudeAttribute( )
    {
      return (float) $this->locationWithGeom->centroid["y"];
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

    public function getLocationNameAttribute()
    {
      return $this->location_first->first()->location_name;
    }

    public function getLocationFullnameAttribute()
    {
      return $this->location_first->first()->location_fullname;
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
                    'App\Identification',
                    'App\Individual',
                    'identification_individual_id', // Foreign key on individual table...
                    'object_id', // Foreign key on identification table...
                    'individual_id', // Local key on voucher table...
                    'id' // Local key on individual table...
                    )->where('object_type', 'App\Individual');
    }

    /* IDENTIFICATION ATTRIBUTES ASSESSORS */
    public function getTaxonNameAttribute()
    {
        if ($this->identification) {
            return $this->identification->taxon->fullname;
        }
        return Lang::get('messages.unidentified');
    }

    public function getTaxonNameModifierAttribute()
    {
      $modifier = null;
      if ($this->identification) {
        $modifier = $this->identification->modifier;
        if ($modifier>0) {
          $modifier = Lang::get('levels.modifier.'.$modifier);
          //$modifier = " (".$modifier.")";
        } else {
          $modifier = null;
        }
      }
      return $modifier;
    }


    public function getTaxonNameWithAuthorAttribute()
    {
        if ($this->identification) {
            return $this->identification->taxon->getFullnameWithAuthor();
        }
        return Lang::get('messages.unidentified');
    }

    public function getTaxonFamilyAttribute()
    {
      if ($this->identification) {
          return $this->identification->taxon->family;
      }
      return Lang::get('messages.unidentified');
    }

    public function getIdentificationDateAttribute()
    {
      if ($this->identification) {
          return $this->identification->formatDate;
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

    public function getIdentificationNotesAttribute()
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


    public function newQuery($excludeDeleted = true)
    {
        // This uses the explicit list to avoid conflict due to global scope
        return parent::newQuery($excludeDeleted)->addSelect(
            'vouchers.id',
            'vouchers.number',
            'vouchers.individual_id',
            'vouchers.project_id',
            'vouchers.date',
            'vouchers.notes',
            'vouchers.biocollection_id',
            'vouchers.biocollection_type',
            'vouchers.biocollection_number',
            DB::raw('odb_voucher_fullname(vouchers.id,vouchers.number,vouchers.individual_id,vouchers.biocollection_id) as fullname')
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

      //ASSESSORS OR MUTATORS - DEFINED AS ATTRIBUTES
        //1. fullname attribute depends on type of collector and for voucher is the taxonomy museum standard of collector+collector_number
        /*public function getFullNameAttribute()
        {
            return $this->FullName;
            if ($this->collector_main()->count()) {
                return $this->main_collector.' - '.$this->number;
            }
            return $this->main_collector." - ".$this->individual->tag;

        }
        */
        //voucher collector_main attribute depends on type of collector
        public function getMainCollectorAttribute()
        {
            if ($this->collectors()->count()) {
                return $this->collector_main()->first()->person->abbreviation;
            }
            return $this->individual->main_collector;
        }

        public function getCollectorNumberAttribute()
        {
          if ($this->collectors()->count()) {
              return $this->number;
          }
          return $this->individual->tag;
        }


        //full list of collectors for display and exports, pipe delimited
        public function getAllCollectorsAttribute()
        {
            if ($this->collectors()->count()) {
                $collectors = $this->collectors();
                $persons = $collectors->cursor()->map(function($q) { return $q->person->abbreviation;})->toArray();
                $persons = implode(' | ',$persons);
            } else {
                $persons = $this->individual->all_collectors;
            }
            return $persons;
        }


        public function getCollectionDateAttribute()
        {
          if ($this->collectors()->count()) {
            return $this->formatDate;
          } else {
            return $this->individual->formatDate;
          }
        }


    /* PROJECT */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function getProjectNameAttribute()
    {
        if ($this->project) {
            return $this->project->name;
        }
        return 'Unknown project';
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

      public function getBiocollectionAcronymAttribute()
      {
        return $this->biocollection->acronym;
      }

      /*
      public function getBiocollectionNumberAttribute()
      {
        if ($this->biocollection_number) {
          return $this->biocollection_number;
        }
        return "";
      }
      */

      public function getIndividualFullnameAttribute()
      {
        return $this->individual->fullname;
      }

    /* PICTURES */
    public function pictures()
    {
        return $this->morphMany(Picture::class, 'object');
    }


    /* NAME Used in ActivityDataTable change display */
    public function identifiableName()
    {
        return $this->fullname;
    }

}
