<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Models\Person;
use App\Models\Biocollection;
use CodeInc\StripAccents\StripAccents;

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
        } elseif (!$this->validateBiocollection($person)) {
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

    protected function validateBiocollection(&$person)
    {
        if (array_key_exists('biocollection', $person) and ('' != $person['biocollection'])) {
            if (!is_numeric($person['biocollection'])) { // It is the biocollection name
                $biocollection_obj = Biocollection::where('name', '=', $person['biocollection'])
                                    ->orWhere('acronym', '=', $person['biocollection'])->get();
                if ($biocollection_obj->count()) {
                    $person['biocollection'] = $biocollection_obj->first()->id;
                } else { // Not found
                    $this->appendLog('WARNING: Biocollection for person '.$person['full_name'].' is listed as '.$person['biocollection'].', but this was not found in the database. Ignoring field...');
                    $person['biocollection'] = null;
                }
            }
        } else { // Not informed
            $person['biocollection'] = null;
        }

        return true;
    }

    public function import($person)
    {
        $full_name = $person['full_name'];
        $abbreviation = $person['abbreviation'];
        $biocollection = $person['biocollection'];

        $notes = array_key_exists('notes', $person) ? $person['notes'] : null;
        $email = array_key_exists('email', $person) ? $person['email'] : null;
        $institution = array_key_exists('institution', $person) ? $person['institution'] : null;

        //search for fuzz duplicates
        //$dupes = Person::duplicates($full_name, $abbreviation);

        //check of exact abbreviation duplicates
        $normalized_abbreviation = trim(mb_strtolower($abbreviation));
        $normalized_abbreviation = StripAccents::strip( (string) $normalized_abbreviation);
        $normalized_abbreviation = preg_replace('/[^a-zA-Z0-9]/', '', $normalized_abbreviation);
        $same = Person::all()->filter(function ($aperson) use ($normalized_abbreviation) {
              $nm_abbreviation = $aperson->abbreviation;
              $nm_abbreviation = trim(mb_strtolower($nm_abbreviation));
              $nm_abbreviation = StripAccents::strip( (string) $nm_abbreviation);
              $nm_abbreviation = preg_replace('/[^a-zA-Z0-9]/', '', $nm_abbreviation);
              return ($normalized_abbreviation==$nm_abbreviation);
        });
        if ($same->count()) {
            //or count($dupes)) {
            $this->skipEntry($person, 'There is another registry of a person with abbreviation '.$abbreviation);
            return;
        }
        //only identica abbreviations are not allowed. Fuzzy search removed here.
        // TODO: check for fuzzy similarities but add option to POST API
        // to allow imports of different abbreviation but similar fuzzy dupes
        // if informed by user.

        $person = new Person([
            'full_name' => $full_name,
            'abbreviation' => $abbreviation,
            'email' => $email,
            'institution' => $institution,
            'biocollection_id' => $biocollection,
            'notes' => $notes,
        ]);
        $person->save();
        $this->affectedId($person->id);

        return;
    }
}
