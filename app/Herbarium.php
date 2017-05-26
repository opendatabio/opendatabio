<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Herbarium extends Model
{
	protected $fillable = ['name', 'acronym', 'irn'];
    //
	//
    public function persons()
    {
        return $this->hasMany('App\Person');
    }
}
