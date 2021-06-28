<?php
/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */
namespace App\Jobs;

use App\Models\Location;
use App\Models\Taxon;
use App\Models\Individual;
use App\Models\Measurement;
use App\Models\Voucher;
use App\Models\ODBFunctions;
use Auth;
use Activity;

class DeleteMany extends AppJob
{

    /**
     * Execute the job.
     *
     * @return void
     */
     public function inner_handle()
     {
         $data =  $this->extractEntrys();

         if (!$this->hasRequiredKeys(["ids_to_delete","model",], $data)) {
               return false;
         }
         //get ids to delete
         $ids_to_delete = collect(explode(",",$data['ids_to_delete']));
         $model = $data["model"];
         if (!$this->setProgressMax($ids_to_delete)) {
            return;
         }
         foreach ($ids_to_delete as $id) {
              if ($this->isCancelled()) {
                 break;
              }
              $this->userjob->tickProgress();
              try {
                  $this->deleteObject($id,$model);
              } catch (\Exception $e) {
                  $this->setError();
                  $this->appendLog('Exception '.$e->getMessage());
              }
              $this->affectedId($id);
        }

     }


     public function deleteObject($id,$model)
     {
       $object = app("App\\Models\\".$model)::findOrFail($id);
       if (!Auth::user()->can('delete', $object)) {
          $this->appendLog('ERROR: you cannot delete '.$model." ".$object->name);
          return false;
       }
       try {
           $object->delete();
       } catch (\Illuminate\Database\QueryException $e) {
           $this->appendLog('ERROR: was not able to delete '.$model." ".$object->name);
           return false;
       }

       //delete also logged activities for the resource
       $activity = Activity::where("subject_type",'App\Models\\'.$model)->where("subject_id",$id);
       if ($activity->count()) {
         $activity->delete();
       }
       $this->appendLog('WARNING:  '.$model." ".$object->name." deleted!");
       return true;
     }

}
