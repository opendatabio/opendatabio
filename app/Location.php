<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Baum\Node;
use DB;
use Log;

class Location extends Node
{

	protected $fillable = ['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y', 'startx', 'starty'];
	protected $lat, $long;

	// The "special" adm levels
	const LEVEL_UC = 99;
	const LEVEL_PLOT = 100;
	const LEVEL_TRANSECT = 101;
	const LEVEL_POINT = 999;
	static public function AdmLevels() {
		return array_merge(config('app.adm_levels'), [
			Location::LEVEL_UC, 
			Location::LEVEL_PLOT, 
			Location::LEVEL_TRANSECT, 
			Location::LEVEL_POINT,
		]);
	}

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

	function getFullNameAttribute() {
		$str = "";
		foreach ($this->getAncestors() as $ancestor) { 
			$str .= $ancestor->name . " > ";
		}
		return $str . $this->name;
	}

	public function setGeomAttribute($value) {
	        $this->attributes['geom'] = DB::raw("GeomFromText('$value')");
	}
	public function getGeomArrayAttribute() {
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
