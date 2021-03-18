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
use App\Individual;
use App\Voucher;
use App\ODBFunctions;
use App\ODBTrait;
use App\BibReference;
use Auth;
use Lang;

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
        $this->requiredKeys = $this->removeHeaderSuppliedKeys(['person', 'object_type','dataset','date']);
        if (!$this->validateHeader()) {
            return;
        }
        foreach ($data as $measurement) {

            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if ($this->validateData($measurement)) {
                // Arrived here: let's import it!!
                try {
                    $this->import($measurement);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on measurement '.$measurement['object_id'].$e->getTraceAsString());
                }
            }
        }
    }

    protected function validateHeader()
    {
        if (array_key_exists('person',$this->header) and !$this->validatePerson($this->header['person'])) {
              return false;
        }
        if (array_key_exists('dataset',$this->header) and !$this->validateDataset($this->header['dataset'])) {
              return false;
        }
        if (array_key_exists('object_type',$this->header) and !$this->validateObjetType($this->header['object_type'])) {
              return false;
        }
        if (array_key_exists('bibreference',$this->header)  and !$this->validateBibReference($this->header['bibreference'])) {
              return false;
        }

        return true;
    }


    protected function validatePerson(&$person)
    {
        //$person = $this->header['person'];
        $valid = ODBFunctions::validRegistry(Person::select('id'), $person, ['id', 'abbreviation', 'full_name', 'email']);
        if (null === $valid) {
            $this->appendLog('Error: Header reffers to '.$person.' as who do these measurements, but this person was not found in the database.');
            return false;
        } else {
            //$this->header['person'] = $valid->id;
            $person = $valid->id;
            return true;
        }
    }
    protected function validateBibReference(&$bibreference)
    {
      $valid = BibReference::whereRaw('odb_bibkey(bibtex) = ?', [$bibreference])->get();
      if (null === $valid) {
        $this->appendLog('Bibreference '.$bibreference.' not found in database');
        return false;
      }
      $bibreference = $valid->id;
      return true;
    }
    protected function validateDataset($dataset)
    {
        $valid = Auth::user()->datasets()->where('id', $dataset);
        if (null === $valid) {
            $this->appendLog('Error: Header reffers to '.$dataset.' as dataset, but this dataset was not found in the database.');
            return false;
        } else {
            return true;
        }
    }

    protected function validateObjetType($object_type)
    {
        $res =  in_array($object_type,["Individual","Voucher","Location","Taxon"]);
        if (!$res) {
          $this->appendLog('object_type '.$object_type.' not found in ['.implode(";",["Individual","Voucher","Location","Taxon"]).']');
        }
        return $res;
    }

    protected function validateData(&$measurement)
    {
        $requiredKeys = array_merge($this->requiredKeys,['object_id','trait_id']);
        if (!$this->hasRequiredKeys($requiredKeys, $measurement)) {
            return false;
        } elseif (array_key_exists('person',$measurement) and !$this->validatePerson($measurement['person'])) {
              return false;
        } elseif (array_key_exists('dataset',$measurement) and !$this->validateDataset($measurement['dataset'])) {
              return false;
        } elseif (array_key_exists('object_type',$measurement) and !$this->validateObjetType($measurement['object_type'])) {
              return false;
        } elseif (!$this->validateObject($measurement)) {
            return false;
        } elseif (!$this->validateMeasurements($measurement)) {
            return false;
        } elseif (array_key_exists('bibreference',$measurement) and !$this->validateBibReference($measurement['bibreference'])) {
            return false;
        } else {
            return true;
        }
    }

    protected function validateObject($measurement)
    {
        $object_type = array_key_exists('object_type', $this->header) ? $this->header['object_type'] : $measurement['object_type'];
        if ('Location' === $object_type) {
            $query = Location::select('id')->where('id', $measurement['object_id'])->get();
        } elseif ('Taxon' === $object_type) {
            // TODO: perhaps add restriction to bibreference when type is taxon
            $query = Taxon::select('id')->where('id', $measurement['object_id'])->get();
        } elseif ('Individual' === $object_type) {
            $query = Individual::select('individuals.id')->where('id', $measurement['object_id'])->get();
        } elseif ('Voucher' === $object_type) {
            $query = Voucher::select('id')->where('id', $measurement['object_id'])->get();
        }
        if (count($query)) {
            return true;
        } else {
            $this->appendLog('WARNING: Object '.$object_type.' - '.$measurement['object_id'].' not found, all of their measurements will be ignored.');
            return false;
        }
    }

    public function validateColor($color) {
      if(preg_match("/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/", $color))
      {
          return true;
      }
      return false;
    }


    protected function validateValue($trait,$value)
    {

        if (!$trait->link_type==ODBTrait::LINK && (empty($value['value']) || (is_array($value['value']) && count($value['value'])==0))) {
          return false;
        }
        switch ($trait->type) {
          case 0:
          case 1:
              if (!is_numeric($value['value'])) {
                return false;
              }
              break;
          case 2:
          case 4:
              if (is_array($value['value']) && count($value['value'])>1) {
                 return false;
              }
          case 3:
              $category_ids = array();
              foreach($trait->categories as $cats) {
                $category_ids[] = $cats->id;
              }
              if (is_array($value['value']) && count(array_diff($value['value'],$category_ids))>0) {
                  return false;
              } else {
                //test if concatenated $string
                if (!is_array($value['value']))  {
                    $possval = explode(";",$value['value']);
                    if (is_array($possval) && count(array_diff($possval,$category_ids))>0) {
                        return false;
                      } else {
                        if (!in_array($value['value'],$category_ids)) {
                          return false;
                        }
                    }
                }
              }
              break;
          case 6:
              if (!$this->validateColor($value['value'])) {
                return false;
              }
              break;
          case 7:
              switch ($trait->link_type) {
                case (Taxon::class):
                  $taxon = Taxon::where('id','=',$value['link_id'])->get();
                  if (count($taxon)==0) {
                    return false;
                  }
                  break;
                case (Person::class):
                  $person = Person::where('id','=',$value['link_id'])->get();
                  if (count($person)==0) {
                    return false;
                  }
                  break;
                case (Individual::class):
                  $individual = Individual::where('id','=',$value['link_id'])->get();
                  if (count($individual)==0) {
                    return false;
                  }
                  break;
             }
             if (array_key_exists('value',$value) && !is_numeric($value['value'])) {
               return false;
             }
             break;
          case 8:
             $values = explode(";",$value['value']);
             if (count($values)!= $trait->value_length) {
               $this->appendLog('WARNING: length '.$trait->value_length.' different than'.count($values));
               return false;
             }
        }
        return true;
    }

    protected function validateMeasurements(&$measurement)
    {
        $valids = array();
        //check that trait exists;
        $trait = ODBFunctions::validRegistry(ODBTrait::with('categories')->select('*'), $measurement['trait_id'], ['id', 'export_name']);
        if (!$trait->id) {
          $this->appendLog('WARNING: Trait_id for trait '.$trait->id.' not found, this measurement will be ignored.');
          return false;
        }
        $measurement['trait_id'] = $trait->id;
        if ($trait->type==ODBTrait::LINK && !array_key_exists('link_id',$measurement)) {
          $this->appendLog('WARNING: Link_id required for trait '.$trait->id.' key not found, this measurement will be ignored.');
          return false;
        }
        if ($trait->type != ODBTrait::LINK and !array_key_exists('value',$measurement)) {
          $this->appendLog('WARNING: There is no value field to import'.serialize($measurement));
          return false;
        }
        if (!$this->validateValue($trait,$measurement)) {
          $this->appendLog('WARNING: Value for trait '.$trait->id.' is invalid, this measurement will be ignored.'.serialize($measurement));
          return false;
        }
        return true;
    }
    protected function checkDuplicateMeasurement($measurement,$value)
    {
      $sql = "dataset_id='".$measurement->dataset_id."' AND trait_id='".$measurement->trait_id."' AND measured_id ='".$measurement->measured_id."' AND measured_type='".addslashes($measurement->measured_type)."' AND date='".$measurement->date."'";
      if (in_array($measurement->type, [ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL])) {
        $same = Measurement::with('categories')->whereRaw($sql)->get();
        if (count($same)>0) {
          foreach($same as $val) {
              $cats = collect($val->categories)->map(function ($newcat) {
                  return $newcat->traitCategory->id;
              })->all();
              if (!is_array($value['value']) && in_array($value['value'],$cats)) {
                 return false;
              } else {
                if (is_array($value['value']) && count(array_diff($value['value'],$cats))==0) {
                  return false;
                }
              }
          }
        }
      } else {
        if (in_array($measurement->type, [ODBTrait::LINK])) {
          $sql .= " AND value_i='".$value['link_id']."'";
        }
        if (in_array($measurement->type, [ODBTrait::QUANT_INTEGER])) {
          $sql .= " AND value_i='".$value['value']."'";
        }
        if (in_array($measurement->type, [ODBTrait::QUANT_REAL])) {
          $sql .= " AND value='".$value['value']."'";
        }
        if (in_array($measurement->type, [ODBTrait::TEXT, ODBTrait::COLOR, ODBTrait::SPECTRAL])) {
          $sql .= " AND value_a='".$value['value']."'";
        }
        //$this->appendLog('WARNING:'.$sql);
        //return false;
        if (Measurement::whereRaw($sql)->count()>0) {
          return false;
        }
      }
      return true;
    }
    private function getObjectTypeClass($object_type) {
      switch ($object_type) {
        case "Individual":
           return Individual::class;
        case "Voucher":
           return Voucher::class;
        case "Location":
            return Location::class;
        case "Taxon":
            return Taxon::class;
        }
    }

    public function import($measurements)
    {
        //$measured_id = $measurements['object_id'];
        //unset($measurements['object_id']);
        //foreach ($measurements as $key => $value) {
        $object_type = array_key_exists('object_type', $this->header) ? $this->header['object_type'] : $measurements['object_type'];
        $measurement = new Measurement([
                'trait_id' => $measurements['trait_id'],
                'measured_id' => $measurements['object_id'],
                'measured_type' => $this->getObjectTypeClass($object_type),
                'dataset_id' => array_key_exists('dataset', $this->header) ? $this->header['dataset'] : $measurements['dataset'],
                'person_id' => array_key_exists('person', $this->header) ? $this->header['person'] : $measurements['person'],
                'bibreference_id' => array_key_exists('bibreference', $measurements) ? $measurements['bibreference'] : null,
                'notes' => array_key_exists('notes', $measurements) ? $measurements['notes'] : null,
        ]);
        $date = array_key_exists('date', $this->header) ? $this->header['date'] : $measurements['date'];
        $datearr = explode('-',$date);
        if (!Measurement::checkDate([$datearr[1],$datearr[2],$datearr[0]])) {
            $this->skipEntry($measurements, Lang::get('messages.invalid_date_error'));
        } else {
            $measurement->setDate($date);
        }
        //prevent duplications unless specified
        $allowDuplication = array_key_exists('duplicated', $measurements) ? $measurements['duplicated'] : 0;
        if (!$this->checkDuplicateMeasurement($measurement,$measurements) && $allowDuplication==0) {
          $this->skipEntry($measurements, "Duplicated measurement. To allow duplicated values for the same date and object include a 'duplicated' with 1 value in your data table");
        } else {
          /*if categorical must save beforehand to be able to save Categories */
          if (in_array($measurement->type, [ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL])) {
                $measurement->save();
                $measurement->setValueActualAttribute($measurements['value']);
          } else {
              if (ODBTrait::LINK == $measurement->type) {
                $measurement->value = array_key_exists('value', $measurements) ? $measurements['value'] : null;
                $measurement->value_i = $measurements['link_id'];
                $measurement->save();
              } else {
                //$this->appendLog('GOT HERE WITH'.$measurements['value']);
                $measurement->setValueActualAttribute($measurements['value']);
                $measurement->save();
              }
          }
        $this->affectedId($measurement->id);

        /* SUMMARY COUNT UPDATE */
        $taxon_id = null;
        $project_id = null;
        $location_id = null;
        if ($measurement->measured_type == Individual::class) {
          $individual = Individual::findOrFail($measurement->measured_id);
          $taxon_id = $individual->identification->taxon_id;
          $location_id = $individual->location_id;
          $project_id = $individual->project_id;
        }
        if ($measurement->measured_type == Voucher::class) {
            $voucher = Voucher::findOrFail($measurement->measured_id);
            $project_id = $voucher->project_id;
            if ($voucher->parent_type == Location::class) {
              $taxon_id =  $voucher->identification->taxon_id;
              $location_id = $voucher->parent_id;
            } else {
              $taxon_id =  $voucher->parent->identification->taxon_id;
              $location_id = $voucher->parent->location_id;
            }
        }
        if ($measurement->measured_type == Taxon::class) {
            $taxon_id = $measurement->measured_id;
        }
        if ($measurement->measured_type == Location::class) {
            $location_id = $measurement->measured_id;
        }
        $newvalues = [
          'taxon_id' => $taxon_id,
          'location_id' => $location_id,
          'project_id' => $project_id,
          'dataset_id' => $measurement->dataset_id
        ];
        $target = 'measurements'
        Summary::updateSummaryMeasurementsCounts($newvalues,$value="value + 1");
        /* END SUMMARY COUNT UPDATE */




        }
        return;
    }
}
