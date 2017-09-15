<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TraitObject extends Model
{
    protected $fillable = ['trait_id', 'object_type'];

    public function odbtrait() {
        return $this->belongsTo(ODBTrait::class, 'trait_id');
    }
}
