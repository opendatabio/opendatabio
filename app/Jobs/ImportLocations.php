<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Models\Location;
use App\Models\ODBFunctions;
use Spatie\SimpleExcel\SimpleExcelReader;
use Storage;


class ImportLocations extends AppJob
{
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
          $howmany = SimpleExcelReader::create($path,$filetype)->getRows()->count();
          $this->userjob->setProgressMax($howmany);
          /* I have to do twice, not understanding why loose the collection if I just count on it */
          $data = SimpleExcelReader::create($path,$filetype)->getRows();
        } else {
          /* this has recieved a json */
          if (!$this->setProgressMax($data)) {
              return;
          }
        }
        //return false;
        foreach ($data as $location) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if ($this->validateData($location)) {
                // Arrived here: let's import it!!
                try {
                    $this->import($location);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on location '.$location['name']);
                }
            }
        }


    }

    protected function validateData(&$location)
    {
        if (!$this->hasRequiredKeys(['name', 'adm_level'], $location)) {
            return false;
        }
        if (!$this->validateGeom($location)) {
            return false;
        }
        if (!$this->validateParent($location)) {
            return false;
        }
        if (!$this->validateUC($location)) {
            return false;
        }

        return true;
    }

    protected function validateGeom(&$location)
    {
        if (array_key_exists('geom', $location)) {
            return true;
        }
        $lat = array_key_exists('lat', $location) ? $location['lat'] : null;
        $long = array_key_exists('long', $location) ? $location['long'] : null;
        if (is_null($lat) or is_null($long)) {
            $this->skipEntry($location, "Position for location $name not available");

            return false;
        }
        $location['geom'] = "POINT ($long $lat)";

        return true;
    }

    protected function validateParent(&$location)
    {
        if ($this->validateRelatedLocation($location, 'parent')) {
            return true;
        } else {
            $this->skipEntry($location, 'Parent for location '.$location['name'].' is listed as '.$location['parent'].', but this was not found in the database, or locations does not fall within it');
            return false;
        }
    }

    protected function validateUC(&$location)
    {
        if ($this->validateRelatedLocation($location, 'uc')) {
            return true;
        } else {
            $this->skipEntry($location, 'Conservation unit for location '.$location['name'].' is listed as '.$location['uc'].', but this was not found in the database or is not a valid UC for the location.');
            return false;
        }
    }

    protected function validateRelatedLocation(&$location, $field)
    {
        $guessedParent = $this->guessParent($location['geom'], $location['adm_level'], 'uc' === $field);
        if (!array_key_exists($field, $location)) {
            $location[$field] = $guessedParent;
            return true;
        } else { //If this is given, we need validate it
            if (0 === $location[$field]) {  // forces null if this was explicitly passed as zero
                $location[$field] = null;
                return true;
            } else {
                $hasparent = $location[$field];
                if (((int) $hasparent) >0) {
                  $valid = Location::where('id',$hasparent);
                } else {
                    if ($field != "uc") {
                      $parlevel = ($location['adm_level']-1);
                      $parlevel = ($parlevel<(-1)) ? -1 : $parlevel;
                    } else {
                      $parlevel = Location::LEVEL_UC;
                    }
                    $valid = Location::where('name','like',$location[$field])->where('adm_level',$parlevel);
                }
                if ($valid->count()==1) {
                  //use guessed if different
                  $valid = $valid->first();
                  if ($guessedParent == $valid->id) {
                    $location[$field] = $valid->id;
                    return true ;
                  } else {
                    $valid = Location::findOrFail($guessedParent);
                    $this->appendLog('FAILED: Location '.$location['name'].' informed parent '.$hasparent." is different from actual spatial parent ".$valid->name);
                  }
                }
                return false;
            }
        }
    }

    protected function guessParent($geom, $adm_level, $parent_uc)
    {
        if (0 == $adm_level) {
            return $parent_uc ? null : Location::world()->id;
        } else { // Autoguess parent
            $parent = Location::detectParent($geom, $adm_level, $parent_uc);
            if ($parent) {
                return $parent->id;
            } else {
                return null;
            }
        }
    }

    public function import($location)
    {
        $name = $location['name'];
        $adm_level = $location['adm_level'];
        $geom = $location['geom'];
        $parent = $location['parent'];
        $uc = $location['uc'];
        $altitude = array_key_exists('altitude', $location) ? $location['altitude'] : null;
        $datum = array_key_exists('datum', $location) ? $location['datum'] : null;
        $notes = array_key_exists('notes', $location) ? $location['notes'] : null;
        $startx = array_key_exists('startx', $location) ? $location['startx'] : null;
        $starty = array_key_exists('starty', $location) ? $location['starty'] : null;
        $x = array_key_exists('x', $location) ? $location['x'] : null;
        $y = array_key_exists('y', $location) ? $location['y'] : null;


        //if (null == $parent) {
        //    $this->appendLog("FAILED: Parent:".$parent." for location ".$name);
        //    return false;
        //}
        // TODO: several other validation checks
        // Is this location already imported?
        if ($parent) {
            if (Location::where('name', '=', $name)->where('parent_id', '=', $parent)->count() > 0) {
                $this->skipEntry($location, 'location '.$name.' already exists in database at same parent location. Must be unique within parent');
                return;
            }
        }
        //this is important to prevent duplicated values
        if (Location::where('geom', '=', $geom)->count() > 0) {
          $this->skipEntry($location, 'location '.$name.' identical geometry already exists in database');
          return;
        }

        $location = new Location([
            'name' => $name,
            'adm_level' => $adm_level,
            'altitude' => $altitude,
            'datum' => $datum,
            'notes' => $notes,
            'startx' => $startx,
            'starty' => $starty,
            'x' => $x,
            'y' => $y,
            'parent_id' => $parent,
            'uc_id' => $uc,
        ]);
        $location->geom = $geom;
        $location->save();

        /*
        $parentnode = Location::where('id', '=', $parent)->first();
        $location->makeChildOf($parentnode);
        $location->save();
        */

        $this->affectedId($location->id);

        return;
    }
}
