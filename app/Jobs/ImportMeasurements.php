<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Measurement;

class ImportMeasurements extends AppJob
{
    protected $sourceType;
    
    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $sourceType = $this->userjob->data['sourceType'];
        switch ($this->userjob->data['sourceType']) {
            case 'location':
                $sourceType = 'App\\Location';
                break;
            case 'taxon':
                $sourceType = 'App\\Taxon';
                break;
            case 'plant':
                $sourceType = 'App\\Plant';
                break;
            case 'sample':
                $sourceType = 'App\\Voucher';
                break;
            default:
                $this->setError();
                $this->appendLog('Unknown type of object measured, try location, taxon, plant or sample');
                return;
        }
        $this->setError();
        $this->appendLog('Import data for '.$sourceType.' is not yet implemented!');
        /* TODO Implement this
        $data = $this->extractEntrys();
        if (!$this->setProgressMax($data)) {
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
        */
    }

    protected function validateData(&$measurement)
    {
        if (!$this->hasRequiredKeys([], $measurement)) {
            return false;
        } elseif (!$this->validateSomething($measurement)) {
            return false;
        } else {
            return true;
        }
    }

    protected function validateSomething(&$measurement)
    {
        // TODO what needs to validate and return if it is valid
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
