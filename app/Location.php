<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class Location extends Model
{
	use NodeTrait;

	protected $fillable = ['name'];

	// Proposal! Needs to be tested and aproved
	function getFullNameAttribute() {
		$str = "";
		foreach ($this->getAncestors() as $ancestor) { 
			$str .= $ancestor->name . " > ";
		}
		return $str . $this->name;
	}
}
