<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Location;
use App\Taxon;
use App\Project;
use App\Dataset;
use App\Measurement;
use App\Picture;
use Activity;
use DB;
use Illuminate\Support\Arr;


class Summary extends Model
{

    protected $table = "counts";

    protected $fillable = ['object_id', 'object_type', 'value', 'target', 'scope_id','scope_type'];

    public function object()
    {
        return $this->morphTo('object');
    }

    public static function fillCounts($object_id=null,$object_type=null,$scope_type='all',$scope_id=null,$storezeros=false)
      {
        /*define the object for which counts will be calculated */
        /* in the models to calculate counts three public static functions have to exist:
          * plantsCount($scope,$scope_id)
          * vouchersCount($scope,$scope_id)
          * measurementsCount($scope,$scope_id)
          * taxonsCount($scope,$scope_id )
        */
        $objects = app($object_type);
        $scope = 'all';
        if ('App\Project' == $scope_type) {
            $scope = 'projects';
        }
        if ('App\Dataset' == $scope_type) {
            $scope = 'datasets';
        }
        if (! null == $object_id) {
          $objects = $objects->where('id',$object_id);
        }
        foreach($objects->cursor() as $object) {
            $targets = ['plants','vouchers','measurements','taxons','pictures'];
            if (get_class($object) == "App\Project") {
              $targets = ['plants','vouchers','measurements','taxons','pictures','locations','datasets'];
            }
            if (get_class($object) == "App\Dataset") {
              $targets = ['plants','vouchers','measurements','taxons','locations','projects'];
            }
            foreach($targets as $target) {
                if ($target=='plants') {
                  $value = $object->plantsCount($scope,$scope_id);
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
                if ($target=='pictures') {
                  $value = $object->picturesCount($scope,$scope_id);
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


    public static function updateSummaryTable($what='all',$taxons=null,$locations=null,$projects=null,$datasets=null)
    {
      //update counts for taxon
      if ($what=="taxons" or $what == 'all') {
        if (null == $taxons) {
          $taxons = Taxon::cursor();
        }
        if ($taxons->count()) {
          foreach($taxons as $taxon) {
            $scope_type='all';
            $scope_id=null;
            $object_id=$taxon->id;
            $object_type="App\Taxon";
            Summary::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=true);
            $projects = Project::cursor();
            if ($projects->count()) {
              foreach($projects as $project) {
                $scope_type="App\Project";
                $scope_id=$project->id;
                self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
              }
            }
            $datasets = Dataset::cursor();
            if ($datasets->count()) {
              foreach($datasets as $dataset) {
                $scope_type="App\Dataset";
                $scope_id=$dataset->id;
                self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
              }
            }
          }
        }

      }

      //update counts for taxon
      if ($what=="locations" or $what == 'all') {
        if (null == $locations) {
          $locations = Location::cursor();
        }
        if ($locations->count()) {
          foreach($locations as $location) {
            $scope_type='all';
            $scope_id=null;
            $object_id=$location->id;
            $object_type="App\Location";
            self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=true);
            $projects = Project::cursor();
            if ($projects->count()) {
              foreach($projects as $project) {
                $scope_type="App\Project";
                $scope_id=$project->id;
                self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=false);
              }
            }
            $datasets = Dataset::cursor();
            if ($datasets->count()) {
              foreach($datasets as $dataset) {
                $scope_type="App\Dataset";
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
            $object_type="App\Project";
            Summary::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=true);
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
            $object_type="App\Dataset";
            self::fillCounts($object_id,$object_type,$scope_type,$scope_id,$storezeros=true);
          }
        }
      }




    return true;
    }


    /* USED AS A FREQUENT SCHEDULED CRON CALL - CALLED BY THE php artisan summary:update  COMMAND*/
    public static function updateCountChanges()
    {
      /* check last time the count table has been changed */
        $lastcount =  Summary::select('updated_at')->orderBy('updated_at','desc')->first()->updated_at;

      /* COUNTS FOR THE TAXON MODEL */
        /*check last identification created */
        $lastdet = Identification::select('updated_at')->orderBy('updated_at','desc')->first()->updated_at;

        /* check taxon counts changes */
        $newtaxons = array();
        if ($lastdet>$lastcount) {
          $newtaxons = Identification::where('updated_at',">",$lastcount)->distinct('taxon_id')->cursor()->pluck('taxon_id')->toArray();
        }
        /*check changes in identifications  */
        $changedtaxons = Activity::where("description","=","identification updated")->where('updated_at',">",$lastcount)->cursor()->map(function($activity) {
            $changes = $activity->changes;
            $newtaxon = (int)$changes['attributes']['taxon_id'];
            $oldtaxon = (int)$changes['old']['taxon_id'];
            if ($newtaxon !== $oldtaxon) {
              return [$newtaxon,$oldtaxon];
            }
          })->toArray();

        /* get new measurements for taxa  */
        $lastmeasurment = Measurement::select('updated_at')->withoutGlobalScopes()->orderBy('updated_at','desc')->first()->updated_at;
        $measurements_taxons = [];
        $measurements_locations = [];
        if ($lastmeasurment>$lastcount) {
          $measurements_taxons = Measurement::withoutGlobalScopes()->where('updated_at',">",$lastcount)->where('measured_type',"=","App\Taxon")->distinct('measured_id')->cursor()->pluck('measured_id')->toArray();
          $measurements_locations = Measurement::withoutGlobalScopes()->where('updated_at',">",$lastcount)->where('measured_type',"=","App\Location")->distinct('measured_id')->cursor()->pluck('measured_id')->toArray();
        }
        /* get taxon pictures */
        $lastpictures = Picture::select('updated_at')->orderBy('updated_at','desc')->first()->updated_at;
        $pictures_taxons = [];
        $pictures_locations = [];
        if ($lastpictures>$lastcount) {
          $pictures_taxons = Picture::where('updated_at',">",$lastcount)->where('object_type',"=","App\Taxon")->distinct('object_id')->cursor()->pluck('object_id')->toArray();
          $pictures_locations = Picture::where('updated_at',">",$lastcount)->where('object_type',"=","App\Location")->distinct('object_id')->cursor()->pluck('object_id')->toArray();
        }




        /* taxa to update include each taxa and their ancestors or parents*/
        $taxons = array_merge($newtaxons,$changedtaxons,$measurements_taxons,$pictures_taxons);
        $taxons = array_unique(Arr::flatten($taxons));
        $alltaxons = [];
        foreach($taxons as $id) {
            $taxons = Taxon::find($id)->getAncestorsAndSelf()->pluck('id')->toArray();
            $alltaxons[] = $taxons;
        }
        $alltaxons = Arr::flatten($alltaxons);
        $alltaxons = array_unique($alltaxons);
        if (count($alltaxons)) {
          //update counts for these taxa
          $taxons = Taxon::whereIn('id',$alltaxons)->cursor();
          self::updateSummaryTable($what='taxons',$taxons=$taxons,$locations=null,$projects=null,$datasets=null);
        }


        /* COUNTS FOR THE Location MODEL */
          /*check last plant change */
          $lastplant = Plant::withoutGlobalScopes()->select('updated_at')->orderBy('updated_at','desc')->limit(0,1)->first();
          $lastplant = $lastplant->updated_at;
          $newplantslocations =[];
          if ($lastplant>$lastcount) {
            $newplantslocations = Plant::withoutGlobalScopes()->where('updated_at',">",$lastcount)->cursor()->pluck('location_id')->toArray();
          }
          /*check last plant change */
          $lastvoucher = Voucher::withoutGlobalScopes()->select('updated_at')->where('parent_type','=',"App\Location")->orderBy('updated_at','desc')->limit(0,1)->first();
          $lastvoucher = $lastvoucher->updated_at;
          $newvoucherslocations =[];
          if ($lastvoucher>$lastcount) {
            $newvoucherslocations = Voucher::withoutGlobalScopes()->where('parent_type','=',"App\Location")->where('updated_at',">",$lastcount)->cursor()->pluck('parent_id')->toArray();
          }

          /* check changes in plant */
          $changedplantlocations = Activity::where("description","=","updated")->where('subject_type','=','App\Plant')->where('updated_at',">",$lastcount)->cursor()->map(function($activity) {
              $changes = $activity->changes;
              $newlocation = $changes['attributes']['location_id'];
              $oldlocation = $changes['old']['location_id'];
              if ($newlocation !== $oldlocation) {
                return [$newlocation,$oldlocation];
              }
            })->toArray();
          /* check changes in plant */
          $changedvoucherlocations = Activity::where("description","=","updated")->where('subject_type','=','App\Voucher')->where('updated_at',">",$lastcount)->cursor()->map(function($activity) {
                $changes = $activity->changes;
                $voucher = Voucher::withoutGlobalScopes()->find($activity->subject_id);
                if ($voucher->parent_type == 'App\Location') {
                  $newlocation = $changes['attributes']['parent_id'];
                  $oldlocation = $changes['old']['parent_id'];
                  if ($newlocation !== $oldlocation ) {
                    return [$newlocation,$oldlocation];
                  }
                }
              })->toArray();
            /* taxa to update include each taxa and their ancestors or parents*/
            $locations = array_merge($changedplantlocations,$changedvoucherlocations,$newvoucherslocations,$newplantslocations,$measurements_locations,$pictures_locations);
            $locations = array_unique(Arr::flatten($taxons));
            $allocations = [];
            foreach($taxons as $id) {
                $allocations = Location::find($id)->getAncestorsAndSelf()->pluck('id')->toArray();
                $allocations[] = $allocations;
            }
            $allocations = Arr::flatten($allocations);
            $allocations = array_unique($allocations);
            if (count($allocations)) {
              //update counts for these locations
              $locations = Location::whereIn('id',$allocations)->cursor();
              self::updateSummaryTable($what='locations',$taxons=null,$locations=$locations,$projects=null,$datasets=null);
            }

        /* COUNTS FOR THE Project MODEL */
          $insertedplantprojects = Plant::withoutGlobalScopes()->where('created_at',">",$lastcount)->distinct('project_id')->cursor()->pluck('project_id')->toArray();
          $changedplantprojects = Activity::where("description","=","updated")->where('subject_type','=','App\Plant')->where('updated_at',">",$lastcount)->cursor()->map(function($activity) {
              $changes = $activity->changes;
              $newproject = isset($changes['attributes']['project_id']) ? $changes['attributes']['project_id'] : null;
              $oldproject = isset($changes['old']['project_id']) ? $changes['attributes']['project_id'] : null;
              if ($newproject !== $oldproject ) {
                  return array_filter([$newproject,$oldlocation]);
              }
          })->toArray();
          $insertedvoucherprojects = Voucher::withoutGlobalScopes()->where('created_at',">",$lastcount)->distinct('project_id')->cursor()->pluck('project_id')->toArray();
          $changedvoucherprojects = Activity::where("description","=","updated")->where('subject_type','=','App\Voucher')->where('updated_at',">",$lastcount)->cursor()->map(function($activity) {
                $changes = $activity->changes;
                $newproject = isset($changes['attributes']['project_id']) ? $changes['attributes']['project_id'] : null;
                $oldproject = isset($changes['old']['project_id']) ? $changes['attributes']['project_id'] : null;
                if ($newproject !== $oldproject ) {
                    return array_filter([$newproject,$oldproject]);
                }
            })->toArray();
          $changedmeasurementsprojects = Measurement::withoutGlobalScopes()->where('updated_at',">",$lastcount)->cursor()->map(function($measurement) {
            return $measurement->measured()->pluck('project_id')->toArray();
          })->toArray();
          $projects = array_merge($insertedplantprojects,$insertedvoucherprojects,$changedplantprojects,$changedvoucherprojects,$changedmeasurementsprojects);
          $projects = array_unique(Arr::flatten($projects));
          if (count($projects)) {
            //update counts for these projects
            $projects = Project::whereIn('id',$projects)->cursor();
            self::updateSummaryTable($what='projects',$taxons=null,$locations=null,$projects,$datasets=null);
          }

        /* COUNTS for the Dataset MODEL
        /* check dataset counts when measurements changes  */
        $changeddatasets = Measurement::withoutGlobalScopes()->where('updated_at',">",$lastcount)->distinct('dataset_id')->cursor()->pluck('dataset_id')->toArray();
        if (count($changeddatasets)) {
          //update counts for these locations
          $datasets = Dataset::whereIn('id',$changeddatasets)->cursor();
          self::updateSummaryTable($what='datasets',$taxons=null,$locations=null,$projects=null,$datasets);
        }


    }


}
