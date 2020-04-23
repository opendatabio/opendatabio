<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Lang;
use DB;

// class name needs to be different as Trait is a PHP reserved word
class ODBTrait extends Model
{
    use Translatable;

    // Types that can have measurements associated with
    // If this is ever changed, remember to edit getObjectTypeNames!
    const OBJECT_TYPES = [
        Plant::class,
        Voucher::class,
        Location::class,
        Taxon::class,
    ];
    // Types that can receive database link traits
    // NOTE: all link_types must support a "fullname" method
    const LINK_TYPES = [
        Taxon::class,
        Person::class,
    ];

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

    protected $fillable = ['type', 'export_name', 'unit', 'range_min', 'range_max', 'link_type'];
    protected $table = 'traits';

    public function rawLink()
    {
        return "<a href='".url('traits/'.$this->id)."'>".htmlspecialchars($this->name).'</a>';
    }

    // for use in the trait edit dropdown
    public static function getObjectTypeNames()
    {
        return [
            Lang::get('classes.'.Plant::class),
            Lang::get('classes.'.Voucher::class),
            Lang::get('classes.'.Location::class),
            Lang::get('classes.'.Taxon::class),
        ];
    }

    public function getObjectKeys()
    {
        $ret = [];
        foreach ($this->object_types()->pluck('object_type') as $search) {
            $ret[] = array_keys(self::OBJECT_TYPES, $search)[0];
        }

        return $ret;
    }

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
                'cat_name' => 'array|required_if:type,2,3,4',
                'cat_name.1' => 'required_if:type,2,3,4',
                'cat_name.1.1' => 'required_if:type,2,3,4',
                'link_type' => 'required_if:type,7',
            ], $merge);
    }

    protected function makeCategory($rank, $names, $descriptions)
    {
        $cat = $this->categories()->create(['rank' => $rank]);
        foreach ($names as $key => $translation) {
            $cat->setTranslation(UserTranslation::NAME, $key, $translation);
        }
        foreach ($descriptions as $key => $translation) {
            $cat->setTranslation(UserTranslation::DESCRIPTION, $key, $translation);
        }

        return $cat;
    }

    public function setFieldsFromRequest($request)
    {
        // Set fields from quantitative traits
        if (in_array($this->type, [self::QUANT_INTEGER, self::QUANT_REAL])) {
            $this->unit = $request->unit;
            $this->range_max = $request->range_max;
            $this->range_min = $request->range_min;
        } else {
            $this->unit = null;
            $this->range_max = null;
            $this->range_min = null;
        }
        // Set fields from categorical traits
        $this->categories()->delete();
        if (in_array($this->type, [self::CATEGORICAL, self::CATEGORICAL_MULTIPLE, self::ORDINAL])) {
            $names = $request->cat_name;
            $descriptions = $request->cat_description;
            // counts the number of skipped entries, so the ranks will be matched to the names/description
            $skips = 0;
            for ($i = 1; $i <= sizeof($names); ++$i) {
                // checks to see if there's at least one name provided
                if (!array_filter($names[$i])) {
                    ++$skips;
                    continue;
                }
                $this->makeCategory($i - $skips, $names[$i], $descriptions[$i]);
            }
        }
        // Set link type
        if (in_array($this->type, [self::LINK])) {
            $this->link_type = $request->link_type;
        } else {
            $this->link_type = null;
        }

        // Set object types
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

    public function details()
    {
        switch ($this->type) {
        case 0:
        case 1:
            return Lang::get('messages.unit').': '.$this->unit;
            break;
        case 2:
        case 3:
        case 4:
            $ret = '';
            $cats = $this->categories;
            $i = 0;
            foreach ($cats as $cat) {
                if ($i++ > 2) {
                    continue;
                }
                $ret .= $cat->name.', ';
            }

            return $ret.'...';
            break;
        case 7:
            return Lang::get('messages.link_type').': '.Lang::get('classes.'.$this->link_type);
            break;
        }
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

    public function getRangeDisplayAttribute()
    {
        if (isset($this->range_min)) {
            $ret = $this->range_min;
        } else {
            $ret = '-'.'&#8734;';
        }

        $ret .= ' '.'&#8212;'.' ';

        if (isset($this->range_max)) {
            $ret .= $this->range_max;
        } else {
            $ret .= '&#8734;';
        }

        return $ret;
    }


}
