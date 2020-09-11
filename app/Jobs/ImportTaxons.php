<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Taxon;
use App\ExternalAPIs;
use App\Person;


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
        //if is unpublished, then there is no need to validade APIs
        if (!$this->validateAPIs($taxon)) {
            return false;
        }
        if (!$this->validateParentAndLevel($taxon)) {
            return false;
        }
        if (!$this->validateSeniorAndValid($taxon)) {
            return false;
        }
        //for unpublished names validation must include a check of persons id
        if (!$this->validadeAuthorID($taxon)) {
            return false;
        }
        //IF BIBKEY PROVIDED VALIDATE
        if (array_key_exists('bibkey',$taxon)) {
          if (!$this->validateBibKey($taxon['bibkey']))
          {
              return false;
          }
        }
        // TODO: several other validation checks

        return true;
    }

    protected function validateAPIs(&$taxon)
    {
        // TODO: check / add level, parent, etc from APIs??
        $apis = new ExternalAPIs();
        $name = $taxon['name'];

        // MOBOT
        $mobotdata['key'] = array_key_exists('mobot', $taxon) ? $taxon['mobot'] : null;
        if (!$mobotdata['key']) {
            try {
                $mobotdata = $apis->getMobot($name);
                if (!$mobotdata['key']) {
                    $mobotdata['key'] = null;
                }
                $taxon['mobot'] = $mobotdata;
            } catch (\Exception $e) {
                // Ignore any Excepetion when tring to found mobot information
                //$taxon['mobot'] = $mobotdata;
            }
        } else {
          //if  a value was informed add it in an array format into the current position, otherwise, import will fail because 'mobot' is not an array
          $taxon['mobot'] = $mobotdata;
        }

        // IPNI
        $ipnidata['key'] = array_key_exists('ipni', $taxon) ? $taxon['ipni'] : null;
        if (!$ipnidata['key']) {
            try {
                $ipnidata = $apis->getIPNI($name);
                if (!array_key_exists('key', $ipnidata)) {
                    $ipnidata['key'] = null;
                }
                $taxon['ipni'] = $ipnidata;
            } catch (\Exception $e) {
                // Ignore any Excepetion when tring to found ipni information
                //but add empty field if not exists, othewise will fail the importation
                //$taxon['ipni'] = $ipnidata;
            }
        } else {
          //if  a value was informed add it in an array format into the current position, otherwise, import will fail because 'ipni' is not an array
          $taxon['ipni'] = $ipnidata;
        }

        return true;
    }

    protected function validateParentAndLevel(&$taxon)
    {
        if (!$this->validateLevel($taxon)) {
            return false;
        }
        if (!$this->validateParent($taxon)) {
            return false;
        }
        if (($taxon['level'] > 180) and (null === $taxon['parent_name'])) {
            $this->skipEntry($taxon, 'Parent for taxon '.$taxon['name'].' is required!');

            return false;
        }

        return true;
    }

    protected function validateSeniorAndValid(&$taxon)
    {
        if (!$this->validateSenior($taxon)) {
            return false;
        }
        if (!$this->validateValid($taxon)) {
            return false;
        }

        return true;
    }

    protected function validadeAuthorID(&$taxon)
    {
        //check if exists in persons table
       if (array_key_exists("author_id",$taxon)) {
        $pess = $this->getAuthorId($taxon['author_id']);
        if (null === $pess) {
            $name = $taxon['name'];
            $pess = $taxon['author_id'];
            $this->skipEntry($taxon, "Author_id for unpublished taxon $name is listed as $pess, but this was not found in the database");
            return false;
        } else {
            $taxon['author_id'] = $pess;
            return true;
        }
      } else {
        //is not a morphotype
        return true;
      }

    }
    //validate person if informed as taxon author
    protected function getAuthorId($ref)
    {
        if (is_null($ref)) {
            return null;
        }
        // ref might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
        if (is_numeric($ref)) {
            $ref = Person::select('id')->where('id', '=', $ref)->get();
        } else {
            return null;
        }
        if (count($ref)) {
            return $ref->first()->id;
        }
        return null;
    }

    protected function validateLevel(&$taxon)
    {
        $level = array_key_exists('level', $taxon) ? $taxon['level'] : null;
        if (!is_numeric($level) and !is_null($level)) {
            $level = Taxon::getRank($level);
        }
        if (is_null($level)) {
            $name = $taxon['name'];
            $this->skipEntry($taxon, "Level for taxon $name not available");
            return false;
        }
        $taxon['level'] = $level;

        return true;
    }

    protected function validateParent(&$taxon)
    {
        if (!array_key_exists('parent_name', $taxon)) {
            $taxon['parent_name'] = $this->getTaxonIdFromAPI($taxon, 'parent');

            return true;
        }
        // parent might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
        $parent = $this->getTaxonId($taxon['parent_name']);
        if (null === $parent) {
            $name = $taxon['name'];
            $parent = $taxon['parent_name'];
            $this->skipEntry($taxon, "Parent for taxon $name is listed as $parent, but this was not found in the database");

            return false;
        } else {
            $taxon['parent_name'] = $parent;

            return true;
        }
    }

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

    protected function validateSenior(&$taxon)
    {
        if (!array_key_exists('senior', $taxon)) {
            $taxon['senior'] = $this->getTaxonIdFromAPI($taxon, 'senior');
            return true;
        }
        // parent might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
        $senior = $this->getTaxonId($taxon['senior']);
        if (null === $senior) {
            $name = $taxon['name'];
            $senior = $taxon['senior'];
            $this->skipEntry($taxon, "Senior for taxon $name is listed as $senior, but this was not found in the database");

            return false;
        } else {
            $taxon['senior'] = $senior;

            return true;
        }
    }

    protected function getTaxonId($ref)
    {
        if (is_null($ref)) {
            return null;
        }
        // ref might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
        if (is_numeric($ref)) {
            $ref = Taxon::select('id')->where('id', '=', $ref)->get();
        } else {
            $ref = Taxon::select('id')->whereRaw('odb_txname(name, level, parent_id) = ?', [$ref])->get();
        }
        if (count($ref)) {
            return $ref->first()->id;
        }

        return null;
    }

    protected function validateBibKey(&$bibkey)
    {
      $valid = BibReference::whereRaw('odb_bibkey(bibtex) = ?', [$bibkey])->get();
      if (null === $valid) {
        $this->appendLog('Provided bibkey '.$bibkey.' not found in database');
        return false;
      }
      $bibkey = $valid->id;
      return true;
    }

    protected function validateValid(&$taxon)
    {
        if (null === $taxon['senior']) {
            if (!array_key_exists('valid', $taxon)) {
                $taxon['valid'] = true;
            }
            return $taxon['valid'];
        } else {
            if (!array_key_exists('valid', $taxon)) {
                $taxon['valid'] = false;
            }
            return !$taxon['valid'];
        }
    }

    public function import($taxon)
    {
        $name = $taxon['name'];
        $parent = $taxon['parent_name'];
        $level = $taxon['level'];
        $valid = $taxon['valid'];
        $bibreference = array_key_exists('bibreference', $taxon) ? $taxon['bibreference'] : null;
        $author = array_key_exists('author', $taxon) ? $taxon['author'] : null;
        $author_id = array_key_exists('author_id', $taxon) ? $taxon['author_id'] : null;
        $senior = array_key_exists('senior', $taxon) ? $taxon['senior'] : null;
        $notes = array_key_exists('notes', $taxon) ? $taxon['notes'] : null;
        $mobot = array_key_exists('mobot', $taxon) ? $taxon['mobot'] : null;
        $ipni = array_key_exists('ipni', $taxon) ? $taxon['ipni'] : null;
        // Is this taxon already imported?
        if (Taxon::whereRaw('odb_txname(name, level, parent_id) = ? AND parent_id = ?', [$name, $parent])->count() > 0) {
            $this->skipEntry($taxon, 'taxon '.$name.' already imported to database');

            return;
        }

        $taxon = new Taxon([
            'level' => $level,
            'parent_id' => $parent,
            'valid' => $valid,
            'senior_id' => $senior,
            'author' => $author,
            'author_id' => $author_id,
            'bibreference' => $bibreference,
            'bibreference_id' => array_key_exists('bibkey', $taxon) ? $taxon['bibkey'] : null,
            'notes' => $notes,
        ]);
        $taxon->fullname = $name;
        $taxon->save();
        if (is_array($mobot)) {
          if ($mobot['key']) {
              $taxon->setapikey('Mobot', $mobot['key']);
            }
        }
        if (is_array($ipni)) {
          if ($ipni['key']) {
              $taxon->setapikey('IPNI', $ipni['key']);
          }
        }
        $taxon->save();
        $this->affectedId($taxon->id);

        return;
    }
}
