<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Models\Location;
use App\Models\ODBFunctions;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;
use JsonMachine\JsonMachine;

use Storage;
use DB;
use File;
use Illuminate\Support\Arr;

class ImportLocations extends AppJob
{
    /**
     * Execute the job.
     */
    public function inner_handle()
    {

        $data = $this->extractEntrys();

        $hasfile = $this->userjob->data['data'];
        $geojsonFile = false;
        $parentIgnore = false;
        /* if a file has been uploaded */
        if (isset($hasfile['filename'])) {
          $filename = $hasfile['filename'];
          $filetype = $hasfile['filetype'];
          $parentIgnore = $hasfile['parent_options'] == 'ignore' ? true : false;

          $path = storage_path('app/public/tmp/'.$filename);

          /*if the file is a geojson collection extract data */
          if (mb_strtolower($filetype) == 'geojson') {
            $geojsonFile = true;
            try {
               /* this will run outof memory for large files
                $json = json_decode(file_get_contents($path),true);
                JsonMachine does not permit total counts however
               */
               $data = JsonMachine::fromFile($path,'/features');
            } catch (\Exception $e) {
               $this->setError();
               $this->appendLog('Exception '.$e->getMessage());
               return;
            }
            /* this is faking the progress status
             because JsonMachine does not allow total counts */
            $howmany = 100;
            $this->userjob->setProgressMax($howmany);
          } else {
            /* this will be a lazy collection to minimize memory issues*/
            $howmany = SimpleExcelReader::create($path,$filetype)->getRows()->count();
            $this->userjob->setProgressMax($howmany);
            /* I have to do twice, not understanding why loose the collection if I just count on it */
            $data = SimpleExcelReader::create($path,$filetype)->getRows();
          }
        } else {
          /* this has recieved a json */
          if (!$this->setProgressMax($data)) {
              return;
          }
        }

        /* first validate and save to import if valid
        * save by adm_level and the import from there
        * will minimize errors by ordering the data by admin level */
        $filesSaved = [];
        $writers = [];
        $jobId = $this->userjob->id;

        /* double time */
        $howmany = ($this->userjob->progress_max)*2;
        $this->userjob->setProgressMax($howmany);
        $counter = 1;
        foreach ($data as $location) {
             if (isset($location['geojson'])) {
               $location =  self::parseGeoJsonFeature($location['geojson']);
             }
             if ($geojsonFile) {
               $location = self::parseGeoJsonFeature($location);
               if ($counter<100) {
                 $this->userjob->tickProgress();
               }
             } else {
               $this->userjob->tickProgress();
             }

            if ( null === $location) {
              continue;
            }

            if ($this->isCancelled()) {
                break;
            }

            $admLevel = $location['adm_level'];
            $filename = "job-".$jobId."_locationImport_admLevel-".$admLevel.".csv";
            $path = 'app/public/downloads/'.$filename;
            $key = (string) $admLevel;
            if (!isset($writers[$key])) {
              $writer = SimpleExcelWriter::create(storage_path($path));
              $writers[$key] = $writer;
            } else {
              $writer = $writers[$key];
            }
            if ($parentIgnore) {
              $location['parentIgnore'] = 1;
            }

            $writer->addRow($location);

            $filesSaved[$filename] = $admLevel;
            $counter++;
        }
        $filesSaved = array_unique($filesSaved);
        // sort files by adm_level
        $filesSaved = Arr::sort($filesSaved);
        //restart counter
        $this->userjob->setProgressMax($counter);
        // import records
        foreach ($filesSaved as $filename => $admLevel) {
            $path = storage_path('app/public/downloads/'.$filename);
            $data = SimpleExcelReader::create($path,'csv')->getRows();
            foreach($data as $location) {

                if ($this->validateData($location)) {
                  try {
                    $this->import($location);
                  } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on location '.$location['name']);
                  }
                }
                $this->userjob->tickProgress();
            }
            if ($this->isCancelled()) {
               break;
            }
            File::delete($path);
        }


    }

    protected function validateData(&$location)
    {
        if (!$this->hasRequiredKeys(['name', 'adm_level'], $location)) {
            return false;
        }
        if (!$this->validateAdmLevel($location)) {
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


    public function validateAdmLevel(&$location)
    {
      $admLevel = (int) $location['adm_level'];
      $validLevels = array_merge(
        (array) config('app.adm_levels'),
        Location::LEVEL_SPECIAL);
      if (!in_array($admLevel,$validLevels)) {
        $locationLog = $location;
        $locationLog['geom'] = substr($locationLog['geom'],0,20);
        $this->skipEntry($locationLog,'adm_level '.$admLevel.' not a valid one');
        return false;
      }
      return true;
    }

    public function geomTypeFromGeom($geom)
    {
      $strGeom = mb_strtolower(substr($geom, 0, 30));
      if ('point' == substr($strGeom, 0, 5)) {
        return Location::GEOM_POINT;
      }
      if ('polygon' == substr($strGeom, 0, 7)) {
        return Location::GEOM_POLYGON;
      }
      if ('multipolygon' == substr($strGeom, 0, 12)) {
        return Location::GEOM_MULTIPOLYGON;
      }
      return null;
    }

    protected function validateGeom(&$location)
    {
        $locationLog = $location;
        if (isset($location['geom'])) {
          $locationLog['geom'] = substr($locationLog['geom'],0,20);
          $geom = $location['geom'];
          $geomType = self::geomTypeFromGeom($location['geom']);
          if
            (
              (
                Location::LEVEL_PLOT == $location['adm_level']
                and
                $geomType==Location::GEOM_POINT
              )
              or
              Location::LEVEL_POINT == $location['adm_level']
            )
          {
          // we check if this exact geometry is already registered
          $alreadyPresent= Location::noWorld()->whereRaw("geom LIKE ST_GeomFromText('$geom')")->count();
          if ($alreadyPresent>0) {
            $this->skipEntry($locationLog,'ERRO: '.Lang::get('messages.geom_duplicate'));
            return false;
          }
          $validGeom = DB::select("SELECT ST_GeomFromText('".$geom."') as valid");
          if ($validGeom == null) {
            $this->skipEntry($locationLog,'ERRO: '.Lang::get('messages.geometry_invalid'));
            return false;
          }
          return true;
        } else {
          /* validate polygon geometries*/
          $valid = DB::select('SELECT ST_Dimension(ST_GeomFromText(?)) as valid', [$location['geom']]);
          if (null == $valid[0]->valid) {
            $this->skipEntry($locationLog,'ERRO: '.Lang::get('messages.geometry_invalid'));
            return false;
          }
          return true;
        }
      }
      /* else we expect lat and long attributes for location */
      $lat = isset($location['lat']) ? $location['lat'] : (isset($location['latitude']) ? $location['latitude'] : null);
      $long = isset($location['long']) ? $location['long'] : (isset($location['longitude']) ? $location['longitude'] : null);
      if (is_null($lat) or is_null($long)) {
        $locationLog = $location;
        $locationLog['geom'] = substr($locationLog['geom'],0,20);
        $this->skipEntry($locationLog, "Coordinates for location $name not available");
        return false;
      }
      $location['geom'] = "POINT($long $lat)";
      return true;
    }

    protected function validateParent(&$location)
    {
        if ($this->validateRelatedLocation($location, 'parent')) {
            return true;
        }

        return false;
    }

    protected function validateUC(&$location)
    {

        if ($this->validateRelatedLocation($location, 'uc')) {
            return true;
        } else {
            $locationLog = $location;
            $locationLog['geom'] = substr($locationLog['geom'],0,20);
            $this->skipEntry($locationLog, 'Conservation unit for location '.$location['name'].' is listed as '.$location['uc'].', but this was not found in the database or is not a valid UC for the location.');
            return false;
        }
    }

    protected function validateRelatedLocation(&$location, $field)
    {
         /* do not check uc if location is country */
         if ($field == "uc" and $location['adm_level'] == config('app.adm_levels')[0]) {
           $location[$field] = null;
           return true;
         }

         if ($field == "parent" and $location['adm_level'] == config('app.adm_levels')[0]) {
           $location[$field] = Location::world()->id;
           return true;
         }

         if (!isset($location[$field])) {
           $location[$field] = null;
         }
         $hasRelated = (null != $location[$field]) ? $location[$field] : null;


         /* detect parent by geometry */
         $guessedParent = $this->guessParent($location['geom'], $location['adm_level'], 'uc' === $field);

         $parentIgnore = isset($location['parentIgnore']) ? (int) $location['parentIgnore'] : 0;

         /* if not found and not informed failed if got here unless filed is uc or parentIgnore has been selected*/
         if (null == $hasRelated and  null== $guessedParent) {
           if ($field != 'uc' and $parentIgnore === 0) {
             $this->skipEntry($location,"ERROR: Location $field was not detected nor you informed. You may inform <code>".$field."=0</code> to import this record without an $field assignment.");
             return false;
           }
           $location[$field] = null;
           return true;
        }
        //if empty but found a parent, get it
        if (null == $hasRelated  and null != $guessedParent) {
            $location[$field] = $guessedParent;
            return true;
        }

        // forces null if this was explicitly passed as zero
        if (0 === $hasRelated or $parentIgnore===0) {
          $location[$field] = null;
          return true;
        }

        /* uc or parent must have either a numeric or string value */
        if (((int) $hasRelated) >0) {
           $valid = Location::where('id',$hasRelated);
        } else {
          if ($field != "uc") {
            $parlevel = ($location['adm_level']-1);
            $parlevel = ($parlevel<(-1)) ? -1 : $parlevel;
          } else {
            $parlevel = Location::LEVEL_UC;
          }
          $valid = Location::where('name','like',$hasRelated)->where('adm_level',$parlevel);
        }
        $forceParent = isset($location['force_parent']) ? ((int) $location['force_parent'])>0 : false;
        if ($valid->count()==1) {
          //compare informed with detected parent
          $valid = $valid->first();
          /* accept informed different if user informed force_parent */
          /* this has been added because a children location sharing a border with its parent location may fail to detect parent
          */
          // TODO:  force_parent may be a bad idea to have it allowed
          if ($guessedParent == $valid->id or $forceParent) {
            $location[$field] = $valid->id;
            return true ;
          }

          if (null != $guessedParent) {
              $valid = Location::findOrFail($guessedParent);
              $parentName = $valid->name;
          } else {
              $parentName = "NO PARENT FOUND";
          }
          $this->appendLog('ERROR: Location '.$location['name'].' informed parent '.$hasparent." is different from detected spatial parent ".$parentName);
        } else {
          $this->appendLog('ERROR: Related location <strong>'.$hasRelated.'</strong> informed for Location '.$location['name'].' was not found');
        }
        return false;
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

        $altitude = null;
        if (isset($location['altitude']) and !empty($location['altitude'])) {
          $altitude = $location['altitude'];
        }

        $datum = null;
        if (isset($location['datum']) and !empty($location['datum'])) {
          $datum = $location['datum'];
        }

        $notes = null;
        if (isset($location['notes']) and !empty($location['notes'])) {
          $notes = $location['notes'];
        }

        $startx = null;
        if (isset($location['startx']) and !empty($location['startx'])) {
          $startx = $location['startx'];
        }

        $starty = null;
        if (isset($location['starty']) and !empty($location['starty'])) {
          $starty = $location['starty'];
        }
        $x = null;
        if (isset($location['x']) and !empty($location['x'])) {
          $x = $location['x'];
        }

        $y = null;
        if (isset($location['y']) and !empty($location['y'])) {
          $y = $location['y'];
        }

        $geojson = null;
        if (isset($location['geojson']) and !empty($location['geojson'])) {
          $geojson = $location['geojson'];
        }

        // TODO: several other validation checks
        // Is this location already imported?
        if ($parent) {
          $sameName = Location::where('name', 'like', $name)->where('parent_id', '=', $parent)->count() ;
          if ($sameName> 0) {
            $locationLog = $location;
            $locationLog['geom'] = substr($locationLog['geom'],0,20);
            $this->skipEntry($locationLog, 'location '.$name.' already exists in database at same parent location. Must be unique within parent');
            return;
          }
        }
        //this is important to prevent duplicated values
        $smallGeom = substr($geom, 0,1000);
        $sameGeometry = Location::whereRaw("geom=ST_GeomFromText('$geom')")->count();
        if ($sameGeometry>0) {
          $locationLog = $location;
          $locationLog['geom'] = substr($locationLog['geom'],0,20);
          $this->skipEntry($locationLog, 'location '.$name.' identical geometry already exists in database');
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
            'geojson' => $geojson,
        ]);
        $location->geom = $geom;
        $location->save();

        $this->affectedId($location->id);

        return;
    }


    /*
      *
      @jsonFeature is a Single Feature of a FeatureCollection
      return an array with keys for data import
    */
    public function parseGeoJsonFeature($jsonFeature)
    {

      $jsonType = isset($jsonFeature['type']) ? mb_strtolower($jsonFeature['type']) : null;
      $errors = [];
      if (!$jsonType=='feature') {
        $this->skipEntry(
            $jsonFeature,
            "ERROR: geojson needs to contain a Feature"
          );
        return null;
      }
      /* which geometries are valid for this ODB instalation? */
      $geomValidTypes = collect(Location::VALID_GEOMETRIES)
          ->map(function($geom) {
            return mb_strtolower($geom);
          })->toArray();

      $geomType = mb_strtolower($jsonFeature['geometry']['type']);
      if (!in_array($geomType,$geomValidTypes)) {
          $this->skipEntry($jsonFeature,
              " Invalid geometry".$geomType);
          return null;
      }

      $featureGeometry = $jsonFeature['geometry']['coordinates'];
      if ($geomType == mb_strtolower(Location::GEOM_MULTIPOLYGON)) {
        $featureGeometry = $featureGeometry[0];
      }

      /* if MultiPolygon but with different type attribute*/
      if (count($featureGeometry)>1
          and $geomType != mb_strtolower(Location::GEOM_MULTIPOLYGON)
      ) {
        $this->skipEntry($jsonFeature," Invalid geometry ".$geomType." should be ".Location::GEOM_MULTIPOLYGON);
        return null;
      }
      /* if single geometry, then not a MultiPolygon */
      if (count($featureGeometry)==1
          and $geomType == mb_strtolower(Location::GEOM_MULTIPOLYGON))
      {
          /* only one polygon */
          $geomType = mb_strtolower(Location::GEOM_POLYGON);
      }

      /* create WKT geometry for odb import */
      /* if has multiple polygons */
      if (count($featureGeometry) >1) {
        $wktGeom = [];
        foreach($featureGeometry as $polygon) {
            if (count($polygon)==1) {
              $polygon = $polygon[0];
            }
            $wktPolygon = [];
            foreach($polygon as $coordinates) {
              $wktPolygon[] =  implode(" ",$coordinates);
            }
            $wktPolygon = "((".implode(", ",$wktPolygon)."))";
            $wktGeom[] = $wktPolygon;
        }
        $wktGeom = "(".implode(",",$wktGeom).")";
        $wktGeom = mb_strtoupper(Location::GEOM_MULTIPOLYGON).$wktGeom;
      }

      if ($geomType == mb_strtolower(Location::GEOM_POINT)) {
        $wktGeom = count($featureGeometry[0])==2 ?  $featureGeometry[0] : $featureGeometry[0][0];
        if (count($wktGeom)==2) {
          $wktGeom = "(".implode(",",$wktGeom).")";
          $wktGeom = mb_strtoupper(Location::GEOM_POINT).$wktGeom;
        }
      }

      if ($geomType == mb_strtolower(Location::GEOM_POLYGON)) {
        $polygon = count($featureGeometry[0])==1 ? $featureGeometry[0][0] : $featureGeometry[0];
        $wktPolygon = [];
        foreach($polygon as $coordinates) {
          $wktPolygon[] =  implode(" ",$coordinates);
        }
        $wktGeom = mb_strtoupper(Location::GEOM_POLYGON);
        $wktGeom .= "((".implode(", ",$wktPolygon)."))";
      }

      /* get attributes if found */
      $properties = isset($jsonFeature['properties']) ? $jsonFeature['properties'] : null;
      if (null == $properties) {
        $this->skipEntry(
          $jsonFeature,
          "No properties attributes found for this geojson feature");
        return null;
      }
      $name = isset($properties['local_name']) ? $properties['local_name'] : (isset($properties['name']) ? $properties['name'] : null);
      $admLevel = isset($properties['adm_level']) ? $properties['adm_level'] : (isset($properties['admin_level']) ? $properties['admin_level'] : null);

      if (!isset($wktGeom)) {
        $this->skipEntry($jsonFeature,"There seem to be no geometry in this feature");
        return null;
      }
      $admLevel = (int) $admLevel;
      $parent = isset($properties['parent']) ? (int) $parent : 0;

      /* if admLevel is the first configured, then link to World */
      if ($admLevel <= config('app.adm_levels')[0]) {
        $parent = Location::world()->id;
        $force_parent = 1;
      }
      $altitude = isset($properties['altitude']) ? $properties['altitude'] : null;
      $x = isset($properties['x']) ? $properties['x'] : null;
      $y = isset($properties['y']) ? $properties['y'] : null;
      $startx = isset($properties['startx']) ? $properties['starty'] : null;
      $starty = isset($properties['notes']) ? $properties['notes'] : null;
      $featureRecord = [
        'name' => $name,
        'adm_level' => $admLevel,
        'geom' => $wktGeom,
        'parent' => $parent>0 ? $parent : null,
        'x' => $x,
        'y' => $y,
        'startx' => $startx,
        'starty' => $starty,
        'geojson' => json_encode($jsonFeature),
      ];
      return $featureRecord;
    }


}
