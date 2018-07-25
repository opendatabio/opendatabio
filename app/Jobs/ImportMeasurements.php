<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Measurement;
use App\Location;
use App\Taxon;
use App\Person;
use App\Plant;
use App\Sample;

class ImportMeasurements extends AppJob
{
    protected $sourceType;
    
    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $data = $this->extractEntrys();
        if (!$this->setProgressMax($data)) {
            return;
        }
        if (!$this->validateHeader()) {
            return;
        }

        foreach ($data as $measurement) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();


            if ($this->validateData($easurement)) {
                // Arrived here: let's import it!!
                try {
                    $this->import($easurement);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on measurement '.$measurement['name'].$e->getTraceAsString());
                }
            }
        }
    }

    protected function validateHeader()
    {
        if (!$this->hasRequiredKeys(['object_type', 'person', 'date', 'dataset'], $this->header)) {
            return false;
        } elseif (!$this->validatePerson()) {
            return false;
        } elseif (!$this->validateDataset()) {
            return false;
        } elseif (!$this->validateObjetType()) {
            return false;
        } else {
            return true;
        }
    }

    protected function validatePerson()
    {
        $person = $this->header['person'];
        $valid = ODBFunctions::validRegistry(Person::select('id'), $person, ['id', 'abbreviation', 'full_name', 'email']);
        if (null === $valid) {
            $this->appendLog('Error: Header reffers to '.$person.' as who do these measurements, but this person was not found in the database.');
            return false;
        } else {
            $this->header['person'] = $valid->id;
            return true;
        }
    }

    protected function validateDataset()
    {
        $valid = Auth::user()->datasets()->where('id', $this->header['dataset'])
        if (null === $valid) {
            $this->appendLog('Error: Header reffers to '.$this->header['dataset'].' as dataset, but this dataset was not found in the database.');
            return false;
        } else {
            return true;
        }
    }

    protected function validateObjetType()
    {
        return in_array($this->header['object_type'], ['App\\Location', 'App\\Taxon', 'App\\Plant', 'App\\Sample']);
    }

    protected function validateData(&$measurement)
    {
        if (!$this->hasRequiredKeys(['object_id'], $measurement)) {
            return false;
        } elseif (!$this->validateObject($measurement)) {
            return false;
        } elseif (!$this->validateMeasurements($measurement)) {
            return false;
        } else {
            return true;
        }
    }

    protected function validateObject(&$measurement)
    {
        if ('App\\Location' === $this->header['object_type']) {
            $query = Location::select('id')->where('id', $measurement['object_id'])->get();
        } elseif ('App\\Taxon' === $this->header['object_type']) {
            $query = Taxon::select('id')->where('id', $measurement['object_id'])->get();
        } elseif ('App\\Plant' === $this->header['object_type']) {
            $query = Plant::select('plants.id')->where('id', $measurement['object_id'])->get();
        } elseif ('App\\Sample' === $this->header['object_type']) {
            $query = Voucher::select('id')->where('id', $measurement['object_id'])->get();
        }
        if (count($query)) {
            return true;
        } else {
            $this->appendLog('WARNING: Object '.$this->header['object_type'].' - '.$measurement['object_id'].' not found, all of their measurements will be ignored.');

            return false;
        }
    }

    protected function validateMeasurements(&$measurement)
    {
        $valids = array ();
        foreach ($measurement as $key => $value) {
            if ('object_id' === $key) {
                $valids[$key] = $value;
            } else {
                $trait = $this->getTrait($key);
                if ($trait) {
                    $valids[$trait] = $value;
                    // TODO validate value
                } else {
                    $this->appendLog('WARNING: Trait '.$key.' of object '.$measurement['object_id'].' not found, this measurement will be ignored.');
                }
            }
        }
        if (count($valids)) {
            $measurement = $valids;

            return true;
        }

        return false;
    }

    private function getTrait($name)
    {
        // TODO interpret $name as export_name of an ODBTrait and return the corresponding ODBTrait if exists, otherwise returns null
        return null;
    }

    public function import($measurement)
    {
        /* TODO Replace this with code to create the new measurement
        $full_name = $person['full_name'];
        $abbreviation = $person['abbreviation'];
        $herbarium = $person['herbarium'];
        $email = array_key_exists('email', $person) ? $person['email'] : null;
        $institution = array_key_exists('institution', $person) ? $person['institution'] : null;
        $dupes = Person::duplicates($full_name, $abbreviation);
        if (count($dupes)) {
            $same = Person::where('abbreviation', '=', $abbreviation)->get();
            if (count($same)) {
                $this->skipEntry($person, 'There is another registry of a person with abbreviation '.$abbreviation);

                return;
            }
            $this->appendLog('WARNING: There is another registry of a person with name like '.$full_name.' or abbreviation like '.$abbreviation);
        }

        $person = new Person([
            'full_name' => $full_name,
            'abbreviation' => $abbreviation,
            'email' => $email,
            'institution' => $institution,
            'herbarium_id' => $herbarium,
        ]);
        $person->save();
        $this->affectedId($person->id);
        */

        return;
    }
}
