<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Auth;
use Spatie\Activitylog\Traits\LogsActivity;

class Measurement extends Model
{
    use IncompleteDate, LogsActivity;

    protected $fillable = ['trait_id', 'measured_id', 'measured_type',
        'date', 'dataset_id', 'person_id', 'bibreference_id',
        'value', 'value_i', 'value_a', 'notes', ];

    protected $appends = ['taxon_id','location_id','linked_type','linked_id'];

    //activity log trait (parent, uc and geometry are logged in controller)
    protected static $logName = 'measurement';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at'];
    protected static $logFillable = true;
    //protected static $logAttributes = ['name','altitude','adm_level','datum','x','y','startx','starty','notes'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function rawLink()
    {
        return '<a href="'.url('measurements/'.$this->id).'">'.
                // Needs to escape special chars, as this will be passed RAW
                htmlspecialchars($this->valueDisplay).'</a>';
    }

    public function measured()
    {
        return $this->morphTo();
    }

    public function getLocationIdAttribute()
    {
      if ($this->measured_type == Individual::class) {
        return $this->measured->location_first()->first()->id;
      }
      if ($this->measured_type == Location::class) {
        return $this->measured_id;
      }
      if ($this->measured_type == Voucher::class) {
        return $this->measured->location_first()->first()->id;
      }
      return NULL;
    }

    public function getTaxonIdAttribute()
    {
      if ($this->measured_type == Individual::class && isset($this->measured->identification)) {
        return $this->measured->identification->taxon_id;
      }

      if ($this->measured_type == Voucher::class && isset($this->measured->identification))
      {
        return $this->measured->identification->taxon_id;
      }
      if ($this->measured_type == Taxon::class) {
        return $this->measured_id;
      }
      return NULL;
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

    /* for some reason this stopped working as used to - AV detected 24-03-2021
    public function linked()
    {
        return $this->morphTo();
    }
    and so, was replaced by the following:
    the polymorphic model could be used by adding columns linked_type + linked_id to the measurements table
    which would be perhaps a better solution than saving the linked_id in  value_i
    */

    public function linked()
    {
      return $this->belongsTo($this->linked_type,'value_i');
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


    //ge value for display in pages and tables
    public function getValueDisplayAttribute()
    {
        switch ($this->type) {
        case ODBTrait::QUANT_INTEGER:
            return $this->value_i;
            break;
        case ODBTrait::QUANT_REAL:
            return $this->value;
            break;
        case ODBTrait::TEXT:
            if (strlen($this->value_a)>191) {
              $val = substr($this->value_a,0,191)."...";
            } else {
              $val = $this->value_a;
            }
            return $val;
            break;
        case ODBTrait::COLOR:
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
       case ODBTrait::SPECTRAL:
            $val = explode(";",$this->value_a);
            return 'Spectrum with '.count($val).' values';
            break;
        }
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
                return $builder->whereRaw("(measurements.dataset_id IN (SELECT dts.id FROM datasets as dts WHERE dts.privacy>='".Dataset::PRIVACY_PUBLIC."'))");
            }
            // superadmins see everything
            if (User::ADMIN == Auth::user()->access_level) {
                return $builder;
            }
            // now the complex case: the regular user
            return $builder->whereRaw("(measurements.dataset_id IN
(SELECT dts.id FROM datasets as dts WHERE dts.privacy>='".Dataset::PRIVACY_REGISTERED."')
OR
measurements.dataset_id IN
(SELECT dts.id FROM datasets as dts JOIN dataset_user as dtu ON dtu.dataset_id=dts.id WHERE (dts.privacy >='".Dataset::PRIVACY_REGISTERED."') OR dtu.user_id='".Auth::user()->id."'))");
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

    /* dwc terms */
    public function getMeasurementTypeAttribute()
    {
        return $this->odbtrait->export_name;
    }
    public function getMeasurementUnitAttribute()
    {
        return $this->odbtrait->unit;
    }
    public function getMeasurementValueAttribute()
    {
      return $this->valueActual;
    }
    public function getMeasurementRemarksAttribute()
    {
      return $this->notes;
    }

    public function getMeasurementDeterminedByAttribute()
    {
      return $this->person->abbreviation;
    }
    public function getMeasurementDeterminedDateAttribute()
    {
      return $this->formatDate;
    }
    public function getResourceRelationshipAttribute()
    {
      $measured_type = str_replace("App\\Models\\","", $this->measured_type);
      switch ($measured_type) {
          case Individual::class:
            $type = 'organism';
            break;
          case Voucher::class:
            $type = 'preservedSpecimen';
            break;
          case Taxon::class:
            $type = 'taxon';
            break;
          case Location::class:
            $type = 'location';
            break;
        default:
          $type=null;
          break;
      }
      return $type;
    }

    public function getResourceRelationshipIDAttribute()
    {
      return $this->measured->fullname;
    }

    public function getRelationshipOfResourceAttribute()
    {
      return "measurement of";
    }

    public function getScientificNameAttribute()
    {
      if(in_array($this->measured_type,[Individual::class,Voucher::class,Taxon::class,Media::class])) {
        return $this->measured->scientificName;
      }
      return null;
    }
    public function getFamilyAttribute()
    {
      if(in_array($this->measured_type,[Individual::class,Voucher::class,Taxon::class,Media::class])) {
        return $this->measured->family;
      }
      return null;
    }
    public function getAccessRightsAttribute()
    {
      return $this->dataset->accessRights;
    }
    public function getBibliographicCitationAttribute()
    {
      return $this->dataset->bibliographicCitation;
    }
    /* will be returned in english */
    public function getMeasurementMethodAttribute()
    {
        return $this->odbtrait->measurementMethod;
    }
    public function getModifiedAttribute()
    {
      return $this->updated_at->toJson();
    }
    public function getBasisOfRecordAttribute()
    {
      return 'MeasurementsOrFact';
    }
    public function getLicenseAttribute()
    {
      return $this->dataset->dwcLicense;
    }
}
