<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Models\IndividualLocation;
use App\Models\Location;
use App\Models\Individual;
use App\Models\ODBFunctions;
use Illuminate\Http\Request;

use Spatie\SimpleExcel\SimpleExcelReader;
use Storage;


class ImportIndividualLocations extends ImportCollectable
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
      foreach ($data as $individual_location) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if ($this->validateData($individual_location)) {
                try {
                    $this->import($individual_location);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine());
                }
            }
        }
    }

    protected function validateIndividual(&$entry)
    {
      $individual = isset($entry['individual_id']) ? $entry['individual_id'] : $entry['individual'];
      if ($individual!=null) {
          if (((int) $individual) != null) {
            $valid = Individual::findOrFail($individual);
          } else {
            $valid = Individual::whereRaw('odb_ind_fullname(individuals.id,individuals.tag) LIKE "'.$individual.'"')->get();
          }
          if ($valid->count()==1) {
            $validindividual = $valid->first();
            $entry['individual_id'] = $validindividual->id;
            if (!$this->authorize('update', $validindividual)) {
              $this->appendLog('ERROR: You do not have permissions to add locations to individual'.$validindividual->fullname);
              return false;
            }
            return true;
          } else {
            $this->appendLog('ERROR: individual '.$individual." not valid";
            return false;
          }
      }
      $this->appendLog('ERROR: individual MUST be informed';
      return false;
    }



    protected function validateData(&$individual_location)
    {
        //dataset may have been informed, will fail only if informed not FOUND
        //else will place individual in its own default dataset
        if (!$this->validateIndividual($individual_location)) {
            return false;
        }
        $newentry = $this->extractLocationFields($individual_location);
        if (!$this->validateLocations($newentry)) {
            return false;
        }
        $individual_location = $newentry;
        return true;
    }

    public function extractLocationFields($entry)
    {
      $possible_keys = ['location','longitude','latitude','notes','date_time','altitude','x','y','distance','angle'];
      $record =  [
      'location' => isset($entry['location_id']) ? $entry['location_id'] : (isset($entry['location']) ? $entry['location'] : null),
      'latitude' => isset($entry['latitude']) ? (float) $entry['latitude'] : null,
      'longitude' => isset($entry['longitude']) ? (float) $entry['longitude'] : null,
      'altitude' => isset($entry['altitude']) ? (float) $entry['altitude'] : (isset($registry['altitude']) ? (float) $registry['altitude'] : null),
      'notes' => isset($entry['notes']) ? $entry['notes'] : (isset($registry['location_notes']) ? $registry['location_notes'] : null),
      'date_time' => isset($entry['date_time']) ? $entry['date_time'] : (isset($registry['location_date_time']) ? $registry['location_date_time'] : null),
      'x' => isset($entry['x']) ? (float) $entry['x'] : (isset($registry['x']) ? (float) $registry['x'] : null),
      'y' => isset($entry['y']) ? (float) $entry['y'] : (isset($registry['y']) ? (float) $registry['y'] : null),
      'distance' => isset($entry['distance']) ? (float) $entry['distance'] : (isset($registry['distance']) ? (float) $registry['distance'] : null),
      'angle' => isset($entry['angle']) ? (float) $entry['angle'] : (isset($registry['angle']) ? (float) $registry['angle'] : null),
      ];
      $record = array_filter($record,function($r){ $r!=null;});
      $newentry[];
      $newentry['individual_id'] = $entry['individual_id'];
      $newentry['location'] = $record;
      return $newentry;
    }



    public function import($entry)
    {
        $location = $entry['individual_locations'][0];
        $location['individual_id'] = $entry['individual_id'];
        $individual = Individual::findOrFail($entry['individual_id']);

        $newindlocation = new Request;
        $newindlocation->merge($location);
        //store the record, which will result in the individual
        $savedindlocation = app('App\Http\Controllers\IndividualController')->saveIndividualLocation($newindlocation);
        if ($savedindlocation->getData()->errors == 1) {
          $this->append('ERROR: location '.json_encode($location).' could not be saved for individual '.$individual->fullname.'  Errors:'.$savedindlocation->getData()->saved);
        }
        $this->affectedId($individual->id);

        return;
    }
}
