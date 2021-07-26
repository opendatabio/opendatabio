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
                //identification will be set to self if explicitly informed
                $oldidentification = null;
                if ($individual->identification) {
                  $oldidentification = $individual->identification()->first()->toArray();
                }
                $old_identification_individual_id = $individual->identification_individual_id;

                //has old update or else create
                $makechange = false;
                $date = $identifiers['date'];

                if ($individual->identificationSet) {
                    $individual->identificationSet()->update($identifiers_nodate);
                    $makechange = true;
                    $individual->identificationSet->setDate($date);
                    $individual->identificationSet->save();
                } else {
                    //if it is linked to another individual do not update and issues a warning
                    if ($old_identification_individual_id != $individual->id  and $old_identification_individual_id>0 and $update_nonself_identification==0) {
                      $this->appendLog('FAILED: Identification for Individual '.$individual->fullname.' was not updated because the Identification of this individual is that of another individual. You must specify to change this explicitly');
                    } else {
                      if ($update_nonself_identification>0 or $old_identification_individual_id == null or $old_identification_individual_id == $individual->id) {
                        $makechange = true;
                        $individual->identification_individual_id  =  $individual->id;
                        $individual->save();

                        $individual->identificationSet = new Identification(array_merge($identifiers_nodate, ['object_id' => $individual->id, 'object_type' => 'App\Models\Individual']));
                        $individual->identificationSet->setDate($date);
                        $individual->identificationSet->save();
                      }
                    }
                }
                if ($makechange) {
                  //log identification changes if any
                  $identifiers_nodate['date'] = $individual->identificationSet->date;
                  if ($old_identification_individual_id != $individual->id and null != $old_identification_individual_id and $update_nonself_identification>0) {
                      $oldidentification['identification_individual_id']  = $old_identification_individual_id;
                      $identifiers_nodate['identification_individual_id'] = $individual->id;
                  }
                  ActivityFunctions::logCustomChanges($individual,$oldidentification,$identifiers_nodate,'individual','identification updated',null);

                  $this->appendLog('UPDATED'.$individual->fullname);
                  //$individual = Individual::findOrFail($id);
                  //$individual->identification_individual_id = $individual->id;
                  //$individual->save();

                  $this->affectedId($individual->id);
                } else {
                  $this->appendLog('FAILED'.$individual->fullname);
                }


              }
           }
        } else {
          // TODO: Include handler to deal with external API data
        }
     }

}
