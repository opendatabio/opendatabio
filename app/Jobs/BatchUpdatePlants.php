<?php

namespace App\Jobs;

use App\Plant;
use App\ODBFunctions;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BatchUpdatePlants extends ImportCollectable
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
           if (!$this->hasRequiredKeys(["taxon_id","identifier_id","plantids_list","identification_date_year"], $data)) {
               return false;
           }
           //define identification array
           $identifiers = $this->extractIdentification($data);

           $identifiers_nodate = [
             'taxon_id' => $identifiers['taxon_id'],
             'person_id' => $identifiers['person_id'],
             'herbarium_id' => $identifiers['herbarium_id'],
             'herbarium_reference' => $identifiers['herbarium_reference'],
             'notes' => $identifiers['notes'],
             'modifier' => $identifiers['modifier']
           ];
           //iterate over all plants to update identification
           $plant_ids = explode(',',$data['plantids_list']);
           if (!$this->setProgressMax($plant_ids)) {
              return;
           }
           foreach ($plant_ids as $id) {
              if ($this->isCancelled()) {
                 break;
              }
              $this->userjob->tickProgress();

              $plant = Plant::findOrFail($id);
              if (!$this->authorize('update', $plant)) {
                $this->appendLog('WARNING: You do not have permission to alter identification of plant'.$plant->fullname);
              } else {
                if ($plant->identification) {
                    $plant->identification()->update($identifiers_nodate);
                  } else {
                    $plant->identification = new Identification(array_merge($identifiers_nodate, ['object_id' => $plant->id, 'object_type' => 'App\Plant']));
                  }
                  $date = $identifiers['date'];
                  $plant->identification->setDate($date[0],$date[1],$date[2]);
                  $plant->identification->save();
                  $this->affectedId($plant->id);
              }
           }
        } else {
          // TODO: Include handler to deal with external API data
        }
     }

}
