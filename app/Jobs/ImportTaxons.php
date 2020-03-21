<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Taxon;
use App\ExternalAPIs;

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
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on taxon '.$taxon['name']);
                }
            }
        }
    }

    protected function validateData(&$taxon)
    {
        if (!$this->hasRequiredKeys(['name'], $taxon)) {
            return false;
        }
        if (!$this->validateAPIs($taxon)) {
            return false;
        }
        if (!$this->validateParentAndLevel($taxon)) {
            return false;
        }
        if (!$this->validateSeniorAndValid($taxon)) {
            return false;
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
            }
        } else {
          // code...
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
            }
        } else {
          // code...
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
        if (array_key_exists($field, $taxon['mobot'])) {
            return $this->getTaxonId($taxon['mobot']['parent']);
        }
        if (array_key_exists($field, $taxon['ipni'])) {
            return $this->getTaxonId($taxon['ipni']['parent']);
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
        $bibreference = array_key_exists('bibreference', $taxon) ? $taxon['bibreference'] : null;
        $author = array_key_exists('author', $taxon) ? $taxon['author'] : null;
        $senior = $taxon['senior'];
        $valid = $taxon['valid'];
        $mobot = $taxon['mobot'];
        $ipni = $taxon['ipni'];
        // Is this taxon already imported?
        if (Taxon::whereRaw('odb_txname(name, level, parent_id) = ? AND parent_id = ?', [$name, $parent])->count() > 0) {
            $this->skipEntry($taxon, 'taxon '.$name.' already imported to database');

            return;
        }
        $banana = 1;
        if ($banana>0) {
          $this->skipEntry($taxon, 'taxon '.$name.' has mobot key '.$mobot['key']);
          return;
        }

        $taxon = new Taxon([
            'level' => $level,
            'parent_id' => $parent,
            'valid' => $valid,
            'senior_id' => $senior,
            'author' => $author,
            'bibreference' => $bibreference,
        ]);
        $taxon->fullname = $name;
        //$taxon->save();
        if ($mobot['key']) {
            $taxon->setapikey('Mobot', $mobot['key']);
        }
        if ($ipni['key']) {
            $taxon->setapikey('IPNI', $ipni['key']);
        }
        //$taxon->save();
        //$this->affectedId($taxon->id);

        return;
    }
}
