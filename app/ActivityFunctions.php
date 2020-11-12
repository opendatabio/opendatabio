<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use App\Plant;
use App\User;
use App\Person;
use App\Voucher;
use App\Taxon;
use App\ODBTrait;
use App\Location;
use Spatie\Activitylog\Traits\LogsActivity;
use Lang;

class ActivityFunctions
{
      /**
       * Helpers for non-trait custom history
       *
      **/
      public static function logCustomChanges($model, $oldArray,$newArray,$logName,$description,$allowedkeys) {
         if (isset($oldArray)) {
              if (!isset($allowedkeys)) {
                $allowedkeys  = array_keys($newArray);
              } else {
                $newArray = array_filter(
                    $newArray,
                    function ($key) use ($allowedkeys) {
                      return in_array($key, $allowedkeys);
                    },
                    ARRAY_FILTER_USE_KEY
                );
              }
              $oldArray = array_filter(
                  $oldArray,
                  function ($key) use ($allowedkeys) {
                    return in_array($key, $allowedkeys);
                  },
                  ARRAY_FILTER_USE_KEY
              );
              //if there is change, then log
              $result = array_diff_assoc($oldArray,$newArray);
              if (count($result)>0) {
                $new= array_diff_assoc($newArray,$oldArray);
                $tolog = array('attributes' => $new, 'old' => $result);
                activity($logName)
                  ->performedOn($model)
                  ->withProperties($tolog)
                  ->log($description);
              }
        }
     }

     public static function logCustomPivotChanges($model,$oldArray,$newArray,$logName,$description,$pivotkey) {
           $new = array_diff($newArray,$oldArray);
           $old = array_diff($oldArray,$newArray);
           if ($new || $old) {
             $tolog = array('attributes' => array($pivotkey => $newArray), 'old' => array($pivotkey => $oldArray));
             activity($logName)
               ->performedOn($model)
               ->withProperties($tolog)
               ->log($description);
           }
     }

     /* compare translations arrays, and log if changed */
     public static function logTranslationsChanges($model,$old_translations,$new_translations,$logName,$logDescription,$logDescriptionDeleted)
     {
       //compare
       $changes_att = array();
       $changes_old = array();

       $oldkeys = array_keys($old_translations);
       $newkeys = array_keys($new_translations);
       $olddeleted = array_diff($oldkeys,$newkeys);
       foreach($new_translations as $key => $new_translation) {
         $old_translation= isset($old_translations[$key]) ? $old_translations[$key] : null;
         if (null !== $old_translation) {
           $newisdifferent = array_diff_assoc($new_translation,$old_translation);
           $oldisdifferent = array_diff_assoc($old_translation,$new_translation);
           if (count($newisdifferent) or count($oldisdifferent)) {
             $tolog = array('attributes' => array('translations' => $newisdifferent), 'old' => array('translations' => $oldisdifferent));
             activity($logName)
             ->performedOn($model)
             ->withProperties($tolog)
             ->log($logDescription);
           }
          }
       }
       if (count($olddeleted)) {
         foreach($olddeleted as $oldkey) {
           $tolog = array('attributes' => array('translations' => ""), 'old' => array('translations' => $old_translations[$oldkey]));
           activity($logName)
           ->performedOn($model)
           ->withProperties($tolog)
           ->log($logDescriptionDeleted);
         }
       }
     }

     /*
      * Helpers for displaying history
     */


     public static function getIdentifiableName($key,$value) {
        //key is a table name and must exists as a class
        $relatedmodel = ucfirst($key);
        $text = $value;
        if (class_exists("App\\" .$relatedmodel)) {
            //get identifiable name if exists
           if (!is_array($value)) {
             $value = array($value);
           }
           $values = array();
           foreach($value as $id) {
                if ($id) {
                  //$item = "App\\".$relatedmodel::find($id);
                  $item = app("App\\" . $relatedmodel)->find($id);
                  $itemvalue = $id;
                  if (is_null($item)) {
                      $itemvalue  = "id: ".$id." ".Lang::get('messages.revisionable_unknown');
                    } else {
                      if (method_exists($item, 'identifiableName')) {
                        $itemvalue = $item->identifiableName();
                      } else {
                        if (isset($item->fullname)) {
                          $itemvalue  = $item->fullname;
                        } else {
                          if (isset($item->name)) {
                            $itemvalue  = $item->name;
                          }
                        }
                      }
                    }
                } else {
                  $itemvalue = Lang::get('messages.revisionable_nothing');
                }
                $values[] = $itemvalue;

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
              $keyvals = explode('_',$key);
              $language = Language::where('id',$keyvals[0])->first()->name;
              if (0 == $keyvals[1]) {
                $type = Lang::get('messages.name');
              } else {
                $type = Lang::get('messages.description');
              }
              if (isset($old[$key]) and $old !== "") {
                $oldvalue = $old[$key];
              } else {
                $oldvalue = "";
              }
              $text .= "<tr><td >".$type."<br>[".$language."]</td><td class='text-danger'>".$oldvalue."</td><td class='text-success'>".$value."</td></tr>";
       }

     } elseif (is_array($old)) {
       foreach ($old as $key => $value) {
              $keyvals = explode('_',$key);
              $language = Language::where('id',$keyvals[0])->first()->name;
              if (0 == $keyvals[1]) {
                $type = Lang::get('messages.name');
              } else {
                $type = Lang::get('messages.description');
              }
              if (isset($new[$key]) and $new !== "") {
                $newvalue = $new[$key];
              } else {
                $newvalue = "";
              }
              $text .= "<tr><td >".$type."<br>[".$language."]</td><td class='text-danger'>".$value."</td><td class='text-success'>".$newvalue."</td></tr>";
        }
      }

       return $text;
     }


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
          foreach($attributes as $key => $newvalue) {
                $oldvalue = "";
                if (null !== $old) {
                  $oldvalue = $old[$key];
                }
                $identifiablenew   = $newvalue;
                $identifiableold   = $oldvalue;
                if ('translations' !== $key) {
                //if foreign key or array get identifiable name for values
                if ((strpos($key, '_id') && 'parent_id' !== $key) || is_array($newvalue)) {
                    $relatedmodel = str_replace('_id', '', $key);
                    if ($relatedmodel == 'uc') {
                      $relatedmodel='location';
                    }
                    if ($relatedmodel=='author') {
                      $relatedmodel = 'person';
                    }
                    if ($relatedmodel=='bibreference') {
                      $relatedmodel = 'BibReference';
                    }                    
                      $identifiablenew = self::getIdentifiableName($relatedmodel,$newvalue);
                      if (null !== $old) {
                        $identifiableold = self::getIdentifiableName($relatedmodel,$oldvalue);
                      }
                } else {
                    //for cases like modifier in identification
                    if ('levels.'.$key.".".$newvalue !== Lang::get('levels.'.$key.".".$newvalue)) {
                      $identifiablenew = Lang::get('levels.'.$key.".".$newvalue);
                      if (null !== $old) {
                        $identifiableold = Lang::get('levels.'.$key.".".$oldvalue);
                      }
                    } else {

                      if ('parent_id' == $key) {
                          $class = $activity->subject_type;
                          $subject = app($class)::findOrFail($newvalue);
                          if (null !== $old) {
                            $oldsubject = app($class)::findOrFail($oldvalue);
                          } else {
                            $oldsubject = null;
                          }
                           if (method_exists($subject, 'identifiableName')) {
                             $identifiablenew = $subject->identifiableName();
                             if (null !== $old) {
                               $identifiableold = $oldsubject->identifiableName();
                             }
                           } else {
                             if ($subject->fullname) {
                                $identifiablenew = $subject->fullname;
                                if (null !== $old) {
                                  $identifiableold = $oldsubject->fullname;
                                }
                             } else {
                               if ($item->name) {
                                 $identifiablenew = $subject->name;
                                 if (null !== $old) {
                                   $identifiableold = $oldsubject->name;
                                 }
                               }
                             }
                          }
                        }
                      if ($key == 'value_a') {
                      $class = $activity->subject_type;
                      $subject = app($class)::findOrFail($activity->subject_id);
                      $identifiablenew .= '&nbsp;<span class="measurement-thumb" style="background-color:'.$identifiablenew.'">';
                      if (null !== $old) {
                        $identifiableold .= '&nbsp;<span class="measurement-thumb" style="background-color:'.$identifiableold.'">';
                      }
                    }
                      if ($key == 'value_i') {
                      $class = $activity->subject_type;
                      $subject = app($class)::findOrFail($activity->subject_id);
                      $odbtrait =  ODBTrait::findOrFail($subject->trait_id);
                      $class = $odbtrait->link_type;
                      $traitcl = app($class)::findOrFail($newvalue);
                      if (null !== $old and null !== $oldvalue) {
                        $oldtraitcl = app($class)::findOrFail($oldvalue);
                      }
                      $identifiablenew = $traitcl->fullname;
                      if (null !== $old and null !== $oldvalue) {
                        $identifiableold .= $oldtraitcl->fullname;
                      }
                    }
                    }
                }
                if (null !== $old) {
                  $line = "<tr><td >".self::keyname($key)."</td><td class='text-danger'>".($identifiableold)."</td><td class='text-success'>".($identifiablenew)."</td><tr>";
                } else {
                  $line = "<tr><td >".self::keyname($key)."</td><td class='text-success'>".($identifiablenew)."</td><tr>";
                }
                $text .= $line;
              } else {
                $text .= self::getTranslatableNames($newvalue, $oldvalue);
              }

          }
          $text .= "</tbody></table>";

          return  $text;
     }



}
