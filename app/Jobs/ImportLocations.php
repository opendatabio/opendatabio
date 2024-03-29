<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Models\Location;
use App\Models\LocationRelated;
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
        if (!$this->validateInformedParent($location)) {
            return false;
        }
        if (!$this->validateDimensions($location)) {
            return false;
        }
        if (!$this->validateGeom($location)) {
            return false;
        }
        if (!$this->validateParent($location)) {
            return false;
        }
        //second pass, for cases when parent is not informed but was detected above
        if (!$this->validateDimensions($location)) {
            return false;
        }
        if (!$this->validateOtherParents($location)) {
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

    public function validateInformedParent(&$location)
    {
      $parent = isset($location['parent']) ? $location['parent'] : (isset($location['parent_id']) ? $location['parent_id'] : null );
      if (null != $parent) {
        /* if interger, then must be the id */
        if (((int) $parent) >0) {
          $infomedParent = Location::where('id',$parent);
        } else {
          $infomedParent = Location::where('name','like',$parent);
        }
        $maxadmin = $location['adm_level'];
        if ($maxadmin==Location::LEVEL_PLOT) {
            $maxadmin = $maxadmin+1;
        }
        $infomedParent = $infomedParent->where('adm_level','<',$maxadmin);
        if ($infomedParent->count()!=1) {
          $locationLog = $location;
          $locationLog['geom'] = substr($locationLog['geom'],0,20);
          $this->skipEntry($locationLog,"Informed Parent ".$parent." for location ".$location['name']." was not found in the database or location adm_level is not greater than parent adm_level. Or there are multiple parents with the same name. Try without parent specification.");
          return false;
        }
        $location['parent'] = $infomedParent->first()->id;
      }
      return true;
    }

    public function validateDimensions(&$location)
    {
      $adm_level = $location['adm_level'];
      if ($adm_level != Location::LEVEL_PLOT) {
        return true;
      }
      if (!isset($location['x']) or $location['x']==0 or !isset($location['y']) or $location['y']==0) {
        $locationLog = $location;
        $locationLog['geom'] = substr($locationLog['geom'],0,20);
        $this->skipEntry($locationLog,'Plot x or y dimension missing or 0');
        return false;
      }
      if (isset($location['parent'])) {
        $parent = Location::withGeom()->findOrFail($location['parent']);
        if ($parent->adm_level==Location::LEVEL_PLOT) {
            if (!isset($location['startx']) or ($location['startx']+$location['x'])>$parent->x or !isset($location['starty']) or ($location['starty']+$location['y'])>$parent->y) {
              $locationLog = $location;
              $locationLog['geom'] = substr($locationLog['geom'],0,20);
              $this->skipEntry($locationLog,'Subplot x,y, startx or starty, is missing or invalid');
              return false;
            }
        }
      }
      return true;
    }

    public static function subplotGeometry($location)
    {
      $adm_level = $location['adm_level'];
      if ($adm_level != Location::LEVEL_PLOT) {
        return false;
      }
      $parent = $location['parent'];
      if (!is_null($parent)) {
        $parent = Location::withGeom()->findOrFail($parent);
        if ($parent->adm_level==Location::LEVEL_PLOT) {
          /* get the 0,0 coordinates for a subplot given the x and y positions in parent */
          return Location::individual_in_plot($parent->footprintWKT,$location['startx'],$location['starty']);
        }
      }
      return false;
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
      if ('linestring' == substr($strGeom, 0, 10)) {
        return Location::GEOM_LINESTRING;
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
            /* create geometry if subplot */
            $geom = self::subplotGeometry($location);
            if (!$geom) {
              $this->skipEntry($location, "Coordinates for location ".$location['name']." not available");
              return false;
            }
            $location['geom'] = $geom;
          } else {
            $location['geom'] = "POINT($long $lat)";
          }
        }
        /* a shorter geometry for logging */
        $locationLog = $location;
        if (isset($location['geom'])) {
          $locationLog['geom'] = substr($locationLog['geom'],0,50);
        }
        $locationLog['geojson'] = null;

        $geom = $location['geom'];
        /* get the geometry type from the string submitted */
        $geomType = mb_strtolower(self::geomTypeFromGeom($location['geom']));
        if($geomType == null ) {
          $this->skipEntry($locationLog, "Invalid geometry type for location ".$location['name']);
          return false;
        }

        //check if geometry is valid
        //Understand https://dev.mysql.com/doc/refman/8.0/en/geometry-well-formedness-validity.html
        //but mariadb does not have st_valid of mysql, so we can evaluate some of these issues only //

        /* 1. If geometry conversion from text pass, then is valid*/
        try {
          $validGeom = DB::statement("SELECT ST_GeomFromText('".$geom."')");
        } catch (\Exception $e) {
          $this->skipEntry($locationLog,Lang::get('messages.geometry_invalid'));
          return false;
        }
        // MariaDB returns 1 for invalid geoms from ST_IsEmpty ref: https://mariadb.com/kb/en/mariadb/st_isempty/
        $invalid = DB::select("SELECT ST_IsEmpty(ST_GeomFromText('$geom')) as val");
        $invalid = count($invalid) ? $invalid[0]->val : 1;
        if ($invalid) {
          $this->skipEntry($locationLog,Lang::get('messages.geometry_invalid'));
          return false;
        }

        /* 2 validate by calculating the area and the centroid if polygon or multipolygon*/
        /* because these are included in the queries */
        if (in_array($geomType,['polygon','multipolygon'])) {
          /* this functions should work for these geometries otherwise is an error */
          /* area */
          $area  = DB::select("SELECT ST_Area(ST_GeomFromText('".$geom."')) as area");
          $area = count($area) ? $area[0]->area : null;
          if ($area===null) {
            $this->skipEntry($locationLog,"Could not calculate ST_Area of the informed polygon");
            return false;
          }
          /*centroid */
          $centroid  = DB::select("SELECT ST_AsText(ST_Centroid(ST_GeomFromText('".$geom."'))) as centroid");
          $centroid = count($centroid) ? $centroid[0]->centroid : null;
          if ($centroid===null) {
            $this->skipEntry($locationLog,"Could not calculate the ST_Centroid for the informed geometry");
            return false;
          }
        }
        // TODO: implemente validation for linestrings (transects)
        if (in_array($geomType,['linestring'])) {
          $start_point  = DB::select("SELECT ST_AsText(ST_StartPoint(ST_GeomFromText('".$geom."'))) as start_point");
          $start_point = count($start_point) ? $start_point[0]->start_point : null;
          if ($start_point==null) {
            $this->skipEntry($locationLog,"Could not calculate the ST_StartPoint for the informed geometry");
            return false;
          }
        }

        // finally check if this exact geometry is already registered
        //$alreadyPresent= Location::noWorld()->whereRaw("ST_AsText(geom) LIKE '".$geom."'")->count();
        $alreadyPresent = Location::whereRaw("ST_Equals(geom,ST_GeomFromText('$geom')) > 0")->count();
        if ($alreadyPresent>0) {
            $this->skipEntry($locationLog,Lang::get('messages.geom_duplicate'));
            return false;
        }
        /* if plot or transect informed as points, define geometry for storage*/
        $angle = isset($location['azimuth']) ? $location['azimuth'] : 0;
        $angle = $angle>=360 ? ($angle-360) : $angle;
        if ($location['adm_level']==Location::LEVEL_TRANSECT and $geomType=='point') {
          $geom = Location::generate_transect_geometry($geom,$location['x'],$angle);
          $location['geom'] = $geom;
        } elseif ($location['adm_level']==Location::LEVEL_PLOT and $geomType=='point') {
          if (isset($location['parent'])) {
            $parent = Location::withGeom()->findOrFail($location['parent']);
            /* if subplot angle must fit parent geometry and is retrieved from there */
            if ($parent->adm_level==Location::LEVEL_PLOT) {
              $parent_wkt = $parent->footprintWKT;
              $pattern = '/\\(|\\)|POLYGON|\\n/i';
              $coordinates = preg_replace($pattern, '', $parent_wkt);
              $coordinates = explode(",",$coordinates);
              $coordA = "POINT(".$coordinates[0].")";
              $coordB = "POINT(".$coordinates[1].")";
              $geotools = new \League\Geotools\Geotools();
              $coordA   = new \League\Geotools\Coordinate\Coordinate(Location::latlong_from_point($coordA));
              $coordB   = new \League\Geotools\Coordinate\Coordinate(Location::latlong_from_point($coordB));
              $angle    =  $geotools->vertex()->setFrom($coordA)->setTo($coordB)->initialBearing();
            }
          }
          $geom = Location::generate_plot_geometry($geom,$location['x'],$location['y'],$angle);
          $location['geom'] = $geom;
        }
        return true;
    }

    protected function validateParent(&$location)
    {
        $parent = isset($location['parent']) ? $location['parent'] : (isset($location['parent_id']) ? $location['parent_id'] : null );
        $location['parent'] = $parent;
        if (!$this->validateParentValues($location)) {
            return false;
        }
        $parent = $location['parent'];
        $sameName = Location::where('name', 'like', $location['name'])->where('parent_id', '=', $parent)->count() ;
        if ($sameName > 0) {
          $locationLog = $location;
          $locationLog['geom'] = substr($locationLog['geom'],0,20);
          $this->skipEntry($locationLog, 'location '.$location['name'].' already exists in database at same parent location. Must be unique within parent');
          return false;
        }
        return true;
    }


    protected function validateOtherParents(&$location)
    {
      if (!isset($location['related_locations'])) {
        $related = Location::detectRelated($location['geom'],$location['adm_level'],true);
        $location['related_locations'] = $related;
        return true;
      }
      $related = explode(",",$location['related_locations']);
      $ids = [];
      foreach($related as $loc) {
          $valid = ODBFunctions::validRegistry(Location::select('id'), $loc, ['id', 'name']);
          if (null === $valid) {
              $this->skipEntry($location,'Informed other parent <strong>'.$loc.'</strong>not found');
              break;
          }
          $query = "ST_Within(ST_GeomFromText('".$location['geom']."'),geom) as inparent";
          $inparent = Location::selectRaw($query)->where('id',$valid->id)->get();
          /* validate geometry now */
          if ($isparent[0]->inparent) {
            $ids[] = $valid->id;
          } else {
            $this->skipEntry($location,'Location does not fall within informed <strong>'.$loc.'</strong>');
            break;
          }
      }
      if (count($ids)!=count($related)) {
         return false;
      }
      $location['related_locations'] = $ids;
      return true;
    }

    protected function validateParentValues(&$location)
    {

         /* do not check uc if location is country */
         if ($location['adm_level'] == config('app.adm_levels')[0]) {
           $location['parent'] = Location::world()->id;
           return true;
         }

         /* check whether parent contains the geometry of the location
         */
         $parent = $location['parent'];
         $informedParent = null;
         if (null != $parent) {
           $informedParent = Location::withGeom()->findOrFail($parent);
           if ($informedParent->adm_level != Location::LEVEL_TRANSECT) {
             $parent_dim = !is_null($informedParent->x) ? (($informedParent->x >= $informedParent->y) ? $informedParent->x : $informedParent->y) : null;
           } else {
             /* transects the buffer is the y parameter */
             $parent_dim = !is_null($informedParent->y) ? $informedParent->y : null;
           }
           $parent_geom = $informedParent->footprintWKT;
           if (!is_null($parent_dim)) {
             /* add a buffer to parent point in the ~ size of its dimension if set */
             $buffer_dd = (($parent_dim*0.00001)/1.11);
             $query_buffer ="ST_Buffer(ST_GeomFromText('".$parent_geom."'), ".$buffer_dd.")";
           } else {
             /* else use config buffer */
             if ($informedParent->adm_level == config('app.adm_levels')[0]) {
               //if country, allow bigger buffer
               $buffer_dd = 0.2;
               $query_buffer ="ST_Buffer(ST_GeomFromText('".$parent_geom."'),".$buffer_dd.")";
             } else {
               //the config buffer
               $buffer_dd = config('app.location_parent_buffer');
               $query_buffer ="ST_Buffer(ST_GeomFromText('".$parent_geom."'), ".$buffer_dd.")";
             }
           }
          //test without buffer nor simplification
          $query = "SELECT ST_Within(ST_GeomFromText('".$location['geom']."'),ST_GeomFromText('".$parent_geom."')) as isparent";
          $isparent = DB::select($query);
          //if not valid, test with buffer
          if ($isparent[0]->isparent) {
            return true ;
          }
          //test with buffered parent
          $query = "SELECT ST_Within(ST_GeomFromText('".$location['geom']."'),".$query_buffer.") as isparent";
          $isparent = DB::select($query);
          if ($isparent[0]->isparent) {
            return true ;
          }
          //if still not fall within buffered parent, then accept only if ismarine informed
          $ismarine = isset($location['ismarine']) ? ($location['ismarine'] != null) : false;
          if ($ismarine and in_array($location['adm_level'], Location::LEVEL_SPECIAL)) {
            $this->appendLog("WARNING: Location ".$location['name']." does not fall within parent ".$parent." but the relation was established because you informed to be a marine location.");
            return true;
          }
          //else informed parent is invalid
          $locationLog = $location;
          $locationLog['geom'] = substr($locationLog['geom'],0,20);
          $this->skipEntry($locationLog,"Location parent ".$parent." is not a valid parent for this location. Not even with considering a buffer around it.");
          return false;
        }
        /* if parent was not informed try to guess */
        $guessedParent = $this->guessParent($location['geom'],$location['adm_level']);
        /* if still not guessed, then it cannot be imported and nothing to do*/
        if (null == $guessedParent) {
          $locationLog = $location;
          $locationLog['geom'] = substr($locationLog['geom'],0,20);
          $this->skipEntry($locationLog,"Location parent was not detected nor informed. Only adm_level ".config('app.adm_levels')[0]." and can be imported without parent assignment.");
          return false;
        }

        $location['parent'] = $guessedParent;
        return true;
    }

    protected function guessParent($geom, $adm_level,$maxdim=0)
    {
        if (config('app.adm_levels')[0] == $adm_level) {
            return Location::world()->id;
        }
        //DETECT ST_WITHIN parent
        $parent = Location::detectParent($geom, $adm_level, null, $ignore_level=false,$parent_buffer=0);
        if ($parent) {
          return $parent->id;
        }
        // IF STILL NOT FOUND TRY IGNORING ADMIN LEVEL
        $parent = Location::detectParent($geom, $adm_level, null, $ignore_level=1,$parent_buffer=0);
        if ($parent) {
            $this->appendLog("Parent detected but of different adm_level than expected");
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
        //$uc = $location['uc'];
        $related_locations = isset($location["related_locations"]) ? $location['related_locations'] : null;
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
            'geojson' => $geojson,
        ]);
        $location->geom = $geom;
        $location->save();
        if ($related_locations) {
            foreach ($related_locations as $related_id) {
                $related = new LocationRelated(['related_id' => $related_id]);
                $location->relatedLocations()->save($related);
            }
        }
        $this->affectedId($location->id);

        $fixed = Location::fixPathAndRelated($location->id);
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
