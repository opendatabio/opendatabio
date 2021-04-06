<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Models\ODBTrait;
use App\Models\Translatable;
use App\Models\TraitObject;
use App\Models\TraitCategory;
use App\Models\UserTranslation;
use App\Models\ODBFunctions;
use App\Models\Language;
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


        //translations may be in a separate file (prep to allow link to variable
        $translations = isset($this->userjob->data['data']['translations']) ? $this->userjob->data['data']['translations'] : null;
        $translations_arr = [];
        if (!is_null($translations))  {
            foreach($translations as $translation) {
                $key = $translation['export_name'];
                if (!$this->hasRequiredKeys(['export_name','name','description','rank','lang'],$translation)) {
                    return false;
                }
                $vararra = [
                  'name' => [],
                  'description' => [],
                  'categories' => [],
                ];
                if (array_key_exists($key,$translations_arr)) {
                  $vararra = $translations_arr[$key];
                }
                $is_category = isset($translation['rank']) ? (int) $translation['rank'] : 0;
                if ($is_category>0) {
                  $vararra['categories'][] = $translation;
                } else {
                  $vararra['name'][$translation['lang']] = $translation['name'];
                  $vararra['description'][$translation['lang']] = $translation['description'];
                }
                $translations_arr[$key] = $vararra;
            }
        }


        foreach ($data as $odbtrait) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();
            if (!$this->hasRequiredKeys(['export_name','type','objects'], $odbtrait)) {
                return false;
            }
            //if translations are from a different file get respective values
            if (count($translations_arr)>0) {
               $key = $odbtrait['export_name'];
               $translation  = isset($translations_arr[$key]) ? $translations_arr[$key] : null;
               if (!is_null($translation)) {
                  if (count($translation['name'])>0 and !isset($odbtrait['name'])) {
                    $odbtrait['name'] = $translation['name'];
                  }
                  if (count($translation['description'])>0 and !isset($odbtrait['description'])) {
                    $odbtrait['description'] = $translation['description'];
                  }
                  if (count($translation['categories'])>0 and !isset($odbtrait['categories'])) {
                    $odbtrait['categories'] = $translation['categories'];
                  }
               }
            }

            if ($this->validateData($odbtrait)) {
                // Arrived here: let's import it!!

                try {
                    $this->import($odbtrait);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+On line'.$e->getLine().' on variable '.$odbtrait['export_name']);
                    //$this->appendLog('cheguei aqui o registro e valido');
                }
            }

        }
    }

    //main function that checks all data validations
    protected function validateData(&$odbtrait)
    {
        if (!$this->hasRequiredKeys(['export_name','name','description','type','objects'], $odbtrait)) {
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
             $result[$lang->id] = $name;
           }
         }
      }
      if (count($result)==0) {
        return null;
      }

      //modify and retur true
      $names = $result;
      return true;
    }


    protected function validateCategoriesTranslations(&$odbtrait)
    {

      $categories = $odbtrait['categories'];
      $result = [];
      //each category may have 'lang', 'name', 'description' and 'rank'
      //name.rank.lang must be unique;
      $mustbeunique = array();
      //$counter = 1;
      $usedranks = [];
      $traittype = $odbtrait['type'];
      foreach($categories as $category) {
         if (!$this->hasRequiredKeys(['lang','name','rank'], $category)) {
            return false;
         }
         //rank is mandatory because it groups different translations to the same category and must be a number greater than zero
         $rank = (int) $category['rank'];
         $uniquerank = $rank.$category['lang'];
         if ($rank == 0 or in_array($uniquerank,$usedranks)) {
           $this->appendLog('FAILED: Variable '.$odbtrait['export_name'].' has invalid rank for category '.$category['name'].': value '.$rank.' is either duplicate or not numeric.');
           return false;
         }
         $lang = ODBFunctions::validRegistry(Language::select('id'),$category['lang'], ['id', 'code','name']);
         if ($lang) {
            $rank = (string) $rank;
            //if language is found
            $result[$rank][] = ['translation_type' => UserTranslation::NAME, 'translation' => $category['name'], 'lang' => $lang->id];
            //description is not mandatory for categories
            if (array_key_exists('description',$category)){
             $result[$rank][] = ['translation_type' => UserTranslation::DESCRIPTION, 'translation' => $category['description'], 'lang' => $lang->id];
            }
            $mustbeunique[] = $category['name'].($lang->id).$rank;
          } else {
            $this->appendLog('FAILED: Variable '.$odbtrait['export_name'].' has category '.$category['name'].' with invalid language '.$category['lang'].' value');
            return false;
          }
          //$counter = $counter+1;
          $usedranks[] =  $uniquerank;
      }
      //if there are duplicated values dont import
      if (count($mustbeunique) > count(array_unique($mustbeunique)))  {
        $this->appendLog('WARNING: Categories are not unique for variable '.$odbtrait['export_name'].'. The combination of (lang+rank+name) must be unique');
        return false;
      }

      //modify and return true
      $odbtrait['categories'] = $result;
      return true;
    }

    protected function validateCategories(&$odbtrait) {
      if (null == $odbtrait['categories']) {
        $this->skipEntry($odbtrait, 'Variable '.$odbtrait['export_name']." requires 'categories' translations, each having the as possible fields 'lang','name','description','rank'");
        return false;
      }
      //validate categories
      if (!is_array($odbtrait['categories']) or  !Self::validateCategoriesTranslations($odbtrait)) {
        $this->skipEntry($odbtrait, 'Variable '.$odbtrait['export_name']." has some problem with the format of the categories field. Must have for each language: 'lang', 'name' and 'rank', and 'description' is optional. Category.Rank.Language must be unique");
        return false;
      }
      return true;
    }


    //check if variable has a name and a description and that language is specified
    //modifie name and description to an import format with ids as keys.
    protected function validateNameTranslations(&$odbtrait) {
      if (null == $odbtrait['name'] or null == $odbtrait['description']) {
        $this->skipEntry($odbtrait, 'Variable '.$odbtrait['export_name'].' requires name and description');
        return false;
      }
      //if name is not array and keys do not matck registered languages
      if (!is_array($odbtrait['name']) || null === Self::validateLanguageKeys($odbtrait['name'])) {
        $this->skipEntry($odbtrait, 'Variable '.$odbtrait['export_name']." requires 'name' to be an array with keys corresponding to registered language ids, codes or names");
        return false;
      }
      //if name is not array and keys do not matck registered languages
      if (!is_array($odbtrait['description']) || null === Self::validateLanguageKeys($odbtrait['description'])) {
        $this->skipEntry($odbtrait, 'Variable '.$odbtrait['export_name']." requires 'description' to be an array with keys corresponding to registered language ids, codes or names");
        return false;
      }
      //check length and descriptions match
      if (count($odbtrait['name'])!=count($odbtrait['description'])) {
        $this->skipEntry($odbtrait, 'Variable '.$odbtrait['export_name']." requires a 'description' for each 'name' translation");
        return false;
      }
      return true;
    }

    protected function validateTraitType(&$odbtrait)
    {
        //check if type informed
        if (!in_array($odbtrait['type'],[ODBTrait::QUANT_INTEGER, ODBTrait::QUANT_REAL, ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL, ODBTrait::TEXT, ODBTrait::COLOR, ODBTrait::LINK, ODBTrait::SPECTRAL])) {
          $this->skipEntry($odbtrait, 'Type ['.$odbtrait['type'].'] for variable '.$odbtrait['export_name'].' is invalid!');
          return false;
        }
        //check if info for quantitative traits was informed
        if (in_array($odbtrait['type'], [ODBTrait::QUANT_INTEGER, ODBTrait::QUANT_REAL, ODBTrait::SPECTRAL])) {
          if (!array_key_exists('unit',$odbtrait)) {
            //null === $odbtrait['unit'] or null === $odbtrait['range_max'] or null === $odbtrait['range_min']) {
            $this->skipEntry($odbtrait, 'Missing unit for variable '.$odbtrait['export_name'].'Mandatory for quantitative and spectral traits');
            return false;
          }

          if (isset($odbtrait['range_max']) or isset($odbtrait['range_min'])) {
            if (!is_numeric($odbtrait['range_max']) or !is_numeric($odbtrait['range_min']) or $odbtrait['range_max']<=$odbtrait['range_min']) {
              $this->appendLog('FAILED: Variable'.$odbtrait['export_name'].' has invalid range_max and/or range_min. They must be numeric and max must be greater than min');
              return false;
            }
          }

          if ($odbtrait['type'] == ODBTrait::SPECTRAL) {
            $wavemin = isset($odbtrait['wavenumber_min']) ? $odbtrait['wavenumber_min'] : (isset($odbtrait['range_min']) ? $odbtrait['range_min'] : null );
            $wavemax = isset($odbtrait['wavenumber_max']) ? $odbtrait['wavenumber_max'] : (isset($odbtrait['range_max']) ? $odbtrait['range_max'] : null );
            if (null == $wavemin or null ==$wavemax)  {
              $this->appendLog("FAILED: Missing or invalid 'range_max' and/or 'range_min' or 'wavenumber_max' and/or 'wavenumber_min' must be informed for spectral variable ".$odbtrait['export_name']);
              return false;
            }
            if (!isset($odbtrait['value_length']) or !is_numeric($odbtrait['value_length'])) {
              $this->appendLog('FAILED: A numeric value_length must be informed for spectral Variable '.$odbtrait['export_name']);
              return false;
            }
            $odbtrait['range_min'] = $wavemin;
            $odbtrait['range_max'] = $wavemax;
          }
        }


        //LINK TYPE TRAITS VALIDATION
        if ($odbtrait['type'] == ODBTrait::LINK) {
          if (!array_key_exists('link_type',$odbtrait)) {
            //null === $odbtrait['unit'] or null === $odbtrait['range_max'] or null === $odbtrait['range_min']) {
            $this->skipEntry($odbtrait, 'Missing link_type for variable '.$odbtrait['export_name'].'. Mandatory for LINK traits');
            return false;
          }
          if (!in_array($odbtrait['link_type'], ODBTrait::LINK_TYPES) and !in_array($odbtrait['link_type'],ODBTrait::getLinkTypeBaseName())) {
            $this->skipEntry($odbtrait, 'Link_type for variable '.$odbtrait['export_name'].' is invalid');
            return false;
          }
          if (in_array($odbtrait['link_type'],ODBTrait::getLinkTypeBaseName())) {
            $odbtrait['link_type'] = ODBTrait::LINK_TYPES[array_search($odbtrait['link_type'],ODBTrait::getLinkTypeBaseName())];
          }
        }

        //check for categorical values
        if (in_array($odbtrait['type'], [ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL])) {
            //minimally requires a category name to be a valid categorical variable
            if (!array_key_exists('categories',$odbtrait)) {
              $this->skipEntry($odbtrait, 'Variable '.$odbtrait['export_name'].' requires categories');
              return false;
            }
            //category name must exist, be an array named with language id or code
            if (!is_array($odbtrait['categories']) or  !Self::validateCategories($odbtrait)) {
                $this->skipEntry($odbtrait, 'Categories names for variable '.$odbtrait['export_name']." must be an array of categories with fields lang, name and rank mandatory");
                return false;
            }
        }
        return true;
    }

    protected function validateObjectType(&$odbtrait) {
      //this may be a string separated by commas or an array
      //convert to array if not array
      if (!is_array($odbtrait['objects'])) {
        $odbtrait['objects'] = explode(',',$odbtrait['objects']);
      }
      $objects_informed = $odbtrait['objects'];
      //values are valid?
      $validobjectsClass = ODBTrait::OBJECT_TYPES;
      $validobjectsName = collect(ODBTrait::OBJECT_TYPES)->map(function($obj) { return mb_strtolower(str_replace("App\Models\\","",$obj)) ;})->toArray();
      $objects = [];
      $notfound = [];
      foreach($objects_informed as $object) {
        if (in_array($object,$validobjectsClass)) {
          $objects[] = $object;
        } else {
          $object = mb_strtolower($object);
          if (in_array($object,$validobjectsName)) {
            $objects[] = "App\Models\\".ucfirst($object);
          } else {
            $nofound[] = $object;
          }
        }
      }
      if (count($objects) == count($objects_informed)) {
        $odbtrait['objects'] = $objects;
        return true;
      }

      //then fail  as object type is invalid
      $this->skipEntry($odbtrait, 'Invalid ObjectType '.implode(" | ",$notfound).' for variable '.$odbtrait['export_name']);
      return false;

    }

    public function import($record)
    {

      $names = $record['name'];
      $descriptions = $record['description'];

      //check if already exists
      if (ODBTrait::whereRaw('export_name like ?', [$record['export_name']])->count() > 0) {
          $this->appendLog('WARNING: Variable '.$record['export_name'].' already exists in the database. Skipped.');
          return ;
      }
      $bibreference = null;
      if (isset($record['bibreference_id'])) {
          $bibreference = ODBFunctions::validRegistry(BibReference::select('id'),$record['bibreference_id'],'id');
          if (!$bibreference) {
            $this->skipEntry($record, ' variable '.$record['export_name'].' informed bibreference id is invalid');
            return;
          }
          $bibreference = $bibreference->id;
      }


      //GET FIELD VALUES
      $export_name = $record['export_name'];
      $odbtype = $record['type'];
      $names = $record['name'];
      $descriptions = $record['description'];

      $objects = $record['objects'];
      // Set fields from quantitative traits
      if (in_array($odbtype, [ODBTrait::QUANT_INTEGER, ODBTrait::QUANT_REAL, ODBTrait::SPECTRAL])) {
          $unit = $record['unit'];
          $range_max = isset($record['range_max']) ? $record['range_max'] : null;
          $range_min = isset($record['range_min']) ? $record['range_min'] : null;
      } else {
        $unit = null;
        $range_max = null;
        $range_min = null;
      }
      $link_type = null;
      if (in_array($odbtype, [ODBTrait::LINK])) {
        $link_type = $record['link_type'];
      }

      $value_length = null;
      if (in_array($odbtype, [ODBTrait::SPECTRAL])) {
        $value_length = $record['value_length'];
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
      //save variable
      $odbtrait->save();

      //save allowed objects
      foreach ($objects as $object) {
        $odbtrait->hasMany(TraitObject::class, 'trait_id')->create(['object_type' => $object]);
      }
      $odbtrait->save();

      //SAVE name and category VIA translations
      //save variable name and descriptions
      foreach ($names as $lang => $translation) {
          $odbtrait->setTranslation(UserTranslation::NAME, $lang, $translation);
      }
      foreach ($descriptions as $lang => $translation) {
          $odbtrait->setTranslation(UserTranslation::DESCRIPTION, $lang, $translation);
      }

      //save categories for categorical and ordinal
      if (in_array($odbtype, [ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL])) {
          $categories = $record['categories'];
          $ranksdone = [];
          foreach($categories as $rank => $translations) {
              $cat = $odbtrait->categories()->create(['rank' => $rank]);
              foreach ($translations as $translation) {
                $cat->setTranslation($translation['translation_type'],$translation['lang'],$translation['translation']);
              }
          }
      }
      $this->affectedId($odbtrait->id);
      return;
    }
}
