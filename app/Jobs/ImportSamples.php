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
use App\Herbarium;
use Lang;

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
        $this->requiredKeys = $this->removeHeaderSuppliedKeys(['number', 'date', 'collector']);
        $this->validateHeader();
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

        $validtypes = array('Plant' => Plant::class,'Location' => Location::class);
        if (array_key_exists('parent_id', $sample)) {
            if (array_key_exists('parent_type', $sample) and array_key_exists($sample['parent_type'],$validtypes)) {
                return array(
                    'id' => $sample['parent_id'],
                    'type' => $validtypes[$sample['parent_type']],
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
                $valid = ODBFunctions::validRegistry(Location::select('id'), $location);
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

    public function extractHerbariaNumbers($herbarios)
    {
        $herbaria = array();
        if (!is_array($herbarios)) {
          if (!empty($herbarios)) {
            $herbarios = explode(";",$herbarios);
          } else {
            $herbarios = array();
          }
        }
        foreach ($herbarios as $key => $value) {
            //validate acronym or id
            if (!array_key_exists('herbarium_code',$value) && !is_array($value)) {
                $herbarium_code = $value;
                $value = array('herbarium_type' => 0);
            } else {
                $herbarium_code = $value['herbarium_code'];
                $value = $value;
            }
            $query = Herbarium::select(['id', 'acronym', 'name', 'irn']);
            $fields = ['id', 'acronym', 'name', 'irn'];
            $valid = ODBFunctions::validRegistry($query, $herbarium_code, $fields);
            if (null !== $valid) {
              unset($value['herbarium_code']);
              if (array_key_exists('herbarium_number',$value) && 0 == $value['herbarium_number']) {
                unset($value['herbarium_number']);
              }
              $herbaria[$valid->id] = $value;
            }
        }
        return $herbaria;
    }



    public function import($sample)
    {

        $number = $sample['number'];
        $date = array_key_exists('date', $this->header) ? $this->header['date'] : $sample['date'];
        $project = array_key_exists('project', $this->header) ? $this->header['project'] : $sample['project'];
        $parent_id = $sample['parent_id'];
        $parent_type = $sample['parent_type'];
        $created_at = array_key_exists('created_at', $sample) ? $sample['created_at'] : null;
        $updated_at = array_key_exists('updated_at', $sample) ? $sample['updated_at'] : null;
        $notes = array_key_exists('notes', $sample) ? $sample['notes'] : null;
        $collectors = $this->extractCollectors('Sample '.$number, $sample);

        //validate herbaria
        $herbaria = null;
        if (array_key_exists("herbaria",$sample) && !empty($sample['herbaria'])) {
            $herbaria = $this->extractHerbariaNumbers($sample['herbaria']);
            if (count($herbaria)==0 && count($sample['herbaria'])>0) {
              $this->skipEntry($sample, Lang::get('messages.invalid_herbaria'));
              return;
            }
        }

        if (0 === count($collectors)) {
            $this->skipEntry($sample, 'Can not found any collector of this voucher in the database');
            return;
        }
        $same = Voucher::where('person_id', '=', $collectors[0])->where('number', '=', $number)->get();
        if (count($same)) {
            $this->skipEntry($sample, 'There is another registry of a voucher with main collector '.$collectors[0].' and number '.$number.' and this must be UNIQUE');
            return;
        }

        if (is_array($date)) {
          if (!$this->hasRequiredKeys(['year'], $date)) {
              $this->skipEntry($value, Lang::get('messages.invalid_date_error')." Date is array and at least year key must exist");
              return ;
          } else {
            $year = array_key_exists('year', $date) ? $date['year'] : null;
            $month = array_key_exists('month', $date) ? $date['month'] : null;
            $day = array_key_exists('day', $date) ? $date['day'] : null;
            $date = array($month,$day,$year);
          }
        } else {
           //format MUST BE YYYY-MM-DD
           $date = explode("-",$date);
           if (3 === count($date)) {
             $date = array($date[1],$date[2],$date[0]);
           } else {
             $date = array();
           }
        }
        if (!Voucher::checkDate($date)) {
            $this->skipEntry($sample, Lang::get('messages.invalid_date_error'));
            return ;
        }
        // collection date must be in the past or today
        if (!Voucher::beforeOrSimilar($date, date('Y-m-d'))) {
            $validator->errors()->add('date_day', Lang::get('messages.date_future_error'));
            return ;
        }

        // vouchers' fields is ok, what about related tables?
        if ('App\Location' === $parent_type) {
          // add corrected date if need to be used as identification date WHEN MISSING
            $sample['date'] = $date;
            $identification = $this->extractIdentification($sample);
            if (null === $identification) {
                $this->skipEntry($sample, 'Vouchers of location must have taxonomic information');
                return;
            }
        } else {
            $identification = null;
        }

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
        $sample->setDate($date[0],$date[1],$date[2]);
        /*
        if (is_array($date[0])) {
          $this->appendLog("nao passou aqui mm ".serialize($date[0]));
        } else {
          $this->appendLog("NOT ARRAY ".serialize($date));
        }
        return;
        */
        $sample->save();
        if (!is_null($herbaria)) {
          $sample->setHerbariaNumbers($herbaria);
        }
        $this->affectedId($sample->id);

        // - Then create the related registries (for identification and collector), if requested
        $this->createCollectorsAndIdentification('App\Voucher', $sample->id, $collectors, $identification);

        return;
    }
}
