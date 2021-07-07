<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Baum\Node;
use DB;
use Lang;
use App\Models\Taxon;
use App\Models\Identification;
use App\Models\IndividualLocation;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Traits\LogsActivity;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Location extends Node implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    public $table = "locations";

    // The "special" adm levels
    const LEVEL_UC = 99;
    const LEVEL_PLOT = 100;
    const LEVEL_TRANSECT = 101;
    const LEVEL_POINT = 999;
    const LEVEL_SPECIAL = [
      self::LEVEL_UC,
      self::LEVEL_PLOT,
      self::LEVEL_POINT,
    ];
    // Valid geometries
    const GEOM_POINT = "Point";
    const GEOM_POLYGON = "Polygon";
    const GEOM_MULTIPOLYGON = "MultiPolygon";
    const VALID_GEOMETRIES = [
      self::GEOM_POINT,
      self::GEOM_POLYGON,
      self::GEOM_MULTIPOLYGON
    ];
    // "LineString","MultiLineString", "Polygon", "MultiPolygon"];

    protected $fillable = ['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y', 'startx', 'starty', 'parent_id','geojson'];
    protected $lat;
    protected $long;
    protected $geom_array = [];
    protected $isSimplified = false;
    protected $leftColumnName = 'lft';
    protected $rightColumnName = 'rgt';
    protected $depthColumnName = 'depth';

    //activity log trait (parent, uc and geometry are logged in controller)
    protected static $logName = 'location';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at','lft','rgt','depth','parent_id','uc_id','geom'];
    protected static $logAttributes = ['name','altitude','adm_level','datum','x','y','startx','starty','notes'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;


    public function getPrecisionAttribute()
    {
        if ($this->adm_level <= 99) {
            return Lang::get('levels.imprecise').': <strong>'.Lang::get('messages.centroidof').' '.Lang::get('levels.adm_level.'.$this->adm_level).'</strong>';
        }
        if ($this->adm_level == self::LEVEL_PLOT) {
            return Lang::get('levels.precise').': '.Lang::get('messages.centroidof').' '.Lang::get('levels.adm_level.'.$this->adm_level).'</strong>';
        }
        return Lang::get('levels.precise').':  <strong>'.Lang::get('levels.adm_level.'.$this->adm_level).'</strong>';
    }

    public function rawLink()
    {
        return "<a href='".url('locations/'.$this->id)."'>".htmlspecialchars($this->name).'</a>';
    }

    public function scopeNoWorld($query)
    {
        return $query->where('adm_level', '<>', -1);
    }

    // quick way to get the World object
    public static function world()
    {
        return self::where('adm_level', -1)->get()->first();
    }

    // for use when receiving this as part of a morph relation
    // TODO: maybe can be changed to get_class($p)?
    public function getTypenameAttribute()
    {
        return 'locations';
    }

    // for use in views/* selects
    public static function AdmLevels()
    {
        return array_merge(config('app.adm_levels'), [
            self::LEVEL_UC,
            self::LEVEL_PLOT,
            self::LEVEL_TRANSECT,
            self::LEVEL_POINT,
        ]);
    }

    public static function used_adm_levels()
    {
      //something wrong here, mysql complains aboud orderby in this statement and it does not has one . perhaps a baum related issue
      //return Location::noWorld()->select("adm_level")->distinct('adm_level')->pluck('adm_level')->toArray();
      return DB::table('locations')->distinct('adm_level')->pluck('adm_level')->toArray();
    }

    public static function scopeWithDistance($query, $geom)
    {
        // this query hangs if you attempt to run it on full geom objects, so we add
        // a "where" to make sure we're only calculating distance from small objects
        return $query->addSelect(DB::Raw("ST_Distance(geom, ST_GeomFromText('$geom')) as distance"))
            ->where('adm_level', '>', self::LEVEL_UC);
    }




    public function measurements()
    {
        return $this->morphMany("App\Models\Measurement", 'measured');
    }

    public function getLatLong()
    {
        // if the "cached" values are already set, do nothing
        if ($this->long or $this->lat) {
           return;
        }
        // for points, extract directly
        if ('POINT' == $this->geomType) {
            $point = substr($this->geom, 6, -1);
            $pos = strpos($point, ' ');
            $this->long = substr($point, 0, $pos);
            $this->lat = substr($point, $pos + 1);

            return;
        }
        // all others, extract from centroid
        $this->long = $this->centroid['x'];
        $this->lat = $this->centroid['y'];
    }

    public function getCentroidAttribute()
    {
        $centroid = $this->centroid_raw;
        if (empty($centroid)) {
            $centroid = $this->geom;
        }
        $point = substr($centroid, 6, -1);
        $pos = strpos($point, ' ');

        return ['x' => substr($point, 0, $pos), 'y' => substr($point, $pos + 1)];
    }
    public function getCentroidWKTAttribute()
    {
        $centroid = $this->centroid;
        return "POINT(".$centroid['x']." ".$centroid['y'].")";
    }


    public function getLatitudeSimpleAttribute()
    {
        $this->getLatLong();
        $letter = $this->lat > 0 ? 'N' : 'S';

        return $this->lat1.'&#176;'.$this->lat2.'\''.$this->lat3.'\'\' '.$letter;
    }

    public function getLongitudeSimpleAttribute()
    {
        $this->getLatLong();
        $letter = $this->long > 0 ? 'E' : 'W';

        return $this->long1.'&#176;'.$this->long2.'\''.$this->long3.'\'\' '.$letter;
    }

    public function getCoordinatesSimpleAttribute()
    {
        return '('.$this->latitudeSimple.', '.$this->longitudeSimple.')';
    }

    // query scope for conservation units
    public function scopeUcs($query)
    {
        return $query->where('adm_level', self::LEVEL_UC);
    }

    // query scope for all except conservation units
    public function scopeExceptUcs($query)
    {
        return $query->where('adm_level', '!=', self::LEVEL_UC);
    }

    public function getLevelNameAttribute()
    {
        return Lang::get('levels.adm_level.'.$this->adm_level);
    }

    public function getFullNameAttribute()
    {
        $str = '';
        foreach ($this->getAncestors() as $ancestor) {
            if ('-1' != $ancestor->adm_level) {
                $str .= $ancestor->name.' > ';
            }
        }

        return $str.$this->name;
    }

    public function getSearchableNameAttribute()
    {
        $name = $this->name;
        $parent = $this->getAncestors()->last()->name;
        if ($this->getAncestorsWithoutRoot()->count()>2) {
          $str = $name." << ".$parent." << ... << ".$this->getAncestorsWithoutRoot()->first()->name;
        } else {
          $str = $name." << ".$parent;
        }
        return $str;
    }

    public function getParentNameAttribute()
    {
      $ancestors = $this->getAncestors();
      if ($ancestors->count()) {
        $parent = $this->getAncestors()->last();
        return $parent->name;
      } else {
        return null;
      }
    }

    public function setGeomAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['geom'] = null;

            return;
        }
        // MariaDB returns 1 for invalid geoms from ST_IsEmpty ref: https://mariadb.com/kb/en/mariadb/st_isempty/
        $invalid = DB::select("SELECT ST_IsEmpty(ST_GeomFromText('$value')) as val");
        $invalid = count($invalid) ? $invalid[0]->val : 1;
        if ($invalid) {
            throw new \UnexpectedValueException('Invalid Geometry object: '.$value);
        }
        $this->attributes['geom'] = DB::raw("ST_GeomFromText('$value')");
    }

    public function getSimplifiedAttribute()
    {
        $this->getGeomArrayAttribute(); // force caching
        return $this->isSimplified;
    }

    protected function extractXY($point)
    {
        $point = str_replace(["(",")"],"", $point);
        $pos = strpos($point, ' ');
        return ['x' => substr($point, 0, $pos), 'y' => substr($point, $pos + 1)];
    }

    public function getGeomTypeAttribute()
    {
        if ('POINT' == substr($this->geom, 0, 5)) {
            return 'point';
        }
        if ('POLYGON' == substr($this->geom, 0, 7)) {
            return 'polygon';
        }
        if ('MULTIPOLYGON' == substr($this->geom, 0, 12)) {
            return 'multipolygon';
        }

        return 'unsupported';
    }

    protected function simplify($array, $factor)
    {
        $this->isSimplified = true;
        // TODO: provide a better simplification for this, such as Douglas-Peucker
        $result = array();
        $lapse = ceil(sizeof($array) / $factor);
        $i = 0;
        foreach ($array as $value) {
            if (0 == $i++ % $lapse) {
                $result[] = $value;
            }
        }

        return $result;
    }

    public function getGeomArrayAttribute()
    {
        // "cache" geom array to reduce overhead
        if (!empty($this->geom_array)) {
            return $this->geom_array;
        }

        if ('point' == $this->geomType) {
            return $this->extractXY(substr($this->geom, 6, -1));
        }
        if ('polygon' == $this->geomType) {
            $array = explode(',', substr($this->geom, 9, -2));
            foreach ($array as &$element) {
                $element = $this->extractXY($element);
            }
            if (sizeof($array) > 1500) {
                //$array = $this->simplify($array, 1500);
            }
            $this->geom_array = [$array];
        }
        if ('multipolygon' == $this->geomType) {
            $array = explode(')),((', substr($this->geom, 15, -3));
            foreach ($array as &$polygon) {
                $p_array = explode(',', $polygon);
                foreach ($p_array as &$element) {
                    $element = $this->extractXY($element);
                }
                $factor = 1500;
                if (sizeof($p_array) > $factor) {
                    //$p_array = $this->simplify($p_array, $factor);
                }
                $polygon = $p_array;
            }
            $this->geom_array = $array;
        }

        return $this->geom_array;
    }

    public static function detectParent($geom, $max_level, $parent_uc, $ignore_level=false,$parent_buffer=0)
    {
        // there can be plots inside plots
        if (self::LEVEL_PLOT == $max_level) {
            $max_level += 1;
        }

        //the $query
        if ($parent_buffer>0) {
          //will use the informed buffer as both:
          // (1) a simplify distance (due to memory for large putative large parents)
          // (2) and the buffer distance;
          // use buffer in parent detection only if noWorld location not detected without buffer.
          // simplify remove because it does not exist i mariadb
          //$query = 'ST_Within(ST_GeomFromText(?), ST_Buffer(ST_Simplify(geom,'.$parent_buffer.'),'.$parent_buffer.'))';
          $query = 'ST_Within(ST_GeomFromText(?), ST_Buffer(geom,'.$parent_buffer.'))';
        } else {
          $query = 'ST_Within(ST_GeomFromText(?), geom)';
        }


        //check which registered polygons (except World)
        //are possible parents (CONTAIN) of submitted location
        //order by adm_level to get the most inclusive first
        $possibles = self::whereRaw($query, [$geom])
            ->where('adm_level','!=',self::LEVEL_POINT)
            ->noWorld()
            ->orderBy('adm_level', 'desc');

        //if looking for an UC parent
        if ($parent_uc) {
            $possibles = $possibles->where('adm_level', '=', self::LEVEL_UC);
        } else {
            // only looks for NON-UCs with level smaller
            // than informed for location
            $possibles = $possibles->where('adm_level', '!=', self::LEVEL_UC);
            if (!$ignore_level) {
                $possibles = $possibles->where('adm_level', '<', $max_level);
            }
        }
        //$possibles = $possibles->cursor();

        //if found return the greatest adm_level location found
        if ($possibles->count()) {
            return $possibles->first();
        }

        return null;
    }

    public function setGeomFromParts($values)
    {
        $geom = self::geomFromParts($values);
        $this->attributes['geom'] = DB::raw("ST_GeomFromText('$geom')");
    }

    public static function geomFromParts($values)
    {
        $lat = $values['lat1'] + $values['lat2'] / 60 + $values['lat3'] / 3600;
        $long = $values['long1'] + $values['long2'] / 60 + $values['long3'] / 3600;
        if (0 == $values['longO']) {
            $long = abs($long)*(-1);
        }
        if (0 == $values['latO']) {
            $lat = abs($lat)*(-1);
        }

        return 'POINT('.$long.' '.$lat.')';
    }

    public function uc()
    {
        return $this->belongsTo('App\Models\Location', 'uc_id');
    }





    //individuals through individual_location pivot
    public function individuals()
    {
      return $this->hasManyThrough(
                    'App\Models\Individual',
                    'App\Models\IndividualLocation',
                    'location_id', // Foreign key on individual_location table...
                    'id', // Foreign key on individual table...
                    'id', // Local key on location table...
                    'individual_id' // Local key on individual_location table...
                    );
    }


    public function getAllIndividuals()
    {
      return  Individual::whereHas('locations',function($location) {
        $location->where('lft',">",$this->lft)->where('rgt',"<",$this->rgt);
      });
    }

    public function getAllProjects()
    {
      return  Project::whereHas('individuals',function($individual) {
        $individual->whereHas('locations',function($location) {
          $location->where('lft',">",$this->lft)->where('rgt',"<",$this->rgt);
        }); });
    }


    public function childrenByLevel($level)
    {
      return  DB::table('locations')->where('lft',">=",$this->lft)->where('lft',"<=",$this->rgt)->where('adm_level',$level)->cursor();
    }

    //vouchers through individual_location
    public function vouchers()
    {
      return $this->hasManyThrough(
                    'App\Models\Voucher',
                    'App\Models\IndividualLocation',
                    'location_id', // Foreign key on individual_location table...
                    'individual_id', // Foreign key on individual table...
                    'id', // Local key on location table...
                    'individual_id' // Local key on individual_location table...
                    );
    }


    public function identifications( )
    {
      return $this->hasManyThrough(
                    'App\Models\Identification',
                    'App\Models\IndividualLocation',
                    'location_id', // Foreign key on individual_location table...
                    'object_id', // Foreign key on individual table...
                    'id', // Local key on location table...
                    'individual_id' // Local key on individual_location table...
                    )->where('object_type','App\Models\Individual');
    }


    // getter method for parts of latitude/longitude
    public function getLat1Attribute()
    {
        $this->getLatLong();

        return floor(abs($this->lat));
    }

    public function getLat2Attribute()
    {
        $this->getLatLong();

        return floor(60 * (abs($this->lat) - $this->lat1));
    }

    public function getLat3Attribute()
    {
        $this->getLatLong();

        return floor(60 * (60 * abs($this->lat) - 60 * $this->lat1 - $this->lat2));
    }

    public function getLatOAttribute()
    {
        $this->getLatLong();

        return $this->lat > 0;
    }

    public function getLong1Attribute()
    {
        $this->getLatLong();

        return floor(abs($this->long));
    }

    public function getLong2Attribute()
    {
        $this->getLatLong();

        return floor(60 * (abs($this->long) - $this->long1));
    }

    public function getLong3Attribute()
    {
        $this->getLatLong();

        return floor(60 * (60 * abs($this->long) - 60 * $this->long1 - $this->long2));
    }

    public function getLongOAttribute()
    {
        $this->getLatLong();

        return $this->long > 0;
    }

    public function scopeWithoutGeom($query)
    {
        return $query->addSelect(
            DB::raw('id,name,parent_id,lft,rgt,depth,altitude,adm_level,datum,uc_id,notes,x,y,startx,starty,created_at,updated_at')
        );
    }

    public function scopeWithGeom($query)
    {
        return $query->addSelect(
            DB::raw('name'),
            DB::raw('ST_AsText(geom) as geom'),
            //DB::raw('IF(adm_level<999,ST_Area(geom),null) as area'),
            DB::raw("IF(ST_GeometryType(geom) like '%Polygon%', ST_Area(geom), null) as area"),
            DB::raw("IF(ST_GeometryType(geom) like '%Point%', ST_AsText(geom),ST_AsText(ST_Centroid(geom))) as centroid_raw")
            //DB::raw('IF(adm_level<999,ST_Area(geom),null) as area'),
            //DB::raw('IF(adm_level=999,ST_AsText(geom),ST_AsText(ST_Centroid(geom))) as centroid_raw')
        );
    }


    public function mediaDescendantsAndSelf()
    {
        $ids = $this->getDescendantsAndSelf()->pluck('id')->toArray();
        return Media::whereIn('model_id',$ids)->where('model_type','=','App\Models\Location');
    }

    /* FUNCTIONS TO INTERACT WITH THE COUNT MODEL */
    public function summary_counts()
    {
        return $this->morphMany("App\Models\Summary", 'object');
    }

    public function summary_scopes()
    {
        return $this->morphMany("App\Models\Summary", 'scope');
    }


    public function getCount($scope="all",$scope_id=null,$target='individuals')
    {
      if (IndividualLocation::count()==0) {
        return 0;
      }

      $query = $this->summary_counts()->where('scope_type',"=",$scope)->where('target',"=",$target);
      if (null !== $scope_id) {
        $query = $query->where('scope_id',"=",$scope_id);
      } else {
        $query = $query->whereNull('scope_id');
      }
      if ($query->count()) {
        return $query->first()->value;
      }
      //get a fresh count
      if ($target=="individuals") {
        return $this->individualsCount($scope,$scope_id);
      }
      //get a fresh count
      if ($target=="measurements") {
        return $this->measurementsCount($scope,$scope_id);
      }
      //get a fresh count
      if ($target=="vouchers") {
        return $this->vouchersCount($scope,$scope_id);
      }
      if ($target=="taxons") {
        return $this->taxonsCount($scope,$scope_id);
      }
      if ($target=="media") {
        return $this->all_media_count();
      }
      return 0;
    }



    /* functions to generate counts */
    public function individualsCount($scope='all',$scope_id=null)
    {
      if ('projects' == $scope and $scope_id>0) {
          return array_sum($this->getDescendantsAndSelf()->loadCount(['individuals' => function ($individual) use($scope_id ) {
              $individual->withoutGlobalScopes()->where('project_id',$scope_id);
            }])->pluck('individuals_count')->toArray());
      }
      if ('datasets' == $scope and $scope_id>0) {
          $query = $this->getDescendantsAndSelf()->loadCount(['individuals' => function ($individual)  use($scope_id) {
            $individual->withoutGlobalScopes()->whereHas('measurements',function($measurement) use($scope_id) {
              $measurement->withoutGlobalScopes()->where('dataset_id','=',$scope_id);
            });}]);
          return array_sum($query->pluck('individuals_count')->toArray());
      }
      return array_sum($this->getDescendantsAndSelf()->loadCount(['individuals' => function ($individual) {
            $individual->withoutGlobalScopes();
        }])->pluck('individuals_count')->toArray());
    }


    public function vouchersCount($scope='all',$scope_id=null)
    {
      if ('projects' == $scope and $scope_id>0) {
        $query = $this->getDescendantsAndSelf()->loadCount(
            ['vouchers' => function ($voucher)  use($scope_id) {
                $voucher->withoutGlobalScopes()->where('vouchers.project_id','=',$scope_id); } ]);
      }
      if ('datasets' == $scope and $scope_id>0) {
        $query = $this->getDescendantsAndSelf()->loadCount(
            ['vouchers' => function ($voucher)  use($scope_id) {
                $voucher->withoutGlobalScopes()->whereHas('measurements',function($measurement) use($scope_id) {
                  $measurement->withoutGlobalScopes()->where('dataset_id','=',$scope_id);
                }); }]);
      }
      if (!isset($query) or null == $scope_id) {
      $query = $this->getDescendantsAndSelf()->loadCount(
          ['vouchers' => function ($voucher) {
              $voucher->withoutGlobalScopes(); } ]);
      }
      $count1 = array_sum($query->pluck('vouchers_count')->toArray());
      return $count1;
    }

    //measurement should count only LOCATION measurements, including descendants (not like taxon as descendant has not a relationship with parent like phylogenetic relationships), so should not count measurements for individuals and vouchers at locations.
    //they also have no relationship with project, so project scope makes no sense for locations
    public function measurementsCount($scope='all',$scope_id=null)
    {
      $query = $this->getDescendantsAndSelf()->loadCount('measurements');
      if ($scope='datasets' and $scope_id>0) {
        $query = $this->getDescendantsAndSelf()->loadCount(
            ['measurements' => function ($query)  use($scope_id) {
                $query->withoutGlobalScopes()->where('dataset_id','=',$scope_id); } ]);
      }
      return array_sum($query->pluck('measurements_count')->toArray());
    }




    public function taxonsCount($scope=null,$scope_id=null)
    {
      $ids =  array_unique($this->summary_scopes()->distinct('object_id')->whereHasMorph('object',['App\Models\Taxon'],function($object) { $object->where('level','>',200);})->where('object_type','App\Models\Taxon')->cursor()->pluck('object_id')->toArray());
      if ('projects' == $scope and $scope_id>0) {
        return  Summary::distinct('object_id')->whereIn('object_id',$ids)->where('object_type','App\Models\Taxon')->where('scope_id',$scope_id)->where('scope_type','App\Models\Project')->count();
      }
      if ('datasets' == $scope and $scope_id>0) {
        return  Summary::distinct('object_id')->whereIn('object_id',$ids)->where('object_type','App\Models\Taxon')->where('scope_id',$scope_id)->where('scope_type','App\Models\Dataset')->count();
      }
      return count(array_unique($ids));
    }

    public function taxonsIDS()
    {
      $taxons  = $this->getDescendantsAndSelf()->map(function($location) {
                  $listp = $location->identifications()->with('taxon')->withoutGlobalScopes()->whereHas('taxon',function($taxon) { $taxon->where('level',">=",Taxon::getRank('species'));})->distinct('taxon_id')->pluck('taxon_id')->toArray();
                  return array_unique($listp);
                })->toArray();
      return array_unique(Arr::flatten($taxons));
    }



    /*  MEDIA RELATED FUNCTIONS */

    /*  all media count
    * for location linked media only, including descendants
    */
    public function all_media_count()
    {
      $query = $this->getDescendantsAndSelf()->loadCount('media');
      return array_sum($query->pluck('media_count')->toArray());
    }



    /* register media modifications used by Spatie media-library trait */
    public function registerMediaConversions(BaseMedia $media = null): void
    {

        $this->addMediaConversion('thumb')
            ->fit('crop', 200, 200)
            ->performOnCollections('images');

        // TODO: this is not working for some reason
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->extractVideoFrameAtSecond(5)
            ->performOnCollections('videos');
    }

    /* helper  to get table name from model instance */
    public static function getTableName()
    {
        return (new self())->getTable();
    }



}
