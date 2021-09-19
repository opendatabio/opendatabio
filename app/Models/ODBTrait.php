<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lang;
use DB;
use Activity;
use App\Models\MeasurementCategory;
use App\Models\TraitCategory;
use App\Models\ActivityFunctions;
use Spatie\Activitylog\Traits\LogsActivity;
use CodeInc\StripAccents\StripAccents;


// class name needs to be different as Trait is a PHP reserved word
class ODBTrait extends Model
{
    use Translatable, LogsActivity;

    // Types that can have measurements associated with
    // If this is ever changed, remember to edit getObjectTypeNames!
    const OBJECT_TYPES = [
        Individual::class,
        Voucher::class,
        Location::class,
        Taxon::class,
    ];
    // Types that can receive database link traits
    // NOTE: all link_types must support a "fullname" method
    const LINK_TYPES = [
        Taxon::class,
        //Person::class,
        //Individual::class,
    ];

    const QUANT_INTEGER = 0;
    const QUANT_REAL = 1;
    const CATEGORICAL = 2;
    const CATEGORICAL_MULTIPLE = 3;
    const ORDINAL = 4;
    const TEXT = 5;
    const COLOR = 6;
    const LINK = 7; // may include genomic / spectral??
    const SPECTRAL =8;
    const TRAIT_TYPES = [
        self::QUANT_INTEGER,
        self::QUANT_REAL,
        self::CATEGORICAL,
        self::CATEGORICAL_MULTIPLE,
        self::ORDINAL,
        self::TEXT,
        self::COLOR,
        self::LINK,
        self::SPECTRAL
    ];

    protected $fillable = ['type', 'export_name', 'unit', 'range_min', 'range_max', 'link_type', 'value_length', 'bibreference_id'];
    protected $table = 'traits';

    //activity log trait
    protected static $logName = 'trait';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at'];
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;


    public function rawLink()
    {
        return "<a href='".url('traits/'.$this->id)."'>".htmlspecialchars($this->name).'</a>';
    }

    // for use in the trait edit dropdown
    public static function getObjectTypeNames()
    {
        return [
            Lang::get('classes.'.Individual::class),
            Lang::get('classes.'.Voucher::class),
            Lang::get('classes.'.Location::class),
            Lang::get('classes.'.Taxon::class),
        ];
    }

    public function getTypenameAttribute()
    {
       switch ($this->type) {
          case self::QUANT_INTEGER:
           return "QUANT_INTEGER";
           break;
          case self::QUANT_REAL:
           return "QUANT_REAL";
           break;
          case self::CATEGORICAL:
           return "CATEGORICAL";
           break;
          case self::CATEGORICAL_MULTIPLE:
           return "CATEGORICAL_MULTIPLE";
           break;
          case self::ORDINAL:
           return "ORDINAL";
           break;
         case self::TEXT:
           return "TEXT";
           break;
         case self::COLOR:
           return "COLOR";
           break;
         case self::LINK:
           return "LINK";
           break;
         case self::SPECTRAL:
           return "SPECTRAL";
           break;
         default:
           return null;
           break;
       }
    }

    public static function getLinkTypeBaseName()
    {
       return collect(self::LINK_TYPES)->map(function($link){ return class_basename($link);})->toArray();
    }

    public function getObjectKeys()
    {
        $ret = [];
        foreach ($this->object_types()->pluck('object_type') as $search) {
            $ret[] = array_keys(self::OBJECT_TYPES, $search)[0];
        }

        return $ret;
    }


    public static function validateExportName(&$request)
    {
      if (!isset($request->export_name)) {
        return false;
      }
      $export_name = str_replace(" ","",$request->export_name);
      $export_name = StripAccents::strip( (string) $export_name);
      preg_match('/^([a-zA-Z0-9]{1}?[a-z0-9|-|_|.]+)*$/', $export_name, $output_array);
      if ($output_array) {
        if ($output_array[0]== $export_name) {
          $request->export_name = $export_name;
          return true;
        }
      }
      return false;
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
                'unit' => 'required_if:type,0,1,8',
                'cat_name' => 'array|required_if:type,2,3,4',
                'cat_name.1' => 'required_if:type,2,3,4',
                'cat_name.1.1' => 'required_if:type,2,3,4',
                'link_type' => 'required_if:type,7',
                'value_length' => 'required_if:type,8',
                'range_min' => 'required_if:type,8',
                'range_max' => 'required_if:type,8',
            ], $merge);
    }

    protected function makeCategory($rank, $names, $descriptions,$bibreference)
    {
        $cat = $this->categories()->create(['rank' => $rank, 'bibreference_id' => $bibreference]);
        foreach ($names as $key => $translation) {
            $cat->setTranslation(UserTranslation::NAME, $key, $translation);
        }
        foreach ($descriptions as $key => $translation) {
            $cat->setTranslation(UserTranslation::DESCRIPTION, $key, $translation);
        }

        return $cat;
    }



    protected function updateCategory($names, $descriptions,$bibreference)
    {
        $current_categories_ids = $this->categories()->pluck('id')->toArray();
        $current_cat_translations =  $this->categories()->get()
                                ->flatMap(function($cat) {
                                      return array('translations_'.$cat->id =>
                                      $cat->translations()->get()->flatMap(function($tr) {
                                      $newkey = $tr->language_id."_".$tr->translation_type;
                                      return array($newkey => $tr->translation);
                                      })->toArray());
                                    })->toArray();
        $skips = 0;
        $checked_ids = array();
        for ($i = 1; $i <= sizeof($names); ++$i) {
          // checks to see if there's at least one name provided
          if (!array_filter($names[$i])) {
              ++$skips;
              continue;
          }
          $rank = $i-$skips;
          $cat = $this->categories()->where('rank',$rank);
          if ($cat->count()) {
            $checked_ids[] = $cat->first()->id;
            $cat = $cat->first();
          } else {
            $cat = $this->categories()->create(['rank' => $rank, 'bibreference_id' => $bibreference]);
          }
          $cat_names = $names[$i];
          $cat_descriptions = $descriptions[$i];
          foreach ($cat_names as $language => $translation) {
             $cat->setTranslation(UserTranslation::NAME, $language, $translation);
          }
          foreach ($cat_descriptions as $language => $translation) {
             $cat->setTranslation(UserTranslation::DESCRIPTION, $language, $translation);
          }
        }
        //if current categories are not in update list, delete if  has NO  measurements
        $todelete = array_diff($current_categories_ids,$checked_ids);
        if (count($todelete)) {
          foreach($todelete as $category_id) {
                $has_measurements = $this->measurements()
                    ->whereHas('categories',function($category) use($category_id) {
                        $category->where('category_id',$category_id);
                    })->count();
                if (0 == $has_measurements) {
                  $this->categories()->where('id',$category_id)->delete();
                }
          }
        }
        //get new categories
        $new_cat_translations = $this->categories()->get()->flatMap(function($cat) {
                return array('translations_'.$cat->id =>
                $cat->translations()->get()->flatMap(function($tr) {
                  $newkey = $tr->language_id."_".$tr->translation_type;
                  return array($newkey => $tr->translation);
                  })->toArray());
                })->toArray();
        $logName = 'trait';
        $logDescription = 'category updated';
        $logDescriptionDeleted = 'category deleted';
        ActivityFunctions::logTranslationsChanges($this,$current_cat_translations,$new_cat_translations,$logName,$logDescription,$logDescriptionDeleted);
        return true;
    }

    public function setFieldsFromRequest($request)
    {
        //log old to track changes
        $old_object_types = $this->object_types()->get()->map(function($obj) {
              return $obj->object_type;})->toArray();
        $old_translations = array('translation' => $this->translations->flatMap(function($translation) {
          $newkey = $translation->language_id."_".$translation->translation_type;
          return array($newkey => $translation->translation);
        })->toArray());

        // Set fields from quantitative traits
        if (in_array($this->type, [self::QUANT_INTEGER, self::QUANT_REAL,self::SPECTRAL])) {
            $this->unit = $request->unit;
            $this->range_max = $request->range_max;
            $this->range_min = $request->range_min;
            if ($this->type == self::SPECTRAL)
            {
               $this->value_length = $request->value_length;
            }
        } else {
            $this->unit = null;
            $this->range_max = null;
            $this->range_min = null;
        }
        if (in_array($this->type, [self::CATEGORICAL, self::CATEGORICAL_MULTIPLE, self::ORDINAL])) {
            $names = $request->cat_name;
            $descriptions = $request->cat_description;
            $bibreference = $request->cat_bibreference;

            $cat_ids = $this->categories()->pluck('id')->toArray();
            $have_measurmements = MeasurementCategory::whereIn('category_id',$cat_ids)->count();
            if ($this->categories()->count() and $have_measurmements) {
                $this->updateCategory($names,$descriptions,$bibreference);
            } else {
              $this->categories()->delete();
              // counts the number of skipped entries, so the ranks will be matched to the names/description
              $skips = 0;
              for ($i = 1; $i <= sizeof($names); ++$i) {
                // checks to see if there's at least one name provided
                if (!array_filter($names[$i])) {
                    ++$skips;
                    continue;
                }
                $this->makeCategory($i - $skips, $names[$i], $descriptions[$i],$bibreference);
              }
            }
        }
        // Set link type
        if (in_array($this->type, [self::LINK])) {
            $this->link_type = $request->link_type;
        } else {
            $this->link_type = null;
        }

        // Set object types (can change if no measurements)
        $this->object_types()->delete();
        foreach ($request->objects as $key) {
            $this->object_types()->create(['object_type' => self::OBJECT_TYPES[$key]]);
        }
        //Translations for name and trait descriptions
        foreach ($request->name as $key => $translation) {
            $this->setTranslation(UserTranslation::NAME, $key, $translation);
        }
        foreach ($request->description as $key => $translation) {
            $this->setTranslation(UserTranslation::DESCRIPTION, $key, $translation);
        }

        $this->save();

        /* log changes if the case */
        $new_object_types = $this->object_types()->get()->map(function($obj) {
              return $obj->object_type;})->toArray();
        $object_types_deleted = array_diff($old_object_types,$new_object_types);
        $object_types_added  = array_diff($old_object_types,$new_object_types);
        if (count($object_types_added) or count($object_types_deleted)) {
          $tolog = array('attributes' => array('object_types' => $new_object_types), 'old' => array('object_types' => $old_object_types));
          activity('trait')
            ->performedOn($this)
            ->withProperties($tolog)
            ->log('updated');
        }


        $new_translations = array('translation' => ODBTrait::where('id',$this->id)->first()->translations->flatMap(function($translation) {
          $newkey = $translation->language_id."_".$translation->translation_type;
          return array($newkey => $translation->translation);
        })->toArray());
        $logName = 'trait';
        $logDescription = 'updated';
        ActivityFunctions::logTranslationsChanges($this,$old_translations,$new_translations,$logName,$logDescription,"");
    }

    public function object_types()
    {
        return $this->hasMany(TraitObject::class, 'trait_id');
    }

    public function getObjectsAttribute()
    {
      return implode(' | ',$this->object_types()->pluck('object_type')->toArray());
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

    public function getSpectralNamesAttribute()
    {
        $ret = array();
        if (isset($this->range_min) & isset($this->range_max) & isset($this->value_length)) {
            $min = $this->range_min;
            $max = $this->range_max;
            $length = $this->value_length;
            $step = ($max-$min)/($length-1);
            $ret = range($min,$max,$step);
        }
        return $ret;
    }

    public function bibreference()
    {
        return $this->belongsTo('App\Models\BibReference', 'bibreference_id');
    }

    public function getMeasurementTypeAttribute()
    {
      return $this->export_name;
    }
    public function getMeasurementUnitAttribute()
    {
      return $this->export_name;
    }
    public function getMeasurementMethodAttribute()
    {
      /* should be returned in english if present */
      $lang = 1;
      $odbtrait_name = $this->translate(0,$lang);
      $odbtrait_description = $this->translate(1,$lang);
      if ($this->categories()->count()==0) {
        return "Name: ".$odbtrait_name." | Definition:".$odbtrait_description;
      }
      $categories = $this->categories()->cursor()->map(function($cat) use ($lang){
        return "CategoryName: ".$cat->translate(0,$lang)." | Definition:".$cat->translate(1,$lang);
      })->toArray();
      $categories = implode(" | ",$categories);
      return "Name: ".$odbtrait_name." | Definition:".$odbtrait_description." | Categories: ".$categories;
    }


}
