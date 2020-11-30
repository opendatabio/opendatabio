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

        //if type is a categorical or ordinal, validate category languages for category names which must be informed
        if (in_array($odbtrait['type'],[ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL])) {
          if (!$this->validateCategories($odbtrait)) {
              return false;
          }
        }
        //check link_types
        if (!$this->validateObjectType($odbtrait)) {
            return false;
        }
        return true;
    }

    //function to validade name array with translation language_ids
    private function validateLanguageKeys(&$names) {
      //name is an array with keys being language ids or codes or names (case sensitive)
      $result = array();
      foreach($names as $key => $name) {
         //if there are multiple languages, name is an array with each language also an array
         if (is_array($name)) {
            foreach($name as $subkey => $subname) {
              $lang = ODBFunctions::validRegistry(Language::select('id'),$subkey, ['id', 'code','name']);
              if ($lang) {
                //place id as code if found language
                $result[] = [$lang->id => $subname];
              }
            }
         } else {
           //name is a single language
           $lang = ODBFunctions::validRegistry(Language::select('id'),$key, ['id', 'code','name']);
           if ($lang) {
             //place id as code if found language
             $result[] = [$lang->id => $name];
           }
         }
      }
      if (count($result)>0) {
        return null;
      }

      //modify and retur true
      $names = $result;
      return true;
    }

    private function validateCategoriesTranslations(&$categories,$traitType)
    {

      $result = array();
      //each category may have 'lang', 'name', 'description' and 'rank'
      //name.rank.lang must be unique;
      $mustbeunique = array();
      foreach($categories as $category) {
          if (!$this->hasRequiredKeys(['lang','name','rank'], $category)) {
            return false;
         }
         //rank is mandatory because it links different translations and must be number
         if (!is_numeric($category['rank'])) {
           return false;
         }
         $rank = $category['rank'];
         if (!array_key_exists('rank',$result)) {
           $result[$rank] = array();
         }
         $lang = ODBFunctions::validRegistry(Language::select('id'),$category['lang'], ['id', 'code','name']);
         if ($lang) {
            //if language is found
            $result[$rank] = ['translation_type' => UserTranslation::NAME, 'translation' => $category['name'], 'lang' => $lang->id];
            //description is not mandatory for categories
            if (array_key_exists('description',$category)){
             $result[$rank] = ['translation_type' => UserTranslation::DESCRIPTION, 'translation' => $category['description'], 'lang' => $lang->id];
            }
            $mustbeunique[] = $category['name'].$lang->id.$rank;
        } else {
            return false;
        }
      }
      //if there are duplicated values dont import
      if (count($mustbeunique) > count(array_unique($mustbeunique))) {
        return false;
      }

      //modify and return true
      $categories = $result;
      return true;
    }

    protected function validateCategories(&$odbtrait) {
      if (null == $odbtrait['categories']) {
        $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name']." requires 'categories' translations, each having the as possible fields 'lang','name','description','rank'");
        return false;
      }
      //validate categories
      if (!is_array($odbtrait['categories']) or  !Self::validateCategoriesTranslations($odbtrait['categories'])) {
        $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name']." has some problem with the format of the categories field. Must have for each language: 'lang', 'name' and 'rank', and 'description' is optional. Category.Rank.Language must be unique");
        return false;
      }
      return true;
    }


    //check if trait has a name and a description and that language is specified
    //modifie name and description to an import format with ids as keys.
    protected function validateNameTranslations(&$odbtrait) {
      if (null == $odbtrait['name'] or null === $odbtrait['description']) {
        $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name'].' requires name and description');
        return false;
      }
      //if name is not array and keys do not matck registered languages
      if (!is_array($odbtrait['name']) || null === Self::validateLanguageKeys($odbtrait['name'])) {
        $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name']." requires 'name' to be an array with keys corresponding to registered language ids, codes or names");
        return false;
      }
      //if name is not array and keys do not matck registered languages
      if (!is_array($odbtrait['description']) || null === Self::validateLanguageKeys($odbtrait['description'])) {
        $this->skipEntry($odbtrait, 'Trait '.$odbtrait['export_name']." requires 'description' to be an array with keys corresponding to registered language ids, codes or names");
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
        if (in_array($odbtrait['type'], [ODBTrait::QUANT_INTEGER, ODBTrait::QUANT_REAL, ODBTrait::SPECTRAL])) {
          if (!array_key_exists('unit',$odbtrait)) {
            //null === $odbtrait['unit'] or null === $odbtrait['range_max'] or null === $odbtrait['range_min']) {
            $this->skipEntry($odbtrait, 'Missing unit for trait '.$odbtrait['export_name'].'Mandatory for quantitative and spectral traits');
            return false;
          }
          if (isset($odbtrait['range_max']) and isset($odbtrait['range_min'])) {
            if (!is_numeric($odbtrait['range_max']) or !is_numeric($odbtrait['range_min'])) {
              $this->skipEntry($odbtrait, 'Range_max and range_min must be numeric. Trait '.$odbtrait['export_name']);
              return false;
            }
          } else {
            if ($odbtrait['type'] == ODBTrait::SPECTRAL) {
              $this->skipEntry($odbtrait, 'Range_max and range_min must be informed for spectral Trait '.$odbtrait['export_name']);
              return false;
            }
          }
          if ($odbtrait['type'] == ODBTrait::SPECTRAL) {
            if (!isset($odbtrait['value_length']) or !is_numeric($odbtrait['value_length'])) {
              $this->skipEntry($odbtrait, 'A numeric value_length must be informed for spectral Trait '.$odbtrait['export_name']);
              return false;
            }
          }
        }


        //LINK TYPE TRAITS VALIDATION
        if ($odbtrait['type'] == ODBTrait::LINK) {
          if (!array_key_exists('link_type',$odbtrait)) {
            //null === $odbtrait['unit'] or null === $odbtrait['range_max'] or null === $odbtrait['range_min']) {
            $this->skipEntry($odbtrait, 'Missing link_type for trait '.$odbtrait['export_name'].'Mandatory for LINK traits');
            return false;
          }
          if (!in_array($odbtrait['link_type'], ODBTrait::LINK_TYPES) and !in_array($odbtrait['link_type'],ODBTrait::getLinkTypeBaseName())) {
            $this->skipEntry($odbtrait, 'Link_type for trait '.$odbtrait['export_name'].' is invalid');
            return false;
          }
          if (in_array($odbtrait['link_type'],ODBTrait::getLinkTypeBaseName())) {
            $odbtrait['link_type'] = ODBTrait::LINK_TYPES[array_search($odbtrait['link_type'],$tr)];
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
      //this may be a string separated by commas or an array
      //convert to array if not array
      if (!is_array($odbtrait['objects'])) {
        $odbtrait['objects'] = explode(','$odbtrait['objects']);
      }
      $objects_informed = $odbtrait['objects'];
      //values are valid?
      $validobjectsClass = ODBTrait::OBJECT_TYPES;
      $validobjectsName = collect(ODBTrait::OBJECT_TYPES)->map(function($obj) { return str_replace("App\\","",$obj);});
      $validobjects = array_combine($validobjectsName,$validobjectsClass);
      $objects = array_filter(
          $validobjects,
          function ($key) use ($objects_informed) {
              return in_array($key, $objects_informed);
          },
          ARRAY_FILTER_USE_KEY
      );
      if (count($objects)>0) {
        $odbtrait['objects'] = array_values($objects);
        return true;
      }

      //then fail  as object type is invalid
       $this->skipEntry($odbtrait, 'ObjectType '.$object.' for trait '.$odbtrait['export_name'].' is invalid!');
      return false;

    }

    public function import($odbtrait)
    {

      //check if already exists if so skip
      if (ODBTrait::whereRaw('export_name like ?', [$odbtrait['export_name']])->count() > 0) {
          $this->skipEntry($odbtrait, ' trait '.$odbtrait['export_name'].' already in the database');
          return false;
      }
      $bibreference = null;
      if (isset($odbtrait['bibreference_id'])) {
          $bibreference = ODBFunctions::validRegistry(BibReference::select('id'),$odbtrait['bibreference_id'],'id');
          if (!$bibreference) {
            $this->skipEntry($odbtrait, ' trait '.$odbtrait['export_name'].' informed bibreference id is invalid');
            return;
          }
          $bibreference = $bibreference->id;
      }


      //GET FIELD VALUES
      $export_name = $odbtrait['export_name'];
      $odbtype = $odbtrait['type'];
      $names = $odbtrait['name'];
      $descriptions = $odbtrait['description'];

      $objects = $odbtrait['objects'];
      // Set fields from quantitative traits
      if (in_array($odbtype, [ODBTrait::QUANT_INTEGER, ODBTrait::QUANT_REAL, ODBTrait::SPECTRAL])) {
          $unit = $odbtrait['unit'];
          $range_max = isset($odbtrait['range_max']) ? $odbtrait['range_max'] : null;
          $range_min = isset($odbtrait['range_min']) ? $odbtrait['range_min'] : null;
      } else {
        $unit = null;
        $range_max = null;
        $range_min = null;
      }
      $link_type = null;
      if (in_array($odbtype, [ODBTrait::LINK])) {
        $link_type = $odbtrait['link_type'];
      }

      $value_length = null;
      if (in_array($odbtype, [ODBTrait::SPECTRAL])) {
        $link_type = $odbtrait['value_length'];
      }

      //create new
      $odbtrait = new ODBTrait([
          'type' => $odbtype,
          'export_name' => $export_name,
          'unit' => $unit,
          'range_max' => $range_max,
          'range_min' => $range_min,
          'value_length' => $value_length,
          'link_type' => $link_type,
          'bibreference_id' => $bibreference,
      ]);
      //save trait
      $odbtrait->save();

      //save allowed objects
      foreach ($objects as $object) {
        $odbtrait->hasMany(TraitObject::class, 'trait_id')->create(['object_type' => $object]);
      }
      $odbtrait->save();

      //SAVE name and category VIA translations

      //save trait name and descriptions
      foreach ($names as $lang => $translation) {
          $odbtrait->setTranslation(UserTranslation::NAME, $lang, $translation);
      }
      foreach ($descriptions as $lang => $translation) {
          $odbtrait->setTranslation(UserTranslation::DESCRIPTION, $lang, $translation);
      }

      //save categories for categorical and ordinal
      if (in_array($odbtype, [ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL])) {
          foreach($categories as $rank => $translations) {
              $cat = $odbtrait->hasMany(TraitCategory::class, 'trait_id')->create(['rank' => $rank]);
              foreach ($translations as $translation) {
                $cat->setTranslation($translation['translation_type'],$translation['lang'],$translation['translation']);
              }
              $cat->save();
          }
      }
      $this->affectedId($odbtrait->id);
      return;
    }
}
