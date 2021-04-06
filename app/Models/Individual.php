<?php
/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

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

    protected $fillable = ['tag', 'date', 'notes','project_id','identification_individual_id'];

    protected $appends = ['format_date','location_parent','taxon_name','taxon_family','project_name'];

    //activity log trait
    protected static $logName = 'individual';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at','notes'];
    protected static $logAttributes = ['tag', 'project_id','date','identification_individual_id'];
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
        static::addGlobalScope('projectScope', function (Builder $builder) {
            // first, the easy cases. No logged in user? can access only public (privacy 2)
            if (is_null(Auth::user())) {
                return $builder->whereRaw('individuals.project_id IN (SELECT id FROM projects WHERE projects.privacy = 2)');
            }
            // superadmins see everything
            if (User::ADMIN == Auth::user()->access_level) {
                return $builder;
            }
            // now the complex case: the regular user see any registered or public or those having specific authorization
            return $builder->whereRaw('individuals.id IN (SELECT individuals.id FROM individuals JOIN projects ON projects.id=individuals.project_id JOIN project_user ON project_user.project_id=projects.id WHERE projects.privacy>0 OR project_user.user_id='.Auth::user()->id.')');
        });
    }

    /* FULLNAME THIS SHOULD BE CONVERTED TO A mysql procedure, facilitation queries */
    /*
    //converted
    public function getFullnameAttribute()
    {
        $collector = preg_replace('[,| |\\.|-|_]','',StripAccents::strip( (string) $this->collector_main->first()->person->abbreviation ));
        //$collector = $this->collector_main->first()->person->abbreviation;
        if ($this->locations->count()) {
            return $this->tag.' - '.$collector." - ".$this->locations->first()->name;
        }
        return 'Unknown location-'.$this->tag;
    }
    */

    /* LOCATIONS */
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

    public function getLocationParentAttribute()
    {
        if ($this->locations->count()) {
          return $this->locations->last()->parentName;
        }
        return 'Unknown parent location';
    }

    public function getLocationFullnameAttribute()
    {
      return $this->locations->last()->fullname;
    }


    // with access to the location geom field
    public function getLocationWithGeomAttribute()
    {
        if ($this->locations->count()) {
          $id = $this->locations->last()->id;
          return Location::withGeom()->addSelect('id', 'name')->find($id);
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

    public function getXInParentLocationAttribute()
    {
       $location = $this->locations->last();
       if ($location->adm_level == Location::LEVEL_PLOT) {
          $x = (float) $this->x;
          $x = $x + ($this->locations->last()->startx);
          return $x;
       }
       return null;
    }

    public function getYInParentLocationAttribute()
    {
      $location = $this->locations->last();
      if ($location->adm_level == Location::LEVEL_PLOT) {
        $y = (float) $this->y;
        return $y + ($this->locations->last()->starty);
      }
      return null;
    }


    public function getLocationLongitudeAttribute()
    {
      return (float) $this->locationWithGeom->centroid["x"];
    }
    public function getLocationLatitudeAttribute( )
    {
      return (float) $this->locationWithGeom->centroid["y"];
    }
    public function getCoordinatesPrecisionAttribute()
    {
      return strip_tags($this->locations->last()->precision);
    }

    /* END INDIVIDUAL LOCATION */


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

    public function getMainCollectorAttribute()
    {
      return $this->collector_main()->first()->person->abbreviation;
    }

    public function getAllCollectorsAttribute()
    {
        $persons = $this->collectors->map(function($person) { return $person->person->abbreviation;})->toArray();
        $persons = implode(' | ',$persons);
        return $persons;
    }

    public function newQuery($excludeDeleted = true)
    {
        // This uses the explicit list to avoid conflict due to global scope
        // maybe check http://lyften.com/journal/user-settings-using-laravel-5-eloquent-global-scopes.html ???
        return parent::newQuery($excludeDeleted)->select(
            'individuals.id',
            'individuals.tag',
            'individuals.project_id',
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

    public function getTaxonNameAttribute()
    {
        if ($this->identification and $this->identification->taxon) {
            return $this->identification->taxon->fullname;
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

    public function getTaxonNameWithAuthorAttribute()
    {
        if ($this->identification) {
            return $this->identification->taxon->getFullnameWithAuthor();
        }
        return Lang::get('messages.unidentified');
    }

    public function getIdentificationDateAttribute()
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

    public function getTaxonNameModifierAttribute()
    {
      $modifier = null;
      if ($this->identification) {
        $modifier = $this->identification->modifier;
        if ($modifier>0) {
          $modifier = Lang::get('levels.modifier.'.$modifier);
          $modifier = " (".$modifier.")";
        }
      }
      return $modifier;
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




}
