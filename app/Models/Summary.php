<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Location;
use App\Models\Taxon;
use App\Models\Project;
use App\Models\Dataset;
use App\Models\Measurement;
use App\Models\Picture;
use Activity;
use DB;
use Illuminate\Support\Arr;


class Summary extends Model
{

    protected $fillable = ['object_id', 'object_type', 'value', 'target', 'scope_id','scope_type'];

    /* Morph to object with counts */
    public function object()
    {
        return $this->morphTo('object');
    }

    /* Morph to the scope counted */
    public function scope()
    {
        return $this->morphTo('scope');
    }



    /* FUNCTION TO BE CALLED ON individual OR VOUCHER EDITS, updates or insertions */
    /* UPDATE  Summary->value COUNTS FOR objects: locations and taxons; target individuals OR vouchers; and, scopes location,project and datasets*/
    #newvalues and oldvalues are arrays with the following keys
    #$taxon_id
    #location_id
    #project_id
    #target is one of 'vouchers' or 'individuals'
    #datasets is an array of datasets ids

    public static function updateSummaryCounts($newvalues,$oldvalues,$target,$datasets=null,$measurements_count=0)
    {

      if (null == $oldvalues) {
        $oldvalues =  [
             "taxon_id" => null,
             "location_id" => null,
             "project_id" => null
        ];
        }
      /* what changed or is new*/
      $taxonchanged = ($newvalues['taxon_id'] != $oldvalues['taxon_id'] and null != $oldvalues['taxon_id']) ? true : false;
      $locationchanged = ($newvalues['location_id'] != $oldvalues['location_id'] and null != $oldvalues['location_id']) ? true : false;
      $projectchanged = ($newvalues['project_id'] != $oldvalues['project_id'] and null != $oldvalues['project_id']) ? true : false;

      //get taxon chain
      $taxonsnew = Taxon::findOrFail($newvalues['taxon_id'])->getAncestorsAndSelf()->pluck('id')->toArray();
      if ($taxonchanged) {
        $taxonsold = Taxon::findOrFail($oldvalues['taxon_id'])->getAncestorsAndSelf()->pluck('id')->toArray();
      }
      //get locations chain
      $locationsnew = Location::findOrFail($newvalues['location_id'])->getAncestorsAndSelf()->pluck('id')->toArray();
      if ($locationchanged) {
          $locationsold = Location::findOrFail($oldvalues['location_id'] )->getAncestorsAndSelf()->pluck('id')->toArray();
      }

      //update taxon if has changed or is new
      if ($taxonchanged or $oldvalues['taxon_id'] == null) {
        $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','all')->where('target',$target);
        /* create records in summaries table if need*/
        if ($current->count() < count($taxonsnew)) {
          $arein = $current->cursor()->pluck('object_id')->toArray();
          $arenot = array_diff($taxonsnew,$arein);
          $txtoinsert = [];
          $targets = [$target,'measurements'];
          foreach ($targets as $current_target) {
            foreach ($arenot as $taxon_id) {
              $txtoinsert[] = ['object_id' => $taxon_id, 'object_type' => 'App\Models\Taxon', 'scope_type' => 'all', 'target' => $current_target, 'value' => 0,'scope_id' => null,'created_at' => now(), 'updated_at' => now()];
              foreach ($locationsnew as $location_id) {
                $txtoinsert[] = ['object_id' => $taxon_id, 'object_type' => 'App\Models\Taxon', 'scope_type' => 'App\Models\Location', 'target' => $current_target, 'value' => 0, 'scope_id' => $location_id,'created_at' => now(), 'updated_at' => now()];
              }
              $txtoinsert[] = ['object_id' => $taxon_id, 'object_type' => 'App\Models\Taxon', 'scope_type' => 'App\Models\Project', 'target' => $current_target, 'value' => 0, 'scope_id' => $newvalues['project_id'],'created_at' => now(), 'updated_at' => now()];
              if (null != $datasets) {
                foreach ($datasets as $dataset_id) {
                  $txtoinsert[] = ['object_id' => $taxon_id, 'object_type' => 'App\Models\Taxon', 'scope_type' => 'App\Models\Dataset', 'target' => $current_target, 'value' => 0, 'scope_id' => $dataset_id,'created_at' => now(), 'updated_at' => now()];
                }
              }
            }
          }
          Summary::insertOrIgnore($txtoinsert);
        }

        /*which summary counts for the taxon objects need to be updated? */
        $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','all')->where('target',$target);
        $current->update(['value'=> DB::raw( 'value + 1' )]);
        if ($measurements_count>0) {
          $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','all')->where('target','measurements');
          $current->update(['value'=> DB::raw( 'value + '.$measurements_count )]);
        }

        if ($oldvalues['taxon_id'] != null) {
          $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','all')->where('target',$target);
          $current->update(['value'=> DB::raw( 'value - 1' )]);
          if ($measurements_count>0) {
            $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','all')->where('target','measurements');
            $current->update(['value'=> DB::raw( 'value - '.$measurements_count )]);
          }
        }

        /*for location scopes */
        $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Location')->where('target',$target)->whereIn('scope_id',$locationsnew);
        $current->update(['value'=> DB::raw( 'value + 1' )]);
        if ($measurements_count>0) {
          $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Location')->where('target','measurements')->whereIn('scope_id',$locationsnew);
          $current->update(['value'=> DB::raw( 'value + '.$measurements_count )]);
        }
        if (!$locationchanged and $taxonchanged) {
          $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Location')->where('target',$target)->whereIn('scope_id',$locationsnew);
          $current->update(['value'=> DB::raw( 'value - 1' )]);
          if ($measurements_count>0) {
            $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Location')->where('target','measurements')->whereIn('scope_id',$locationsnew);
            $current->update(['value'=> DB::raw( 'value - '.$measurements_count )]);
          }
        } elseif ($locationchanged and $taxonchanged) {
          $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Location')->where('target',$target)->whereIn('scope_id',$locationsold);
          $current->update(['value'=> DB::raw( 'value - 1' )]);
          if ($measurements_count>0) {
            $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Location')->where('target','measurements')->whereIn('scope_id',$locationsold);
            $current->update(['value'=> DB::raw( 'value - '.$measurements_count )]);
          }
        }

        /* for project scopes */
        if ($projectchanged or null == $oldvalues['project_id']) {
          $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Project')->where('target',$target)->where('scope_id',$newvalues['project_id']);
          $current->update(['value'=> DB::raw( 'value + 1' )]);
          if ($measurements_count>0) {
            $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Project')->where('target','measurements')->where('scope_id',$newvalues['project_id']);
            $current->update(['value'=> DB::raw( 'value + '.$measurements_count )]);
          }

        }
        if (!$projectchanged and $taxonchanged) {
          $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Project')->where('target',$target)->where('scope_id',$newvalues['project_id']);
          $current->update(['value'=> DB::raw( 'value - 1' )]);
          if ($measurements_count>0) {
            $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Project')->where('target','measurements')->where('scope_id',$newvalues['project_id']);
            $current->update(['value'=> DB::raw( 'value - '.$measurements_count )]);
          }
        } elseif ($taxonchanged and $projectchanged) {
          $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Project')->where('target',$target)->where('scope_id',$oldvalues['project_id']);
          $current->update(['value'=> DB::raw( 'value - 1' )]);
          if ($measurements_count>0) {
            $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Project')->where('target','measurements')->where('scope_id',$oldvalues['project_id']);
            $current->update(['value'=> DB::raw( 'value - '.$measurements_count )]);
          }
        }

        /* for datasets scopes */
        if (null != $datasets and ($taxonchanged or null == $oldvalues['taxon_id'])) {
          $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Dataset')->where('target',$target)->whereIn('scope_id',$datasets);
          $values = $current->selectRaw('(value+1) as newvalue')->cursor()->pluck('newvalue')->toArray();
          $current->update(['value'=> DB::raw( 'value + 1' )]);

          if ($measurements_count>0) {
            $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Dataset')->where('target','measurements')->whereIn('scope_id',$datasets);
            $current->update(['value'=> DB::raw( 'value + '.$measurements_count )]);
          }

          if (null != $oldvalues['taxon_id']) {
              $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Dataset')->where('target',$target)->whereIn('scope_id',$datasets);
              $current->update(['value'=> DB::raw( 'value - 1' )]);
              if ($measurements_count>0) {
                $current = Summary::whereIn('object_id',$taxonsold)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Dataset')->where('target','measurements')->whereIn('scope_id',$datasets);
                $current->update(['value'=> DB::raw( 'value - '.$measurements_count )]);
              }
          }
        }
      } else {
        if ($projectchanged) {
          $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Project')->where('target',$target)->where('scope_id',$oldvalues['project_id']);
          $current->update(['value'=> DB::raw( 'value - 1' )]);
          if ($measurements_count>0) {
            $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Project')->where('target','measurements')->where('scope_id',$oldvalues['project_id']);
            $current->update(['value'=> DB::raw( 'value - '.$measurements_count )]);
          }
          $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Project')->where('target',$target)->where('scope_id',$newvalues['project_id']);
          $current->update(['value'=> DB::raw( 'value + 1' )]);
          if ($measurements_count>0) {
            $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Project')->where('target','measurements')->where('scope_id',$newvalues['project_id']);
            $current->update(['value'=> DB::raw( 'value - '.$measurements_count )]);
          }
        }
      }


      if ($locationchanged or $oldvalues['location_id'] == null) {
        $current = Summary::whereIn('object_id',$locationsnew)->where('object_type',"App\Models\Location")->where('scope_type','all')->where('target',$target);
        /*  create records if needed*/
        if ($current->count() < count($locationsnew)) {
          $arein = $current->cursor()->pluck('object_id')->toArray();
          $arenot = array_diff($locationsnew,$arein);
          $loctoinsert = [];
          foreach ($arenot as $location_id) {
            $loctoinsert[] = ['object_id' => $location_id, 'object_type' => 'App\Models\Location', 'scope_type' => 'all', 'target' => $target, 'value' => 0,'scope_id' => null, 'created_at' => now(), 'updated_at' => now()];
            $loctoinsert[] = ['object_id' => $location_id, 'object_type' => 'App\Models\Location', 'scope_type' => 'App\Models\Project', 'target' => $target, 'value' => 0, 'scope_id' => $newvalues['project_id'],'created_at' => now(), 'updated_at' => now()];
            if (null != $datasets) {
              foreach ($datasets as $dataset_id) {
                $loctoinsert[] = ['object_id' => $location_id, 'object_type' => 'App\Models\Location', 'scope_type' => 'App\Models\Dataset', 'target' => $target, 'value' => 0, 'scope_id' => $dataset_id,'created_at' => now(), 'updated_at' => now()];
              }
            }
          }
          Summary::insertOrIgnore($loctoinsert);
        }
        /*which records need to be updated for location objects? */
        $current = Summary::whereIn('object_id',$locationsnew)->where('object_type',"App\Models\Location")->where('scope_type','all')->where('target',$target);
        $current->update(['value'=> DB::raw( 'value + 1' )]);
        if ($oldvalues['location_id'] != null) {
          $current = Summary::whereIn('object_id',$locationsold)->where('object_type',"App\Models\Location")->where('scope_type','all')->where('target',$target);
          $current->update(['value'=> DB::raw( 'value - 1' )]);
        }
        if ($projectchanged or null == $oldvalues['project_id']) {
          $current = Summary::whereIn('object_id',$locationsnew)->where('object_type',"App\Models\Location")->where('scope_type','App\Models\Project')->where('target',$target)->where('scope_id',$newvalues['project_id']);
          $current->update(['value'=> DB::raw( 'value + 1' )]);
        }
        if (!$projectchanged and $locationchanged) {
          $current = Summary::whereIn('object_id',$locationsold)->where('object_type',"App\Models\Location")->where('scope_type','App\Models\Project')->where('target',$target)->where('scope_id',$newvalues['project_id']);
          $current->update(['value'=> DB::raw( 'value - 1' )]);
        } elseif ($locationchanged and $projectchanged) {
          $current = Summary::whereIn('object_id',$locationsold)->where('object_type',"App\Models\Location")->where('scope_type','App\Models\Project')->where('target',$target)->where('scope_id',$oldvalues['project_id']);
          $current->update(['value'=> DB::raw( 'value - 1' )]);
        }

        if (null != $datasets and ($locationchanged or null == $oldvalues['location_id'])) {
          $current = Summary::whereIn('object_id',$locationsnew)->where('object_type',"App\Models\Location")->where('scope_type','App\Models\Dataset')->where('target',$target)->whereIn('scope_id',$datasets);
          $current->update(['value'=> DB::raw( 'value + 1' )]);
          if ($oldvalues['location_id'] != null) {
            $current = Summary::whereIn('object_id',$locationsold)->where('object_type',"App\Models\Location")->where('scope_type','App\Models\Dataset')->where('target',$target)->whereIn('scope_id',$datasets);
            $current->update(['value'=> DB::raw( 'value - 1' )]);
          }
        }
      } else {
        if ($projectchanged) {
          $current = Summary::whereIn('object_id',$locationsnew)->where('object_type',"App\Models\Location")->where('scope_type','App\Models\Project')->where('target',$target)->where('scope_id',$oldvalues['project_id']);
          $current->update(['value'=> DB::raw( 'value - 1' )]);

          $current = Summary::whereIn('object_id',$locationsnew)->where('object_type',"App\Models\Location")->where('scope_type','App\Models\Project')->where('target',$target)->where('scope_id',$newvalues['project_id']);
          $current->update(['value'=> DB::raw( 'value + 1' )]);
        }
      }

      if ($projectchanged or $oldvalues['project_id']==null) {
        $current = Summary::where('object_id',$newvalues['project_id'])->where('object_type',"App\Models\Project")->where('scope_type','all')->where('target',$target);
        if ($current->count()) {
          $current->update(['value'=> DB::raw( 'value + 1' )]);
        } else {
          $record = ['object_id' => $newvalues['project_id'], 'object_type' => 'App\Models\Project', 'scope_type' => 'all', 'target' => $target, 'value' => 1,'scope_id' => null,'created_at' => now(), 'updated_at' => now()];
          Summary::insertOrIgnore($record);
        }

        if ($measurements_count>0) {
          $current = Summary::where('object_id',$newvalues['project_id'])->where('object_type',"App\Models\Project")->where('scope_type','all')->where('target','measurements');
          if ($current->count()) {
            $current->update(['value'=> DB::raw( 'value + '.$measurements_count )]);
          } else {
            $record = ['object_id' => $newvalues['project_id'], 'object_type' => 'App\Models\Project', 'scope_type' => 'all', 'target' => 'measurements', 'value' => $measurements_count,'scope_id' => null,'created_at' => now(), 'updated_at' => now()];
            Summary::insertOrIgnore($record);
          }
        }

        if ($oldvalues['project_id'] != null) {
          $current = Summary::where('object_id',$oldvalues['project_id'])->where('object_type',"App\Models\Project")->where('scope_type','all')->where('target',$target);
          $current->update(['value'=> DB::raw( 'value - 1' )]);
          if ($measurements_count>0) {
            $current = Summary::where('object_id',$oldvalues['project_id'])->where('object_type',"App\Models\Project")->where('scope_type','all')->where('target','measurements');
            $current->update(['value'=> DB::raw( 'value - '.$measurements_count )]);
          }
        }



      }

    }


    /*FUNCTION TO BE CALLED WHEN CREATING MEASUREMENTS */
    public static function updateSummaryMeasurementsCounts($measurement_entry,$value="value + 1")
    {
      //expects:$taxon_id,$location_id,$project_id,$dataset_id
      extract($measurement_entry);
      $target='measurements';
      if (null != $location_id) {
        $locationsnew = Location::findOrFail($location_id)->getAncestorsAndSelf()->pluck('id')->toArray();
      } else {
        $locationsnew = [];
      }
      if (null != $taxon_id) {
        //get taxon chain
        $taxonsnew = Taxon::findOrFail($taxon_id)->getAncestorsAndSelf()->pluck('id')->toArray();
        //get locations chain
        $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','all')->where('target',$target);
        /* create records in summaries table if need*/
        if ($current->count() < count($taxonsnew)) {
          $arein = $current->cursor()->pluck('object_id')->toArray();
          $arenot = array_diff($taxonsnew,$arein);
          $txtoinsert = [];
          foreach ($arenot as $taxon) {
            $txtoinsert[] = ['object_id' => $taxon, 'object_type' => 'App\Models\Taxon', 'scope_type' => 'all', 'target' => $target, 'value' => 0,'scope_id' => null,'created_at' => now(), 'updated_at' => now()];
            foreach ($locationsnew as $location_id) {
              $txtoinsert[] = ['object_id' => $taxon, 'object_type' => 'App\Models\Taxon', 'scope_type' => 'App\Models\Location', 'target' => $target, 'value' => 0, 'scope_id' => $location_id,'created_at' => now(), 'updated_at' => now()];
            }
            if (null != $project_id) {
              $txtoinsert[] = ['object_id' => $taxon, 'object_type' => 'App\Models\Taxon', 'scope_type' => 'App\Models\Project', 'target' => $target, 'value' => 0, 'scope_id' => $project_id,'created_at' => now(), 'updated_at' => now()];
            }
            $txtoinsert[] = ['object_id' => $taxon, 'object_type' => 'App\Models\Taxon', 'scope_type' => 'App\Models\Dataset', 'target' => $target, 'value' => 0, 'scope_id' => $dataset_id,'created_at' => now(), 'updated_at' => now()];
          }
          Summary::insertOrIgnore($txtoinsert);
        }

        /*update summary counts for the taxon objects need to be updated? */
        $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','all')->where('target',$target);
        $current->update(['value'=> DB::raw( $value )]);

        /*for location scopes */
        if (count($locationsnew)>0) {
          $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Location')->where('target',$target)->whereIn('scope_id',$locationsnew);
          $current->update(['value'=> DB::raw( $value )]);
        }
        /* for project scopes */
        if (null != $project_id) {
          $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Project')->where('target',$target)->where('scope_id',$project_id);
          $current->update(['value'=> DB::raw( $value )]);
        }

        $current = Summary::whereIn('object_id',$taxonsnew)->where('object_type',"App\Models\Taxon")->where('scope_type','App\Models\Dataset')->where('target',$target)->where('scope_id',$dataset_id);
        $current->update(['value'=> DB::raw( $value )]);
     }

     //if is a location measurement, then only location counts need to be updated
     if (null != $location_id and null == $taxon_id)  {
        $current = Summary::whereIn('object_id',$locationsnew)->where('object_type',"App\Models\Location")->where('scope_type','all')->where('target',$target);
        /*  create records if needed*/
        if ($current->count() < count($locationsnew)) {
          $arein = $current->cursor()->pluck('object_id')->toArray();
          $arenot = array_diff($locationsnew,$arein);
          $loctoinsert = [];
          foreach ($arenot as $location) {
            $loctoinsert[] = ['object_id' => $location, 'object_type' => 'App\Models\Location', 'scope_type' => 'all', 'target' => $target, 'value' => 0,'scope_id' => null, 'created_at' => now(), 'updated_at' => now()];
            $loctoinsert[] = ['object_id' => $location, 'object_type' => 'App\Models\Location', 'scope_type' => 'App\Models\Dataset', 'target' => $target, 'value' => 0, 'scope_id' => $dataset_id,'created_at' => now(), 'updated_at' => now()];
          }
          Summary::insertOrIgnore($loctoinsert);
        }
        /*which records need to be updated for location objects? */
        $current = Summary::whereIn('object_id',$locationsnew)->where('object_type',"App\Models\Location")->where('scope_type','all')->where('target',$target);
        $current->update(['value'=> DB::raw( $value )]);
        $current = Summary::whereIn('object_id',$locationsnew)->where('object_type',"App\Models\Location")->where('scope_type','App\Models\Dataset')->where('target',$target)->where('scope_id',$dataset_id);
        $current->update(['value'=> DB::raw( $value )]);
      }

      if (null != $project_id) {
        $current = Summary::where('object_id',$project_id)->where('object_type',"App\Models\Project")->where('scope_type','all')->where('target',$target);
        if ($current->count()) {
          $current->update(['value'=> DB::raw( $value )]);
        } else {
          $record = ['object_id' => $project_id, 'object_type' => 'App\Models\Project', 'scope_type' => 'all', 'target' => $target, 'value' => 1,'scope_id' => null,'created_at' => now(), 'updated_at' => now()];
          Summary::insertOrIgnore($record);
        }
      }

    }



    /* Function to calculate the full counts of object+scope+target */
    public static function fillCounts($object_id=null,$object_type=null,$scope_type='all',$scope_id=null,$storezeros=false,$target=null)
      {
        /*define the object for which counts will be calculated */
        /* in the models to calculate counts three public static functions have to exist:
          * individualsCount($scope,$scope_id)
          * vouchersCount($scope,$scope_id)
          * measurementsCount($scope,$scope_id)
          * taxonsCount($scope,$scope_id )
        */
        $objects = app($object_type);
        $scope = 'all';
        if ('App\Models\Project' == $scope_type) {
            $scope = 'projects';
        }
        if ('App\Models\Dataset' == $scope_type) {
            $scope = 'datasets';
        }
        if ('App\Models\Location' == $scope_type) {
            $scope = 'locations';
        }
        if (! null == $object_id) {
          $objects = $objects->where('id',$object_id);
        }
        foreach($objects->cursor() as $object) {
            if ($target==null) {
              $targets = ['individuals','vouchers','measurements','media'];
              if (get_class($object) == "App\Models\Location") {
                $targets = ['individuals','vouchers','measurements','media'];
              }
              if (get_class($object) == "App\Models\Project") {
                $targets = ['individuals','vouchers','measurements','media','locations','datasets'];
              }
              if (get_class($object) == "App\Models\Dataset") {
                $targets = ['individuals','vouchers','measurements','locations','projects'];
              }
            } else {
              $targets = (array)$target;
            }
            foreach($targets as $target) {
                if ($target=='individuals') {
                  $value = $object->individualsCount($scope,$scope_id);
                }
                if ($target=='vouchers') {
                  $value = $object->vouchersCount($scope,$scope_id);
                }
                if ($target=='measurements') {
                  $value = $object->measurementsCount($scope,$scope_id);
                }
                if ($target=='taxons') {
                  $value = $object->taxonsCount($scope,$scope_id);
                }
                if ($target=='media') {
                  $value = $object->mediaCount($scope,$scope_id);
                }
                if ($target=='locations') {
                  $value = $object->locationsCount($scope,$scope_id);
                }
                if ($target=='datasets') {
                  $value = $object->datasetsCount($scope,$scope_id);
                }
                if ($target=='projects') {
                  $value = $object->projectsCount($scope,$scope_id);
                }
                $record = [
                  'object_id'=> $object->id,
                  'object_type'=> get_class($object),
                  'scope_type'=> $scope_type,
                  'scope_id' => $scope_id,
                  'target' => $target,
                  'value' => $value
                ];
                if ($value>0 or $storezeros) {
                  if (null !== $scope_id) {
                    $currentcount = Summary::where('object_type','=',$record['object_type'])->where('scope_type',"=",$scope_type)->where('target',"=",$target)->where('object_id',"=",$object->id)->where('scope_id',"=",$scope_id);
                  } else {
                    $currentcount = Summary::where('object_type','=',$record['object_type'])->where('scope_type',"=",$scope_type)->where('target',"=",$target)->where('object_id',"=",$object->id)->whereNull('scope_id');
                  }
                  if ($currentcount->count() > 0 ) {
                    $count = Summary::findOrFail($currentcount->first()->id);
                    $count->value = $value;
                    $count->save();
                  } else {
                    Summary::create($record);
                  }
                }
            }
          }
    }



    public static function updateSummaryTable($what='all',$taxons=null,$locations=null,$projects=null,$datasets=null,$scope='all')
    {
      //update counts for taxon
      if ($what=="taxons" or $what == 'all') {
        if (null == $taxons) {
          //get used taxons and calculate only for taxons above the species level
          $ids = Taxon::has('identifications')->cursor()->map(function($taxon) { return $taxon->getAncestorsAndSelf()->pluck('id')->toArray();})->toArray();
          $taxons_ids = array_unique(Arr::flatten($ids));
          //$noneed = Summary::where('object_type',"App\Models\Taxon")->selectRaw('DISTINCT object_id')->cursor()->pluck('object_id')->toArray();
          //$taxons = Taxon::whereIn('id',$taxons_ids)->where('level','<',Taxon::getRank('species')->orderBy('level')->cursor();
          //$taxons_ids = array_diff($taxons_ids,$noneed);
          $taxons = Taxon::whereIn('id',$taxons_ids)->orderBy('level')->cursor();
        }
        if ($taxons->count()) {
          foreach($taxons as $taxon) {
            $scope_type='all';
            $scope_id=null;
            $object_id=$taxon->id;
            $object_type="App\Models\Taxon";
            if ($scope=="all") {
              self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
            }

            $selfanddescendants = $taxon->getDescendantsAndSelf()->pluck('id')->toArray();
            if ($scope=="all" or $scope=='projects') {
              //get the project the taxon was used for
              $projects = Project::whereHas('individualsIdentifications',function($identification) use($selfanddescendants) {
                $identification->withoutGlobalScopes()->whereIn('taxon_id',$selfanddescendants);
              })->cursor();
              if ($projects->count()) {
                foreach($projects as $project) {
                  $scope_type="App\Models\Project";
                  $scope_id=$project->id;
                  self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
                }
              }
            }
            if ($scope=="all" or $scope=='datasets') {
              //get the dataset the taxon is measured or has individuals or vouchers measured
              $datasets_ids = Measurement::withoutGlobalScopes()->whereHasMorph('measured',['App\Models\Individual',"App\Models\Voucher"],function($measured) {
                $measured->identification->whereIn('taxon_id',$selfanddescendants);
              })->cursor()->map(function($q) { return $q->dataset_id; })->toArray();
              $datasets_ids = array_unique($datasets_ids);
              //DB::select("(SELECT DISTINCT dataset_id  FROM identifications JOIN measurements ON measurements.measured_id=identifications.object_id WHERE identifications.object_type=measurements.measured_type AND identifications.taxon_id IN (".implode(',',$selfanddescendants)."))");
              //$datasets_ids = Arr::flatten(array_map(function($value) { return (array)$value;},$datasets_ids));
              $datasets = Dataset::whereIn('id',$datasets_ids)->cursor();
              if ($datasets->count()) {
                foreach($datasets as $dataset) {
                  $scope_type="App\Models\Dataset";
                  $scope_id=$dataset->id;
                  self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
                }
              }
            }
            if ($scope=="all" or $scope=='locations') {
              //non point locations only (points will have easier counts //
              $locations = Location::whereHas('identifications',function($identification) use($selfanddescendants) {
                $identification->withoutGlobalScopes()->whereIn('taxon_id',$selfanddescendants);
              });
              $ancestorsandself = $locations->cursor()->map(function($location) {
                return $location->getAncestorsAndSelf()->pluck('id')->toArray();
              })->toArray();
              $ancestorsandself = array_unique(Arr::flatten($ancestorsandself));

              //eliminate
              //$noneed = Summary::where('object_id',$object_id)->where('object_type',$object_type)->whereIn('scope_id',$ancestorsandself)->where('scope_type','App\Models\Location');
              //$noneed = $noneed->cursor()->pluck('scope_id')->toArray();
              //$ancestorsandself = array_diff($ancestorsandself,$noneed);

              $locations = Location::noWorld()->whereIn('id',$ancestorsandself);
              //elimitate leaves as they should contain few counts and may be easily dinamically calculated
              $leaves = $locations->cursor()->map(function($location) {
                return $location->getLeaves()->pluck('id')->toArray();
              })->toArray();
              $leaves = array_filter($leaves);
              $leaves = array_unique(Arr::flatten($leaves));
              if (count($leaves)>0) {
                $locations = $locations->whereNotIn('id',$leaves)->cursor();
              } else {
                $locations = $locations->cursor();
              }

              //get_all ancestor locations
              if ($locations->count()) {
                foreach($locations as $location) {
                    $scope_type="App\Models\Location";
                    $scope_id=$location->id;
                    //$tem = Summary::where('object_id',$object_id)->where('object_type',$object_type)->where('scope_id',$scope_id)->where('scope_type',$scope_type)->count();
                    //if (!$tem) {
                    self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
                    //}
                }
              }
            }
          }
        }
      }

      //update counts for taxon
      if ($what=="locations" or $what == 'all') {
        if (null == $locations) {
          //exclude $leaves
          $leaves = Location::noWorld()->where('adm_level','<',999)->cursor()->map(function($location) { return $location->getLeaves()->pluck('id')->toArray();})->toArray();
          $leaves = array_unique(Arr::flatten($leaves));
          $locations_ids = Location::noWorld()->where('adm_level','<',999)->whereNotIn('id',$leaves)->cursor()->map(function($location) { return $location->getAncestorsAndSelf()->pluck('id')->toArray();})->toArray();
          $locations_ids = array_unique(Arr::flatten($locations_ids));
          $locations = Location::noWorld()->whereIn('id',$locations_ids)->orderBy('adm_level')->cursor();

          //has('vouchers')->orHas('individuals')->orderBy('adm_level')
          //->pluck('id')->toArray();
          //$locations_ids = Location::whereIn('id',$ids)->cursor()->map(function($location) { return $location->getAncestorsAndSelf()->pluck('id')->toArray();})->toArray();
          //$locations_ids = array_unique(Arr::flatten($locations_ids));
          //$locations = Location::whereIn('id',$locations_ids)->orderBy('adm_level')->cursor();
        }
        if ($locations->count()) {
          foreach($locations as $location) {

            $scope_type='all';
            $scope_id=null;
            $object_id=$location->id;
            $object_type="App\Models\Location";

            self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
            $selfanddescendants = $location->getDescendantsAndSelf()->pluck('id')->toArray();

            $projects = Project::whereHas('individuals',function($individual) use($selfanddescendants) {
              $individual->withoutGlobalScopes()->whereHas('locations',function($location) use($selfanddescendants) {
                $location->whereIn('location_id',$selfanddescendants);
              });
            });

            if ($projects->count()) {
              foreach($projects as $project) {
                $scope_type="App\Models\Project";
                $scope_id=$project->id;
                self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
              }
            }
            $datasets = Dataset::whereHas('individuals', function($individual) use($selfanddescendants) {
              $individual->withoutGlobalScopes()->whereHas('locations',function($location) use($selfanddescendants) {
                $location->whereIn('location_id',$selfanddescendants);
              });
            })->orWhereHas('vouchers',function($voucher) use($selfanddescendants) {
              $voucher->withoutGlobalScopes()->whereHas('locations',function($location) use($selfanddescendants) {
                $location->whereIn('location_id',$selfanddescendants);
              });
            })->cursor();
            if ($datasets->count()) {
              foreach($datasets as $dataset) {
                $scope_type="App\Models\Dataset";
                $scope_id=$dataset->id;
                self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
              }
            }
          }
        }
      }


      //update counts for taxon
      if ($what=="projects" or $what == 'all') {
        if (null == $projects) {
          $projects = Project::cursor();
        }
        if ($projects->count()) {
          foreach($projects as $project) {
            $scope_type='all';
            $scope_id=null;
            $object_id=$project->id;
            $object_type="App\Models\Project";
            Summary::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
          }
        }
      }

      if ($what=="datasets" or $what == 'all') {
        if (null == $datasets) {
          $datasets = Dataset::cursor();
        }
        if ($datasets->count()) {
          foreach($datasets as $dataset) {
            $scope_type='all';
            $scope_id=null;
            $object_id=$dataset->id;
            $object_type="App\Models\Dataset";
            self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
          }
        }
      }
      return true;
    }




}
