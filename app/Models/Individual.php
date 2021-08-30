<?php
/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;
use App\Models\Dataset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use CodeInc\StripAccents\StripAccents;
use DB;
use Auth;
use Lang;
use Spatie\Activitylog\Traits\LogsActivity;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Individual extends Model implements HasMedia
{
    use IncompleteDate, InteractsWithMedia, LogsActivity;


    // NOTICE regarding attributes!! relative_position is the name of the database column, so this should be called when writing to database
    // (ie, setRelativePosition), but this is read as position, so this should be called on read context

    protected $fillable = ['tag', 'date', 'notes','dataset_id','identification_individual_id'];

    //protected $appends = ['format_date','location_parentname','taxon_name','taxon_family','dataset_name'];
    protected $decimalLatitude;
    protected $decimalLongitude;
    protected $georeferenceRemarks;
    protected $appends = ['format_date'];

    //activity log trait
    protected static $logName = 'individual';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at','notes'];
    protected static $logAttributes = ['tag', 'dataset_id','date','identification_individual_id'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    //protected $casts = ['identification' => 'collection' ];// casting the JSON database column

    public function rawLink($addId = false)
    {
        $text = "<a href='".url('individuals/'.$this->id)."'>".htmlspecialchars($this->fullname).'</a>';
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
    // TODO: maybe can be changed to get_class($p)?
    public function getTypenameAttribute()
    {
        return 'individuals';
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('datasetScope', function (Builder $builder) {
            // first, the easy cases. No logged in user? can access only public (privacy 2)
            if (is_null(Auth::user())) {
                return $builder->whereRaw('((individuals.dataset_id IS NULL) OR individuals.dataset_id IN (SELECT id FROM datasets WHERE datasets.privacy >='.Dataset::PRIVACY_PUBLIC.'))');
            }
            // superadmins see everything
            if (User::ADMIN == Auth::user()->access_level) {
                return $builder;
            }
            // now the complex case: the regular user see any registered or public or those having specific authorization
            return $builder->whereRaw('( (individuals.dataset_id IS NULL) OR individuals.id IN (SELECT individuals.id FROM individuals JOIN datasets ON datasets.id=individuals.dataset_id JOIN dataset_user ON dataset_user.dataset_id=datasets.id WHERE (datasets.privacy >='.Dataset::PRIVACY_REGISTERED.') OR dataset_user.user_id='.Auth::user()->id.'))');
        });
    }

    public function locations()
    {
      return $this->belongsToMany(Location::class)->withPivot(['date_time','notes','altitude','relative_position','first'])->withTimestamps();
    }


    public function location_first()
    {
      return $this->locations()->where('first',1);
    }

    public function LocationDisplay()
    {
      $location = $this->locations->last()->fullname;
      $coordinates = $this->locationWithGeom->coordinatesSimple;
      $altitude = $this->locations->last()->pivot->altitude ? $this->locations->last()->pivot->altitude : $this->locations->last()->altitude;
      $altitude = ($altitude != "" and null != $altitude) ? "<br>".$altitude."m.a.s.l." : null;
      $txt = "";
      if ($this->locations->last()->adm_level == Location::LEVEL_POINT and $this->angle != null) {
        $txt = "<br>".Lang::get('messages.relative_position').": ".Lang::get('messages.angle')." = ".$this->angle."&#176 | ";
        $txt .= Lang::get('messages.distance')." = ".$this->distance."m<br>";
      } elseif (null != $this->x) {
        $txt = "<br>".Lang::get('messages.relative_position').": X = ".$this->x."m | Y = ".$this->y."m<br>";
      }
      return $location."<br>".$coordinates.$altitude.$txt;
    }

    public function getLocationNameAttribute()
    {
        if ($this->locations->count()) {
          return $this->locations->last()->name;
        }
        return 'Unknown location';
    }

    public function getLocationParentNameAttribute()
    {
        if ($this->locations->count()) {
          return $this->locations->last()->parentName;
        }
        return 'Unknown location';
    }

    public function getHigherGeographyAttribute()
    {
      if ($this->locations->count()) {
        return $this->locations->last()->higherGeography;
      }
      return 'Unknown location';
    }


    // with access to the location geom field
    public function getLocationWithGeomAttribute()
    {
        if ($this->locations->count()) {
          $id = $this->locations->last()->id;
          return Location::withGeom()->find($id);
        }
        return;
    }


    /*
    public function getLocationCentroidLatitudeAttribute() {
      if ($this->locations->count()) {
          return (float) $this->locationWithGeom->centroid["y"];
      }
      return null;
    }
    public function getLocationCentroidLongitudeAttribute() {
      if ($this->locations->count()) {
        return (float) $this->locationWithGeom->centroid["x"];
      }
      return null;
    }
    */


    /*
      WHEN LOCATION IS EITHER PLOT OR POINT GET RELATIVE POSITION IF PRESENT
      IS POINT, THEN $x = $angle and $y = $distance
    */
    /*
    public function setRelativePosition($x, $y = null)
    {
        if (is_null($x) and is_null($y)) {
            $this->attributes['relative_position'] = null;
            return;
        }

        $location_type = $this->locations->last()->adm_level;
        if (Location::LEVEL_POINT == $location_type) {
            $angle = $x * M_PI / 180;
            $distance = $y;
            // converts the angle and distance to x/y
            $x = $distance * cos($angle);
            $y = $distance * sin($angle);
        }

        // MariaDB returns 1 for invalid geoms from ST_IsEmpty ref: https://mariadb.com/kb/en/mariadb/st_isempty/
        $invalid = DB::select("SELECT ST_IsEmpty(GeomFromText('POINT($y $x)')) as val")[0]->val;
        if ($invalid) {
            throw new \UnexpectedValueException('Invalid Geometry object');
        }
        $this->attributes['relative_position'] = DB::raw("GeomFromText('POINT($y $x)')");
    }
    */

    // getters for the Relative Position
    public function getXAttribute()
    {
        if (null == $this->attributes['relativePosition']) {
          return null;
        }
        $point = substr($this->attributes['relativePosition'], 6, -1);
        $pos = strpos($point, ' ');

        return substr($point, $pos + 1);
    }

    public function getYAttribute()
    {
        if (null == $this->attributes['relativePosition']) {
          return null;
        }
        $point = substr($this->attributes['relativePosition'], 6, -1);
        $pos = strpos($point, ' ');

        return substr($point, 0, $pos);
    }

    public function getAngleAttribute()
    {
        $x = $this->x;
        if (null == $x) {
            return ;
        }
        $y = $this->y;
        $angle = (180 / M_PI) * atan2((float) $y, (float) $x);
        if ($angle<0) {
          $angle = $angle+360;
        }
        return round($angle,2);
    }

    public function getDistanceAttribute()
    {
        $x = $this->x;
        if (null == $x) {
            return ;
        }
        $y = $this->y;
        $distance = sqrt((float) $x * (float) $x + (float) $y * (float) $y);
        return round($distance,2);
    }

    public function getGxAttribute()
    {
       $location = $this->locations->last();
       if ($location->adm_level == Location::LEVEL_PLOT) {
          $x = (float) $this->x;
          $x = $x + ($this->locations->last()->startx);
          return $x;
       }
       return null;
    }

    public function getGyAttribute()
    {
      $location = $this->locations->last();
      if ($location->adm_level == Location::LEVEL_PLOT) {
        $y = (float) $this->y;
        return $y + ($this->locations->last()->starty);
      }
      return null;
    }

    public function getOrganismRemarksAttribute()
    {
      return $this->notes;
    }

    public function getDecimalLatitudeAttribute()
    {
      $this->getLatLong();
      return $this->decimalLatitude;
    }
    public function getDecimalLongitudeAttribute()
    {
      $this->getLatLong();
      return $this->decimalLongitude;
    }
    public function getGeoreferenceRemarksAttribute()
    {
      $this->getLatLong();
      return $this->georeferenceRemarks;
    }

    public function getLatLong()
    {
        // if the "cached" values are already set, do nothing
        if ($this->decimalLongitude or $this->decimalLatitude) {
           return;
        }
        $coords = $this->getGlobalPosition();
        $coords = Location::latlong_from_point($coords);
        // all others, extract from centroid
        $this->decimalLongitude = $coords[1];
        $this->decimalLatitude = $coords[0];
    }

    public function getCoordinatesPrecisionAttribute()
    {
      return strip_tags($this->locations->last()->precision);
    }

    /* get geographical coordinates of individual based on relative position */
    public function getGlobalPosition($geojson=false)
    {
      //if location is POINT
      $remarks = [];
      if ($this->locations->count()>1) {
        $remarks[] = "This individual has multiple locations. Coordinates refer to the last.";
      }
      $geomtype = $this->locationWithGeom->geomType;
      $bearing = null;
      $distance = null;
      $start_point = $this->locationWithGeom->geom;
      $individual_point = $this->locationWithGeom->centroid_raw;
      if ($this->locationWithGeom->adm_level == Location::LEVEL_POINT and $this->angle and $this->distance) {
         //map with bearing and distance (destination point)
        $remarks[] = "Decimal coordinates were calculated from POINT location using angle and distance attributes. They refer to destinationPoints";
        $bearing = $this->angle;
        $distance = $this->distance;
        $individual_point = Location::destination_point($start_point,$bearing,$distance);
      }
      //if location is PLOT
      if ($this->locationWithGeom->adm_level == Location::LEVEL_PLOT and $this->x and $this->y) {
        if ($geomtype == 'polygon') {
            $geom = $this->locationWithGeom->geom;
        } else {
            $geom = $this->locationWithGeom->plot_geometry;
        }
        $individual_point = Location::individual_in_plot($geom,$this->x,$this->y);
        $remarks[] = "Decimal coordinates were calculated using Plot geometry and the X and Y attributes (i.e. the relativePosition)";
      }
      //linestrings mapping
      if ($this->locationWithGeom->adm_level == Location::LEVEL_TRANSECT and $this->x) {
          if ($geomtype == 'linestring') {
            $start_point = Location::interpolate_on_transect($this->locationWithGeom->id,$this->x);
            if ($this->y) {
              $bearing = Location::bearing_at_postion_for_destination($this->locationWithGeom->id,$this->x,$this->y);
              $remarks[] = "Decimal coordinates were calculated using the X and Y attribute, the X interpolated on linestring from start, the Y at 90dgs (positive right; negative left-side) along the lineString at the X position from the first point";
            } else {
              $remarks[] = "Decimal coordinates were calculated using the X attribute, interpolated along the linestring from start.";
            }
          } else {
            //then location is a transect but defined as point (the start point)
            $start_point = Location::destination_point($this->locationWithGeom->geom,0,$this->x);
            if ($this->y < 0) {
              $remarks[] = "Decimal coordinates were calculated using the X and Y attributes, the X interpolated along the Transect (N oriented), the negative Y at 270dgs from interpolated X";
              $bearing = 270;
            } else {
              $remarks[] = "Decimal coordinates were calculated using the X and Y attributes, the X interpolated along the Transect (N oriented), the positive Y at 90dgs from interpolated X";
              $bearing = 90;
            }
          }
          if ($this->y) {
            $distance = abs($this->y);
            $individual_point = Location::destination_point($start_point,$bearing,$distance);
          } else {
            $individual_point = $start_point;
          }
      }
      if (count($remarks)) {
        $this->georeferenceRemarks = implode(' | ',$remarks);
      }
      if ($geojson) {
        $individual_point = DB::select("SELECT ST_ASGEOJSON(ST_GeomFromText('".$individual_point."')) as geojson")[0]->geojson;
      }
      return $individual_point;
    }



    /* END INDIVIDUAL LOCATION */


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


    /* VOUCHER */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    /* MEASUREMENTS */
    public function measurements()
    {
        return $this->morphMany(Measurement::class, 'measured');
    }
    public function getMeasurementsCountAttribute()
    {
        return $this->measurements()->withoutGlobalScopes()->count();
    }

    /* FIRST COLLECTOR IS ALWAYS THE MAIN IDENTIFIER */
    public function collectors()
    {
      return $this->morphMany(Collector::class, 'object')->with('person');
    }

    public function collector_main()
    {
        return $this->collectors()->where('main',1);
    }



    public function newQuery($excludeDeleted = true)
    {
        // This uses the explicit list to avoid conflict due to global scope
        // maybe check http://lyften.com/journal/user-settings-using-laravel-5-eloquent-global-scopes.html ???
        return parent::newQuery($excludeDeleted)->select(
            'individuals.id',
            'individuals.tag',
            'individuals.dataset_id',
            'individuals.date',
            'individuals.notes',
            'individuals.identification_individual_id',
            DB::raw('odb_ind_relativePosition(individuals.id) as relativePosition'),
            DB::raw('odb_ind_fullname(individuals.id,individuals.tag) as fullname')
        );
    }


    /* INDIVIDUAL TAXONOMY ONLY TO SET THE IDENTIFICATION*/
    public function identificationSet()
    {
        return $this->morphOne(Identification::class, 'object');
    }

    /* INDIVIDUAL TAXONOMY TO RETRIEVE THE IDENTIFICATION*/
    /* as some individuals may have the identification of another individual */
    public function identification()
    {
        return $this->morphOne(Identification::class,'identification_individual_id', 'object_type','object_id');
    }





    /* register media modifications */
    public function registerMediaConversions(BaseMedia $media = null): void
    {

        $this->addMediaConversion('thumb')
            ->fit('crop', 200, 200)
            ->performOnCollections('images');

        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->extractVideoFrameAtSecond(5)
            ->performOnCollections('videos');
    }

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    /* dwc terms */
    public function getOrganismIDAttribute()
    {
      return $this->fullname;
    }

    public function getRecordedByMainAttribute()
    {
      return $this->collector_main->first()->person->abbreviation;
    }

    public function getNormalizedAbbreviationAttribute()
    {
      return $this->collector_main->first()->person->normalizedAbbreviation;
    }

    public function getRecordedByAttribute()
    {
      $persons = $this->collectors->map(function($person) { return $person->person->abbreviation;})->toArray();
      $persons = implode(' | ',$persons);
      return $persons;
    }

    public function getRecordNumberAttribute()
    {
      return $this->tag;
    }
    public function getRecordedDateAttribute()
    {
      return $this->formatDate;
    }


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
    public function getScientificNameAuthorshipAttribute()
    {
        if ($this->identification) {
            return $this->identification->taxon->scientificNameAuthorship;
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
    public function getIdentificationQualifierAttribute()
    {
      $modifier = null;
      if ($this->identification) {
        $modifier = $this->identification->modifier;
        if ($modifier>0) {
          $modifier = Lang::get('levels.modifier.'.$modifier);
          //$modifier = " (".$modifier.")";
        }
      }
      return $modifier;
    }

    public function getTypeStatusAttribute()
    {
      $vouchers = $this->vouchers()->where('biocollection_type','>',0);
      if ($vouchers->count()) {
        $result = $vouchers->cursor()->map(function($v){
            $acronym = $v->biocollection->acronym;
            $reference = isset($v->biocollection_number) ? " #".$v->biocollection_number : null;
            $type = Lang::get('levels.vouchertype.'.$v->biocollection_type);
            return $type." @ ".$acronym.$reference;
        })->toArray();
        return implode(" | ",$result);
      }
      return null;
    }

    public function getAssociatedMediaAttribute()
    {
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

    // TODO: NOT FINISHED
    public function getPreviousIdentificationsAttribute()
    {
      /* list of concatenated previous name + identifer + date */
      return null;

      $query = Activity::select([
          'id',
          'log_name',
          'description',
          'properties',
          'subject_type',
          'subject_id',
          'causer_id',
          'created_at',
      ])->where('subject_type',self::class)->where('subject_id',$this->id)->where('description','like','identification updated');
      if ($query->count()) {
        $activities = $query->orderBy("created_at","DESC")->cursor();
        $activities->map(function($det){
          $old = $det->properties->old;
          $new = $det->properties->attributes;
          if ($old != null) {
              $taxon_id = $old['taxon_id'];
              if ($new['taxon_id']!=$taxon_id) {

              }
          }
        });

      }

      return null;
    }
    public function getBasisOfRecordAttribute()
    {
      return 'Organism';
    }
    public function getLicenseAttribute()
    {
      if ($this->dataset) {
        return $this->dataset->dwcLicense;
      }
      return null;
    }

}
