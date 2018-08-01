<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Plant;
use App\Taxon;
use App\Identification;
use App\Location;
use App\Project;
use App\Person;
use App\Collector;
use App\Http\Api\v0\PlantController; //for use asIdList function

class ImportPlants extends AppJob
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
        foreach ($data as $plant) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if (!$this->hasRequiredKeys(['tag', 'date', 'location', 'project'], $plant)) {
                continue;
            //validate location
            $valid = $this->validIdOrName(Location::select('id'), $plant['location']);
            if ($valid === null) {
                $this->skipEntry($plant, 'location '.$plant['location'].' was not found in the database');
                continue;
            } else
                $plant['location'] = $valid;
            //validate project
            $valid = $this->validIdOrName(Project::select('id'), $plant['project']);
            if ($valid === null) {
                $this->skipEntry($plant, 'project '.$plant['project'].' was not found in the database');
                continue;
            } else
                $plant['project'] = $valid;
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
        if (count($same)) {
            $this->setError();
            $this->appendLog('ERROR: There is another registry of a plant with location '.$location.' and tag '.$tag);

            return;
        }

        // Plants' fields is ok, what about related tables?
        $identification = $this->extractIdentification($plant);
        $collectors = $this->extractCollectors($plant);

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
        if ($identification) {
            $date = $identification['date'];
            $identification = new Identification([
                'object_id' => $plant->id,
                'object_type' => 'App\Plant',
                'modifier' => $identification['modifier'],
                'taxon_id' => $identification['taxon_id'],
                'person_id' => array_key_exists('person_id', $identification) ? $identification['person_id'] : null,

            ]);
            $identification->setDate($date);
            $identification->save();
        }
        if ($collectors) {
            foreach ($collectors as $collector) {
                Collector::create([
                        'person_id' => $collector,
                        'object_id' => $plant->id,
                        'object_type' => 'App\Plant',
                ]);
            }
        }

        return;
    }

    protected function extractCollectors($plant)
    {
        if (!array_key_exists('tagging_team', $plant)) {
            return null;
        $tagging_team = explode(',', $plant['tagging_team']);
        $ids = array();
        foreach ($tagging_team as $person) {
            $valid = $this->validIdOrName(Person::select('id'), $person, 'id', 'abbreviation');
            if ($valid === null)
                $this->appendLog('WARNING: Plant '.$plant['tag'].' reffers to '.$person.' as member of tagging team, but this person was not found in the database. Ignoring person '.$person);
            else
                array_push($ids, $valid);
        }
        return $ids;
    }

    protected function extractIdentification($plant)
    {
        if (!array_key_exists('taxon', $plant)) {
            return null;
        }
        $taxon = $plant['taxon'];
        if (is_numeric($taxon)) {
            $taxon_id = Taxon::select('id')->where('id', '=', $taxon)->get();
        } else {
            $taxon = $this->breakTaxonNameModifier($taxon);
            $identification['modifier'] = $taxon['modifier'];
            $taxon = $taxon['name'];
            $taxon_id = Taxon::select('id')->whereRaw('odb_txname(name, level, parent_id) LIKE ?', ['%'.$taxon.'%'])->get();
        }
        if (count($taxon_id)) {
            $identification['taxon_id'] = $taxon_id->first()->id;
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
            if (count($identifier_id)) {
                $identification['person_id'] = $identifier_id->first()->id;
            } else {
                $this->appendLog("WARNING: Identifier $identifier was not found in the person table.");
            }
        }
        if (!array_key_exists('modifier', $identification)) {
            $identification['modifier'] = 0;
        }
        $identification['date'] = array_key_exists('identification_date', $plant) ? $plant['identification_date'] : $plant['date'];
        $identification['object_type'] = 'App\Plant';

        return $identification;
    }

    protected function breakTaxonNameModifier($taxon)
    {
        if ($this->endsWith($taxon, ' s.s.')) {
            $name = substr($taxon, 0, -5);
            $modifier = Identification::SS;
        } elseif ($this->endsWith($taxon, ' s.l.')) {
            $name = substr($taxon, 0, -5);
            $modifier = Identification::SL;
        } elseif ($this->endsWith($taxon, ' c.f.')) {
            $name = substr($taxon, 0, -5);
            $modifier = Identification::CF;
        } elseif ($this->endsWith($taxon, ' vel aff.')) {
            $name = substr($taxon, 0, -9);
            $modifier = Identification::VEL_AFF;
        } elseif ($this->endsWith($taxon, ' aff.')) {
            $name = substr($taxon, 0, -5);
            $modifier = Identification::AFF;
        } else {
            $name = $taxon;
            $modifier = Identification::NONE;
        }

        return array(
            'name' => $name,
            'modifier' => $modifier,
        );
    }

    protected function endsWith($complete, $end)
    {
        return $end === substr($complete, 0, -strlen($end));
    }
}
