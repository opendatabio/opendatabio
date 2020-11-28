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

class ImportVouchers extends ImportCollectable
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
        foreach ($data as $voucher) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if ($this->validateData($voucher)) {
                // Arrived here: let's import it!!
                try {
                    $this->import($voucher);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on voucher '.$voucher['number']);
                }
            }
        }
    }

    protected function validateData(&$voucher)
    {
        if (!$this->hasRequiredKeys($this->requiredKeys, $voucher)) {
            return false;
        }
        if (!$this->validateProject($voucher)) {
            return false;
        }

        //validate parent
        $parent = $this->validParent($voucher);
        // TODO: if lat and long are informed, then create location or search for registered location with same coordinates.
        if (null === $parent) {
            $this->skipEntry($voucher, 'especified parent was not found in the database');

            return false;
        }
        $voucher['parent_id'] = $parent['id'];
        $voucher['parent_type'] = $parent['type'];

        return true;
    }

    private function validParent($voucher)
    {

        $validtypes = array('Plant' => Plant::class,'Location' => Location::class);
        if (array_key_exists('parent_id', $voucher)) {
            if (array_key_exists('parent_type', $voucher) and (array_key_exists($voucher['parent_type'],$validtypes) or array_key_exists($voucher['parent_type'],array_flip($validtypes))) {
                return array(
                    'id' => $voucher['parent_id'],
                    'type' => array_key_exists($voucher['parent_type'],$validtypes) ? $validtypes[$voucher['parent_type']] : $voucher['parent_type'];
                );
            }
            return null; // has id, but not type of parent
        } elseif (array_key_exists('parent_type', $voucher)) {
            return null; // has type, but not id of parent
        } elseif (array_key_exists('location', $voucher)) {
            if (array_key_exists('plant_tag', $voucher)) {
                $valid = $this->validate($voucher['location'], $voucher['plant_tag']);
                if (null === $valid) {
                    return null;
                }

                return array(
                    'id' => $valid,
                    'type' => 'App\Plant',
                );
            } else {
                $fields = ['id', 'name'];
                $valid = ODBFunctions::validRegistry(Location::select('id'), $location,$fields);
                if (null === $valid) {
                    return null;
                }

                return array(
                    'id' => $valid->id,
                    'type' => 'App\Location',
                );
            }
        } elseif (array_key_exists('plant', $voucher)) {
            $valid = Plant::select('id')
                    ->where('id', $voucher['plant'])
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
        $fields = ['id', 'name'];
        $valid = ODBFunctions::validRegistry(Location::select('id'), $location,$fields);
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
            $herbarios = explode(",",$herbarios);
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



    public function import($voucher)
    {

        $number = $voucher['number'];
        $date = array_key_exists('date', $this->header) ? $this->header['date'] : $voucher['date'];
        $project = array_key_exists('project', $this->header) ? $this->header['project'] : $voucher['project'];
        $parent_id = $voucher['parent_id'];
        $parent_type = $voucher['parent_type'];
        $created_at = array_key_exists('created_at', $voucher) ? $voucher['created_at'] : null;
        $updated_at = array_key_exists('updated_at', $voucher) ? $voucher['updated_at'] : null;
        $notes = array_key_exists('notes', $voucher) ? $voucher['notes'] : null;
        $collectors = $this->extractCollectors('Voucher '.$number, $voucher);

        //validate herbaria
        $herbaria = null;
        if (array_key_exists("herbaria",$voucher) && !empty($voucher['herbaria'])) {
            $herbaria = $this->extractHerbariaNumbers($voucher['herbaria']);
            if (count($herbaria)==0 && count($voucher['herbaria'])>0) {
              $this->skipEntry($voucher, Lang::get('messages.invalid_herbaria'));
              return;
            }
        }

        if (0 === count($collectors)) {
            $this->skipEntry($voucher, 'Can not found any collector of this voucher in the database');
            return;
        }
        $same = Voucher::where('person_id', '=', $collectors[0])->where('number', '=', $number)->get();
        if (count($same)) {
            $this->skipEntry($voucher, 'There is another registry of a voucher with main collector '.$collectors[0].' and number '.$number.' and this must be UNIQUE');
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
            $this->skipEntry($voucher, Lang::get('messages.invalid_date_error'));
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
            $voucher['date'] = $date;
            $identification = $this->extractIdentification($voucher);
            if (null === $identification) {
                $this->skipEntry($voucher, 'Vouchers of location must have taxonomic information');
                return;
            }
        } else {
            $identification = null;
        }

        //Finaly create the registries:
        // - First voucher's registry, to get their id
        $voucher = new Voucher([
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
        $voucher->setDate($date[0],$date[1],$date[2]);
        /*
        if (is_array($date[0])) {
          $this->appendLog("nao passou aqui mm ".serialize($date[0]));
        } else {
          $this->appendLog("NOT ARRAY ".serialize($date));
        }
        return;
        */
        $voucher->save();
        if (!is_null($herbaria)) {
          $voucher->setHerbariaNumbers($herbaria);
        }
        $this->affectedId($voucher->id);

        // - Then create the related registries (for identification and collector), if requested
        $this->createCollectorsAndIdentification('App\Voucher', $voucher->id, $collectors, $identification);

        return;
    }
}
