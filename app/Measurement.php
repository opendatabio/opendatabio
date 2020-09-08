<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Auth;

class Measurement extends Model
{
    use IncompleteDate;

    protected $fillable = ['trait_id', 'measured_id', 'measured_type',
        'date', 'dataset_id', 'person_id', 'bibreference_id',
        'value', 'value_i', 'value_a', 'notes', ];

    public function rawLink()
    {
        return '<a href="'.url('measurements/'.$this->id).'">'.
                // Needs to escape special chars, as this will be passed RAW
                htmlspecialchars($this->valueActual).'</a>';
    }

    public function measured()
    {
        return $this->morphTo();
    }

    public function getMeasuredFullnameAttribute()
    {
      return $this->measured->fullname;
    }

    public function getMeasuredTaxonNameAttribute()
    {
      return $this->measured->taxonName;
    }

    public function getMeasuredTaxonFamilyAttribute()
    {
      return $this->measured->taxonFamily;
    }

    public function getMeasuredProjectAttribute()
    {
      return $this->measured->projectName;
    }

    public function getDatasetNameAttribute()
    {
      return $this->dataset->name;
    }

    // These functions use Laravel magic to create a "linked_id" and "linked_type"
    // fields, used by the $measurement->linked() function below
    public function getLinkedTypeAttribute()
    {
        return $this->odbtrait->link_type;
    }

    public function getLinkedIdAttribute()
    {
        return $this->value_i;
    }

    public function linked()
    {
        return $this->morphTo();
    }

    public function odbtrait()
    {
        return $this->belongsTo(ODBTrait::class, 'trait_id');
    }

    public function getTypeAttribute() // easy accessor
    {
        return $this->odbtrait->type;
    }

    public function bibreference()
    {
        return $this->belongsTo(BibReference::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function dataset()
    {
        return $this->belongsTo(Dataset::class);
    }

    public function categories()
    {
        return $this->hasMany(MeasurementCategory::class);
    }

    public function getTraitNameAttribute()
    {
        return $this->odbtrait->export_name;
    }

    public function getTraitUnitAttribute()
    {
        return $this->odbtrait->unit;
    }


    // provides a common interface for getting/setting value for different types of measurements
    public function getValueActualAttribute()
    {
        switch ($this->type) {
        case ODBTrait::QUANT_INTEGER:
            return $this->value_i;
            break;
        case ODBTrait::QUANT_REAL:
            return $this->value;
            break;
        case ODBTrait::TEXT:
        case ODBTrait::COLOR:
        case ODBTrait::SPECTRAL:
            return $this->value_a;
            break;
        case ODBTrait::CATEGORICAL:
            return $this->categories()->first()->traitCategory->name;
            break;
        case ODBTrait::CATEGORICAL_MULTIPLE:
            $cats = collect($this->categories)->map(function ($newcat) {
                return $newcat->traitCategory->name;
            })->all();

            return implode(', ', $cats);
            break;
        case ODBTrait::ORDINAL:
            $tcat = $this->categories()->first()->traitCategory;

            return $tcat->rank.' - '.$tcat->name;
            break;
        case ODBTrait::LINK:
            $val = '';
            if (!empty($this->value)) {
                $val = $this->value;
            }

            return $val.' '.(empty($this->linked) ? 'ERROR' : $this->linked->fullname);
            break;
        }
    }

    //this is used only in import_jobs
    public function setValueActualAttribute($value)
    {
        switch ($this->type) {
        case ODBTrait::QUANT_INTEGER:
            $this->value_i = $value;
            break;
        case ODBTrait::QUANT_REAL:
            $this->value = $value;
            break;
        case ODBTrait::TEXT:
        case ODBTrait::COLOR:
        case ODBTrait::SPECTRAL:
            $this->value_a = $value;
            break;
        case ODBTrait::CATEGORICAL:
        case ODBTrait::ORDINAL:
            $this->categories()->delete();
            $this->categories()->create(['category_id' => $value,'measurement_id' => $this->measured_id]);
            break;
        case ODBTrait::CATEGORICAL_MULTIPLE:
            $this->categories()->delete();
            if (!is_array($value)) {
                $value = explode(";",$value);
            }
            if (is_array($value)) {
                foreach ($value as $v) {
                    $this->categories()->create(['category_id' => $v]);
                }
            } else {
                $this->categories()->create(['category_id' => $value]);
            }
            break;
        case ODBTrait::LINK:
            // handled by MeasurementController, as it requires value AND value_i
            break;
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('datasetScope', function (Builder $builder) {
            // first, the easy cases. No logged in user?
            if (is_null(Auth::user())) {
                return $builder->join('datasets', 'datasets.id', '=', 'dataset_id')
                    ->where('datasets.privacy', '=', Dataset::PRIVACY_PUBLIC);
            }
            // superadmins see everything
            if (User::ADMIN == Auth::user()->access_level) {
                return $builder;
            }
            // now the complex case: the regular user
            return $builder->whereRaw('measurements.id IN
(SELECT p1.id FROM measurements AS p1
JOIN datasets ON (datasets.id = p1.dataset_id)
WHERE datasets.privacy > 0
UNION
SELECT p1.id FROM measurements AS p1
JOIN datasets ON (datasets.id = p1.dataset_id)
JOIN dataset_user ON (datasets.id = dataset_user.dataset_id)
WHERE datasets.privacy = 0 AND dataset_user.user_id = '.Auth::user()->id.'
)');
        });
    }

    public function newQuery($excludeDeleted = true)
    {
        // This uses the explicit list to avoid conflict due to global scope
        return parent::newQuery($excludeDeleted)->addSelect(
            'measurements.id',
            'measurements.trait_id',
            'measurements.measured_id',
            'measurements.measured_type',
            'measurements.date',
            'measurements.dataset_id',
            'measurements.person_id',
            'measurements.bibreference_id',
            'measurements.value',
            'measurements.value_i',
            'measurements.value_a',
            'measurements.notes'
        );
    }
}
