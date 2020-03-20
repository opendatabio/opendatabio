<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Person;
use App\Herbarium;

class ImportPersons extends AppJob
{
    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $data = $this->extractEntrys();
        if (!$this->setProgressMax($data)) {
            return;
        }
        foreach ($data as $person) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if ($this->validateData($person)) {
                // Arrived here: let's import it!!
                try {
                    $this->import($person);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on person '.$person['full_name'].$e->getTraceAsString());
                }
            }
        }
    }

    protected function validateData(&$person)
    {
        if (!$this->hasRequiredKeys(['full_name'], $person)) {
            return false;
        } elseif (!$this->validateAbbreviation($person)) {
            return false;
        } elseif (!$this->validateHerbarium($person)) {
            return false;
        } else {
            return true;
        }
    }

    protected function validateAbbreviation(&$person)
    {
        if (array_key_exists('abbreviation', $person) and ('' != $person['abbreviation'])) {
            return true;
        } else {
            $names = explode(' ', strtoupper($person['full_name']));
            $size = count($names);
            $abbreviation = $names[$size - 1];
            for ($i = 0; $i < $size - 1; ++$i) {
                $abbreviation = $abbreviation.' '.mb_substr($names[$i], 0, 1);
            }
            $person['abbreviation'] = $abbreviation;

            return true;
        }
    }

    protected function validateHerbarium(&$person)
    {
        if (array_key_exists('herbarium', $person) and ('' != $person['herbarium'])) {
            if (!is_numeric($person['herbarium'])) { // It is the herbarium name
                $herbarium_obj = Herbarium::where('name', '=', $person['herbarium'])
                                    ->orWhere('acronym', '=', $person['herbarium'])->get();
                if ($herbarium_obj->count()) {
                    $person['herbarium'] = $herbarium_obj->first()->id;
                } else { // Not found
                    $this->appendLog('WARNING: Herbarium for person '.$person['full_name'].' is listed as '.$person['herbarium'].', but this was not found in the database. Ignoring field...');
                    $person['herbarium'] = null;
                }
            }
        } else { // Not informed
            $person['herbarium'] = null;
        }

        return true;
    }

    public function import($person)
    {
        $full_name = $person['full_name'];
        $abbreviation = $person['abbreviation'];
        $herbarium = $person['herbarium'];
        $notes = $person['notes'];
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
            'notes' => $notes,
        ]);
        $person->save();
        $this->affectedId($person->id);

        return;
    }
}
