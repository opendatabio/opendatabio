<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

// class name needs to be different as Trait is a PHP reserved word
class ODBTrait extends Model
{
    use Translatable; 

    protected $fillable = ['type', 'export_name', 'unit', 'range_min', 'range_max', 'link_type'];
    protected $table = ['traits'];

    const QUANT_INTEGER = 0;
    const QUANT_REAL = 1;
    const CATEGORICAL = 2;
    const CATEGORICAL_MULTIPLE = 3;
    const ORDINAL = 4;
    const TEXT = 5;
    const COLOR = 6;
    const LINK = 7; // may include genomic / spectral??
    const TRAIT_TYPES = [
        ODBTrait::QUANT_INTEGER,
        ODBTrait::QUANT_REAL,
        ODBTrait::CATEGORICAL,
        ODBTrait::CATEGORICAL_MULTIPLE,
        ODBTrait::ORDINAL,
        ODBTrait::TEXT,
        ODBTrait::COLOR,
        ODBTrait::LINK,
    ];

    public function objects() {
        return $this->hasMany(TraitObject::class, 'trait_id');
    }
    public function categories() {
        return $this->hasMany(TraitCategory::class, 'trait_id');
    }
}
