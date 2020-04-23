<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\ODBTrait;
use App\Translatable;
use App\TraitObject;
use App\TraitCategory;
use App\UserTranslation;
use Lang;
use DB;


class ImportTraits extends AppJob
{
    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $data = $this->extractEntrys();
        if (!$this->setProgressMax($data)) {
            return;
        }
        foreach ($data as $odbtrait) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if ($this->validateData($odbtrait)) {
                // Arrived here: let's import it!!
                try {
                    $this->import($odbtrait);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+On line'.$e->getLine().' on trait '.$odbtrait['export_name']);
                    //$this->appendLog('cheguei aqui o registro e valido');
                }
            }
        }
    }

    //main function that checks all data validations
    protected function validateData(&$odbtrait)
    {
        if (!$this->hasRequiredKeys(['export_name','type','name','description','objects'], $odbtrait)) {
            return false;
        }
        //validate name and translations
        if (!$this->validateNameTranslations($odbtrait)) {
            return false;
        }
        //check type for mandatory info
        if (!$this->validateTraitType($odbtrait)) {
            return false;
        }
        //check link_types
        if (!$this->validateObjectType($odbtrait)) {
            return false;
        }
        return true;
    }

    //function to validade name array with translation language_ids
    private function validateLanguageKeys($names) {
      //name is an array with keys being language ids or codes
      $validids = DB::table('languages')->pluck('id')->toArray();
      $validcodes = DB::table('languages')->pluck('code')->toArray();
      $validkeys = 1;
      foreach($names as $key => $name) {
         if (is_array($name)) {
            foreach($name as $subkey => $subname) {
              if (!in_array($subkey,$validids) && !in_array($subkey,$validcodes)) {
                $validkeys = null;
              }
            }
         } else {
           if (!in_array($key,$validids) && !in_array($key,$validcodes)) {
             $validkeys = null;
           }
         }
      }
      return $validkeys;
    }


    //check if trait has a name and a description and that language is specified
    protected function validateNameTranslations(&$odbtrait) {
      if (null == $odbtrait['name'] or null === $odbtrait['description']) {
        $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name'].' requires name and description, and these must to be arrays with names corresponding to registered language ids or codes');
        return false;
      }
      //if name is not array and keys do not matck registered languages
      if (!is_array($odbtrait['name']) || null === Self::validateLanguageKeys($odbtrait['name'])) {
        $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name']." requires 'name' to be an array with names corresponding to registered language ids or codes");
        return false;
      }
      //if name is not array and keys do not matck registered languages
      if (!is_array($odbtrait['description']) || null === Self::validateLanguageKeys($odbtrait['description'])) {
        $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name']." requires 'description' to be an array with names corresponding to registered language ids or codes");
        return false;
      }
      //check length and descriptions match
      if (count($odbtrait['name'])!=count($odbtrait['description'])) {
        $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name']." requires a 'description' for each 'name' translation");
        return false;
      }
      return true;
    }

    protected function validateTraitType(&$odbtrait)
    {
        //check if type informed
        if (!in_array($odbtrait['type'],[ODBTrait::QUANT_INTEGER, ODBTrait::QUANT_REAL, ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL, ODBTrait::TEXT, ODBTrait::COLOR])) {
          $this->skipEntry($odbtrait, 'Type ['.$odbtrait['type'].'] for trait '.$odbtrait['export_name'].' is invalid!');
          return false;
        }
        //check if info for quantitative traits was informed
        if (in_array($odbtrait['type'], [ODBTrait::QUANT_INTEGER, ODBTrait::QUANT_REAL])) {
          if (!array_key_exists('unit',$odbtrait) || !array_key_exists("range_max",$odbtrait) || !array_key_exists("range_min",$odbtrait)) {
            //null === $odbtrait['unit'] or null === $odbtrait['range_max'] or null === $odbtrait['range_min']) {
            $this->skipEntry($odbtrait, 'Missing unit, range_min or range_max for trait '.$odbtrait['export_name'].'Mandatory for quantitative traits');
            return false;
          }
          if (!is_numeric($odbtrait['range_max']) or !is_numeric($odbtrait['range_min'])) {
            $this->skipEntry($odbtrait, 'Range_max and range_min must be numeric. Trait '.$odbtrait['export_name']);
            return false;
          }
        }
        //check for categorical values
        if (in_array($odbtrait['type'], [ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL])) {
            //minimally requires a category name to be a valid categorical trait
            if (!array_key_exists('cat_name',$odbtrait)) {
              $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name'].' requires at least one category informed in cat_name');
              return false;
            }
            //category name must exist, be an array named with language id or code
            if (!is_array($odbtrait['cat_name']) || null === Self::validateLanguageKeys($odbtrait['cat_name'])) {
                $this->skipEntry($odbtrait, 'Categories names for trait '.$odbtrait['export_name']." must be an array with names corresponding to registered language ids or codes");
                return false;
            }
            //descriptions are not mandatory for categories, otherwise redundant in some cases, but if informed, must be valid
            if (array_key_exists('cat_description',$odbtrait)) {
              if (!is_array($odbtrait['cat_description']) || null === Self::validateLanguageKeys($odbtrait['cat_description'])) {
                $this->skipEntry($odbtrait, 'Categories descriptions for trait '.$odbtrait['export_name']." must be an array with names corresponding to registered language ids or codes");
                return false;
              }
              //if description exists for categories. Must have the same number of elements
              if (count($odbtrait['cat_description'])!==count($odbtrait['cat_name'])) {
                $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name'].' cat_name and cat_description must have the same length');
                return false;
              }
            }
            // // TODO: if ordnial issue a // WARNING:that rank is assumed as order of categories.
            //Or add documentation
        }
        return true;
    }

    protected function validateObjectType(&$odbtrait) {
      //convert to array if only one value informed
      if (!is_array($odbtrait['objects'])) {
        $odbtrait['objects'] = array($odbtrait['objects']);
      }
      //format with key values
      foreach($odbtrait['objects'] as $key => $object) {
         if (in_array($object,array("Plant","Voucher","Location","Taxon"))) {
           $odbtrait['objects'][$key] = array_search($object,array("Plant","Voucher","Location","Taxon"));
         } else {
           //then fail  as object type is invalid
           $this->skipEntry($odbtrait, 'ObjectType '.$object.' for trait '.$odbtrait['export_name'].' is invalid!');
           break;
           return false;
         }
      }
      return true;
    }

    public function import($odbtrait)
    {

      //check if already exists if so skip
      if (ODBTrait::whereRaw('export_name = ?', [$odbtrait['export_name']])->count() > 0) {
          $this->skipEntry($odbtrait, ' trait '.$odbtrait['export_name'].' already in the database');
          return;
      }

      //GET FIELD VALUES
      $export_name = $odbtrait['export_name'];
      $odbtype = $odbtrait['type'];
      $names = $odbtrait['name'];



      #if (!is_array($odbtrait['name'])) {
      #  $txt2 = "";
      #  foreach($odbtrait['cat_name'] as $key => $val) {
      #    $txt2 .= $key."=>";
      #    foreach($val as $k2 => $val2) {
      #      $txt2 .= "Sub:".$k2."=>".$val2."\n";
      #    }
      #  }
      #  $txttest = Self::validateLanguageKeys($odbtrait['cat_name']);
      #  $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name']." o problema esta aqui".$txt2." e o teste=".$txttest."  ATE AQUI");
      #  return false;
      #}



      $descriptions = $odbtrait['description'];
      $objects = $odbtrait['objects'];
      // Set fields from quantitative traits
      if (in_array($odbtype, [ODBTrait::QUANT_INTEGER, ODBTrait::QUANT_REAL])) {
          $unit = $odbtrait['unit'];
          $range_max = $odbtrait['range_max'];
          $range_min = $odbtrait['range_min'];
      } else {
        $unit = null;
        $range_max = null;
        $range_min = null;
      }

      // Set link type
      //if (in_array($this->type, [self::LINK])) {
      //    $this->link_type = $request->link_type;
      //} else {
      //    $this->link_type = null;
      //}

      $cat_names = array_key_exists('cat_name', $odbtrait) ? $odbtrait['cat_name'] : null;
      $cat_descriptions = array_key_exists('cat_description', $odbtrait) ? $odbtrait['cat_description'] : null;

      //create new
      $odbtrait = new ODBTrait([
          'type' => $odbtype,
          'export_name' => $export_name,
          'unit' => $unit,
          'range_max' => $range_max,
          'range_min' => $range_min,
      ]);
      //save name translations
      $odbtrait->save();
      foreach ($objects as $key) {
        $odbtrait->hasMany(TraitObject::class, 'trait_id')->create(['object_type' => ODBTrait::OBJECT_TYPES[$key]]);
      }
      $odbtrait->save();

      //SAVE name and category VIA translations

      //get ids and codes from language table
      $validids = DB::table('languages')->pluck('id')->toArray();
      $validcodes = DB::table('languages')->pluck('code')->toArray();

      //save trait name and descriptions
      foreach ($names as $key => $translation) {
          $lang = $key;
          if (in_array($key,$validcodes)) {
            $lang = $validids[array_search($key,$validcodes)];
          }
          $odbtrait->setTranslation(UserTranslation::NAME, $lang, $translation);
      }
      foreach ($descriptions as $key => $translation) {
          $lang = $key;
          if (in_array($key,$validcodes)) {
            $lang = $validids[array_search($key,$validcodes)];
          }
          $odbtrait->setTranslation(UserTranslation::DESCRIPTION, $lang, $translation);
      }
      $odbtrait->save();


      //save categories for categorical and ordinal
      if (in_array($odbtype, [ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL])) {
          foreach($cat_names as $rank => $cat_name) {
              $cat = $odbtrait->hasMany(TraitCategory::class, 'trait_id')->create(['rank' => $rank+1]);
              foreach ($cat_name as $key => $translation) {
                $lang = $key;
                if (in_array($key,$validcodes)) {
                  $lang = $validids[array_search($key,$validcodes)];
                }
                $cat->setTranslation(UserTranslation::NAME, $lang, $translation);
              }
              //descriptions are not required for categories
              //if (null === $cat_descriptions) {
              $cat_description = $cat_descriptions[$rank];
              if (is_array($cat_description)) {
                foreach ($cat_description as $key => $translation) {
                  $lang = $key;
                  if (in_array($key,$validcodes)) {
                    $lang = $validids[array_search($key,$validcodes)];
                  }
                  $cat->setTranslation(UserTranslation::DESCRIPTION, $lang, $translation);
                }
              }
          }
          $cat->save();
      }
      $this->affectedId($odbtrait->id);
      return;
    }
}
