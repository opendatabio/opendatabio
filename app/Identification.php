<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Identification extends Model
{
    const SS = 1;
    const SL = 2;
    const CF = 3;
    const AFF = 4;
    const VEL_AFF = 5;

    protected $fillable = ['person_id', 'taxon_id', 'object_id', 'object_type', 'date', 'modifier', 'herbarium_id', 'notes'];
    public function object() {
        return $this->morphTo('object');
    }
    public function person() {
        return $this->belongsTo(Person::class);
    }
    public function taxon() {
        return $this->belongsTo(Taxon::class);
    }
    public function herbarium() {
        return $this->belongsTo(Herbarium::class);
    }
        
    //
}
