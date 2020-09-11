<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Plant;
use App\Location;
use App\Project;
use App\ODBFunctions;

class ImportPlants extends ImportCollectable
{
    private $requiredKeys;

    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $data = $this->extractEntrys();
        if (!$this->setProgressMax($data)) {
            return;
        }
        $this->requiredKeys = $this->removeHeaderSuppliedKeys(['tag', 'date', 'location']);
        $this->validateHeader('tagging_team');
        foreach ($data as $plant) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if ($this->validateData($plant)) {
                // Arrived here: let's import it!!
                try {
                    $this->import($plant);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on plant '.$plant['tag']);
                }
            }
        }
    }

    protected function validateData(&$plant)
    {
        if (!$this->hasRequiredKeys($this->requiredKeys, $plant)) {
            return false;
        }
        if (!$this->validateProject($plant)) {
            return false;
        }
        //validate location
        $valid = ODBFunctions::validRegistry(Location::select('id'), $plant['location']);
        if (null === $valid) {
            $this->skipEntry($plant, 'location '.$plant['location'].' was not found in the database');

            return false;
        }
        $plant['location'] = $valid->id;

        return true;
    }

    public function import($plant)
    {
        $location = $plant['location'];
        $tag = $plant['tag'];
        $date = array_key_exists('date', $plant) ? $plant['date'] : $this->header['date'];
        $project = $plant['project'];
        $created_at = array_key_exists('created_at', $plant) ? $plant['created_at'] : null;
        $updated_at = array_key_exists('updated_at', $plant) ? $plant['updated_at'] : null;
        $notes = array_key_exists('notes', $plant) ? $plant['notes'] : null;
        $relative_position = array_key_exists('relative_position', $plant) ? $plant['relative_position'] : null;
        $same = Plant::where('location_id', '=', $location)->where('tag', '=', $tag)->get();
        if (count($same)) {
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
        ]);
        if (!is_null($relative_position)) {
            if (!is_array($relative_position)) {
                $xy = explode(',',$relative_position);
            }  else {
                $xy = $relative_position;
            }
            if (count($xy)==2) {
                $plant->setRelativePosition($relative_position['x'], $relative_position['y']);
            } else {
                $this->skipEntry($plant, 'Relative position  for '.$location.' and tag '.$tag.' is wrong. Must have only two values');
                return;
            }

        }
        //date can not be set into constructor due to IncompleteDate compatibility
        $plant->setDate($date);
        $plant->save();
        $this->affectedId($plant->id);

        // - Then create the related registries (for identification and collector), if requested
        $this->createCollectorsAndIdentification('App\Plant', $plant->id, $collectors, $identification);

        return;
    }
}
