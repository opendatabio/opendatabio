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
use Lang;
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
               if ($counter<50) {
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
        $this->userjob->progress_max = ($counter*2);
        $this->userjob->progress = $counter;
        $this->userjob->save();

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
        }
        foreach ($filesSaved as $filename) {
            $path = storage_path('app/public/downloads/'.$filename);
            File::delete($path);
        }
        //set progress to max if got here
        $this->userjob->progress = $this->userjob->progress_max;
        $this->userjob->save();
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
        if (!$this->adjustAdmLevel($location)) {
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

    public function adjustAdmLevel(&$location)
    {
      $adm_level = $location['adm_level'];
      $parent = $location['parent'];
      if (!is_null($parent)) {
        $parent = Location::withGeom()->findOrFail($parent);
        $p_adm_level = $parent->adm_level;
        if ($adm_level <= $p_adm_level and in_array($adm_level,config('app.adm_levels'))) {
          $msg = $location['name'].'  adm_level '.$adm_level.' is equal or smaller than its parent '.$parent->name.' adm_level '.$p_adm_level;
          $new_level = $adm_level+1;
          if (!in_array($new_level,config('app.adm_levels'))) {
            $msg .= "The new location CANNOT BE registered as adm_level ".$new_level." because this is not set in the config. Ask the administrator to add an additional adm_level to config/app!";
            $locationLog = $location;
            $locationLog['geom'] = substr($locationLog['geom'],0,20);
            $this->skipEntry($locationLog,$msg);
            return false;
          }
          $location['adm_level'] = $new_level;
          $msg .= "The new location was therefore registered as adm_level ".$new_level;
          $this->appendLog($msg);
        }
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
        /* if not set, then must be a point */
        if (!isset($location['geom'])) {
          /* and we expect lat and long attributes for location */
          $lat = isset($location['lat']) ? $location['lat'] : (isset($location['latitude']) ? $location['latitude'] : null);
          $long = isset($location['long']) ? $location['long'] : (isset($location['longitude']) ? $location['longitude'] : null);
          if (is_null($lat) or is_null($long)) {
            $this->skipEntry($location, "Coordinates for location $name not available");
            return false;
          }
          $location['geom'] = "POINT($long $lat)";
        }
        /* a shorter geometry for logging */
        $locationLog = $location;
        $locationLog['geom'] = substr($locationLog['geom'],0,50);
        $locationLog['geojson'] = null;

        $geom = $location['geom'];
        /* get the geometry type from the string submitted */
        $geomType = self::geomTypeFromGeom($location['geom']);
        if($geomType == null ) {
          $this->skipEntry($locationLog, "Invalid geometry type for location".$location['name']);
          return false;
        }

        //check if geometry is valid
        //Understand https://dev.mysql.com/doc/refman/8.0/en/geometry-well-formedness-validity.html
        //but mariadb does not have st_valid of mysql, so we can evaluate some of these issues only //
        /* 1. geometry conversion from text should pass is valid*/
        try {
          $validGeom = DB::statement("SELECT ST_GeomFromText('".$geom."')");
        } catch (\Exception $e) {
          $this->skipEntry($locationLog,Lang::get('messages.geometry_invalid'));
          return false;
        }
        /* 2 validate by calculating the area and the centroid if polygon */
        /* because these are included in the queries */
        if (in_array($geomType,['polygon','multipolygon'])) {
          /* this functions should work for these geometries otherwise is an error */
          /* area */
          try {
            $area  = DB::statement("SELECT ST_Area(ST_GeomFromText('".$geom."'))");
          } catch (\Exception $e) {
            $this->skipEntry($locationLog,"Could not calculate area of the informed polygon");
            return false;
          }
          /*centroid */
          try {
            $centroid  = DB::statement("SELECT ST_Centroid(ST_GeomFromText('".$geom."'))");
          } catch (\Exception $e) {
            $this->skipEntry($locationLog,"Could not calculate the centroid for the informed polygon");
            return false;
          }
        }

        // TODO: implemente validation for linestrings (transects)

        // finally check if this exact geometry is already registered
        $alreadyPresent= Location::noWorld()->whereRaw("ST_AsText(geom) LIKE '".$geom."'")->count();
        if ($alreadyPresent>0) {
            $this->skipEntry($locationLog,Lang::get('messages.geom_duplicate'));
            return false;
        }

        return true;


    }

    protected function validateParent(&$location)
    {
        $parent = isset($location['parent']) ? $location['parent'] : (isset($location['parent_id']) ? $location['parent_id'] : null );
        $location['parent'] = $parent;
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

         /* return World if location is county */
         if ($field == "parent" and $location['adm_level'] == config('app.adm_levels')[0]) {
           $location[$field] = Location::world()->id;
           return true;
         }

         /* if parent or uc are not defined, set them to null */
         if (!isset($location[$field])) {
           $location[$field] = null;
         }
         /*if not null, then an informed related exists */
         $hasRelated = (null != $location[$field]) ? $location[$field] : null;

         /* validate related if exists
         * uc or parent must have either a numeric or string value
         * if valid, then check whether it contains the geometry of the location
         */
         $informedRelated = null;
         if (null != $hasRelated) {
           /* if interger, then must be the id */
           if (((int) $hasRelated) >0) {
             $informedRelated = Location::where('id',$hasRelated);
           } else {
             $informedRelated = Location::where('name','like',$hasRelated)->withoutGeom();
           }
           if ($field != "uc") {
             $maxadmin = $location['adm_level'];
             if ($location['adm_level']==Location::LEVEL_PLOT) {
               $maxadmin = $maxadmin+1;
             }
             $informedRelated = $informedRelated->where('adm_level','<',$maxadmin);
           } else {
             $informedRelated = $informedRelated->where('adm_level',Location::LEVEL_UC);
           }
           if ($informedRelated->count()) {
               $informedRelated = $informedRelated->first();
               $parent_dim = !is_null($informedRelated->x) ? (($informedRelated->x >= $informedRelated->y) ? $informedRelated->x : $informedRelated->y) : null;
               if (!is_null($parent_dim)) {
                 /* add a buffer to parent point in the ~ size of its dimension if set */
                 $buffer_dd = (($parent_dim*0.00001)/1.11);
                 $query_buffer ="ST_Buffer(geom, ".$buffer_dd.")";
                 //do not simplify parent geometry
               } else {
                 /* else use config buffer */
                 if ($informedRelated->adm_level == config('app.adm_levels')[0]) {
                   //if country, allow bigger buffer
                   $simplify =0.001;
                   $buffer_dd = 0.2;
                   $query_buffer ="ST_Buffer(geom,".$buffer_dd.")";
                   //$query_buffer ="ST_Buffer(ST_Simplify(geom, ".$simplify."),".$buffer_dd.")";
                 } else {
                   //the config buffer
                   $simplify = config('app.location_parent_buffer');
                   $buffer_dd = config('app.location_parent_buffer');
                   $query_buffer ="ST_Buffer(geom, ".$buffer_dd.")";
                 }
               }
              //test without buffer nor simplification
              $query = "ST_Within(ST_GeomFromText('".$location['geom']."'),geom) as isparent";
              $isparent = Location::selectRaw($query)->where('id',$informedRelated->id)->get();
              if ($isparent[0]->isparent) {
                $location[$field] = $informedRelated->id;
                return true ;
              } else {
                $query = "ST_Within(ST_GeomFromText('".$location['geom']."'),".$query_buffer.") as isparent";
                $isparent = Location::selectRaw($query)->where('id',$informedRelated->id)->get();
                if ($isparent[0]->isparent) {
                  $location[$field] = $informedRelated->id;
                  return true ;
                } else {
                  $locationLog = $location;
                  $locationLog['geom'] = substr($locationLog['geom'],0,20);
                  $this->skipEntry($locationLog,"Location $field is not a valid parent for this location. Not even with considering a buffer around it.");
                  return false;
                }
              }
           }

           $this->appendLog("Informed ".$field." for location ".$location['name']." was not found in the database.[value ".$hasRelated."]");
           return false;
        }


         /* if related was not informed try to guess */
         $guessedParent = $this->guessParent($location['geom'], $location['adm_level'], 'uc' === $field);

         //$parentIgnore = isset($location['parentIgnore']) ? (int) $location['parentIgnore'] : 0;

         /* if not found and not informed failed if got here unless filed is uc or parentIgnore has been selected*/
         if (null == $guessedParent) {
           if ($field != 'uc') {
             $locationLog = $location;
             $locationLog['geom'] = substr($locationLog['geom'],0,20);
             $this->skipEntry($locationLog,"Location $field was not detected nor informed. Only adm_level ".config('app.adm_levels')[0]." can be imported without $field assignment.");
             return false;
           }
           /* ucs are not mandatory */
           $location[$field] = null;
           return true;
        }

        $location[$field] = $guessedParent;
        return true;

    }

    protected function guessParent($geom, $adm_level, $parent_uc, $maxdim=0)
    {
        if (config('app.adm_levels')[0] == $adm_level) {
            return $parent_uc ? null : Location::world()->id;
        }
        //DETECT ST_WITHIN parent
        $parent = Location::detectParent($geom, $adm_level, $parent_uc, $ignore_level=false,$parent_buffer=0);
        if ($parent) {
            return $parent->id;
        }
        // IF NOT FOUND TRY WITH ST_BUFFER on parent

        //define buffer, for plots uses plot dimension, else default
        $buffer_dd = config("app.location_parent_buffer");
        if ($adm_level == Location::LEVEL_PLOT and $maxdim>0) {
           $buffer_dd = (($maxdim*0.00001)/1.11);
        }

        if ($adm_level != Location::LEVEL_POINT) {
            $parent = Location::detectParent($geom, $adm_level, $parent_uc, $ignore_level=false,$parent_buffer=$buffer_dd);
            if ($parent) {
              return $parent->id;
            }
        }
        // IF STILL NOT FOUN TRY IGNORING ADMIN LEVEL
        $parent = Location::detectParent($geom, $adm_level, $parent_uc, $ignore_level=1,$parent_buffer=0);
        if ($parent) {
            //$this->appendLog("Parent detected but of different adm_level than expected");
            return $parent->id;
        }

        // IF STILL NOT FOUN TRY IGNORING ADMIN LEVEL AND ADDING BUFFER
        $parent = Location::detectParent($geom, $adm_level, $parent_uc, $ignore_level=1,$parent_buffer=$buffer_dd);
        if ($parent) {
            //$this->appendLog("WARNING: Parent detected but of different adm_level than expected.");
            return $parent->id;
        }

        return null;
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
        if (isset($location['startx']) and ($location['startx']== '0' or $location['startx']>0)) {
          $startx = $location['startx'];
        }

        $starty = null;
        if (isset($location['starty']) and ($location['starty']== '0' or $location['starty']>0)) {
          $starty = $location['starty'];
        }
        $x = null;
        if (isset($location['x']) and ($location['x'])>0) {
          $x = $location['x'];
        }

        $y = null;
        if (isset($location['y']) and ($location['y'])>0) {
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
        //this is important to prevent duplicated values (redundand?)
        $smallGeom = substr($geom, 0,1000);
        $sameGeometry = Location::whereRaw("ST_Equals(geom,ST_GeomFromText('$geom')) > 0")->count();
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
      //if ($geomType == mb_strtolower(Location::GEOM_MULTIPOLYGON)) {
        //$featureGeometry = $featureGeometry[0];
      //}

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
              //$this->appendLog("THE WRONG VALUE IS : has ".count($polygon[1])."  polygons which have ".count($coordinates)." but coordinates have ".count($coordinates[1]));
              //if true then polygons have holes
              if (count($coordinates)>2) {
                $wktSubPol = [];
                foreach ($coordinates as $subcoordinates) {
                  $wktSubPol[] =  implode(" ",$subcoordinates);
                }
                $wktSubPol = "(".implode(", ",$wktSubPol).")";
                $wktPolygon[] =  $wktSubPol;
              } else {
                $wktPolygon[] =  implode(" ",$coordinates);
              }
            }
            $wktPolygon = "((".implode(", ",$wktPolygon)."))";
            $wktPolygon = str_replace("(((","((",$wktPolygon);
            $wktPolygon = str_replace(")))","))",$wktPolygon);
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
          //$wktPolygon[] =  implode(" ",$coordinates);
          if (count($coordinates)>2) {
            $wktSubPol = [];
            foreach ($coordinates as $subcoordinates) {
              $wktSubPol[] =  implode(" ",$subcoordinates);
            }
            $wktSubPol = "(".implode(", ",$wktSubPol).")";
            $wktPolygon[] =  $wktSubPol;
          } else {
            $wktPolygon[] =  implode(" ",$coordinates);
          }
        }
        $wktGeom = mb_strtoupper(Location::GEOM_POLYGON);
        $wktGeom .= "((".implode(", ",$wktPolygon)."))";
        $wktGeom = str_replace("(((","((",$wktGeom);
        $wktGeom = str_replace(")))","))",$wktGeom);
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
