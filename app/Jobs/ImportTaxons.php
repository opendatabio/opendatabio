<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Models\Taxon;
use App\Models\ExternalAPIs;
use App\Models\Person;
use Lang;
use Illuminate\Http\Request as therequest;


class ImportTaxons extends AppJob
{
    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        $data = $this->extractEntrys();
        if (!$this->setProgressMax($data)) {
            return;
        }
        foreach ($data as $taxon) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();

            if ($this->validateData($taxon)) {
                // Arrived here: let's import it!!
                try {
                    $this->import($taxon);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+On line'.$e->getLine().' on taxon '.$taxon['name']);
                }
            }
        }
    }

    protected function validateData(&$taxon)
    {
        if (!$this->hasRequiredKeys(['name'], $taxon)) {
            return false;
        }
        $this->cleanRecord($taxon);

        //validate informed parent to the parent field if present
        $parent = isset($taxon['parent_name']) ? $taxon['parent_name'] : (isset($taxon['parent']) ? $taxon['parent'] : (isset($taxon['parent_id']) ? $taxon['parent_id'] : null ));
        if (!is_null($parent)) {
          // parent might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
          $checkedparent = $this->getTaxonId($parent);
          if (null === $checkedparent) {
              $taxon['parent'] = ($parent=="") ? null : $parent;
          } else {
              $taxon['parent_id'] = $checkedparent->id;
              $taxon['parent'] = $checkedparent->fullname;
          }
        }
        $senior = isset($taxon['senior']) ? $taxon['senior'] : null;
        if (!is_null($senior)) {
          // parent might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
          $checkedsenior = $this->getTaxonId($senior);
          if (!is_null($checkedsenior)) {
            $taxon['senior_id'] = $checkedsenior->id;
            $taxon['senior'] = $checkedsenior->fullname;
          } else {
            $taxon['senior'] = ($senior=="") ? null : $senior;
          }
        }

        //VALIDATE MOBOT, IPNI, MYCOBANK, GBIF AND ZOOBANK apis
        $this->validateAPIs($taxon);

        //check if registered if parent is registered
        if (isset($taxon['parent_id'])) {
          $name = $taxon['name'];
          $parent_id = $taxon['parent_id'];
          $hadtaxon = Taxon::whereRaw('(odb_txname(name, level, parent_id) LIKE ?) AND parent_id = ?', [$name, $parent_id])->count();
          if ($hadtaxon>0) {
              $this->appendLog('WARNING: taxon '.$name.' under the parent '.$taxon['parent'].' is already imported into the database');
              return false;
          }
        }

        //validate parents and senior paths and create array to import them as needed
        //this will got down the root
        if (!$this->validateRelatedAndLevel($taxon)) {
              return false;
        }
        if (!$this->validateValid($taxon)) {
            return false;
        }

        //if (!$this->validateSeniorAndValid($taxon)) {
        //      return false;
        //}
        //for unpublished names validation must include a check of persons id
        //then assumes that it is an unpublished name and requires a valid person
        if (!isset($taxon['apiwaschecked']) ) {
          $this->validatePerson($taxon);
        }

        //IF BIBKEY PROVIDED VALIDATE
        if (isset($taxon['bibkey'])) {
          if (!$this->validateBibKey($taxon['bibkey']))
          {
              return false;
          }
        }
        //if author is not informed for levels genus or below, then missing info
        $condition = (!isset($taxon['author_id']) and !isset($taxon['author']) and $taxon['level']>=Taxon::getRank('genus'));
        if ($condition)
        {
          $this->skipEntry($taxon, 'Author is mandatory for levels genus or below');
          return false;
        }
        //or clade is the level and bibreference is missing, then issues a warning only,
        $condition = ($taxon['level']==Taxon::getRank('clade') and !isset($taxon['bibreference']) and !isset($taxon['bibkey']) and !isset($taxon['author']) and !isset($taxon['author_id']));
        if ($condition)
        {
          $this->appendLog("WARNING: taxon ".$taxon['name']." was registered without a bibreference or a Authorship. Consider adding this information to the record");
          //return false;
        }
        return true;
    }

    protected function validateAPIs(&$taxon)
    {

        //this only makes sense if author_id
        //check API for taxon name as it may be the only thing informed
        //this will validate the name and get info
        //transform info in a request
        $checkname = ['name' => $taxon['name']];
        $request = new therequest;
        $request = $request->merge($checkname);


        //get results from api checks
        $apicheck = app('App\Http\Controllers\TaxonController')->checkapis($request);

        $apiresults = $apicheck->getData(true);
        if (isset($apiresults["error"])) {
          return false;
        }
        $apidata = $apiresults['apidata'];

        //if level and author the api has found something by the name informed
        //if this is the case all fields that could be retrieved by the api are informed
        if (isset($apidata['rank'])) {
          $info_level = isset($taxon['level']) ? Taxon::getRank($taxon['level']) : null;
          if (is_null($info_level)) {
            $taxon['level'] = $apidata['rank'];
          } elseif (!is_null($info_level) and $info_level != $apidata['rank'] ) {
            $apilevel = Lang::get('levels.tax.'.$apidata['rank']);
            $this->appendLog('WARNING: the informed level "'.$taxon['level'].'" for taxon "'.$taxon['name'].'"  is different from the API detected level: "'.$apilevel.'". The informed level was used for the record.');
          }
          //if this true, the informed parent exists and was validated (requires the validationParent to be executed before validateAPIs)
          $taxon['author'] = $apidata['author'];
          $taxon['author_id'] = null;
          $taxon['bibreference'] = (null == $apidata['reference'] and isset($taxon['bibreference'])) ? $taxon['bibreference'] : $apidata['reference'];
          if (is_array($apidata['parent'])) {
            if (!isset($taxon['parent_id'])) {
              $taxon['parent_id'] =  $apidata['parent'][0];
            }
            //if this true, the informed parent exists and was validated (requires the validationParent to be executed before validateAPIs)
            if (isset($taxon['parent_id']) and $taxon['parent_id'] != $apidata['parent'][0]) {
                $this->appendLog('WARNING: the parent '.$taxon['parent'].'  informed for taxon '.$taxon['name'].' is different from the one found by the API: '.$apidata['parent'][1].'. The Informed parent was used for the record.');
            } else {
                //just add the api detected parent as the parent, regardless of whether it is registered or not in odb
                $taxon['parent'] =  $apidata['parent'][1];
            }
          }
          if (is_array($apidata['senior'])) {
            $taxon['senior_id'] = $apidata['senior'][0];
            $taxon['senior'] = $apidata["senior"][1];
          }
          $taxon['mobotkey'] = $apidata["mobot"];
          $taxon['ipnikey'] = $apidata['ipni'];
          $taxon['mycobankkey'] = $apidata['mycobank'];
          $taxon['gbifkey'] = $apidata['gbif'];
          $taxon['apiwaschecked'] = 1;
          //return true;
        }

        //then maybe this is un unpublished name and will be and either person or author_id must have benn informed
        //if (!isset($taxon['person']) and !isset($taxon['author_id']) and !isset($taxon['author'])) {
          //  return false;
        //}
        return true;
    }

        protected function cleanRecord(&$taxon)
        {
          foreach($taxon as $key => $value) {
              if ($value == "" or empty($value)) {
                unset($taxon[$key]);
              }
          }
        }

        protected function validateRelatedAndLevel(&$taxon)
        {
            if (!$this->validateLevel($taxon)) {
                return false;
            }

            //will only test if api validation has not already found a registered parent
            $gbifkey = isset($taxon['gbifkey']) ? $taxon['gbifkey'] : null;
            $parent_id = isset($taxon['parent_id']) ? $taxon['parent_id'] : null;
            $parent = (isset($taxon['parent']) and $taxon['parent']!="") ? $taxon['parent'] : null;
            $senior = (isset($taxon['senior']) and $taxon['senior']!="") ? $taxon['senior'] : null;
            $senior_id = isset($taxon['senior_id']) ? $taxon['senior_id'] : null;

            $condition1 = (!isset($parent_id) and null != $parent);
            $condition2 = (!isset($senior_id) and null != $senior);
            $condition3 = isset($gbifkey);


            if ($condition3 and ($condition1 or $condition2)) {
                $related = ExternalAPIs::getGBIFParentPathData($gbifkey,$include_first=false);
                $taxon['related_to_import'] = $related;
            }
            if (!$condition3 and ($condition1 or $condition2)) {
                $related_data = null;
                $related_data2 = null;
                $level = $taxon['level'];
                if ($condition1) {
                   $checkname = ['name' => $taxon['parent']];
                   $request = new therequest;
                   $request = $request->merge($checkname);
                   $apicheck = app('App\Http\Controllers\TaxonController')->checkapis($request);
                   $apiresults = $apicheck->getData(true);
                   if (isset($apiresults['apidata'])) {
                      $gbif = $apiresults['apidata']['gbif'];
                      $rank = $apiresults['apidata']['rank'];
                      $validlevel = ((isset($level) and $level>$rank) or !isset($level)) ? true : false;
                      if (isset($gbif) and $validlevel) {
                        $related_data = ExternalAPIs::getGBIFParentPathData($gbif,$include_first=true);
                      }
                   }
                }
                if ($condition2) {
                   $checkname = ['name' => $taxon['senior']];
                   $request = new therequest;
                   $request = $request->merge($checkname);
                   $apicheck = app('App\Http\Controllers\TaxonController')->checkapis($request);
                   $apiresults = $apicheck->getData(true);
                   if (isset($apiresults['apidata'])) {
                      $gbif = $apiresults['apidata']['gbif'];
                      $rank = $apiresults['apidata']['rank'];
                      $validlevel = ((isset($level) and $level>=$rank) or !isset($level)) ? true : false;
                      if (isset($gbif) and $validlevel) {
                        $related_data2 = ExternalAPIs::getGBIFParentPathData($gbif,$include_first=true);
                        if ($related_data !== null) {
                          $related_data = array_merge($related_data,$related_data2);
                        } else {
                          //$related_data = $related_data2;
                        }
                      }
                   }
                }
                if (is_array($related_data)) {
                  $related_data = array_unique($related_data,SORT_REGULAR);
                  ksort($related_data);
                  $taxon['related_to_import'] = $related_data;
                }
            }
            $condition4 = isset($taxon['related_to_import']) ? count($taxon['related_to_import'])>0 : false;
            $condition5 = ($taxon['level'] > 0);
            if ($condition1 and $condition2 and !$condition4 and $condition5) {
                $name = $taxon['name'];
                $parent = $taxon['parent'];
                $senior = $taxon['senior'];
                $message = $condition1 ? ("The informed Parent for taxon $name is $parent, but this is not registered and was not found by the API") : "";
                $message2 = $condition2 ? ("The informed Senior for taxon $name is $senior, but this is not registered and was not found by the API") : "";
                $message = $message." ".$message2;
                $this->skipEntry($taxon,$message);
                return false;
            }
            if (!$condition5 and $condition1 and !$condition4) {
                $root = Taxon::root();
                $taxon['parent_id'] = $root->id;
                $parent = $root->fullname;
                $this->appendLog("WARNING: missing parent for taxon ".$taxon['name']." The root node of the taxon table  '".$parent."' was used");
            }
            return true;
        }

        protected function validateSeniorAndValid(&$taxon)
    {
        if (!$this->validateSenior($taxon)  and !isset($taxon['apiwaschecked']) and !isset($taxon['senior_id'])) {
            return false;
        }
        if (!$this->validateValid($taxon)) {
            return false;
        }

        return true;
    }


    //validate author of unpublished names
    protected function validatePerson(&$taxon)
    {

       $person = isset($taxon['person']) ? $taxon['person'] : (isset($taxon['author_id']) ? $taxon['author_id'] : null);
       $name = $taxon['name'];
       if (null != $person)  {
         $valid = ODBFunctions::validRegistry(Person::select('id'), $person, ['id', 'abbreviation', 'full_name', 'email']);
         if (null != $valid) {
           $taxon['author_id'] = $valid->id;
           return true;
         }
       }
       $taxon['author_id'] = null;
       //$this->skipEntry($taxon, "Author_id for unpublished taxon $name is listed as $person, but this was not found in the database");
       return true;
    }


    //must be run after parent validation
    protected function validateLevel(&$taxon)
    {
        //level must be greater than parent level if is not a clade, which can be anywehre
        $level = array_key_exists('level', $taxon) ? $taxon['level'] : null;
        if (!is_numeric($level) and !is_null($level)) {
            $level = Taxon::getRank($level);
        }
        if (is_null($level)) {
            $name = $taxon['name'];
            $this->skipEntry($taxon, "Level for taxon $name not available");
            return false;
        }
        if (isset($taxon['parent_id'])) {
          $parent = Taxon::findOrFail($taxon['parent_id']);
          $name = $taxon['name'];
          if ($level != Taxon::getRank('clade')  and $level <= $parent->level) {
            $this->appendLog("FAILED Level $level for taxon $name is invalid in relation to the parent ".$parent->fullname." taxon level ".$parent->level);
            return false;
          }
        }
        $taxon['level'] = $level;
        return true;
    }

    //must be used after validateAPIs or will always fail
    protected function validateParent(&$taxon)
    {
        $parent = isset($taxon['parent_name']) ? $taxon['parent_name'] : (isset($taxon['parent']) ? $taxon['parent'] : (isset($taxon['parent_id']) ? $taxon['parent_id'] : null ));
        if (!is_null($parent)) {
          // parent might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
          $checkedparent = $this->getTaxonId($parent);
          if (null === $checkedparent) {
              $name = $taxon['name'];
              $this->skipEntry($taxon, "The informed Parent for taxon $name is $parent, but this was not found in the database");
              return false;
          } else {
              $taxon['parent_id'] = $checkedparent->id;
              $taxon['parent'] = $checkedparent->fullname;
              return true;
          }
        }
        return true;
    }

    /*
    protected function getTaxonIdFromAPI($taxon, $field)
    {
        if (array_key_exists("mobot",$taxon)) {
          if (array_key_exists($field, $taxon['mobot'])) {
              return $this->getTaxonId($taxon['mobot']['parent']);
          }
        }
        if (array_key_exists("ipni",$taxon)) {
          if (array_key_exists($field, $taxon['ipni'])) {
              return $this->getTaxonId($taxon['ipni']['parent']);
          }
        }
        return null;
    }
    */

    protected function validateSenior(&$taxon)
    {
        $senior = isset($taxon['senior']) ? $taxon['senior'] : null;
        if (null != $senior) {
          // parent might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
          $checkedsenior = $this->getTaxonId($senior);
          if (null === $checkedsenior) {
              $name = $taxon['name'];
              $this->skipEntry($taxon, "Senior for taxon $name is listed as $senior, but this was not found in the database");
              return false;
          }
          $taxon['senior_id'] = $checkedsenior->id;
          $taxon['senior'] = $checkedsenior->fullname;
          return true;
        }
        return true;
    }

    protected function getTaxonId($ref)
    {
        if (is_null($ref)) {
            return null;
        }
        // ref might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
        if (is_numeric($ref)) {
            $ref = Taxon::where('id',$ref)->get();
        } else {
            $ref = Taxon::whereRaw('odb_txname(name, level, parent_id) = ?', [$ref])->get();
        }
        if ($ref->count() == 1) {
            return $ref->first();
        }

        return null;
    }

    protected function validateBibKey(&$bibkey)
    {
      if (is_numeric($bibkey)) {
        $valid = BibReference::where('id',$bibkey)->get();
      } else {
        $valid = BibReference::whereRaw('odb_bibkey(bibtex) = ?', [$bibkey])->get();
      }
      if ($valid->count()) {
        $bibkey = $valid->get()->first()->id;
        return true;
      }
      $this->appendLog('FAILED: Provided bibkey '.$bibkey.' not found in database.');
      return false;
    }

    protected function validateValid(&$taxon)
    {
        //this depends on senior, if it exist or no
        if (!isset($taxon['senior_id']) and !isset($taxon['senior'])) {
            $taxon['valid'] = true;
            //$taxon
            return $taxon['valid'];
        } else {
            $taxon['valid'] = false;
            //returns true
            return !$taxon['valid'];
        }
    }

    public function import($taxon)
    {
        $name = $taxon['name'];
        $parent = isset($taxon['parent_id']) ? $taxon['parent_id'] : null;
        $related_to_import = isset($taxon['related_to_import']) ? $taxon['related_to_import'] : [];
        //import related taxa as needed retrieving last parent
        if (is_null($parent) and count($related_to_import)>0) {
          self::importRelated($taxon);
          $parent = isset($taxon['parent_id']) ? $taxon['parent_id'] : null;
        }
        if (is_null($parent)) {
          $this->appendLog("ERROR: taxon '$name' could not be imported into the database. Missing parent has ".count($related_to_import)." and the key is ".$taxon['gbifkey']." and parent is ".$taxon['parent']);
          return;
        }
        // Is this taxon already imported?
        $hadtaxon = Taxon::whereRaw('odb_txname(name, level, parent_id) = ? AND parent_id = ?', [$name, $parent])->count();
        if ($hadtaxon>0) {
            $parentname = Taxon::findOrFail($parent)->fullname;
            $this->appendLog("ERROR: taxon '$name' under parent '$parentname' is already imported into the database");
            return;
        }

        $level = $taxon['level'];
        $valid = $taxon['valid'];
        $bibreference = array_key_exists('bibreference', $taxon) ? $taxon['bibreference'] : null;
        $author = array_key_exists('author', $taxon) ? $taxon['author'] : null;
        $author_id = array_key_exists('author_id', $taxon) ? $taxon['author_id'] : null;
        $senior = array_key_exists('senior_id', $taxon) ? $taxon['senior_id'] : null;
        $notes = array_key_exists('notes', $taxon) ? $taxon['notes'] : null;
        $mobot = array_key_exists('mobotkey', $taxon) ? $taxon['mobotkey'] : null;
        $ipni = array_key_exists('ipnikey', $taxon) ? $taxon['ipnikey'] : null;
        $mycobankkey = array_key_exists('mycobankkey', $taxon) ? $taxon['mycobankkey'] : null;
        $gbifkey = array_key_exists('gbifkey', $taxon) ? $taxon['gbifkey'] : null;
        $zoobankkey = array_key_exists('zoobankkey', $taxon) ? $taxon['zoobankkey'] : null;

        $values = [
            'level' => $level,
            'parent_id' => $parent,
            'valid' => $valid,
            'senior_id' => $senior,
            'author' => $author,
            'author_id' => $author_id,
            'bibreference' => $bibreference,
            'bibreference_id' => array_key_exists('bibkey', $taxon) ? $taxon['bibkey'] : null,
            'notes' => $notes,
        ];
        //$this->skipEntry($taxon, 'taxon '.$name.' ARRIVED  here to be save');
        //return;

        $newtaxon = new Taxon($values);
        $newtaxon->fullname = $name;
        $newtaxon->save();
        //sleep(2);
        if (!is_null($mobot)) {
          $newtaxon->setapikey('Mobot', $mobot);
        }
        if (!is_null($ipni)) {
          $newtaxon->setapikey('IPNI', $ipni);
        }
        if (!is_null($gbifkey)) {
          $newtaxon->setapikey('GBIF', $gbifkey);
        }
        if (!is_null($zoobankkey)) {
          $newtaxon->setapikey('ZOOBANK', $zoobankkey);
        }
        if (!is_null($mycobankkey)) {
          $newtaxon->setapikey('Mycobank', $mycobankkey);
        }
        $newtaxon->save();
        $this->affectedId($newtaxon->id);

        return;
    }


    public function importRelated(&$taxon)
    {
      $related_to_import = isset($taxon['related_to_import']) ? $taxon['related_to_import'] : [];
      $parent = isset($taxon['parent']) ? $taxon['parent'] : null;
      $senior = isset($taxon['senior']) ? $taxon['senior'] : null;

      //$parent_id = isset($related_to_import[0]['parent_id']) ? $related_to_import[0]['parent_id'] : 1;
      //$this->appendLog(serialize($related_to_import));
      //return false;
      $finalid = null;
      foreach($related_to_import as $related) {
            if (!isset($previous_id)) {
              $previous_id = $related['parent_id'];
            }
            $values = [
                'level' => $related['rank'],
                'parent_id' => $previous_id,
                'valid' => $related['valid'],
                'author' => $related['author'],
                'bibreference' => $related['reference'],
            ];
            $newtaxon = new Taxon($values);
            $newtaxon->fullname = $related['name'];
            $newtaxon->save();
            if (isset($related['mobot'])) {
              $newtaxon->setapikey('Mobot', $related['mobot']);
            }
            if (isset($related['ipni'])) {
              $newtaxon->setapikey('IPNI', $related['ipni']);
            }
            if (isset($related['gbif'])) {
              $newtaxon->setapikey('GBIF', $related['gbif']);
            }
            if (isset($related['zoobank'])) {
              $newtaxon->setapikey('ZOOBANK', $related['zoobank']);
            }
            if (isset($related['mycobank'])) {
              $newtaxon->setapikey('Mycobank', $related['zoobank']);
            }
            $newtaxon->save();
            $previous_id = $newtaxon->id;
            if ($related['name']==$parent) {
              $taxon['parent_id'] = $previous_id;
            }
            if ($related['name']==$senior) {
              $taxon['senior_id'] = $previous_id;
            }
    }
    return true;
  }
}
