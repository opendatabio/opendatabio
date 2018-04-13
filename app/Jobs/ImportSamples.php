<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Voucher;
use App\Location;
use App\Plant;
use App\Project;

class ImportSamples extends AppJob
{
    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $data = $this->extractEntrys();
        if (!$this->setProgressMax($data))
            return;
        foreach ($data as $sample) {
            if ($this->isCancelled())
                break;
            $this->userjob->tickProgress();

            if (!$this->hasRequiredKeys(['number', 'date', 'collector', 'project'], $sample))
                continue;
            //validate parent
            $parent = $this->validParent($sample);
            if ($parent === null) {
                $this->skipEntry($sample, 'especified parent was not found in the database');
                continue;
            } else {
                $sample['parent_id'] = $parent['id'];
                $sample['parent_type'] = $parent['type'];
            }
            //validate project
            $valid = ODBFunctions::validRegistry(Project::select('id'), $sample['project']);
            if ($valid === null) {
                $this->skipEntry($sample, 'project '.$sample['project'].' was not found in the database');
                continue;
            } else
                $sample['project'] = $valid->id;
            // Arrived here: let's import it!!
            try {
                $this->import($sample);
            } catch (\Exception $e) {
                $this->setError();
                $this->appendLog('Exception '.$e->getMessage().' on voucher '.$sample['collector'].' - '.$sample['number']);
            }
        }
    }

    private function validParent($sample)
    {
        if (array_key_exists('parent_id', $sample)) {
            if (array_key_exists('parent_type', $sample))
                return array (
                    'id' => $sample['parent_id'],
                    'type' => $sample['parent_type']
                );
            return null;
        } elseif (array_key_exists('parent_type', $sample))
            return null;
        elseif (array_key_exists('location', $sample)) {
            if (array_key_exists('plant', $sample)) {
                $valid = validate($sample['location'], $sample['plant']);
                if ($valid === null)
                    return null;
                return array (
                    'id' => $valid,
                    'type' => 'App\Plant'
                );
            } else {
                $valid = ODBFunctions::validRegistry(Location::select('id'), $location);
                if ($valid === null)
                    return null;
                return array (
                    'id' => $valid->id,
                    'type' => 'App\Location'
                );
            }
        } elseif (array_key_exists('plant', $sample)) {
            $valid = Plant::select('id')
                    ->where('id', $sample['plant'])
                    ->get();
            if (count($valid) === 0)
                return null;
            return array (
                'id' => $valid->first()->id,
                'type' => 'App\Plant'
            );
        }
    }

    // Given a location name or id and a plant tag, returns the id of the plant with this tag and location if exists, otherwise returns null.
    private function validate($location, $plant)
    {
        $valid = ODBFunctions::validRegistry(Location::select('id'), $location);
        if ($valid === null)
            return null;
        $valid = Plant::select('id')
                ->where('location_id', $valid->id)
                ->where('tag', $plant)
                ->get();
        if (count($valid) === 0)
            return null;
        return $valid->first()->id;
    }

    public function extractHerbariaNumers($sample)
    {
        $herbaria = array();
        foreach ($sample as $key => $value)
            if (0 === strpos($key, 'H_') {
                $valid = ODBFunctions::validRegistry(Herbarium::select('id'), substr($key, 2), ['id','acronym','name','irn'])
                if ($valid !== null)
                    $herbaria[$valid->id] = $value;
            }
        return $herbaria;
    }

    public function import($sample)
    {
        $number = $sample['number'];
        $date = $sample['date'];
        $project = $sample['project'];
        $parent_id = $sample['parent_id'];
        $parent_type = $sample['parent_type'];
        $created_at = array_key_exists('created_at', $sample) ? $sample['created_at'] : null;
        $updated_at = array_key_exists('updated_at', $sample) ? $sample['updated_at'] : null;
        $notes = array_key_exists('notes', $sample) ? $sample['notes'] : null;
        $collectors = $this->extractCollectors('Sample '.$number, $sample);
        if (count($collectors) === 0) {
            $this->skipEntry($sample, 'Can not found any collector of this voucher in the database');
            return;
        }
        $same = Voucher::where('person_id', '=', $collectos[0])->where('number', '=', $number)->get();
        if (count($same)){
            $this->skipEntry($sample, 'There is another registry of a voucher with main collector '.$collectors[0].' and number '.$number);
            return;
        }

        // vouchers' fields is ok, what about related tables?
        if ($parent_type === 'App\Location') {
            $identification = $this->extractIdentification($sample);
            if ($identification === null) {
                $this->skipEntry($sample, 'Vouchers of location must have taxonomic information');
                return;
            }
        } else
            $identification = null;
        
        $herbaria = $this->extractHerbariaNumers($sample);
        //Finaly create the registries:
        // - First voucher's registry, to get their id
        $sample = new Voucher([
            'person_id' => $collectors[0],
            'number' => $number,
            'project_id' => $project,
            'parent_type' => $parent_type,
            'parent_id' => $parent_id,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'notes' => $notes,
        ]);
        //date can not be set into constructor due to IncompleteDate compatibility
        $sample->setDate($date);
        $sample->setHerbariaNumbers($herbaria);
        $sample->save();
        $this->affectedId($sample->id);
        
        // - Then create the related registries (for identification and collector), if requested
        $this->createCollectorsAndIdentification('App\Voucher', $sample->id, $collectors, $identification);
        return;
    }
}
