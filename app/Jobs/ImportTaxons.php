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

        //check if taxon is already in database before validating the API
        $parent = isset($taxon['parent_name']) ? $taxon['parent_name'] : (isset($taxon['parent']) ? $taxon['parent'] : null);
        if (!is_null($parent)) {
          if (!$this->validateParent($taxon)) {
            return false;
          }
          $name = $taxon['name'];
          $parent_id = $taxon['parent_id'];
          $hadtaxon = Taxon::whereRaw('odb_txname(name, level, parent_id) = ? AND parent_id = ?', [$name, $parent_id])->count();
          if ($hadtaxon>0) {
              $this->appendLog('WARNING: taxon '.$name.' under the parent '.$parent.' is already imported into the database');
              return false;
          }
        }

        //VALIDATE MOBOT, IPNI, MYCOBANK, GBIF AND ZOOBANK apis
        $this->validateAPIs($taxon);

        if (!$this->validateParentAndLevel($taxon)) {
              return false;
        }
        if (!$this->validateSeniorAndValid($taxon)) {
              return false;
        }
        //for unpublished names validation must include a check of persons id
        //then assumes that it is an unpublished name and requires a valid person
        if (!isset($taxon['apiwaschecked']) ) {
          $this->validatePerson($taxon);
        }

        //IF BIBKEY PROVIDED VALIDATE
        //$this->appendLog("GOT HERE 04");
        if (array_key_exists('bibkey',$taxon)) {
          if (!$this->validateBibKey($taxon['bibkey']))
          {
              return false;
          }
        }
        //if author is not informed for levels diferent than clade, then information is missing
        $condition = (!isset($taxon['author_id']) and !isset($taxon['author']) and $taxon['level']!=Taxon::getRank('clade'));
        if ($condition)
        {
          $this->skipEntry($taxon, 'Author is mandatory for this record');
          return false;
        }
        //$this->appendLog("GOT HERE 06");
        //or clade is the level and bibreference is missing, then issues a warning only,
        $condition = ($taxon['level']==Taxon::getRank('clade') and !isset($taxon['bibreference']) and !isset($taxon['bibkey']));
        $condition2 = ($taxon['level']==Taxon::getRank('clade') and !isset($taxon['author']) and !isset($taxon['author_id']));
        if ($condition and $condition2 )
        {
          $this->appendLog("WARNING: taxon ".$taxon['name']." was registered without a bibreference or a Authorship. Consider adding this information to the record");
          //return false;
        }
        //$this->appendLog("GOT HERE 07");
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
        $apidata = app('App\Http\Controllers\TaxonController')->checkapis($request);
        $apidata = $apidata->getData()->apidata;

        //if level and author the api has found something by the name informed
        //if this is the case all fields that could be retrieved by the api are informed
        if (null != $apidata[0] and null != $apidata[0]) {
          $info_level = isset($taxon['level']) ? Taxon::getRank($taxon['level']) : null;
          if (is_null($info_level)) {
            $taxon['level'] = $apidata[0];
          } elseif (!is_null($info_level) and $info_level != $apidata[0] ) {
            $apilevel = Lang::get('levels.tax.'.$apidata[0]);
            $this->appendLog('WARNING: the informed level "'.$taxon['level'].'" for taxon "'.$taxon['name'].'"  is different from the API detected level: "'.$apilevel.'". The informed level was used for the record.');
          }
          //if this true, the informed parent exists and was validated (requires the validationParent to be executed before validateAPIs)
          $taxon['author'] = $apidata[1];
          $taxon['author_id'] = null;
          $taxon['bibreference'] = (null == $apidata[3] and isset($taxon['bibreference'])) ? $taxon['bibreference'] : $apidata[3];
          if (is_array($apidata[4])) {
            if (is_null($taxon['parent_id'])) {
              $taxon['parent_id'] =  $apidata[4][0];
            }
            //if this true, the informed parent exists and was validated (requires the validationParent to be executed before validateAPIs)
            if ($taxon['parent_id'] != $apidata[4][0]) {
                $this->appendLog('WARNING: the parent '.$taxon['parent'].'  informed for taxon '.$taxon['name'].' is different from the one found by the API: '.$apidata[4][1].'. The informed parent was used for the record.');
            } else {
              $taxon['parent'] =  $apidata[4][1];
            }
          }
          if (is_array($apidata[5])) {
            $taxon['senior_id'] = $apidata[5][0];
            $taxon['senior'] = $apidata[5][1];
          }
          $taxon['mobotkey'] = $apidata[6];
          $taxon['ipnikey'] = $apidata[7];
          $taxon['mycobankkey'] = $apidata[8];
          $taxon['gbifkey'] = $apidata[9];
          //if API did not found a registered parent, but parent was informed, check if it is already registered
          $taxon['apiwaschecked'] = 1;
          //return true;
        }

        //then maybe this is un unpublished name and will be and either person or author_id must have benn informed
        //if (!isset($taxon['person']) and !isset($taxon['author_id']) and !isset($taxon['author'])) {
          //  return false;
        //}
        return true;
    }

    protected function validateParentAndLevel(&$taxon)
    {
        //will only test if api validation has not already found a registered parent
        if (!isset($taxon['parent_id'])) {
           if (!$this->validateParent($taxon)) {
             return false;
           }
        }

        if (!$this->validateLevel($taxon)) {
            return false;
        }
        //if taxon is genus or below it must have a parent taxon assigned and so a parent value must have been validated
        if (($taxon['level'] > 180) and (null === $taxon['parent_id'])) {
            $this->skipEntry($taxon, 'Parent for taxon '.$taxon['name'].' is required!');
            return false;
        }
        if (is_null($taxon['parent_id'])) {
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

    //must be used afeter validateAPIs or will always fail
    protected function validateParent(&$taxon)
    {
        $parent = isset($taxon['parent_name']) ? $taxon['parent_name'] : (isset($taxon['parent']) ? $taxon['parent'] : null);
        if (!is_null($parent)) {
          // parent might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
          $checkedparent = $this->getTaxonId($parent);
          if (null === $checkedparent) {
              $name = $taxon['name'];
              $this->skipEntry($taxon, "Parent for taxon $name is listed as $parent, but this was not found in the database");
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
            $ref = Taxon::findOrFail($ref);
        } else {
            $ref = Taxon::whereRaw('odb_txname(name, level, parent_id) = ?', [$ref])->get();
        }
        if ($ref->count()) {
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
        if (!isset($taxon['senior_id'])) {
            //if (!array_key_exists('valid', $taxon)) {
            $taxon['valid'] = true;
            //}
            //$taxon
            return $taxon['valid'];
        } else {
            //if (!array_key_exists('valid', $taxon)) {
            $taxon['valid'] = false;
            //}
            //returns true
            return !$taxon['valid'];
        }
    }

    public function import($taxon)
    {
        $name = $taxon['name'];
        $parent = $taxon['parent_id'];
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




        // Is this taxon already imported?
        $hadtaxon = Taxon::whereRaw('odb_txname(name, level, parent_id) = ? AND parent_id = ?', [$name, $parent])->count();
        if ($hadtaxon>0) {
            $parentname = Taxon::findOrFail($parent)->fullname;
            $this->appendLog("ERROR: taxon '$name' under parent '$parentname' is already imported into the database");
            return;
        }

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

        $taxon = new Taxon($values);
        $taxon->fullname = $name;
        $taxon->save();
        if (!is_null($mobot)) {
          $taxon->setapikey('Mobot', $mobot);
        }
        if (!is_null($ipni)) {
          $taxon->setapikey('IPNI', $ipni);
        }
        if (!is_null($gbifkey)) {
          $taxon->setapikey('GBIF', $gbifkey);
        }
        if (!is_null($zoobankkey)) {
          $taxon->setapikey('ZOOBANK', $zoobankkey);
        }
        if (!is_null($mycobankkey)) {
          $taxon->setapikey('Mycobank', $mycobankkey);
        }

        $taxon->save();
        $this->affectedId($taxon->id);

        return;
    }
}
