<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Plant;
use App\Location;
use App\Project;

class ImportPlants extends ImportCollectable
{
    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $data = $this->extractEntrys();
        if (!$this->setProgressMax($data))
            return;
        foreach ($data as $plant) {
            if ($this->isCancelled())
                break;
            $this->userjob->tickProgress();

            if (!$this->hasRequiredKeys(['tag', 'date', 'location', 'project'], $plant))
                continue;
            //validate location
            $valid = ODBFunctions::validRegistry(Location::select('id'), $plant['location']);
            if ($valid === null) {
                $this->skipEntry($plant, 'location '.$plant['location'].' was not found in the database');
                continue;
            } else
                $plant['location'] = $valid->id;
            //validate project
            $valid = ODBFunctions::validRegistry(Project::select('id'), $plant['project']);
            if ($valid === null) {
                $this->skipEntry($plant, 'project '.$plant['project'].' was not found in the database');
                continue;
            } else
                $plant['project'] = $valid->id;
            // Arrived here: let's import it!!
            try {
                $this->import($plant);
            } catch (\Exception $e) {
                $this->setError();
                $this->appendLog('Exception '.$e->getMessage().' on plant '.$plant['tag']);
            }
        }
    }

    public function import($plant)
    {
        $location = $plant['location'];
        $tag = $plant['tag'];
        $date = $plant['date'];
        $project = $plant['project'];
        $created_at = array_key_exists('created_at', $plant) ? $plant['created_at'] : null;
        $updated_at = array_key_exists('updated_at', $plant) ? $plant['updated_at'] : null;
        $notes = array_key_exists('notes', $plant) ? $plant['notes'] : null;
        $relative_position = array_key_exists('relative_position', $plant) ? $plant['relative_position'] : null;
        $same = Plant::where('location_id', '=', $location)->where('tag', '=', $tag)->get();
        if (count($same)){
            $this->skipEntry($plant, 'There is another registry of a plant with location '.$location.' and tag '.$tag);
            return;
        }

        // Plants' fields is ok, what about related tables?
        $identification = $this->extractIdentification($plant);
        $collectors = $this->extractCollectors('Plant '.$tag, $plant, 'tagging_team');
        
        //Finaly create the registries:
        // - First plant's registry, to get their id
        $plant = new Plant([
            'location_id' => $location,
            'tag' => $tag,
            'project_id' => $project,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'notes' => $notes,
            'relative_position' => $relative_position,
        ]);
        //date can not be set into constructor due to IncompleteDate compatibility
        $plant->setDate($date);
        $plant->save();
        $this->affectedId($plant->id);
        
        // - Then create the related registries (for identification and collector), if requested
        $this->createCollectorsAndIdentification('App\Plant', $plant->id, $collectors, $identification);
        return;
    }
}
