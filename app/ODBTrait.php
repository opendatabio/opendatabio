<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

// class name needs to be different as Trait is a PHP reserved word
class ODBTrait extends Model
{
    use Translatable;

    protected $fillable = ['type', 'export_name', 'unit', 'range_min', 'range_max', 'link_type'];
    protected $table = 'traits';

    const OBJECT_TYPES = [
        Plant::class,
        Voucher::class,
        Location::class,
        Taxon::class,
    ];

    public function getObjectKeys()
    {
        $ret = [];
        foreach ($this->object_types()->pluck('object_type') as $search) {
            $ret[] = array_keys(self::OBJECT_TYPES, $search)[0];
        }

        return $ret;
    }

    const QUANT_INTEGER = 0;
    const QUANT_REAL = 1;
    const CATEGORICAL = 2;
    const CATEGORICAL_MULTIPLE = 3;
    const ORDINAL = 4;
    const TEXT = 5;
    const COLOR = 6;
    const LINK = 7; // may include genomic / spectral??
    const TRAIT_TYPES = [
        self::QUANT_INTEGER,
        self::QUANT_REAL,
        self::CATEGORICAL,
        self::CATEGORICAL_MULTIPLE,
        self::ORDINAL,
        self::TEXT,
        self::COLOR,
        self::LINK,
    ];

    // for input validation
    public static function rules($id = null, $merge = [])
    {
        return array_merge(
            [
                'name' => 'required|array',
                'name.*' => 'required',
                'description' => 'required|array',
                'export_name' => 'required|string|unique:traits,export_name,'.$id,
                'type' => 'required|integer',
                'objects' => 'required|array|min:1',
                'objects.*' => 'required|integer|min:0|max:'.(count(self::OBJECT_TYPES) - 1),
                'unit' => 'required_if:type,0,1',
            ], $merge);
    }

    public function setFieldsFromRequest($request)
    {
        if (in_array($this->type, [self::QUANT_INTEGER, self::QUANT_REAL])) {
            $this->unit = $request->unit;
            $this->range_max = $request->range_max;
            $this->range_min = $request->range_min;
        } else {
            $this->unit = null;
            $this->range_max = null;
            $this->range_min = null;
        }
        $this->object_types()->delete();
        foreach ($request->objects as $key) {
            $this->object_types()->create(['object_type' => self::OBJECT_TYPES[$key]]);
        }
        foreach ($request->name as $key => $translation) {
            $this->setTranslation(UserTranslation::NAME, $key, $translation);
        }
        foreach ($request->description as $key => $translation) {
            $this->setTranslation(UserTranslation::DESCRIPTION, $key, $translation);
        }
        $this->save();
    }

    public function object_types()
    {
        return $this->hasMany(TraitObject::class, 'trait_id');
    }

    public function valid_type($type)
    {
        return in_array($type, $this->object_types->pluck('object_type')->all());
    }

    public function categories()
    {
        return $this->hasMany(TraitCategory::class, 'trait_id');
    }

    public function measurements()
    {
        return $this->hasMany(Measurement::class, 'trait_id');
    }

    public function scopeAppliesTo($query, $class)
    {
        return $query->whereHas('object_types', function ($q) use ($class) {
            return $q->where('object_type', '=', $class);
        });
    }
}
