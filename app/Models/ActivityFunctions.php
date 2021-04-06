<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use App\Models\Individual;
use App\Models\User;
use App\Models\Person;
use App\Models\Voucher;
use App\Models\Taxon;
use App\Models\ODBTrait;
use App\Models\Location;
use Lang;


use Spatie\Activitylog\Traits\LogsActivity;


class ActivityFunctions
{
      /**
       * Helpers for non-trait custom history
       *
      **/
      public static function logCustomChanges($model, $oldArray,$newArray,$logName,$description,$allowedKeys) {
         if (isset($oldArray)) {
              if (!isset($allowedKeys)) {
                $allowedKeys  = array_keys($newArray);
              } else {
                $newArray = array_filter(
                    $newArray,
                    function ($key) use ($allowedKeys) {
                      return in_array($key, $allowedKeys);
                    },
                    ARRAY_FILTER_USE_KEY
                );
              }
              $oldArray = array_filter(
                  $oldArray,
                  function ($key) use ($allowedKeys) {
                    return in_array($key, $allowedKeys);
                  },
                  ARRAY_FILTER_USE_KEY
              );
              //if there is change, then log
              $result = array_diff_assoc($oldArray,$newArray);
              if (count($result)>0) {
                $new= array_diff_assoc($newArray,$oldArray);
                $toLog = array('attributes' => $new, 'old' => $result);
                activity($logName)
                  ->performedOn($model)
                  ->withProperties($toLog)
                  ->log($description);
              }
        }
     }

     public static function logCustomPivotChanges($model,$oldArray,$newArray,$logName,$description,$pivotkey) {
           $new = array_diff($newArray,$oldArray);
           $old = array_diff($oldArray,$newArray);
           if ($new || $old) {
             $toLog = array('attributes' => array($pivotkey => $newArray), 'old' => array($pivotkey => $oldArray));
             activity($logName)
               ->performedOn($model)
               ->withProperties($toLog)
               ->log($description);
           }
     }

     /*
     * Compare translations arrays, and log if changed
     */
     public static function logTranslationsChanges($model,$oldTranslations,$newTranslations,$logName,$logDescription,$logDescriptionDeleted)
     {
       //compare
       $oldkeys = array_keys($oldTranslations);
       $newkeys = array_keys($newTranslations);
       $oldDeleted = array_diff($oldkeys,$newkeys);
       foreach($newTranslations as $key => $newTranslation) {
         $oldTranslation= isset($oldTranslations[$key]) ? $oldTranslations[$key] : null;
         if (null !== $oldTranslation) {
           $newIsDifferent = array_diff_assoc($newTranslation,$oldTranslation);
           $oldIsDifferent = array_diff_assoc($oldTranslation,$newTranslation);
           if (count($newIsDifferent) or count($oldIsDifferent)) {
             $toLog = array('attributes' => array('translations' => $newIsDifferent), 'old' => array('translations' => $oldIsDifferent));
             activity($logName)
             ->performedOn($model)
             ->withProperties($toLog)
             ->log($logDescription);
           }
          }
       }
       if (count($oldDeleted)) {
         foreach($oldDeleted as $oldKey) {
           $toLog = array('attributes' => array('translations' => ""), 'old' => array('translations' => $oldTranslations[$oldKey]));
           activity($logName)
           ->performedOn($model)
           ->withProperties($toLog)
           ->log($logDescriptionDeleted);
         }
       }
     }

     /*
      * Helpers for displaying history
     */


     public static function getIdentifiableName($key,$value) {
        $relatedModel = ucfirst($key);
        $text = $value;
        if (class_exists("App\Models\\" .$relatedModel)) {
            //get identifiable name if exists
           if (!is_array($value)) {
             $value = [$value];
           }
           $values = [];
           foreach($value as $id) {
                if ($id) {
                  //$item = "App\\".$relatedModel::find($id);
                  $item = app("App\Models\\" . $relatedModel)->find($id);
                  $itemValue = $id;
                  if (is_null($item)) {
                      $itemValue  = "id: ".$id." ".Lang::get('messages.revisionable_unknown');
                    } else {
                      if (method_exists($item, 'identifiableName')) {
                        $itemValue = $item->identifiableName();
                      } else {
                        if (isset($item->fullname)) {
                          $itemValue  = $item->fullname;
                        } else {
                          if (isset($item->name)) {
                            $itemValue  = $item->name;
                          }
                        }
                      }
                    }
                } else {
                  $itemValue = Lang::get('messages.revisionable_nothing');
                }
                $values[] = $itemValue;

           }
           $text = implode(" | ",$values);
        }
        if (is_array($text)) {
          $text = implode(" | ",$text);
        }
        return $text;
     }


     /* format translation values */
     public static function getTranslatableNames($new, $old)
     {
       $text = "";
       if (is_array($new)) {
       foreach ($new as $key => $value) {
              $langAndType = explode('_',$key);
              $language = Language::where('id',$langAndType[0])->first()->name;
              if (0 == $langAndType[1]) {
                $type = Lang::get('messages.name');
              } else {
                $type = Lang::get('messages.description');
              }
              if (isset($old[$key]) and $old !== "") {
                $oldValue = $old[$key];
              } else {
                $oldValue = "";
              }
              $text .= "<tr><td >".$type."<br>[".$language."]</td><td class='text-danger'>".$oldValue."</td><td class='text-success'>".$value."</td></tr>";
       }

     } elseif (is_array($old)) {
       foreach ($old as $key => $value) {
              $langAndType = explode('_',$key);
              $language = Language::where('id',$langAndType[0])->first()->name;
              if (0 == $langAndType[1]) {
                $type = Lang::get('messages.name');
              } else {
                $type = Lang::get('messages.description');
              }
              if (isset($new[$key]) and $new !== "") {
                $newValue = $new[$key];
              } else {
                $newValue = "";
              }
              $text .= "<tr><td >".$type."<br>[".$language."]</td><td class='text-danger'>".$value."</td><td class='text-success'>".$newValue."</td></tr>";
        }
      }

       return $text;
     }

     /* Translation of field names */
     public static function keyname($key) {
       $text = $key;
       $key = str_replace('_id', '', $key);
       if ('messages.'.$key !== Lang::get('messages.'.$key)) {
         $text = Lang::get('messages.'.$key);
       }
       return $text;
     }


     public static function formatActivityProperties($activity) {
          //$properties = $this->properties->all();
          $properties = $activity->properties->toArray();
          $attributes = $properties['attributes'];
          $old = $properties['old'];
          $text = "<table class='table table-bordered table-responsive'><thead>";
          $text .= "<tr><th>".Lang::get('messages.field')."</th>";
          if (null !== $old) {
            $text .= "<th class='text-danger'>".Lang::get('messages.old_value')."</th>";
          }
          $text .= "<th class='text-success'>".Lang::get('messages.new_value')."</th></tr></thead><tbody>";
          foreach($attributes as $key => $newValue) {
                $oldValue = "";
                if (null !== $old) {
                  $oldValue = $old[$key];
                }
                $identifiableNameNew   = $newValue;
                $identifiableNameOld   = $oldValue;
                if ('translations' !== $key) {
                //if foreign key or array get identifiable name for values
                if ((strpos($key, '_id') && 'parent_id' !== $key) || is_array($newValue)) {
                    $relatedModel = str_replace('_id', '', $key);
                    if ($relatedModel == 'uc') {
                      $relatedModel='Location';
                    }
                    if ($relatedModel=='author') {
                      $relatedModel = 'Person';
                    }
                    if ($relatedModel=='bibreference') {
                      $relatedModel = 'BibReference';
                    }
                      $identifiableNameNew = self::getIdentifiableName($relatedModel,$newValue);
                      if (null !== $old) {
                        $identifiableNameOld = self::getIdentifiableName($relatedModel,$oldValue);
                      }
                } else {
                    //for cases like modifier in identification
                    if ('levels.'.$key.".".$newValue !== Lang::get('levels.'.$key.".".$newValue)) {
                      $identifiableNameNew = Lang::get('levels.'.$key.".".$newValue);
                      if (null !== $old) {
                        $identifiableNameOld = Lang::get('levels.'.$key.".".$oldValue);
                      }
                    } else {

                      if ('parent_id' == $key) {
                          $modelClass = $activity->subject_type;
                          $newSubject = app($modelClass)::findOrFail($newValue);
                          if (null !== $old) {
                            $oldSubject = app($modelClass)::findOrFail($oldValue);
                          } else {
                            $oldSubject = null;
                          }
                           if (method_exists($newSubject, 'identifiableName')) {
                             $identifiableNameNew = $newSubject->identifiableName();
                             if (null !== $old) {
                               $identifiableNameOld = $oldSubject->identifiableName();
                             }
                           } else {
                             if ($newSubject->fullname) {
                                $identifiableNameNew = $newSubject->fullname;
                                if (null !== $old) {
                                  $identifiableNameOld = $oldSubject->fullname;
                                }
                             } else {
                               if ($item->name) {
                                 $identifiableNameNew = $newSubject->name;
                                 if (null !== $old) {
                                   $identifiableNameOld = $oldSubject->name;
                                 }
                               }
                             }
                          }
                        }
                      if ($key == 'value_a') {
                      $modelClass = $activity->subject_type;
                      $newSubject = app($modelClass)::findOrFail($activity->subject_id);
                      $identifiableNameNew .= '&nbsp;<span class="measurement-thumb" style="background-color:'.$identifiableNameNew.'">';
                      if (null !== $old) {
                        $identifiableNameOld .= '&nbsp;<span class="measurement-thumb" style="background-color:'.$identifiableNameOld.'">';
                      }
                    }
                      if ($key == 'value_i') {
                      $modelClass = $activity->subject_type;
                      $newSubject = app($modelClass)::findOrFail($activity->subject_id);
                      $odbtrait =  ODBTrait::findOrFail($newSubject->trait_id);
                      $modelClass = $odbtrait->link_type;
                      $traitcl = app($modelClass)::findOrFail($newValue);
                      if (null !== $old and null !== $oldValue) {
                        $oldtraitcl = app($modelClass)::findOrFail($oldValue);
                      }
                      $identifiableNameNew = $traitcl->fullname;
                      if (null !== $old and null !== $oldValue) {
                        $identifiableNameOld .= $oldtraitcl->fullname;
                      }
                    }
                    }
                }
                if (null !== $old) {
                  $line = "<tr><td >".self::keyname($key)."</td><td class='text-danger'>".($identifiableNameOld)."</td><td class='text-success'>".($identifiableNameNew)."</td><tr>";
                } else {
                  $line = "<tr><td >".self::keyname($key)."</td><td class='text-success'>".($identifiableNameNew)."</td><tr>";
                }
                $text .= $line;
              } else {
                $text .= self::getTranslatableNames($newValue, $oldValue);
              }

          }
          $text .= "</tbody></table>";

          return  $text;
     }



}
