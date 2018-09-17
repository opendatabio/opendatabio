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
use App\ODBFunctions;

class ImportSamples extends ImportCollectable
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
        $this->requiredKeys = $this->removeHeaderSuppliedKeys(['number', 'date', 'collector', 'project']);
        if ($this->validateHeader()) {
            foreach ($data as $sample) {
                if ($this->isCancelled()) {
                    break;
                }
                $this->userjob->tickProgress();

                if ($this->validateData($sample)) {
                    // Arrived here: let's import it!!
                    try {
                        $this->import($sample);
                    } catch (\Exception $e) {
                        $this->setError();
                        $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on sample '.$sample['number']);
                    }
                }
            }
        } else {
            $this->setError();
        }
    }

    protected function validateData(&$sample)
    {
        if (!$this->hasRequiredKeys($this->requiredKeys, $sample)) {
            return false;
        }
        if (!$this->validateProject($sample)) {
            return false;
        }

        //validate parent
        $parent = $this->validParent($sample);
        if (null === $parent) {
            $this->skipEntry($sample, 'especified parent was not found in the database');

            return false;
        }
        $sample['parent_id'] = $parent['id'];
        $sample['parent_type'] = $parent['type'];

        return true;
    }

    private function validParent($sample)
    {
        if (array_key_exists('parent_id', $sample)) {
            if (array_key_exists('parent_type', $sample)) {
                return array(
                    'id' => $sample['parent_id'],
                    'type' => $sample['parent_type'],
                );
            }

            return null; // has id, but not type of parent
        } elseif (array_key_exists('parent_type', $sample)) {
            return null; // has type, but not id of parent
        } elseif (array_key_exists('location', $sample)) {
            if (array_key_exists('plant', $sample)) {
                $valid = $this->validate($sample['location'], $sample['plant']);
                if (null === $valid) {
                    return null;
                }

                return array(
                    'id' => $valid,
                    'type' => 'App\Plant',
                );
            } else {
                $valid = ODBFunctions::validRegistry(Location::select('id'), $sample['location']);
                if (null === $valid) {
                    return null;
                }

                return array(
                    'id' => $valid->id,
                    'type' => 'App\Location',
                );
            }
        } elseif (array_key_exists('plant', $sample)) {
            $valid = Plant::select('id')
                    ->where('id', $sample['plant'])
                    ->get();
            if (0 === count($valid)) {
                return null;
            }

            return array(
                'id' => $valid->first()->id,
                'type' => 'App\Plant',
            );
        }
    }

    // Given a location name or id and a plant tag, returns the id of the plant with this tag and location if exists, otherwise returns null.
    private function validate($location, $plant)
    {
        $valid = ODBFunctions::validRegistry(Location::select('id'), $location);
        if (null === $valid) {
            return null;
        }
        $location = $valid->id;
        $valid = Plant::select('plants.id as plant_id')
                ->where('plants.location_id', $location)
                ->where('plants.tag', $plant)
                ->get();
        if (0 === count($valid)) {
            return null;
        }

        return $valid->first()->plant_id;
    }

    public function extractHerbariaNumers($sample)
    {
        $herbaria = array();
        foreach ($sample as $key => $value) {
            if (0 === strpos($key, 'H_')) {
                $query = Herbarium::select('id');
                $value = substr($key, 2);
                $fields = ['id', 'acronym', 'name', 'irn'];
                $valid = ODBFunctions::validRegistry($query, $value, $fields);
                if (null !== $valid) {
                    $herbaria[$valid->id] = $value;
                }
            }
        }

        return $herbaria;
    }

    public function import($sample)
    {
        $number = $sample['number'];
        $date = $this->getValue($sample, 'date');
        $project = $this->getValue($sample, 'project');
        $parent_id = $sample['parent_id'];
        $parent_type = $sample['parent_type'];
        $created_at = array_key_exists('created_at', $sample) ? $sample['created_at'] : null;
        $updated_at = array_key_exists('updated_at', $sample) ? $sample['updated_at'] : null;
        $notes = array_key_exists('notes', $sample) ? $sample['notes'] : null;
        $collectors = $this->extractCollectors('Sample '.$number, $sample);
        if (0 === count($collectors)) {
            $this->skipEntry($sample, 'Can not found any collector of this sample in the database');

            return;
        }
        $same = Voucher::select('id')->where('person_id', $collectors[0])->where('number', $number)->get();
        if (count($same)) {
            $this->skipEntry($sample, 'There is another sample of '.$collectors[0].' with number '.$number, $same->first()->id);

            return;
        }

        // vouchers' fields is ok, what about related tables?
        if ('App\Location' === $parent_type) {
            $identification = $this->extractIdentification($sample);
            if (null === $identification) {
                $this->skipEntry($sample, 'Samples of location must have taxonomic information');

                return;
            }
        } else {
            $identification = null;
        }

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
