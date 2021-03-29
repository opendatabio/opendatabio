<?php
/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Models\Individual;
use App\Models\Identification;
use App\Models\ODBFunctions;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Activity;
use App\Models\ActivityFunctions;
//use Spatie\Activitylog\Traits\LogsActivity;


class BatchUpdateIndividuals extends ImportCollectable
{

    use AuthorizesRequests;

    /**
     * Execute the job.
     *
     * @return void
     */
     public function inner_handle()
     {

         $data =  $this->extractEntrys();

         //If comming from the web interface, fix date to extract identifications
         if (array_key_exists('not_external',$this->header)) {
           if (!$this->hasRequiredKeys(["taxon_id","identifier_id","individualids_list","identification_date_year"], $data)) {
               return false;
           }
           //define identification array
           $identifiers = $this->extractIdentification($data);

           $identifiers_nodate = [
             'taxon_id' => $identifiers['taxon_id'],
             'person_id' => $identifiers['person_id'],
             'biocollection_id' => $identifiers['biocollection_id'],
             'biocollection_reference' => $identifiers['biocollection_reference'],
             'notes' => $identifiers['notes'],
             'modifier' => $identifiers['modifier']
           ];
           //iterate over all individuals to update identification
           $individuals = explode(',',$data['individualids_list']);
           $update_nonself_identification = isset($data['update_nonself_identification']) ? (int) $data['update_nonself_identification'] : 0;
           if (!$this->setProgressMax($individuals)) {
              return;
           }
           foreach ($individuals as $id) {
              if ($this->isCancelled()) {
                 break;
              }
              $this->userjob->tickProgress();

              $individual = Individual::findOrFail($id);
              if (!$this->authorize('update', $individual)) {
                $this->appendLog('WARNING: You do not have permission to alter identification of individual'.$individual->fullname);
              } else {
                //identification will be set to self
                $oldidentification = null;
                if ($individual->identification) {
                  $oldidentification = $individual->identification()->first()->toArray();
                }

                if ($individual->identification_individual_id != $individual->id) {
                  // TODO: LONG CHANGE ON THIS
                  $individual->identification_individual_id  =  $individual->id;
                  $individual->save();
                }
                //has old update or else create
                $makechange = false;
                if ($individual->identificationSet) {
                    $individual->identificationSet()->update($identifiers_nodate);
                    $makechange = true;
                } else {
                    //if it is linked to another individual do not update and issues a warning
                    if ($individual->identification_individual_id != $individual->id  and $update_nonself_identification==0) {
                      $this->appendLog('FAILED: Identification for Individual '.$individual->fullname.' was not updated because the Identification of this individual is that of another individual. You must specify to change this explicitly.');
                    } else {
                      if ($update_nonself_identification>0 or $individual->identification_individual_id == null) {
                        $makechange = true;
                        $individual->identificationSet = new Identification(array_merge($identifiers_nodate, ['object_id' => $individual->id, 'object_type' => 'App\Models\Individual']));
                        //the individual
                      }
                    }
                }
                if ($makechange) {
                  $date = $identifiers['date'];
                  $individual->identificationSet->setDate($date);
                  $individual->identificationSet->save();
                  //log identification changes if any
                  $identifiers_nodate['date'] = $individual->identificationSet->date;
                  if ($individual->identification_individual_id != $individual->id and null != $individual->identification_individual_id) {
                      $oldidentification['identification_individual_id']  = $individual->identification_individual_id;
                      $identifiers_nodate['identification_individual_id'] = $individual->id;
                  }
                  ActivityFunctions::logCustomChanges($individual,$oldidentification,$identifiers_nodate,'individual','identification updated',null);

                  $individual = Individual::findOrFail($id);
                  $individual->identification_individual_id = $individual->id;
                  $individual->save();

                  $this->affectedId($individual->id);
                }


              }
           }
        } else {
          // TODO: Include handler to deal with external API data
        }
     }

}
