<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Person;
use App\Plant; //?

class Collector extends Model
{
    protected $fillable = ['person_id', 'object_id', 'object_type'];
    public function person() {
        return $this->belongsTo(Person::class);
    }
    public function collected() {
        return $this->morphTo('object');
    }
    //
}
