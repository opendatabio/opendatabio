<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
	// Gramatically incorrect, but helps development
	protected $table = 'persons';
}
