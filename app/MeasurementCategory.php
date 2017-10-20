<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MeasurementCategory extends Model
{
    protected $fillable = ['category_id', 'measurement_id'];
    public function traitCategory() {
        return $this->belongsTo(TraitCategory::class, 'category_id');
    }
}
