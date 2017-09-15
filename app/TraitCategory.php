<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TraitCategory extends Model
{
    protected $fillable = ['trait_id', 'rank'];
    public function odbtrait() {
        return $this->belongsTo(ODBTrait::class, 'trait_id');
    }
}
