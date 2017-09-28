<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use DB;
use Auth;

class Measurement extends Model
{
    protected $fillable = ['trait_id', 'measured_id', 'measured_type', 
        'date', 'dataset_id', 'person_id', 'bibreference_id',
        'value', 'value_i', 'value_a'];

    use IncompleteDate;

    public function measured() 
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

    // provides a common interface for getting/setting value for different types of measurements
    public function getValueActualAttribute() {
        switch ($this->type) {
        case ODBTrait::QUANT_INTEGER:
            return $this->value_i;
            break;
        case ODBTrait::QUANT_REAL:
            return $this->value;
            break;
        case ODBTrait::TEXT:
        case ODBTrait::COLOR:
            return $this->value_a;
            break;
            // TODO: Link & categories
        }
    }
    public function setValueActualAttribute($value) {
        switch ($this->type) {
        case ODBTrait::QUANT_INTEGER:
            $this->value_i = $value;
            break;
        case ODBTrait::QUANT_REAL:
            $this->value = $value;
            break;
        case ODBTrait::TEXT:
        case ODBTrait::COLOR:
            $this->value_a = $value;
            break;
            // TODO: Link & categories
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
            if (Auth::user()->access_level == User::ADMIN) {
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
WHERE datasets.privacy = 0 AND dataset_user.user_id = ' . Auth::user()->id . '
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
            'measurements.value_a'
		);
	}
}
