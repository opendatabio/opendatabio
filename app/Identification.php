<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Identification extends Model
{
    const NONE = 0;
    const SS = 1;
    const SL = 2;
    const CF = 3;
    const AFF = 4;
    const VEL_AFF = 5;
    const MODIFIERS = [
        Identification::NONE,
        Identification::SS,
        Identification::SL,
        Identification::CF,
        Identification::AFF,
        Identification::VEL_AFF,
    ];

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
