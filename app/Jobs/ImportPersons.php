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
        $data = $this->userjob->data['data'];
        if (!count($data)) {
            $this->setError();
            $this->appendLog('ERROR: data received is empty!');

            return;
        }
        $this->userjob->setProgressMax(count($data));
        foreach ($data as $person) {
            // has this job been cancelled?
            // calls "fresh" to make sure we're not receiving a cached object
            if ('Cancelled' == $this->userjob->fresh()->status) {
                $this->appendLog('WARNING: received CANCEL signal');
                break;
            }
            $this->userjob->tickProgress();

            if (!is_array($person)) {
                $this->setError();
                $this->appendLog('ERROR: person entry is not formatted as array!'.serialize($person));
                continue;
            }
            if (!array_key_exists('full_name', $person)) {
                $this->setError();
                $this->appendLog('ERROR: entry needs a name: '.implode(';', $person));
                continue;
            }
            // Arrived here: let's import it!!
            try {
                $this->import($person);
            } catch (\Exception $e) {
                $this->setError();
                $this->appendLog('Exception '.$e->getMessage().' on person '.$person['full_name']);
            }
        }
    }

    public function import($person)
    {
	    $full_name = $person['full_name'];
        $email = array_key_exists('email', $person) ? $person['email'] : null;
        $institution = array_key_exists('institution', $person) ? $person['institution'] : null;
        $abbreviation = $this->extractAbbreviation($person);
        $herbarium = $this->extractHerbarium($person);
        $dupes = Person::duplicates($full_name, $abbreviation);
        if (count($dupes)) {
            $same = Person::where('abbreviation', '=', $abbreviation)->get();
            if (count($same)){
                $this->setError();
                $this->appendLog('ERROR: There is another registry of a person with name '.$full_name.' and abbreviation '.$abbreviation);
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
        return;
    }

    protected function extractAbbreviation($person)
    {
        if (array_key_exists('abbreviation', $person) && ('' != $person['abbreviation']))
            return $person['abbreviation'];
        else
        {
            $names = explode(' ', strtoupper($person['full_name']));
            $size = count($names);
            $abbreviation = $names[$size-1] . ', ';
            for ($i = 0; $i < $size-1; $i++)
                $abbreviation = $abbreviation . $names[$i][0] . '. ';
            return $abbreviation;
        }
    }

    protected function extractHerbarium($person)
    {
        if (array_key_exists('herbarium', $person) and ('' != $person['herbarium']))
        {
            if (is_numeric($person['herbarium']))
                return $person['herbarium'];
            else // It is the herbarium name
            {
                $herbarium_obj = Herbarium::where('name', '=', $person['herbarium'])
                                    ->orWhere('acronym', '=', $person['herbarium'])->get();
                if ($herbarium_obj->count()) {
                    return $herbarium_obj->first()->id;
                } else {
                    $this->appendLog("WARNING: Herbarium for person ".$person['full_name']." is listed as ".$person['herbarium'].", but this was not found in the database.");
                    return null;
		}
	    }
        }
        else
        {
            return null;
        }
    }
}
