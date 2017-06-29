<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use DB;

class Location extends Model
{
	use NodeTrait;

	protected $fillable = ['name', 'altitude', 'datum', 'adm_level', 'notes'];

	// query scope for conservation units
	public function scopeUcs($query) {
		return $query->where('adm_level', 99);
	}

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
    public function uc()
    {
        return $this->belongsTo('App\Location', 'uc_id');
    }

	public function newQuery($excludeDeleted = true)
	{
		// this "area" is used as an example, needs to be removed in the final product
	return parent::newQuery($excludeDeleted)->addSelect('*',DB::raw('AsText(geom) as geom, Area(geom) as area'));
	}

}
