<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Baum\Node;
use DB;
use Lang;

class Location extends Node
{
    // The "special" adm levels
    const LEVEL_UC = 99;
    const LEVEL_PLOT = 100;
    const LEVEL_TRANSECT = 101;
    const LEVEL_POINT = 999;

    protected $fillable = ['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y', 'startx', 'starty'];
    protected $lat;
    protected $long;
    protected $geom_array = [];
    protected $isSimplified = false;

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

    public static function scopeWithDistance($query, $geom)
    {
        // this query hangs if you attempt to run it on full geom objects, so we add
        // a "where" to make sure we're only calculating distance from small objects
        return $query->addSelect('*', DB::Raw("ST_Distance(geom, GeomFromText('$geom')) as distance"))
            ->where('adm_level', '>', 99);
    }

    public function measurements()
    {
        return $this->morphMany(Measurement::class, 'measured');
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

    public function getLatitudeSimpleAttribute()
    {
        $this->getLatLong();
        $letter = $this->lat > 0 ? ' N' : ' S';

        return $this->lat1.'&#176;'.$this->lat2.'\''.$this->lat3.'\'\' '.$letter;
    }

    public function getLongitudeSimpleAttribute()
    {
        $this->getLatLong();
        $letter = $this->long > 0 ? ' E' : ' W';

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
        return Lang::get('levels.adm.'.$this->adm_level);
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

    public function setGeomAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['geom'] = null;

            return;
        }
        // MariaDB returns 1 for invalid geoms from ST_IsEmpty ref: https://mariadb.com/kb/en/mariadb/st_isempty/
        $invalid = DB::select("SELECT ST_IsEmpty(GeomFromText('$value')) as val")[0]->val;
        if ($invalid) {
            throw new \UnexpectedValueException('Invalid Geometry object');
        }
        $this->attributes['geom'] = DB::raw("GeomFromText('$value')");
    }

    public function getSimplifiedAttribute()
    {
        $this->getGeomArrayAttribute(); // force caching
        return $this->isSimplified;
    }

    protected function extractXY($point)
    {
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
                $array = $this->simplify($array, 1500);
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
                    $p_array = $this->simplify($p_array, $factor);
                }
                $polygon = $p_array;
            }
            $this->geom_array = $array;
        }

        return $this->geom_array;
    }

    public static function detectParent($geom, $max_level, $parent_uc)
    {
        // there can be plots inside plots
        if (self::LEVEL_PLOT == $max_level) {
            $max_level += 1;
        }
        $possibles = self::whereRaw('ST_Within(GeomFromText(?), geom)', [$geom])
            ->orderBy('adm_level', 'desc');
        if ($parent_uc) { // only looks for UCs
            $possibles = $possibles->where('adm_level', '=', self::LEVEL_UC);
        } else { // only looks for NON-UCs
            $possibles = $possibles->where('adm_level', '!=', self::LEVEL_UC)
            ->where('adm_level', '<', $max_level);
        }
        $possibles = $possibles->get();
        if ($possibles->count()) {
            return $possibles->first();
        }

        return null;
    }

    public function setGeomFromParts($values)
    {
        $geom = self::geomFromParts($values);
        $this->attributes['geom'] = DB::raw("GeomFromText('$geom')");
    }

    public static function geomFromParts($values)
    {
        $lat = $values['lat1'] + $values['lat2'] / 60 + $values['lat3'] / 3600;
        $long = $values['long1'] + $values['long2'] / 60 + $values['long3'] / 3600;
        if (0 == $values['longO']) {
            $long *= -1;
        }
        if (0 == $values['latO']) {
            $lat *= -1;
        }

        return 'POINT('.$long.' '.$lat.')';
    }

    public function uc()
    {
        return $this->belongsTo('App\Location', 'uc_id');
    }

    public function plants()
    {
        return $this->hasMany(Plant::class);
    }

    public function vouchers()
    {
        return $this->morphMany(Voucher::class, 'parent');
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

    public function scopeWithGeom($query)
    {
        return $query->addSelect(
            DB::raw('AsText(geom) as geom'),
            DB::raw('Area(geom) as area'),
            DB::raw('AsText(Centroid(geom)) as centroid_raw')
        );
    }

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'object');
    }
}
