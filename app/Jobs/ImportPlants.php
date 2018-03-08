<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Plants;
use App\Taxon;
use App\Identification;
use App\Location;
use App\Project;
use App\Person;

class ImportPlants extends AppJob
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
        foreach ($data as $plant) {
            // has this job been cancelled?
            // calls "fresh" to make sure we're not receiving a cached object
            if ('Cancelled' == $this->userjob->fresh()->status) {
                $this->appendLog('WARNING: received CANCEL signal');
                break;
            }
            $this->userjob->tickProgress();

            if (!is_array($plant)) {
                $this->setError();
                $this->appendLog('ERROR: person entry is not formatted as array!'.serialize($plant));
                continue;
            }
            if (!hasRequiredKeys(['tag', 'date', 'location', 'project'], $plant))
                continue;
            if (!validLocation($plant)) {
                $this->setError();
                $this->appendLog('ERROR: entry needs a location: '.implode(';', $plant));
                continue;
            }
            if (!validProject($plant)) {
                $this->setError();
                $this->appendLog('ERROR: entry needs a project: '.implode(';', $plant));
                continue;
            }
            // Arrived here: let's import it!!
            try {
                $this->import($plant);
            } catch (\Exception $e) {
                $this->setError();
                $this->appendLog('Exception '.$e->getMessage().' on person '.$plant['full_name']);
            }
        }
    }

    protected function hasRequiredKeys($requiredKeys, $registry) {
        for ($requiredKeys as $key)
            if (!array_key_exists($key, $registry)) {
                $this->setError();
                $this->appendLog('ERROR: entry needs a '.$key.': '.implode(';', $registry));
                return false;
            }
        return true;
    }
    
    protected function validLocation($plant)
    {
        $location = $plant['location'];
        if (!is_numeric($location)) {
            $locations_id = Location::select('id')->where('name', '=', $location)->get();
            if (count($locations_id)) {
                $location = $locations_id->first();
                $plant['location'] = $location;
            } else {
                $this->appendLog("WARNING: Location $location was not found in the database.");
                return false;
            }
        }
        return true;
    }

    protected function validProject($plant)
    {
        $project = $plant['project'];
        if (!is_numeric($project)) {
            $projects_id = Project::select('id')->where('name', '=', $project)->get();
            if (count($projects_id)) {
                $project = $projects_id->first();
                $plant['project'] = $project;
            } else {
                $this->appendLog("WARNING: Project $project was not found in the database.");
                return false;
            }
        }
        return true;
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
        $same = Plant::where('location', '=', $location)->where('tag', '=', $tag)->get();
        if (count($same)){
            $this->setError();
            $this->appendLog('ERROR: There is another registry of a plant with location '.$location.' and tag '.$tag);
            return;
        }

        // Plants' fields is ok, what about indetification of this plant?
        $identification = extractIdentification($plant);
        
        //Finaly create the registries
        $plant = new Plant([
            'location_id' => $location,
            'tag' => $tag,
            'date' => $date,
            'project_id' => $project,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'notes' => $notes,
            'relative_position' => $relative_position,
        ]);
        $plant->save();
        $this->affectedId($plant->id);
        if ($identification) {
            $identification['object_id'] = $plant->id;
            $identification = new Identification($identification);
        }
        return;
    }
    
    protected function extractIdentification($plant)
    {
        if (!array_key_exists('taxon', $plant))
            return null;
        $taxon = $plant['taxon'];
        if (is_numeric($taxon)) {
            $taxon_id = Taxon::select('id')->where('id', '=', $taxon])->get();
        } else {
            $taxon = breakTaxonNameModifier($taxon);
            $idetification['modifier'] = $taxon['modifier'];
            $taxon = $taxon['name'];
            $taxon_id = Taxon::select('id')->whereRaw('odb_txname(name, level, parent_id) LIKE ?', ['%'.$request->taxon.'%'])->get();
        }
        if (count($taxon_id)) {
            $identification['taxon_id'] = $taxon_id->first();
        } else {
            $this->appendLog("WARNING: Taxon $taxon was not found in the database.");
            return null;
        }
        // Map $plant['identifier'] to $identification['person_id']
        if (array_key_exists('identifier', $plant)) {
            $identifier = $plant['identifier'];
            if (is_numeric($identifier)) {
                $identifier_id = Person::select('id')->where('id', '=', $identifier)->get();
            } else {
                $identifier_id = Person::select('id')
                                ->where('full_name', 'LIKE', '%'.$identifier.'%')
                                ->orWhere('abbreviation', 'LIKE', '%'.$identifier.'%')
                                ->orWhere('email', 'LIKE', '%'.$identifier.'%')
                                ->get();
            }
            if (count($identifier_id))
                $identification['person_id'] = $identifier_id->first();
            else
                $this->appendLog("WARNING: Identifier $identifier was not found in the person table.");
        }
        if (!array_key_exists('modifier', $idetification))
            $idetification['modifier'] = 0;
        $idetification['date'] = array_key_exists('idetification_date', $plant) ? $plant['idetification_date'] : $plant['date'];
        $identification['object_type'] = 'App\Plant';
        return $identification;
    }

    protected function breakTaxonNameModifier($taxon)
    {
        if (endsWith($taxon, ' s.s.') {
            $name = sustr($taxon, 0, -5);
            $modifier = Identification::SS;
        } elseif (endsWith($taxon, ' s.l.') {
            $name = sustr($taxon, 0, -5);
            $modifier = Identification::SL;
        } elseif (endsWith($taxon, ' c.f.') {
            $name = sustr($taxon, 0, -5);
            $modifier = Identification::CF;
        } elseif (endsWith($taxon, ' vel aff.') {
            $name = sustr($taxon, 0, -9);
            $modifier = Identification::VEL_AFF;
        } elseif (endsWith($taxon, ' aff.') {
            $name = sustr($taxon, 0, -5);
            $modifier = Identification::AFF;
        } else {
            $name = $taxon;
            $modifier = Identification::NONE;
        }
        return array (
            'name' -> $name;
            'modifier' -> $modifier
        );
    }
}
