<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
	// Gramatically incorrect, but helps development
	protected $table = 'persons';
	protected $fillable = ['full_name', 'abbreviation', 'email', 'institution', 'herbarium_id'];


    public function herbarium()
    {
        return $this->belongsTo('App\Herbarium');
    }
}
