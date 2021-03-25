<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Individual;
use App\Location;
use App\Project;
use App\ODBFunctions;
use Illuminate\Http\Request;

use Spatie\SimpleExcel\SimpleExcelReader;
use Storage;


class ImportIndividuals extends ImportCollectable
{
    private $requiredKeys;

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
        /* this has recieved a json */
        if (!$this->setProgressMax($data)) {
            return;
        }
      }

      //if these fields are provided in header, remove from there
      $this->requiredKeys = $this->removeHeaderSuppliedKeys(['tag','collector','date']);
      //if collector and/or project  are supplied in header, they will be validated here
      $this->validateHeader('collector');

      foreach ($data as $individual) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if ($this->validateData($individual)) {
                try {
                    $this->import($individual);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on individual '.$individual['tag']);
                }
            }
        }
    }

    protected function validateData(&$individual)
    {
        if (!$this->hasRequiredKeys($this->requiredKeys, $individual)) {
            return false;
        }
        //project must be informed
        if (!$this->validateProject($individual)) {
            return false;
        }

        //collectors (at least a valid one, must be informed)
        $collectors = $this->extractCollectors('Individual', $individual, 'collector');
        if (count($collectors)==0) {
          return false;
        }
        $individual['collector'] = $collectors;

        //validate date
        $date = isset($individual['date']) ? $individual['date'] : (isset($this->header['date']) ? $this->header['date'] : null);
        if (null == $date) {
          $year = isset($individual['date_year']) ? $individual['date_year'] : (isset( $individual['year']) ? $individual['year'] : null);
          $month = isset($individual['date_month']) ? $individual['date_month'] : (isset( $individual['month']) ? $individual['month'] : null);
          $day = isset($individual['date_day']) ? $individual['date_day'] : (isset( $individual['day']) ? $individual['day'] : null);
          $date = [$month,$day,$year];
        }
        if (is_string($date)) {
          if (preg_match("/\//",$date)) {
              $date = explode("/",$date);
              $date = [$date[1],$date[2],$date[0]];
          } elseif (preg_match("/-/",$date)) {
              $date = explode("-",$date);
              $date = [$date[1],$date[2],$date[0]];
          }
        } elseif (!is_array($date)) {
          if (get_class($date)==="DateTime") {
             $year = $date->format('Y');
             $day = $date->format('d');
             $month = $date->format('m');
             $date = [$month,$day,$year];
          }
        }
        if (!(Individual::checkDate($date))) {
          $this->skipEntry($individual,'Informed date is invalid!');
          return false;
        }
        $individual['date'] = $date;

        $identification = null;
        $taxon = isset($individual['taxon_id']) ? $individual['taxon_id'] : (isset($individual['taxon']) ? $individual['taxon'] : null);
        if (!empty($taxon) and $taxon != "" ) {
          $identification = $this->extractIdentification($individual);
          if (null == $identification) {
            $this->skipEntry($individual,'Problem in the IDENTIFICATION of this individual');
            return false;
          }
          $individual['identification'] = $identification;
        }

        /* alternatively, the identification is from another individual */
        $hasidother = isset($individual['identification_individual']) ? ("" == $individual['identification_individual'] ? null : $individual['identification_individual']) : null;
        if (null != $hasidother and null == $identification) {
          $individual['identification_individual_id'] = null;
          $hasIndividual = Individual::where('id',$individual['identification_individual'])->orWhereRaw('odb_ind_fullname(individuals.id,individuals.tag) like "'.$individual['identification_individual'].'"');
          if ($hasIndividual->count()==1) {
            $individual['identification_individual_id'] = $hasIndividual->get()->first()->id;
          } else {
            $this->skipEntry($individual,'Problem in the identification_individual value for which a single match was notfound in the database');
            return false;
          }
        } elseif (null != $hasidother) {
          $this->appendLog(' WARNING: identification_individual value was informed but also a self identification. Therefore, self identification was used.');
        }

        if (!$this->extractBioCollection($individual)) {
            $this->skipEntry($individual,'You informed a biocollection for this individual, but the info is invalid');
            return false;
        }

        //last validation is location as it will save a new location if coordinates informed
        if (!$this->validateLocations($individual)) {
            return false;
        }


        return true;
    }

    public function import($individual)
    {
        /*for creating we need the first location if more than one present */
        $firstlocation = $individual['individual_locations'][0];

        /*create a store request */
        /*fields have already been validate */
        $store_request = [
          'from_the_api' => 1,  /* this to prevents the controller from redirecting */
          'project_id' => $individual['project'],
          'tag' => (string) $individual['tag'],
          'notes' => array_key_exists('notes', $individual) ? $individual['notes'] : null,
          'date' => $individual['date'],
          'collector' => $individual['collector'],
          'identification_individual_id' => array_key_exists('identification_individual_id', $individual) ? $individual['identification_individual_id'] : null,
          'location_id' => $firstlocation['location_id'],
          'altitude' => array_key_exists('altitude', $firstlocation) ? $firstlocation['altitude'] : null,
          'location_notes' =>  array_key_exists('notes', $firstlocation) ? $firstlocation['notes'] : null,
          'location_date_time' => array_key_exists('date_time', $firstlocation) ? $firstlocation['date_time'] : null,
          'angle' => array_key_exists('angle', $firstlocation) ? $firstlocation['angle'] : null,
          'x' => array_key_exists('x', $firstlocation) ? $firstlocation['x'] : null,
          'y' => array_key_exists('y', $firstlocation) ? $firstlocation['y'] : null,
          'distance' => array_key_exists('distance', $firstlocation) ? $firstlocation['distance'] : null,
        ];
        if (isset($individual['identification'])) {
            $store_request['taxon_id'] = $individual['identification']['taxon_id'];
            $store_request['identifier_id'] = $individual['identification']['person_id'];
            $store_request['modifier'] = $individual['identification']['modifier'];
            $store_request['biocollection_id'] = $individual['identification']['biocollection_id'];
            $store_request['biocollection_reference'] = $individual['identification']['biocollection_reference'];
            $store_request['identification_notes'] = $individual['identification']['notes'];
            $store_request['identification_date'] = $individual['identification']['date'];
        }
        //transform info in a request
        $saverequest = new Request;
        $saverequest->merge($store_request);
        //store the record, which will result in the individual
        $savedindividual = app('App\Http\Controllers\IndividualController')->store($saverequest);

        //if this is true, errors where find
        if (is_string($savedindividual)) {
          $this->appendLog("FAILED:  individual with tag #".$store_request['tag']." could not be imported: ".$savedindividual);
          return false;
        }

        //create voucher if informed with array created during validation
        if (isset($individual['biocollections'])) {            
            foreach ($individual['biocollections'] as $voucher) {
              $voucher['individual_id'] = $savedindividual->id;
              $voucher['project_id'] = $savedindividual->project_id;
              $voucher['from_the_api'] = 1;
              //transform info in a request
              $newvoucher = new Request;
              $newvoucher->merge($voucher);
              //store the record, which will result in the individual
              $savedvoucher = app('App\Http\Controllers\VoucherController')->store($newvoucher);
              if (is_string($savedvoucher)) {
                $this->appendLog('WARNING: voucher '.json_encode($voucher).' could not be saved for individual '.$savedindividual->fullname.' Errors:'.json_encode($savedvoucher));
              }
            }
        }

        //create locations if more than one with array created during validation
        $nlocs = count($individual['individual_locations']);
        if ($nlocs>1) {
          for($i=1;$i<$nlocs;$i++) {
              $location = $individual['individual_locations'][$i];
              $location['individual_id'] = $savedindividual->id;

              $newindlocation = new Request;
              $newindlocation->merge($location);
              //store the record, which will result in the individual
              $savedindlocation = app('App\Http\Controllers\IndividualController')->saveIndividualLocation($newindlocation);
              if ($savedindlocation->getData()->errors == 1) {
                $this->append('WARNING: additional location '.json_encode($location).' could not be saved for individual '.$savedindividual->fullname.'  Errors:'.$savedindlocation->getData()->saved);
              }
          }
        }

        //$created_at = array_key_exists('created_at', $individual) ? $individual['created_at'] : null;
        //$updated_at = array_key_exists('updated_at', $individual) ? $individual['updated_at'] : null;
        // - Then create the related registries (for identification and collector), if requested
        $this->affectedId($savedindividual->id);

        return;
    }
}
