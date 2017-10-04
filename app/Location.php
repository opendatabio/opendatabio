<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Baum\Node;
use DB;
use Log;
use Lang;

class Location extends Node
{
	protected $fillable = ['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y', 'startx', 'starty'];
	protected $lat, $long;
	protected $geom_array = [];
	protected $isSimplified = false;

    // for use when receiving this as part of a morph relation
    // TODO: maybe can be changed to get_class($p)?
    public function getTypenameAttribute() { return "locations"; }
	// The "special" adm levels
	const LEVEL_UC = 99;
	const LEVEL_PLOT = 100;
	const LEVEL_TRANSECT = 101;
	const LEVEL_POINT = 999;
	// for use in views/* selects
	static public function AdmLevels() {
		return array_merge(config('app.adm_levels'), [
			Location::LEVEL_UC, 
			Location::LEVEL_PLOT, 
			Location::LEVEL_TRANSECT, 
			Location::LEVEL_POINT,
		]);
	}

    public function measurements()
    {
        return $this->morphMany(Measurement::class, 'measured');
    }
	// helper method to get lat/long from POINTS only
	public function getlatlong() {
		$point = substr($this->geom, 6, -1);
		$pos = strpos($point, ' ');
		$this->long = substr($point,0, $pos);
		$this->lat = substr($point, $pos+1);
	}
	public function getCentroidAttribute() {
		$centroid = $this->centroid_raw;
		if (empty($centroid)) $centroid = $this->geom;
		$point = substr($centroid, 6, -1);
		$pos = strpos($point, ' ');
		return [ 'x' => substr($point,0, $pos), 'y' => substr($point, $pos+1)] ;
	}

	// query scope for conservation units
	public function scopeUcs($query) {
		return $query->where('adm_level', Location::LEVEL_UC);
	}

    public function getLevelNameAttribute() {
            return Lang::get('levels.adm.' . $this->adm_level);
    }

	function getFullNameAttribute() {
		$str = "";
		foreach ($this->getAncestors() as $ancestor) { 
			$str .= $ancestor->name . " > ";
		}
		return $str . $this->name;
	}

	public function setGeomAttribute($value) {
		if (is_null($value)) {
			$this->attributes['geom'] = null;
			return;
		}
		// MariaDB returns 1 for invalid geoms from ST_IsEmpty ref: https://mariadb.com/kb/en/mariadb/st_isempty/
		$invalid = DB::select("SELECT ST_IsEmpty(GeomFromText('$value')) as val")[0]->val;
		if($invalid) { throw new \UnexpectedValueException('Invalid Geometry object'); }
	        $this->attributes['geom'] = DB::raw("GeomFromText('$value')");
	}
	public function getSimplifiedAttribute() {
		$this->getGeomArrayAttribute(); // force caching
		return $this->isSimplified;
	}
	public function getGeomArrayAttribute() {
		// "cache" geom array to reduce overhead
		if (!empty($this->geom_array)) return $this->geom_array;

		if (substr($this->geom, 0, 5) == "POINT") {
			$point = substr($this->geom, 6, -1);
			$pos = strpos($point, ' ');
			return [['x' => substr($point,0, $pos), 'y' => substr($point, $pos+1)]];
		}
		if (substr($this->geom, 0, 7) != "POLYGON") return; // not working with other things
		$array = explode(',', substr($this->geom, 9, -2));
		foreach($array as &$element) {
			$pos = strpos($element, ' ');
			$element = ['x' => substr($element,0, $pos), 'y' => substr($element, $pos+1)];
		}
		if (sizeof($array) > 1500) {
			$this->isSimplified = true;
			// TODO: provide a better simplification for this, such as Douglas-Peucker
			$result = array();
			$lapse = ceil(sizeof($array) / 1500);
			$i = 0;
			foreach($array as $value) {
			    if ($i++ % $lapse == 0) {
			        $result[] = $value;
			    }
			}
			$array = $result;
		}

		$this->geom_array = $array;
		return $array;
	}

	public function setGeomFromParts($values) {
		$lat = $values['lat1'] + $values['lat2'] / 60 + $values['lat3'] / 3600;
		$long = $values['long1'] + $values['long2'] / 60 + $values['long3'] / 3600;
		if ( $values['longO'] == 0) $long *= -1;
		if ( $values['latO'] == 0) $lat *= -1;
		$geom = "POINT(" . $long . " " . $lat . ")";
	        $this->attributes['geom'] = DB::raw("GeomFromText('$geom')");
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
	public function getLat1Attribute() { 
		$this->getLatLong();
		return floor(abs($this->lat));
	}
	public function getLat2Attribute() { 
		$this->getLatLong();
		return floor(60 * (abs( $this->lat) - $this->lat1  ));
	}
	public function getLat3Attribute() { 
		$this->getLatLong();
		return floor(60 * (60 * abs( $this->lat) - 60 * $this->lat1 - $this->lat2  ));
	}
	public function getLatOAttribute() { 
		$this->getLatLong();
		return ($this->lat > 0);
	}
	public function getLong1Attribute() { 
		$this->getLatLong();
		return floor(abs($this->long));
	}
	public function getLong2Attribute() { 
		$this->getLatLong();
		return floor(60 * (abs( $this->long) - $this->long1  ));
	}
	public function getLong3Attribute() { 
		$this->getLatLong();
		return floor(60 * (60 * abs( $this->long) - 60 * $this->long1 - $this->long2  ));
	}
	public function getLongOAttribute() { 
		$this->getLatLong();
		return ($this->long > 0);
	}

	public function newQuery($excludeDeleted = true)
	{
		return parent::newQuery($excludeDeleted)->addSelect(
			'*', 
			DB::raw('AsText(geom) as geom'),
			DB::raw('Area(geom) as area'),
			DB::raw('AsText(Centroid(geom)) as centroid_raw')
		);
	}
}
