<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Models\Taxon;
use App\Models\Location;
use App\Models\Identification;
use App\Models\Person;
use App\Models\Collector;
use App\Models\Biocollection;
use App\Models\Project;
use App\Models\Dataset;
use App\Models\ODBFunctions;
use Auth;
use Lang;
use Illuminate\Http\Request;


class ImportCollectable extends AppJob
{

    protected function validateHeader($field = 'collector')
    {
        if (array_key_exists('dataset', $this->header)) {
            $this->validateDataset($this->header);
        }
        if (array_key_exists($field, $this->header)) {
            $person = $this->extractCollectors('Header', $this->header, $field);
            if ($person) {
                $this->header[$field] = $person;
            }
        }
    }

    /*
     * Changes the $fieldName item of the $registry array to the id of valid Project.
     * If this item is not present in the array, it uses the defaultProject of the user.
     * Otherwise it interprets the value of this item as id or name of a project.
     * @retuns true if the project is validated; false if it fails.
     */
     // TODO: FUNCTION OBSOLETE NOT BEING USED
    protected function validateProject(&$registry)
    {
        $project = array_key_exists('project',$registry) ? ((null != $registry['project']) ? $registry['project'] : null) : null;
        if (null == $project  and array_key_exists('project', $this->header)) {
            $project = $this->header['project'];
        }
        if (null != $project) {
            $valid = ODBFunctions::validRegistry(Project::select('id'),$project,['id','name']);
            if (null === $valid) {
                $this->skipEntry($registry, 'project'.' '.$registry['project'].' was not found in the database');
                return false;
            }
            $registry['project'] = $valid->id;
            return true;
        }
        $registry['project'] = Auth::user()->defaultProject->id;
        return true;
    }


    /* if dataset is not informed return true, return false only if informed and invalid */
    protected function validateDataset(&$registry)
    {
        $header = $this->header;
        $dataset = isset($registry['dataset_id']) ? $registry['dataset_id'] : (isset($registry['dataset']) ? $registry['dataset'] : null);
        $header = isset($header['dataset_id']) ? $header['dataset_id'] : (isset($header['dataset']) ? $header['dataset'] : null);
        if (null == $dataset and $header != null) {
            $dataset = $header;
        }
        if (null != $dataset) {
            $valid = ODBFunctions::validRegistry(Dataset::select('id'),$dataset,['id','name']);
            if (null === $valid) {
                $this->skipEntry($registry, 'dataset'.' '.$dataset.' was not found in the database');
                return false;
            }
            $registry['dataset'] = $valid->id;
            return true;
        }
        $registry['dataset'] = Auth::user()->defaultDataset->id;
        return true;
    }


    protected function extractCollectors($callerName, $registry, $field = 'collector')
    {
        if (('Header' !== $callerName) and array_key_exists($field, $this->header)) {
            return $this->header[$field];
        }
        if (!array_key_exists($field, $registry) or null == $registry[$field]) {
            return null;
        }
        #explode comma will fail when abbreviation is provided and contain commas
        #replace by | which is gbif standard
        $persons = [$registry[$field]];
        if (strpos($registry[$field], '|') !== false) {
            $persons = explode('|', $registry[$field]);
        } else {
            if (strpos($registry[$field], ';') !== false) {
              $persons = explode(';', $registry[$field]);
            } else {
              /*
              commented: commas are used in abbreviations so, not valid for persons
              if (strpos($registry[$field], ',') !== false) {
                $persons = explode(',', $registry[$field]);
              }
              */
            }
        }
        if (!is_array($persons)) {
          $persons = [$persons];
        }
        $ids = [];
        $counter = 0;
        foreach ($persons as $person) {
            $valid = ODBFunctions::validRegistry(Person::select('id'), $person, ['id', 'abbreviation', 'full_name', 'email']);
            if (null === $valid) {
                if($counter==0) {
                  $this->skipEntry($registry,'The first person is the required Main Collector and your value '.$person." is was not found in the database.");
                  break;
                }
                $this->appendLog('WARNING: '.$callerName.' reffers to '.$person.' as member of '.$field.', but this person was not found in the database. Ignoring person '.$person);
            } else {
                $ids[] = $valid->id;
            }
            $counter = $counter+1;
        }
        return array_unique($ids);
    }


    public function validateModifier($modifier)
    {
      if (null == $modifier) {
        return 0;
      }
      $validmods = Identification::MODIFIERS;
      $validcodes = [];
      foreach($validmods as $md) {
        $validcodes[Lang::get('levels.modifier.'.$md)] = (string) $md;
      }
      if (array_key_exists($modifier,$validcodes)) {
        return (int) $validcodes[$modifier];
      }
      $modifier = (string) $modifier;
      if (!in_array($modifier,$validcodes)) {
          $this->appendLog("WARNING: Identification modifier informed ".$modifier." is not valid");
          return null;
      }
      return (int) $modifier;
    }



    protected function extractIdentification($registry)
    {
        if (!array_key_exists('taxon', $registry)) {
            if (!array_key_exists('taxon_id',$registry)) {
                return null;
            }
            $taxon = $registry['taxon_id'];
        } else {
          $taxon = $registry['taxon'];
        }


        if ( ((int)$taxon) >0) {
          $taxon_id = Taxon::select('id')->where('id', '=', $taxon)->get();
        } else {
          $taxon_id = Taxon::select('id')->whereRaw('odb_txname(name, level, parent_id) = ?', [$taxon])->get();
        }
        if (count($taxon_id)) {
            $identification['taxon_id'] = $taxon_id->first()->id;
        } else {
            $this->appendLog("WARNING: Taxon $taxon was not found in the database.");
            return null;
        }
        // Map $registry['identifier'] to $identification['person_id']
        if (!array_key_exists('identifier', $registry) && array_key_exists('identifier_id', $registry)) {
            $registry = array_merge($registry,array('identifier' => $registry['identifier_id']));
        }
        if (array_key_exists('identifier', $registry)) {
            $person = ODBFunctions::validRegistry(Person::select('id'), $registry['identifier'], ['id', 'abbreviation', 'full_name', 'email']);
            if (null === $person) {
                $this->appendLog('WARNING: Taxonomic identifier '.$registry['identifier'].' was not found in the person table.');
                return null;
            }
            $identification['person_id'] = $person->id;

        } else {
            $this->appendLog('WARNING: Taxonomic identifier was not informed and was assigned the Individual main_collector');
            $identification['person_id'] = isset($registry['collector']) and is_array($registry['collector']) ? $registry['collector'][0] : null;
        }
        //the following refer to external(or internal) records upon which the identification was based upon (we may remove this likely without harm)
        $identification['biocollection_id'] = null;
        $identification['biocollection_reference'] = null;
        if (array_key_exists('identification_based_on_biocollection', $registry) && array_key_exists('identification_based_on_biocollection_id', $registry)) {
           if (null != $registry['identification_based_on_biocollection']) {
            $identification['biocollection_id'] = ODBFunctions::validRegistry(Biocollection::select('id'), $registry['identification_based_on_biocollection'], ['id', 'acronym', 'name', 'irn']);
            if (null === $identification['biocollection_id']) {
                $this->appendLog("WARNING: Biocollection ".$registry['identification_based_on_biocollection']." was not found in the biocollection table or their reference is missed! Ignoring in the identification the relationship with this external reference");
                $identification['biocollection_reference'] = null;
            } else {
                $identification['biocollection_id'] = $identification['biocollection_id']->id;
                $identification['biocollection_reference'] = $registry['identification_based_on_biocollection_id'];
            }
          }
        }
        $identification['notes'] = array_key_exists('identification_notes', $registry) ? $registry['identification_notes'] : null;

        //modifier must be a valid code else is false
        $modifier = isset($registry['modifier']) ? $registry['modifier'] : null;
        $identification['modifier'] = self::validateModifier($modifier);

        //implemented to account for incomplete dates in identification (most commom)
        $date = null;
        if (isset($registry['identification_date_year'])) {
          $year = ((int) $registry['identification_date_year'])>0 ? $registry['identification_date_year'] : null;
          $month = isset($registry['identification_date_month']) ? $registry['identification_date_month'] : null;
          $day = isset($registry['identification_date_day']) ? $registry['identification_date_day'] : null;
          $date = array("month" => $month,"day" => $day,'year' => $year);
        } elseif (isset($registry['identification_date'])) {
            $date = $registry['identification_date'];
        }
        //ic string assumes "-" or "/" as separators and format YYYY-MM-DD
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
        if (null == $date) {
          $this->appendLog("WARNING: identification_date not informed. Record date used instead!");
          $identification['date'] = $registry['date'];
        } else {
          if (!(Identification::checkDate($date))) {
            $this->appendLog("FAILED: identification_date YYY=".$date[2]." MM=".$date[0]." DD=".$date[1]." is invalid");
            return false;
          }
          $identification['date'] = $date;
        }
        //$identification['date'] = array_key_exists('identification_date', $registry) ? $registry['identification_date'] : $registry['date'];

        return $identification;
    }

    protected function createCollectorsAndIdentification($object_type, $object_id, $collectors = null, $identification = null)
    {
        if ($identification) {
            $date = $identification['date'];
            $identification = new Identification([
                'object_id' => $object_id,
                'object_type' => $object_type,
                'taxon_id' => $identification['taxon_id'],
                'person_id' => $identification['person_id'],
                'biocollection_id' => $identification['biocollection_id'],
                'biocollection_reference' => $identification['biocollection_reference'],
                'notes' => $identification['notes'],
                'modifier' => $identification['modifier'],
            ]);
            $identification->setDate($date[0],$date[1],$date[2]);
            $identification->save();
        }
        if ($collectors) {
            foreach ($collectors as $collector) {
                Collector::create([
                        'person_id' => $collector,
                        'object_id' => $object_id,
                        'object_type' => $object_type,
                ]);
            }
        }
    }

    /* location may be uploaded for an individual as :
    a) a string for name or id
    b) latitude and longitude
    c) array of arrays with keys  location_id or latitude+longitude, location_date_time,location_notes*/
    protected function validateLocations(&$registry)
    {
      $location = isset($registry['location']) ? $registry['location'] : (isset($this->header['location']) ? $this->header['location'] : null);
      $longitude = isset($registry['longitude']) ? (float) $registry['longitude'] : null;
      $latitude = isset($registry['latitude']) ? (float) $registry['latitude'] : null;

      if (null == $location and (null == $longitude or null == $latitude)) {
        $this->skipEntry($registry, 'location is missing or incomplete');
        return false;
      }

      $thelocations = [];
      if (null != $location and !is_array($location)) {
        $record =  [
          'location' => $location,
          'altitude' => isset($registry['altitude']) ? (float) $registry['altitude'] : null,
          'notes' => isset($registry['location_notes']) ? $registry['location_notes'] : null,
          'date_time' => isset($registry['location_date_time']) ? $registry['location_date_time'] : null,
          'x' => isset($registry['x']) ? (float) $registry['x'] : null,
          'y' => isset($registry['y']) ? (float) $registry['y'] : null,
          'distance' => isset($registry['distance']) ? (float) $registry['distance'] : null,
          'angle' => isset($registry['angle']) ? (float) $registry['angle'] : null,
        ];
        $thelocations[] = array_filter($record);
      }
      if (null == $location and (abs($longitude)+abs($latitude))>0 ) {
        $record =  [
          'latitude' => $latitude,
          'longitude' => $longitude,
          'altitude' => isset($registry['altitude']) ? (float) $registry['altitude'] : null,
          'notes' => isset($registry['location_notes']) ? $registry['location_notes'] : null,
          'date_time' => isset($registry['location_date_time']) ? $registry['location_date_time'] : null,
          'x' => isset($registry['x']) ? (float) $registry['x'] : null,
          'y' => isset($registry['y']) ? (float) $registry['y'] : null,
          'distance' => isset($registry['distance']) ? (float) $registry['distance'] : null,
          'angle' => isset($registry['angle']) ? (float) $registry['angle'] : null,
        ];
        $thelocations[] = array_filter($record);
      }
      if (is_array($location)) {
        $possible_keys = ['location','longitude','latitude','notes','date_time','altitude','x','y','distance','angle'];
        $locationkeys = array_filter(array_keys($location));
        if (count($locationkeys)>0) {
          $issingle = array_diff($locationkeys,$possible_keys);
          if (count($issingle)==0) {
            $record =  [
            'location' => isset($location['location']) ? (float) $location['location'] : null,
            'latitude' => isset($location['latitude']) ? (float) $location['latitude'] : null,
            'longitude' => isset($location['longitude']) ? (float) $location['longitude'] : null,
            'altitude' => isset($location['altitude']) ? (float) $location['altitude'] : (isset($registry['altitude']) ? (float) $registry['altitude'] : null),
            'notes' => isset($location['notes']) ? $location['notes'] : (isset($registry['location_notes']) ? $registry['location_notes'] : null),
            'date_time' => isset($location['date_time']) ? $location['date_time'] : (isset($registry['location_date_time']) ? $registry['location_date_time'] : null),
            'x' => isset($location['x']) ? (float) $location['x'] : (isset($registry['x']) ? (float) $registry['x'] : null),
            'y' => isset($location['y']) ? (float) $location['y'] : (isset($registry['y']) ? (float) $registry['y'] : null),
            'distance' => isset($location['distance']) ? (float) $location['distance'] : (isset($registry['distance']) ? (float) $registry['distance'] : null),
            'angle' => isset($location['angle']) ? (float) $location['angle'] : (isset($registry['angle']) ? (float) $registry['angle'] : null),
            ];
            $thelocations[] = array_filter($record);
          }
          /* if it is a single record, but some informed keys are not present, then is invalid */
          if (count($issingle)>0 and count($issingle)<count($location)) {
            $this->skipEntry($registry, 'The location keys '.implode('|',$issingle).' are invalid.');
            return false;
          }
        }

        /* if this is true, there should be multiple locations informed and each array element is a location value */
        if (count($thelocations)==0) {
          foreach ($location as $value) {
            $keysvalid = array_diff(array_keys($value),$possible_keys);
            if (count($keysvalid)==0) {
              $record =  [
                'location' => isset($value['location']) ? (float) $value['location'] : null,
                'latitude' => isset($value['latitude']) ? (float) $value['latitude'] : null,
                'longitude' => isset($value['longitude']) ? (float) $value['longitude'] : null,
                'altitude' => isset($value['altitude']) ? (float) $value['altitude'] : null,
                'notes' => isset($value['notes']) ? $value['notes'] : null,
                'date_time' => isset($value['date_time']) ? $value['date_time'] : null,
                'x' => isset($value['x']) ? (float) $value['x'] : (isset($registry['x']) ? (float) $registry['x'] : null),
                'y' => isset($value['y']) ? (float) $value['y'] : (isset($registry['y']) ? (float) $registry['y'] : null),
                'distance' => isset($value['distance']) ? (float) $value['distance'] : (isset($registry['distance']) ? (float) $registry['distance'] : null),
                'angle' => isset($value['angle']) ? (float) $value['angle'] : (isset($registry['angle']) ? (float) $registry['angle'] : null),
              ];
              $thelocations[] = array_filter($record);
            }
            if (count($keysvalid)>0) {
              $this->skipEntry($registry, 'The location keys '.implode('|',$keysvalid).' are invalid.');
              break;
              return false;
            }
         }
       }
      }

      /*if got here fields for location exist and must be validated */
      $validatelocations = [];
      $messages = [];
      foreach($thelocations as $alocation) {
        $location = isset($alocation['location']) ? $alocation['location'] : null;
        $latitude = isset($alocation['latitude']) ? $alocation['latitude'] : null;
        $longitude = isset($alocation['longitude']) ? $alocation['longitude'] : null;
        if (null != $location and (abs($longitude)+abs($latitude))==0) {
            $valid = ODBFunctions::validRegistry(Location::select('id'), $location);
            if (null === $valid) {
              $messages[] = 'location'.' '.$location.' was not found in the database';
            } else {
              $alocation['location'] = null;
              $alocation['location_id'] = $valid->id;
              $validatelocations[] = $alocation;
            }
        }
        // if coordinates were informed detect parent or self, not saving
        elseif (null == $location and (abs($longitude)+abs($latitude))>0) {
            $data = [];
            $data['lat1'] = $latitude;
            $data['long1']= $longitude;
            $data['adm_level'] = Location::LEVEL_POINT;
            $data['geom_type'] = "point";
            $locrequest = new Request;
            $locrequest->merge($data);
            //autodetect parent or self if the case
            $detected_locations = app('App\Http\Controllers\LocationController')->autodetect($locrequest);
            //if found an exact match retrieve location
            $hadlocation =  $detected_locations->getData()->detectedLocation;
            $newlocation =  $detected_locations->getData()->detectdata;
            $hadrelated = $detected_locations->getData()->detectrelated;
            //if found nothing, neither parent nor self, then issue error
            if (!array_filter($hadlocation) and !array_filter($newlocation)) {
              $messages[] =  'Location latitude '.$latitude.' and/or location longitude'.$longitude.' are invalid!';
            } elseif (array_filter($hadlocation)) {
                //if a location with the same coordinates was found, then use it for the individual
                $alocation['latitude'] = null;
                $alocation['longitude'] = null;
                $alocation['location_id'] = $hadlocation[0];
                $validatelocations[] = $alocation;
            } elseif (!array_filter($hadlocation) and array_filter($newlocation)) {
                //if a location with the same coordinates was found, then use it for the individual
                $alocation['latitude'] = null;
                $alocation['longitude'] = null;
                $alocation['location_tosave']  = [
                  'name' => config('app.unnamedPoint_basename')."_".preg_replace("/[A-Z\(\)-\.\s]/","",$newlocation[4]),
                  'parent_id' => $newlocation[1],
                  'uc_id' => $newlocation[3],
                  'geom' => $newlocation[4],
                  'adm_level' => Location::LEVEL_POINT,
                  'related_locations' => $hadrelated,
                ];
                $validatelocations[] = $alocation;
            }
        }
        else {
            $messages[] =  'Location record has invalid keys: '.json_encode($alocation);
        }
      }

      if (count($messages)>0 or count($validatelocations)<count($thelocations)) {
        $this->skipEntry($registry, 'One of more locations with problems: '.implode('|',$messages));
        return false;
      }

      /* save locations if any to save */
      /* validate all individual_location attributes */
      $validatedlocation_messages = [];
      $finallocations = [];
      foreach($validatelocations as $individual_location) {
          // if a new location need to be save, then save it
          if(isset($individual_location['location_tosave'])) {
            $saverequest = new Request;
            $saverequest->merge($individual_location['location_tosave']);
            $savedlocation = app('App\Http\Controllers\LocationController')->saveForIndividual($saverequest);
            if (isset($savedlocation['error'])) {
                $validatedlocation_messages [] = 'Could not save detected location: '.implode('|',$individual_location);
            } else {
              $savedlocation = array_filter($savedlocation->getData()->savedlocation);
              $individual_location['location_id'] = $savedlocation[0];
              $individual_location['location_tosave'] = null;
            }
          }
          if (isset($individual_location['location_id'])) {
            $locationrequest = new Request;
            $locationrequest->merge($individual_location);
            //check the individual location attributes are valid
            $vallocation = app('App\Http\Controllers\IndividualController')->validateIndividualLocation($locationrequest);
            if ($vallocation->fails()) {
              $validatedlocation_messages [] = 'Individual location defined by: '.json_encode($individual_location).' is not valid. Errors: '.implode(" | ",$vallocation->errors()->all());
            }
            $finallocations[] = $individual_location;
          }
      }
      if (count($validatedlocation_messages)>0) {
        $this->skipEntry($registry, 'Problems in individual location validation: '.implode('|',$validatedlocation_messages));
        return false;
      }

      $registry['individual_locations'] = $finallocations;
      return true;
    }



    protected function extractBioCollection(&$registry)
    {
        // if none are present or both are null then this is missing
        if (!array_key_exists('biocollection',$registry)) {
          return true;
        }
        if (null == $registry['biocollection']) {
          return true;
        }
        $biocollections = [];
        $validtypes = [];
        foreach (Biocollection::NOMENCLATURE_TYPE as $type) {
          $validtypes[Lang::get('levels.vouchertype.'.$type)] = $type;
          $validtypes[Lang::choice('levels.vouchertype.'.$type,1,[],'pt')] = $type;
        }
        $validtypes_keys = array_keys($validtypes);

        //if is a string, then acronyms only are expected, validate them
        if (!is_array($registry['biocollection'])) {
          //then we expect a single or a list of acronyms
          $pattern = "/[;,|]/";
          $biocols= preg_split($pattern, $registry['biocollection']);
          $biocolsnumbers = [];
          if (array_key_exists('biocollection_number',$registry)) {
            $biocolsnumbers = preg_split($pattern, $registry['biocollection_number']);
          }
          $biocolstypes = [];
          if (array_key_exists('biocollection_type',$registry)) {
            $biocolstypes = preg_split($pattern, $registry['biocollection_type']);
          }
          foreach ($biocols as $key => $value) {
            $query = Biocollection::select(['id', 'acronym', 'name', 'irn']);
            $fields = ['id', 'acronym', 'name', 'irn'];
            $valid = ODBFunctions::validRegistry($query, $value, $fields);
            if (!$valid) {
              break;
            }
            $thetype = isset($biocolstypes[$key]) ? $biocolstypes[$key] : 0;
            if (!in_array($thetype,$validtypes_keys) and !in_array($thetype,Biocollection::NOMENCLATURE_TYPE)) {
              break;
            } elseif (in_array($thetype,$validtypes_keys) ) {
              $kk = array_search($thetype,$validtypes_keys);
              if ($kk) {
                $thetype = array_values($validtypes)[$kk];
              } else {
                break;
              }
            }
            $biocollections[] = [
              'biocollection_id' => $valid->id,
              'biocollection_number' => isset($biocolsnumbers[$key]) ? $biocolsnumbers[$key] : null,
              'biocollection_type' => $thetype,
            ];
          }
        } else {
          //expects at least biocollection_number, biocollection,
          foreach($registry['biocollection'] as $key => $value) {
            $biocollection_number = null;
            $biocollection_type = isset($value['biocollection_type']) ? $value['biocollection_type']: 0;
            if (!in_array($biocollection_type,$validtypes_keys) and !in_array($biocollection_type,Biocollection::NOMENCLATURE_TYPE)) {
              break;
            } elseif (in_array($biocollection_type,$validtypes_keys) ) {
              $kk = array_search($biocollection_type,$validtypes_keys);
              if ($kk) {
                $biocollection_type = array_values($validtypes)[$kk];
              } else {
                break;
              }
            }
            if (is_array($value)) {
                $biocollection_id = !isset($value['biocollection_code']) ? (isset($value[0]) ? $value[0] : null) : $value['biocollection_code'];
                $biocollection_number = !isset($value['biocollection_number']) ? (isset($value[1]) ? $value[1] : null) : $value['biocollection_number'];
            } else {
               $biocollection_id = $value;
            }

            $query = Biocollection::select(['id', 'acronym', 'name', 'irn']);
            $fields = ['id', 'acronym', 'name', 'irn'];
            $valid = ODBFunctions::validRegistry($query, $biocollection_id, $fields);
            if (!$valid) {
              break;
            }
            $biocollections[] = [
              'biocollection_id' => $valid->id,
              'biocollection_number' => $biocollection_number,
              'biocollection_type' => $biocollection_type,
            ];
          }
        }
        if (count($biocollections)==0) {
          return false;
        }
        $registry['biocollections'] = $biocollections;
        return true;
    }





}
