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
use App\Summary;
use Auth;
use Lang;
use Spatie\SimpleExcel\SimpleExcelReader;

class ImportMeasurements extends AppJob
{
    protected $sourceType;

    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $data = $this->extractEntrys();

        $hasfile = $this->userjob->data['data'];
        /* if a file has been uploaded */
        if (isset($hasfile['filename'])) {
          $filename = $hasfile['filename'];
          $filetype = $hasfile['filetype'];
          $path = storage_path('app/public/tmp/'.$filename);
          /* this will be a lazy collection to minimize memory issues*/
          $howmany = SimpleExcelReader::create($path)->getRows()->count();
          $this->userjob->setProgressMax($howmany);
          /* I have to do twice, not understanding why loose the collection if I just count on it */
          $data = SimpleExcelReader::create($path)->getRows();
        } else {
          if (!$this->setProgressMax($data)) {
              return;
          }
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
      if (null == $bibreference) {
        $bibreference = null;
        return true;
      }
      if (is_numeric($bibreference)) {
        $valid = BibReference::where('id',$bibreference);
      } else {
        $valid = BibReference::whereRaw('odb_bibkey(bibtex) = ?', [$bibreference]);
      }
      if ($valid->count()) {
        $bibreference = $valid->get()->first()->id;
        return true;
      }
      $this->appendLog('Bibreference '.$bibreference.' not found in database');
      return false;
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
        if (!isset($measurement['trait_id']) & isset($measurement['trait'])) {
          $measurement['trait_id'] = $measurement['trait'];
        }
        $requiredKeys = array_merge($this->requiredKeys,['object_id','trait_id']);
        if (!$this->hasRequiredKeys($requiredKeys, $measurement)) {
            return false;
        }
        if (array_key_exists('person',$measurement) and !$this->validatePerson($measurement['person'])) {
              return false;
        }

        if (array_key_exists('dataset',$measurement) and !$this->validateDataset($measurement['dataset'])) {
              return false;
        }

        if (array_key_exists('object_type',$measurement) and !$this->validateObjetType($measurement['object_type'])) {
              return false;
        }

        if (!$this->validateObject($measurement)) {
            return false;
        }

        if (!$this->validateMeasurements($measurement)) {
            return false;
        }

        if (array_key_exists('bibreference',$measurement) and !$this->validateBibReference($measurement['bibreference'])) {
            return false;
        }

        return true;
    }

    protected function validateObject($measurement)
    {
        $object_type = array_key_exists('object_type', $this->header) ? $this->header['object_type'] : $measurement['object_type'];
        $valid = app('App\\'.$object_type)::where('id', $measurement['object_id']);
        if ($valid->count()) {
            return true;
        } else {
            $this->appendLog('WARNING: Object '.$object_type.' - '.$measurement['object_id'].' not found. Measurement ignored.');
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


    protected function validateValue($odbtrait,&$measurement)
    {

        if (!$odbtrait->link_type == ODBTrait::LINK && (empty($measurement['value']) || (is_array($measurement['value']) && count($measurement['value'])==0))) {
          $this->appendLog('ERROR: Value for '.$odbtrait->export_name.' is missing'.$measurement['value']);
          return false;
        }
        switch ($odbtrait->type) {
          case ODBTrait::QUANT_INTEGER:
          case ODBTrait::QUANT_REAL:
              if (!is_numeric($measurement['value'])) {
                $this->appendLog('ERROR: Value for '.$odbtrait->export_name.' must be numeric');
                return false;
              }
              break;
          case ODBTrait::CATEGORICAL:
          case ODBTrait::ORDINAL:
              if (is_array($measurement['value']) && count($measurement['value'])>1) {
                 $this->appendLog('ERROR: Value for '.$odbtrait->export_name.' must have ONE category only');
                 return false;
              }
          case ODBTrait::CATEGORICAL_MULTIPLE:
              if (!$this->validateCategories($odbtrait,$measurement['value'])) {
                return false;
              }
              break;
          case ODBTrait::COLOR:
              if (!$this->validateColor($measurement['value'])) {
                $this->appendLog('ERROR: Value for '.$odbtrait->export_name.' color is invalid');
                return false;
              }
              break;
          case ODBTrait::LINK:
              switch ($odbtrait->link_type) {
                case (Taxon::class):
                  $taxon = Taxon::where('id','=',$measurement['link_id']);
                  if ($taxon->count()==0) {
                    $this->appendLog('ERROR: Value for '.$odbtrait->export_name.' has and invalid Taxon link');
                    return false;
                  }
                  break;
                case (Person::class):
                  $person = Person::where('id','=',$measurement['link_id']);
                  if ($person->count()==0) {
                    $this->appendLog('ERROR: Value for '.$odbtrait->export_name.' has and invalid Person link');
                    return false;
                  }
                  break;
                case (Individual::class):
                  $individual = Individual::where('id','=',$measurement['link_id']);
                  if ($individual->count()==0) {
                    $this->appendLog('ERROR: Value for '.$odbtrait->export_name.' has and invalid Individual link');
                    return false;
                  }
                  break;
             }
             if (isset($measurement['value']) && !is_numeric($measurement['value']) && !empty($measurement['value'])) {
               $this->appendLog('ERROR: Value for '.$odbtrait->export_name.' must be a number');
               return false;
             }
             break;
          case ODBTrait::SPECTRAL:
             $measurements = explode(";",$measurement['value']);
             if (count($measurements) != $odbtrait->value_length) {
               $this->appendLog('ERROR: Value for '.$odbtrait->export_name.' must have '.$odbtrait->value_length." but it has ".count($measurements));
               return false;
             }
        }
        return true;
    }


    public function validateCategories($odbtrait,&$value)
    {
        $cats = [];
        if (!is_array($value)) {
          if (strpos($value, '|') !== false) {
              $value = explode('|', $value);
          } elseif (strpos($value, ';') !== false) {
              $value = explode(';', $value);
          } elseif (strpos($value, ',') !== false) {
              $value = explode(',', $value);
          }
        }
        $msg = [];
        foreach ($value as $key => $cat) {
          if (null != $cat) {
            $thetrait = clone $odbtrait;
            if (is_string($cat)) {
              $valid = $thetrait->categories()->whereHas('translations',function($tr) use($cat){ $tr->where('translation','like',$cat);});
            } else {
              $valid = $thetrait->categories()->where('id',$cat);
            }
            if ($valid->count() == 1) {
              $cats[] = $valid->first()->id;
            } else {
              $msg[] = $cat.' is an invalid category for trait '.$thetrait->export_name;
            }
          }
        }
        if (count($msg)>0) {
          $this->appendLog('ERROR:'.implode(" | ",$msg));
          return false;
        }
        /* save ids and return valid */
        $value = $cats;
        return true;
    }


    protected function validateMeasurements(&$measurement)
    {
        $valids = array();
        //check that trait exists;
        $odbtrait = ODBFunctions::validRegistry(ODBTrait::with('categories')->select('*'), $measurement['trait_id'], ['id', 'export_name']);
        if (!$odbtrait->id) {
          $this->appendLog('WARNING: Trait_id for trait '.$odbtrait->id.' not found, this measurement will be ignored.');
          return false;
        }
        $measurement['trait_id'] = $odbtrait->id;
        if ($odbtrait->type==ODBTrait::LINK && !array_key_exists('link_id',$measurement)) {
          $this->appendLog('WARNING: Link_id required for trait '.$odbtrait->id.' key not found, this measurement will be ignored.');
          return false;
        }
        if ($odbtrait->type != ODBTrait::LINK and !array_key_exists('value',$measurement)) {
          $this->appendLog('WARNING: There is no value field to import'.serialize($measurement));
          return false;
        }
        if (!$this->validateValue($odbtrait,$measurement)) {
          $this->appendLog('WARNING: Value for trait '.$odbtrait->id.' is invalid, this measurement will be ignored.'.serialize($measurement));
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

    public function extractDate($measurement)
    {
      //validate date
      $date = isset($measurement['date']) ? $measurement['date'] : (isset($this->header['date']) ? $this->header['date'] : null);
      if (null == $date) {
        $year = isset($measurement['date_year']) ? $measurement['date_year'] : (isset( $measurement['year']) ? $measurement['year'] : null);
        $month = isset($measurement['date_month']) ? $measurement['date_month'] : (isset( $measurement['month']) ? $measurement['month'] : null);
        $day = isset($measurement['date_day'])  ? $measurement['date_day'] : (isset( $measurement['day']) ? $measurement['day'] : null);
        return ['month' => $month,'day' => $day,'year' =>$year];
      }
      if (is_string($date)) {
        if (preg_match("/\//",$date)) {
            $date = explode("/",$date);
            return ['month' => $date[1],'day' => $date[2],'year' =>$date[0]];
        } elseif (preg_match("/-/",$date)) {
            $date = explode("-",$date);
            return ['month' => $date[1],'day' => $date[2],'year' =>$date[0]];
        }
      }
      if (get_class($date)==="DateTime") {
           $year = $date->format('Y');
           $day = $date->format('d');
           $month = $date->format('m');
           return ['month' => $month,'day' => $day,'year' =>$year];
      }
      return $date;
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
        $date = $this->extractDate($measurements);
        if (!Measurement::checkDate($date)) {
            $this->skipEntry($date, Lang::get('messages.invalid_date_error'));
        } else {
            $measurement->setDate($date);
        }
        //prevent duplications unless specified
        $allowDuplication = array_key_exists('duplicated', $measurements) ? $measurements['duplicated'] : 0;
        if (!$this->checkDuplicateMeasurement($measurement,$measurements) && $allowDuplication==0) {
          $this->skipEntry($measurements, "Duplicated measurement. To allow duplicated values for the same date and object include a 'duplicated' with 1 value in your record");
        } else {
          /*if categorical must save beforehand to be able to save Categories */
          if (in_array($measurement->type, [ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL])) {
                $measurement->save();
                $measurement->setValueActualAttribute($measurements['value']);
          } else {
              if (ODBTrait::LINK == $measurement->type) {
                $measurement->value = ($measurement['value'] != null) ? $measurements['value'] : null;
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
            $taxon_id =  $voucher->identification->taxon_id;
            $location_id = $voucher->locations->last()->location_id;
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
        $target = 'measurements';
        Summary::updateSummaryMeasurementsCounts($newvalues,$value="value + 1");
        /* END SUMMARY COUNT UPDATE */




        }
        return;
    }
}
