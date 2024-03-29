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
    const LEVEL_UC = 97;
    const LEVEL_TI = 98;
    const LEVEL_ENV = 99;
    const LEVEL_PLOT = 100;
    const LEVEL_TRANSECT = 101;
    const LEVEL_POINT = 999;
    const LEVEL_SPECIAL = [
      self::LEVEL_UC,
      self::LEVEL_PLOT,
      self::LEVEL_POINT,
      self::LEVEL_TI,
      self::LEVEL_ENV,
      self::LEVEL_TRANSECT,
    ];
    // Valid geometries
    const GEOM_POINT = "Point";
    const GEOM_POLYGON = "Polygon";
    const GEOM_MULTIPOLYGON = "MultiPolygon";
    const GEOM_LINESTRING = "LineString";
    const VALID_GEOMETRIES = [
      self::GEOM_POINT,
      self::GEOM_POLYGON,
      self::GEOM_MULTIPOLYGON,
      self::GEOM_LINESTRING
    ];
    // "LineString","MultiLineString", "Polygon", "MultiPolygon"];

    protected $fillable = ['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y', 'startx', 'starty', 'parent_id','geojson'];
    protected $lat;
    protected $long;
    protected $footprintWKT;
    protected $geom_array = [];
    protected $isSimplified = false;
    protected $leftColumnName = 'lft';
    protected $rightColumnName = 'rgt';
    protected $depthColumnName = 'depth';

    //activity log trait (parent, uc and geometry are logged in controller)
    protected static $logName = 'location';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at','lft','rgt','depth','parent_id','geom'];
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
            self::LEVEL_TI,
            self::LEVEL_ENV,
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
        return $query->addSelect(DB::Raw("id,name,ST_Distance(geom, ST_GeomFromText('$geom')) as distance"))
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
        $adm_level=$this->adm_level;
        $remarks = [];
        switch ($adm_level) {
          case  self::LEVEL_POINT:
            $remarks[] = "decimal coordinates are POINT locations, ~ GPS precision;";
            break;
          case  self::LEVEL_PLOT:
          case  self::LEVEL_TRANSECT:
            if ($this->geom_type=="point") {
              $remarks[] = "decimal coordinates are POINT locations, ~ GPS precision";
              $remarks[] = "footprintWKT geometry drawn from informed Start Point and location dimensions. A Northern (NE) orientation is assumed.";
            } else {
              $remarks[] = "decimal coordinates are the START POINT in footprintWKT geometry";
            }
            break;
          default:
            $remarks[] = "decimal coordinates are the CENTROID of the footprintWKT geometry";
            break;
        }

        if (count($remarks)) {
          $remarks = implode(" | ",$remarks);
        } else {
          $remarks = null;
        }
        $this->georeferenceRemarks = $remarks;
        $coords = self::latlong_from_point($this->geomFirstPoint);
        // all others, extract from centroid
        $this->long = $coords[1];
        $this->lat = $coords[0];
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
        return $this->centroid_raw;
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

    public function scopeRelated($query)
    {
        return $query->whereIn('adm_level',[self::LEVEL_UC,self::LEVEL_TI,self::LEVEL_ENV]);
    }

    // query scope for all except conservation units
    public function scopeExceptUcs($query)
    {
        return $query->whereNotIn('adm_level',[self::LEVEL_UC,self::LEVEL_TI,self::LEVEL_ENV]);
    }

    public function getLevelNameAttribute()
    {
        return Lang::get('levels.adm_level.'.$this->adm_level,[],'en');
    }


    public function getFullNameAttribute()
    {
      $path = $this->getAncestorsWithoutRoot()->pluck('name')->toArray();
      if (count($path)) {
        $path[] = $this->name;
        return implode(" > ",$path);
      }
      return $this->name;
    }

    public function getBelongsToAttribute()
    {
        $str = [$this->parent->name];
        foreach ($this->relatedLocations as $related) {
            $str[] = $related->relatedLocation->name;
        }

        return implode(" | ",$str);
    }

    public function getSearchableNameAttribute()
    {
        $name = $this->name;
        $parent = $this->parent->name;
        $str = $name." << ".$parent;
        return $str;
    }

    public function getParentNameAttribute()
    {
      return $this->parent->name;
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

    //public function getSimplifiedAttribute()
    //{
        //$this->getGeomArrayAttribute(); // force caching
        //return $this->isSimplified;
    //}

    //if the location is drawn according to dimensions informed over a POINT location
    public function getIsDrawnAttribute()
    {
      $adm_level = $this->adm_level;
      $geomtype = $this->geomType;
      if ($geomtype == "point" and ($adm_level == self::LEVEL_PLOT or $adm_level == self::LEVEL_TRANSECT)) {
        return true;
      }
      return false;
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
        if ('LINESTRING' == substr($this->geom, 0, 10)) {
            return 'linestring';
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

    public function getGeomOriginAttribute()
    {
        // "cache" geom array to reduce overhead
        if (!empty($this->geom_origin)) {
            return $this->geom_origin;
        }

        if ('point' == $this->geomType) {
            return $this->geom;
        }
        if ('polygon' == $this->geomType and $this->adm_level== self::LEVEL_PLOT) {
            $array = explode(',', substr($this->geom, 9, -2));
            $element = $this->extractXY($array[0]);
            return "POINT(".$element['x']." ".$element['x'].")";
        }
        if ("linestring" == $this->geomType) {
            return $this->start_point;
        }
        return $this->centroid_WKT;
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
            $first = $possibles->whereNotIn('adm_level', [self::LEVEL_UC,self::LEVEL_TI,self::LEVEL_ENV]);
            if (!$ignore_level) {
                $first = $first->where('adm_level', '<', $max_level);
            }
            if ($first->count()) {
                $possibles = $first;
            } elseif (!$ignore_level) {
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


    public function all_individual_ids()
    {
      $query = DB::select("SELECT DISTINCT tb.theid FROM ((SELECT DISTINCT individual_location.individual_id as theid FROM individual_location JOIN locations ON locations.id=individual_location.location_id WHERE locations.lft>=".$this->lft." AND rgt=<".$this->rgt.") UNION (SELECT DISTINCT individual_location.individual_id as theid FROM individual_location JOIN location_related ON location_related.location_id=individual_location.location_id JOIN locations ON locations.id=location_related.related_id WHERE locations.lft>='".$this->lft."' AND rgt=<'".$this->rgt."') AS tb)");
      return collect($query)->map(function($q) { return $q->theid;})->toArray();
    }

    public function getAllIndividuals()
    {
      return Individual::whereIn('id',$this->all_individual_ids());
      /*
      return  Individual::whereHas('locations',function($location) {
        $location->where('lft',">",$this->lft)->where('rgt',"<",$this->rgt);
      });
      */
    }

    public function getAllProjects()
    {
      return  Project::whereHas('individuals',function($individual) {
        $individual->whereHas('locations',function($location) {
          $location->where('lft',">=",$this->lft)->where('rgt',"=<",$this->rgt);
        }); });
    }


    public function childrenByLevel($level)
    {
      return  DB::table('locations')->where('lft',">=",$this->lft)->where('lft',"<=",$this->rgt)->where('adm_level',$level)->cursor();
    }


    public function childrenCount()
    {
      return  self::select("id")->where('lft',">",$this->lft)->where('lft',"<=",$this->rgt)->count();
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
            DB::raw('locations.id,locations.name,locations.parent_id,locations.lft,locations.rgt,locations.depth,locations.altitude,locations.adm_level,locations.datum,locations.notes,locations.x,locations.y,locations.startx,locations.starty,locations.created_at,locations.updated_at'),
            DB::raw("ST_AsText(ST_Centroid(geom)) as centroid_raw")
        );
    }

    public function scopeWithGeom($query)
    {
        return $query->addSelect(
            DB::raw('locations.id,locations.name, locations.adm_level, locations.parent_id, locations.x, locations.y,locations.lft,locations.rgt,locations.depth,locations.startx,locations.starty,locations.altitude,locations.notes,locations.created_at,locations.updated_at'),
            DB::raw('ST_AsText(geom) as geom'),
            DB::raw("IF(ST_GeometryType(geom) like '%Polygon%',ST_Area(geom), null) as area_raw"),
            DB::raw("ST_AsText(ST_Centroid(geom)) as centroid_raw"),
            DB::raw("IF(ST_GeometryType(geom) like '%linestring%',ST_AsText(ST_StartPoint(geom)), null) as start_point"),
        );
    }

    // TODO: implement geo pacakge to deal with transformations or implement equal area srid in mysql
    public function getAreaAttribute()
    {
      if (in_array($this->adm_level,[self::LEVEL_POINT,self::LEVEL_TRANSECT])) {
        return null;
      }
      if ($this->x and $this->y) {
        return $this->x*$this->y;
      }
      // converto meters
      return round($this->area_raw*11100000000,2);
    }


    /*public function scopeWithGeojson($query)
    {
        return $query->addSelect(
            DB::raw('id,name, adm_level, parent_id, x, y,lft,rgt,depth,startx,starty'),
            DB::raw("ST_ASGEOJSON(geom) as geomjson"),
            DB::raw("IF(ST_GeometryType(geom) like '%Polygon%', ST_Area(geom), null) as area_raw"),
            DB::raw("ST_AsText(ST_Centroid(geom)) as centroid_raw")
        );
    }
    */


    public function mediaDescendantsAndSelf()
    {
        #$ids = $this->getDescendantsAndSelf()->pluck('id')->toArray();
        $ids =  self::select("id")->where('lft',">=",$this->lft)->where('lft',"<=",$this->rgt)->pluck('id')->toArray();
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


    public function getCount($scope="all",$scopeId=null,$target='individuals')
    {
      if (IndividualLocation::count()==0) {
        return 0;
      }
      /*
      $query = $this->summary_counts()->where('scope_type',"=",$scope)->where('target',"=",$target);
      if (null !== $scopeId) {
        $query = $query->where('scope_id',"=",$scopeId);
      } else {
        $query = $query->whereNull('scope_id');
      }
      if ($query->count()) {
        return $query->first()->value;
      }
      */
      //get a fresh count
      if ($target=="individuals") {
        return $this->individualsCount($scope,$scopeId);
      }
      //get a fresh count
      if ($target=="measurements") {
        return $this->measurementsCount($scope,$scopeId);
      }
      //get a fresh count
      if ($target=="vouchers") {
        return $this->vouchersCount($scope,$scopeId);
      }
      if ($target=="taxons") {
        return $this->taxonsCount($scope,$scopeId);
      }
      if ($target=="media") {
        return $this->all_media_count();
      }
      return 0;
    }



    /* functions to generate counts */
    public function individualsCount($scope='all',$scopeId=null)
    {
      $sql = "SELECT DISTINCT(individual_location.individual_id) FROM individual_location,locations where individual_location.location_id=locations.id AND locations.lft>='".$this->lft."' AND locations.lft<='".$this->rgt."'";
      if ('projects' == $scope and $scopeId>0) {
        $dataset_ids = Dataset::where('project_id',$scopeId)->cursor()->pluck('datasets.id')->toArray();
        if (count($dataset_ids)==0) {
          return 0;
        }
        $dataset_ids = implode(",",$dataset_ids);
        $sql = "SELECT DISTINCT individuals.id FROM individuals,individual_location,locations where
        (individual_location.individual_id=individuals.id AND individual_location.location_id=locations.id AND locations.lft>='".$this->lft."' AND locations.lft<='".$this->rgt."')
        AND (
          individuals.dataset_id IN ($dataset_ids)
          OR individuals.id IN (SELECT measurements.measured_id FROM measurements WHERE measurements.measured_type LIKE '%individual%' AND measurements.dataset_id IN ($dataset_ids))
          OR individuals.id IN (SELECT media.model_id FROM media WHERE media.model_type LIKE '%individual%' AND media.dataset_id IN ('".$dataset_ids."'))
          OR individuals.id IN (SELECT vouchers.individual_id FROM vouchers LEFT JOIN measurements as vm ON vm.measured_id=vouchers.id WHERE (vm.measured_type LIKE '%voucher%' AND vm.dataset_id IN ($dataset_ids)) OR vouchers.dataset_id IN ($dataset_ids))
        )";
      }
      if ('datasets' == $scope and $scopeId>0) {
        $sql = "SELECT DISTINCT individuals.id FROM individuals,individual_location,locations where
        (individual_location.individual_id=individuals.id AND individual_location.location_id=locations.id AND locations.lft>='".$this->lft."' AND locations.lft<='".$this->rgt."')
        AND (
          individuals.dataset_id='".$scopeId."'
          OR individuals.id IN (SELECT measurements.measured_id FROM measurements WHERE measurements.measured_type LIKE '%individual%' AND measurements.dataset_id='".$scopeId."')
          OR individuals.id IN (SELECT media.model_id FROM media WHERE media.model_type LIKE '%individual%' AND media.dataset_id='".$scopeId."')
          OR individuals.id IN (SELECT vouchers.individual_id FROM vouchers LEFT JOIN measurements as vm ON vm.measured_id=vouchers.id WHERE (vm.measured_type LIKE '%voucher%' AND vm.dataset_id='".$scopeId."') OR vouchers.dataset_id='".$scopeId."')
        )";
      }
      $query = DB::select($sql);
      return count($query);
    }

    //count direct and indirect vouchers
    public function vouchersCount($scope='all',$scopeId=null)
    {
        $sql = "SELECT DISTINCT(vouchers.id) FROM vouchers,individual_location,locations where vouchers.individual_id=individual_location.individual_id AND individual_location.location_id=locations.id AND locations.lft>=".$this->lft." AND locations.lft<=".$this->rgt;
        if ('projects' == $scope and $scopeId>0) {
          $dataset_ids = Dataset::where('project_id',$scopeId)->cursor()->pluck('datasets.id')->toArray();
          if (count($dataset_ids)==0) {
            return 0;
          }
          $dataset_ids = implode(",",$dataset_ids);
          $sql = "SELECT DISTINCT vouchers.id FROM vouchers,individual_location,locations where
          (individual_location.individual_id=vouchers.individual_id AND individual_location.location_id=locations.id AND locations.lft>='".$this->lft."' AND locations.lft<='".$this->rgt."')
          AND (
            vouchers.dataset_id IN ($dataset_ids)
            OR vouchers.id IN (SELECT measurements.measured_id FROM measurements WHERE measurements.measured_type LIKE '%voucher%' AND measurements.dataset_id IN ($dataset_ids))
            OR vouchers.id IN (SELECT media.model_id FROM media WHERE media.model_type LIKE '%voucher%' AND media.dataset_id IN ($dataset_ids))
          )";
        }
        if ('datasets' == $scope and $scopeId>0) {
          $sql = "SELECT DISTINCT vouchers.id FROM vouchers,individual_location,locations where
          (individual_location.individual_id=vouchers.individual_id AND individual_location.location_id=locations.id AND locations.lft>='".$this->lft."' AND locations.lft<='".$this->rgt."')
          AND (
            vouchers.dataset_id='".$scopeId."'
            OR vouchers.id IN (SELECT measurements.measured_id FROM measurements WHERE measurements.measured_type LIKE '%voucher%' AND measurements.dataset_id='".$scopeId."')
            OR vouchers.id IN (SELECT media.model_id FROM media WHERE media.model_type LIKE '%voucher%' AND media.dataset_id='".$scopeId."')
          )";
        }
        $query = DB::select($sql);
        return count($query);
    }

    //measurement should count only LOCATION measurements, including descendants (not like taxon as descendant has not a relationship with parent like phylogenetic relationships), so should not count measurements for individuals and vouchers at locations.
    public function measurementsCount($scope='all',$scopeId=null)
    {
      $sql = "SELECT DISTINCT(measurements.measured_id) FROM locations,measurements where measurements.measured_type like '%location%' AND measurements.measured_id=locations.id AND locations.lft>=".$this->lft." AND locations.lft<=".$this->rgt;
      if ('projects' == $scope and $scopeId>0) {
        $dataset_ids = Dataset::where('project_id',$scopeId)->cursor()->pluck('datasets.id')->toArray();
        if (count($dataset_ids)==0) {
          return 0;
        }
        $dataset_ids = implode(",",$dataset_ids);
        $sql .= " AND measurements.dataset_id IN ($dataset_ids)";
      }
      if ('datasets' == $scope and $scopeId>0) {
        $sql .= " AND measurements.dataset_id=".$scopeId;
      }
      $query = DB::select($sql);
      return count($query);
    }




    public function taxonsCount($scope=null,$scopeId=null)
    {
      $sql = "SELECT DISTINCT(taxon_id) FROM identifications,taxons,individual_location,locations where
      identifications.object_id=individual_location.individual_id AND (identifications.object_type LIKE '%individual%') AND identifications.taxon_id=taxons.id AND individual_location.location_id=locations.id AND locations.lft>=".$this->lft." AND locations.lft<=".$this->rgt;
      if ('projects' == $scope and $scopeId>0) {
        $dataset_ids = Dataset::where('project_id',$scopeId)->cursor()->pluck('datasets.id')->toArray();
        if (count($dataset_ids)==0) {
          return 0;
        }
        $dataset_ids = implode(",",$dataset_ids);
        $sql = "SELECT DISTINCT(taxon_id) FROM identifications,taxons,individual_location,locations,individuals where
        individual_location.individual_id=individuals.id
        AND  (identifications.object_id=individuals.ID AND (identifications.object_type LIKE '%individual%'))
        AND (identifications.taxon_id=taxons.id AND individual_location.location_id=locations.id AND locations.lft>='".$this->lft."' AND locations.lft<='".$this->rgt."')
        AND (
          individuals.dataset_id IN ($dataset_ids)
          OR individuals.id IN (SELECT measurements.measured_id FROM measurements WHERE measurements.measured_type LIKE '%individual%' AND measurements.dataset_id IN ($dataset_ids))
          OR individuals.id IN (SELECT media.model_id FROM media WHERE media.model_type LIKE '%individual%' AND media.dataset_id IN ($dataset_ids))
          OR individuals.id IN (SELECT vouchers.individual_id FROM vouchers LEFT JOIN measurements as vm ON vm.measured_id=vouchers.id WHERE (vm.measured_type LIKE '%voucher%' AND vm.dataset_id IN ($dataset_ids)) OR vouchers.dataset_id IN ($dataset_ids))
        )";
      }
      if ('datasets' == $scope and $scopeId>0) {
        $sql = "SELECT DISTINCT(taxon_id) FROM identifications,taxons,individual_location,locations,individuals where
        individual_location.individual_id=individuals.id
        AND  (identifications.object_id=individuals.ID AND (identifications.object_type LIKE '%individual%'))
        AND (identifications.taxon_id=taxons.id AND individual_location.location_id=locations.id AND locations.lft>='".$this->lft."' AND locations.lft<='".$this->rgt."')
        AND (
          individuals.dataset_id IN ($scopeId)
          OR individuals.id IN (SELECT measurements.measured_id FROM measurements WHERE measurements.measured_type LIKE '%individual%' AND measurements.dataset_id IN ($scopeId))
          OR individuals.id IN (SELECT media.model_id FROM media WHERE media.model_type LIKE '%individual%' AND media.dataset_id IN ($scopeId))
          OR individuals.id IN (SELECT vouchers.individual_id FROM vouchers LEFT JOIN measurements as vm ON vm.measured_id=vouchers.id WHERE (vm.measured_type LIKE '%voucher%' AND vm.dataset_id IN ($scopeId)) OR vouchers.dataset_id IN ($scopeId))
        )";
      }
      $query = DB::select($sql);
      return count($query);
    }


   public function all_taxons_ids()
    {
        $sql = "SELECT DISTINCT(taxon_id) FROM identifications,individuals,taxons,individual_location,locations where
        identifications.object_id=individuals.id AND (identifications.object_type LIKE '%individual%') AND identifications.taxon_id=taxons.id AND individual_location.individual_id=individuals.id AND individual_location.location_id=locations.id AND locations.lft>=".$this->lft." AND locations.lft<=".$this->rgt;
        $query = collect(DB::select($sql))->pluck('taxon_id')->toArray();
        return $query;
    }

    public function all_voucher_ids()
    {
        $sql = "SELECT DISTINCT(vouchers.id) FROM vouchers,individuals,individual_location,locations where
        vouchers.individual_id=individuals.id AND individual_location.individual_id=individuals.id AND individual_location.location_id=locations.id AND locations.lft>=".$this->lft." AND locations.lft<=".$this->rgt;
        $query = collect(DB::select($sql))->pluck('id')->toArray();
        return $query;
    }


    /*  MEDIA RELATED FUNCTIONS */

    /*  all media count
    * for location linked media only, including descendants
    */
    public function all_media_count()
    {
      return $this->mediaDescendantsAndSelf()->count();
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



    /* function to get first point of plots and transects */
    public function getGeomFirstPointAttribute()
    {
      $adm_level = $this->adm_level;
      switch ($adm_level) {
        case self::LEVEL_POINT:
          return $this->geom;
          break;
        case self::LEVEL_TRANSECT:
          if ($this->geom_type=="point") {
            return $this->geom;
          }
          return $this->start_point;
          break;
        case self::LEVEL_PLOT:
          if ($this->geom_type=="point") {
            return $this->geom;
          }
          $geom = $this->geom;
          $pattern = '/\\(|\\)|POLYGON|\\n/i';
          $coordinates = preg_replace($pattern, '', $geom);
          $coordinates = explode(",",$coordinates);
          return "POINT(".$coordinates[0].")";
          break;
        default:
          return $this->centroid_raw;
          break;
       }
    }





    public function getTransectLengthAttribute()
    {
      if ($this->adm_level != self::LEVEL_TRANSECT) {
        return null;
      }
      if ($this->geomType == 'linestring') {
          $distance = self::linestring_length($this->geom);
          return $distance;
      }
      //then is a point location with a X dimension attribute
      return $this->x;
    }

    /* Length of transects == linestrings in meters */
    /* instaed of using ST_LENGTH directly
    * because using distance_sphere seems to give more precise results
    */
    public static function linestring_length($geom)
    {
      $linestring = explode(",",$geom);
      $pattern = '/\\(|\\)|LINESTRING|\\n/i';
      $coordinates = preg_replace($pattern, '', $linestring);
      $distance = 0;
      $ncoords = count($coordinates);
      for($i=1;$i<$ncoords;$i++) {
        $point_s = "POINT(".$coordinates[($i-1)].")";
        $point_e = "POINT(".$coordinates[$i].")";
        $geom = DB::select("SELECT ROUND(ST_Distance_Sphere(ST_GeomFromText('$point_s'), ST_GeomFromText('$point_e')),2) as distance" );
        $distance_r = $geom[0]->distance;
        $distance = $distance+$distance_r;
      }
      return $distance;
    }

    /* Find a point location along a LineString, having:
    * a distance from the origin (first point of linestring )
    * by having the X distance from the start of a transect (first point in linestring)
    */
    public static function interpolate_on_transect($location_id,$x)
    {
      if ($x == null) {
        return null;
      }
      $location = self::withGeom()->findOrFail($location_id);
      $transect_length = $location->transect_length;
      if (null != $transect_length) {
        $fraction= $x/$transect_length;
        $geom = self::selectRaw('ST_AsText(ST_LineInterpolatePoint(geom,'.$fraction.')) as point')->where('id',$location_id)->get();
        return $geom[0]->point;
      }
      return null;
    }

    /* function  to define a geometry for a subplot being imported
    *  without a geometry specification
    *  to be used only when parent plot is defined as point
    *  or as polygon with four vertices and 0,0 being SW corner,
    *  and then the other coordinates clock wise.
    */
    public static function subplot_geometry($parent_id,$start_x,$start_y)
    {
      $parent = self::withGeom()->findOrFail($parent_id)->first();
      if (!$parent) {
        return null;
      }
      /* get the 0,0 coordinates for a subplot given the x and y dimensions */
      $subplot_origin = self::individual_in_plot($parent->plot_geometry,$start_x,$start_y);
      return $subplot_origin;
    }

    /* function to calculate a destination point having:
    * $point = wkt POINT geometry in Latitude and Longitude degrees
    * $brng = a azimuth or global bearing rangin from 0 to 360
    * $meters = a distance in meter to place the new location
    */
     public static function destination_point($point, $brng, $meters) {
          $start = preg_split('/\\(|\\)/', $point)[1];
          $start = explode(" ",trim($start));
          $lat = $start[1];
          $long = $start[0];
          $geotools = new \League\Geotools\Geotools();
          $start_point   = new \League\Geotools\Coordinate\Coordinate([$lat,$long]);

          $destinationPoint = $geotools->vertex()->setFrom($start_point)->destination($brng, $meters); //

          $destination_lat =$destinationPoint->getLatitude();
          $destination_long =$destinationPoint->getLongitude();

          return "POINT(".$destination_long." ".$destination_lat.")";
    }

    public static function latlong_from_point($point)
    {
      $coords = preg_split('[\\(|\\)]',$point)[1];
      $coords = explode(" ",trim($coords));
      return [(float) $coords[1], (float) $coords[0]];
    }


    public static function generate_plot_geometry($first_point,$dim_x,$dim_y,$azimuth)
    {
      $angle1 = ($azimuth>=360 or $azimuth<0) ? 0 : $azimuth;
      $angle2 = $angle1+90;
      $angle2 = ($angle2>=360) ? ($angle2-360) : $angle2;
      $second_point = Location::destination_point($first_point,$angle1,$dim_y);
      $third_point =  Location::destination_point($second_point,$angle2,$dim_x);
      $fourth_point = Location::destination_point($first_point,$angle2,$dim_x);
      $first_point = self::latlong_from_point($first_point);
      $second_point = self::latlong_from_point($second_point);
      $third_point = self::latlong_from_point($third_point);
      $fourth_point = self::latlong_from_point($fourth_point);
      $geom = "POLYGON((".$first_point[1]." ".$first_point[0].",".$second_point[1]." ".$second_point[0].",".$third_point[1]." ".$third_point[0].",".$fourth_point[1]." ".$fourth_point[0].",".$first_point[1]." ".$first_point[0]."))";
      return $geom;
    }

    public function getPlotGeometryAttribute()
    {
      /* draw a polygon when plot is points
        * N oriented
        * point is 0,0 SW corner
      */
      if ($this->adm_level == self::LEVEL_PLOT and $this->geom_type== "point") {
        return self::generate_plot_geometry($this->geom,$this->x,$this->y,0);
      }
      return $this->geom;
    }

    public static function generate_transect_geometry($first_point,$dim_x,$azimuth)
    {
      $azimuth = ($azimuth>=360 or $azimuth<0) ? 0 : $azimuth;
      $second_point = Location::destination_point($first_point,$azimuth,$dim_x);
      $first_point = self::latlong_from_point($first_point);
      $second_point = self::latlong_from_point($second_point);
      return "LineString(".$first_point[1]." ".$first_point[0].",".$second_point[1]." ".$second_point[0].")";
    }

    public function getTransectGeometryAttribute()
    {
      /* draw a polygon when plot is points
        * N oriented
        * point is 0,0 SW corner
      */
      if ($this->adm_level == self::LEVEL_TRANSECT and $this->geomType== "point") {
        return self::generate_transect_geometry($this->geom,$this->x,0);
      }
      return $this->geom;
    }

    public function getGeomjsonAttribute()
    {
      $geom = $this->geom;
      if ($this->adm_level == self::LEVEL_PLOT and $this->geomType== "point") {
         $geom = $this->plot_geometry;
      }
      if ($this->adm_level == self::LEVEL_TRANSECT and $this->geomType== "point") {
         $geom = $this->transect_geometry;
      }

      return DB::select("SELECT ST_ASGEOJSON(ST_GeomFromText('".$geom."')) as geojson")[0]->geojson;
    }


    /* map individuals in plots having a geometry */
    public static function individual_in_plot($geom,$x,$y)
    {
      if ($x===null or $y===null) {
        return $geom;
      }
      $pattern = '/\\(|\\)|POLYGON|\\n/i';
      $coordinates = preg_replace($pattern, '', $geom);
      $array = explode(",",trim($coordinates));
      $first_point = "POINT(".$array[0].")";
      $last_point = "POINT(".$array[count($array)-2].")";
      $secont_point = "POINT(".$array[1].")";
      $geotools = new \League\Geotools\Geotools();
      $coordA   = new \League\Geotools\Coordinate\Coordinate(Location::latlong_from_point($first_point));
      $coordB   = new \League\Geotools\Coordinate\Coordinate(Location::latlong_from_point($secont_point));
      $coordC   = new \League\Geotools\Coordinate\Coordinate(Location::latlong_from_point($last_point));

      $bearingY    =  $geotools->vertex()->setFrom($coordA)->setTo($coordB)->initialBearing();
      $bearingX    =  $geotools->vertex()->setFrom($coordA)->setTo($coordC)->initialBearing();

      $pointatborder = Location::destination_point($first_point,$bearingY,$y);
      return Location::destination_point($pointatborder,$bearingX,$x);
    }


    /* for linestrings
      $x = the individual x position
      $y = the individual y position (perpendicular to linestring)
    */
    public static function bearing_at_postion_for_destination($location_id,$x,$y)
    {
      if ($x == null) {
        return null;
      }
      $location = self::withGeom()->findOrFail($location_id);

      //the X position of the individual along the transect
      $start_point = self::interpolate_on_transect($location_id,$x);

      //define a previous point from the X position
      $pd = 2;
      $lag1 = $pd < $x ? ($x - $pd) : $x;
      $previous_point = Location::interpolate_on_transect($location_id,$lag1);

      //define an after point from the X position
      $lag2 = ($x + $pd) > $location->transect_length ? $location->transect_length : $x + $pd;
      $after_point = Location::interpolate_on_transect($location_id,$lag2);

      $geotools = new \League\Geotools\Geotools();
      $coordA   = new \League\Geotools\Coordinate\Coordinate(self::latlong_from_point($previous_point));
      $coordB   = new \League\Geotools\Coordinate\Coordinate(self::latlong_from_point($start_point));
      $coordC   = new \League\Geotools\Coordinate\Coordinate(self::latlong_from_point($after_point));

      //bearings to get a 90 degrees position in relation to transect
      $vertex1    =  $geotools->vertex()->setFrom($coordA)->setTo($coordB);
      $vertex2    =  $geotools->vertex()->setFrom($coordB)->setTo($coordC);
      //average from previous and after
      $bearing = ($vertex1->initialBearing()+$vertex2->initialBearing())/2;

      //if Y is negative (left side of transect )
      if ($y < 0) {
        $bearing = $bearing - 90;
      } else {
        //else rigth side of transect
        $bearing = $bearing + 90;
      }
      //adjust values if negative or greater than valid
      if ($bearing < 0 ) {
        $bearing = 360 - abs($bearing);
      } elseif ($bearing > 360) {
        $bearing = $bearing - 360;
      }
      //bearing to place a point in relation to the linestring
      return $bearing;
    }

    /* generates contents for mapping locations and/or individuals */
    public function generateFeatureCollection($individual_id=null)
    {
        $ids = $this->getAncestorsAndSelf()->pluck('id')->toArray();
        //add related if any
        $related_ids = $this->relatedLocations->pluck('related_id')->toArray();
        if (count($related_ids)) {
          $ids = array_merge($ids,$related_ids);
        }
        $string = [ "type" => "FeatureCollection", "features" => []];
        $locations = self::whereIn("id",$ids)->noWorld()->withGeom()->orderBy('adm_level')->cursor();
        $fit_geometry = null;
        $idx = 0;
        foreach($locations as $location) {
              $issubplot = ($this->adm_level==self::LEVEL_PLOT and $this->parent->adm_level==self::LEVEL_PLOT);
              $isplot_or_trans = (($this->adm_level==self::LEVEL_PLOT or $this->adm_level==self::LEVEL_TRANSECT) and $this->parent->adm_level!=self::LEVEL_PLOT);
              if ($location->id == $this->parent_id and (!in_array($this->adm_level,[self::LEVEL_UC,self::LEVEL_TI,self::LEVEL_ENV]) or $issubplot) and !$isplot_or_trans) {
                $fit_geometry = $idx;
              } elseif ($location->id == $this->id and in_array($this->adm_level,[self::LEVEL_UC,self::LEVEL_TI,self::LEVEL_ENV]) or $isplot_or_trans) {
                $fit_geometry = $idx;
              }
              $idx++;
              $properties = [
                'name' => $location->name,
                'area' => $location->area,
                'centroid_raw' => $location->centroid_raw,
                'adm_level' => $location->adm_level,
                'location_type' => Lang::get("levels.adm_level.".$location->adm_level),
                'parent_adm_level' => $location->parent->adm_level,
                'fit_geometry' => ($location->id==$this->id) ? $fit_geometry : null,
              ];
              $str = [
                "type" => "Feature",
                "geometry" => json_decode($location->geomjson),
                "properties" => $properties,
              ];
              $string['features'][] = $str;
              //add starting point if polygon as drawn
              if (($location->adm_level==self::LEVEL_PLOT or $location->adm_level==self::LEVEL_TRANSECT)) {
                $properties = [
                  'name' => $location->name." [0,0]",
                  'centroid_raw' => $location->centroid_raw,
                  'adm_level' => self::LEVEL_POINT,
                  'location_type' => Lang::get("levels.adm_level.".$location->adm_level),
                  'parent_adm_level' => $location->parent->adm_level,
                  'fit_geometry' => ($location->id==$this->id) ? $fit_geometry : null,
                ];
                $geom = DB::select("SELECT ST_ASGEOJSON(ST_GeomFromText('".$location->geomFirstPoint."')) as geojson")[0]->geojson;
                $str = [
                  "type" => "Feature",
                  "geometry" => json_decode($geom),
                  "properties" => $properties,
                ];
                $string['features'][] = $str;
              }

        }
        if ($individual_id != null) {
          $individual = Individual::findOrFail($individual_id);
          if ($individual->count()) {
            $properties = [
              'name' => $individual->fullname,
              'centroid_raw' => $individual->getGlobalPosition(),
              'adm_level' => 1000,
              'location_type' => Lang::get("messages.individual"),
              'parent_adm_level' => $this->adm_level,
              'fit_geometry' => $fit_geometry,
            ];
            $str = [
              "type" => "Feature",
              "geometry" => json_decode($individual->getGlobalPosition($geojson=1)),
              "properties" => $properties,
            ];
            $string['features'][] = $str;
          }
        }

        return json_encode($string);
    }






    /* DARWIN CORE mutators */
    public function getHigherGeographyAttribute()
    {
        $path = $this->getAncestorsWithoutRoot()->pluck('name')->toArray();
        if (count($path)) {
          return implode(" | ",$path);
        }
        return null;
    }


    public function getFootprintWKTAttribute()
    {
      $adm_level = $this->adm_level;
      $this->getLatLong();
      if ($adm_level==self::LEVEL_PLOT) {
        return $this->plot_geometry;
      }
      if ($adm_level==self::LEVEL_TRANSECT) {
        return $this->transect_geometry;
      }
      return $this->geom;
    }

    public function getDecimalLatitudeAttribute()
    {
      $this->getLatLong();
      return $this->lat;
    }
    public function getDecimalLongitudeAttribute()
    {
      $this->getLatLong();
      return $this->long;
    }

    public function getLocationRemarksAttribute()
    {
      return $this->notes;
    }
    public function getGeodeticDatumAttribute()
    {
      return $this->datum;
    }

    /* this is not in dwc only, location there but for different purpose */
    public function getLocationNameAttribute()
    {
      return $this->name;
    }

    public function getBasisOfRecordAttribute()
    {
      return 'Location';
    }

    /* uc replacement for related */
    public function relatedLocations()
    {
        return $this->hasMany('App\Models\LocationRelated', 'location_id');
    }

    public function uc()
    {
        return $this->relatedLocations()->whereHas('relatedLocation',function($l){
          $l->where('adm_level',self::LEVEL_UC);
        });
    }

    public static function detectRelated($geom, $adm_level,$idsOnly=false)
    {
        //check only for non administrative levels
        if (!in_array($adm_level,[Location::LEVEL_PLOT,Location::LEVEL_TRANSECT,Location::LEVEL_POINT])) {
          return null;
        }
        $query = 'ST_Within(ST_GeomFromText(?), geom)';
          //check which registered polygons (except World) of special cases
        $validtypes = [Location::LEVEL_UC,Location::LEVEL_TI,Location::LEVEL_ENV];
        //are possible parents (CONTAIN) of submitted location
        //order by adm_level to get the most inclusive first
        $possibles = Location::select(['id','name','adm_level'])->whereRaw($query, [$geom])->whereIn('adm_level',$validtypes)->noWorld()->orderBy('adm_level', 'desc');

        //if found return the greatest adm_level location found
        if ($possibles->count()) {
            if ($idsOnly) {
                return $possibles->cursor()->pluck('id')->toArray();
            }
            return $possibles->cursor()->map(function($p) { return ['id'=>$p->id,'name'=>$p->name];})->toArray();
        }
        return null;
    }

    public static function fixPathAndRelated($id)
    {
      $location = self::withGeom()->findOrFail($id);
      $adm_level = $location->adm_level;

      if (in_array($adm_level,[self::LEVEL_PLOT,self::LEVEL_TRANSECT,self::LEVEL_POINT])) {
        return null;
      }

      //move sister locations to within if that is the case
      $others = Location::where('parent_id',$location->parent_id)->where('id','<>',$location->id);
      if ($others->count() and !in_array($adm_level,[self::LEVEL_UC,self::LEVEL_TI,self::LEVEL_ENV])) {
          $query = 'ST_Within(geom,ST_GeomFromText(?))';
          $others = $others->whereRaw($query,$location->geom);
          if ($others->count()) {
            foreach ($others->cursor() as $tofix) {
              $tofix->parent_id = $location->id;
              $tofix->save();
            }
          }
      }
      //special locations are new, is this a new parent for other registered locations
      //if so establish the relationship
      if (in_array($adm_level,[self::LEVEL_UC,self::LEVEL_TI,self::LEVEL_ENV])) {
        $existing = $location->relatedLocations->pluck('related_id')->toArray();
        $query = 'ST_Within(geom,ST_GeomFromText(?))';
        $others = Location::whereRaw($query,$location->geom)->whereIn('adm_level',[self::LEVEL_PLOT,self::LEVEL_TRANSECT,self::LEVEL_POINT]);
        if (count($existing)) {
          $others = $others->whereNotIn('id',$existing);
        }
        if ($others->count()) {
          foreach ($others->cursor() as $tocreate) {
            $related = new LocationRelated(['related_id' => $location->id]);
            $tocreate->relatedLocations()->save($related);
          }
        }
      }
      return null;
    }

}
