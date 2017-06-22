<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use DB;

class Location extends Model
{
	use NodeTrait;

	protected $fillable = ['name', 'altitude', 'datum', 'adm_level'];

	// Proposal! Needs to be tested and aproved
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
		if (substr($this->geom, 0, 7) != "POLYGON") return; // not working with other things
		$array = explode(',', substr($this->geom, 9, -2));
		foreach($array as &$element) {
			$pos = strpos($element, ' ');
			$element = ['x' => substr($element,0, $pos), 'y' => substr($element, $pos+1)];
		}
		return $array;
	}

	public function newQuery($excludeDeleted = true)
	{
        return parent::newQuery($excludeDeleted)->addSelect('*',DB::raw('AsText(geom) as geom, Area(geom) as area'));
	}

}
