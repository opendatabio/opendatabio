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

            if (!$this->hasRequiredKeys(['name'], $taxon)) {
                continue;
            }
            // Arrived here: let's import it!!
            try {
                $this->import($taxon);
            } catch (\Exception $e) {
                $this->setError();
                $this->appendLog('Exception '.$e->getMessage().' on taxon '.$taxon['name']);
            }
        }
    }

    public function import($taxon)
    {
        // First, the easy case. We receive name, level, parent, etc.
        $name = $taxon['name'];
        $level = array_key_exists('level', $taxon) ? $taxon['level'] : null;
        if (!is_numeric($level) and !is_null($level)) {
            $level = Taxon::getRank($level);
        }
        if (is_null($level)) {
            $this->appendLog("WARNING: Level for taxon $name not available. Skipping import...");

            return;
        }
        // parent might be numeric (ie, already the ID) or a name. if it's a name, let's get the id
        $parent = array_key_exists('parent_name', $taxon) ? $taxon['parent_name'] : null;
        if (!is_numeric($parent) and !is_null($parent)) {
            $parent_obj = Taxon::whereRaw('odb_txname(name, level, parent_id) = ?', [$parent])->get();
            if ($parent_obj->count()) {
                $parent = $parent_obj->first()->id;
            } else {
                $this->appendLog("WARNING: Parent for taxon $name is listed as $parent, but this was not found in the database.");

                return;
            }
        }
        if ($level > 180 and !$parent) {
            $this->appendLog("WARNING: Parent for taxon $name is required!");

            return;
        }
        // TODO: several other validation checks
        $bibreference = array_key_exists('bibreference', $taxon) ? $taxon['bibreference'] : null;
        $author = array_key_exists('author', $taxon) ? $taxon['author'] : null;
        // if no "valid" field is present, presume it's valid
        $valid = array_key_exists('valid', $taxon) ? $taxon['valid'] : true;
        // TODO: senior_id
        // Is this taxon already imported?
        if (Taxon::whereRaw('odb_txname(name, level, parent_id) = ? AND parent_id = ?', [$name, $parent])->count() > 0) {
            $this->appendLog('WARNING: taxon '.$name.' already imported to database');

            return;
        }
        // Set the API Keys. If blank, try to get them from the right API
        // TODO: check / add level, parent, etc from APIs??
        $apis = new ExternalAPIs();
        $mobot = array_key_exists('mobot', $taxon) ? $taxon['mobot'] : null;
        if (!$mobot) {
            $mobotdata = $apis->getMobot($name);
            if (!is_null($mobotdata) && array_key_exists('key', $mobotdata)) {
                $mobot = $mobotdata['key'];
            }
        }
        $ipni = array_key_exists('mobot', $taxon) ? $taxon['mobot'] : null;
        if (!$ipni) {
            $ipnidata = $apis->getIPNI($name);
            if (!is_null($ipnidata) && array_key_exists('key', $ipnidata)) {
                $ipni = $ipnidata['key'];
            }
        }

        $taxon = new Taxon([
            'level' => $level,
            'parent_id' => $parent,
            'valid' => $valid,
            'author' => $author,
            'bibreference' => $bibreference,
        ]);
        $taxon->fullname = $name;
        $taxon->save();

        if ($mobot) {
            $taxon->setapikey('Mobot', $mobot);
        }
        if ($ipni) {
            $taxon->setapikey('IPNI', $ipni);
        }
        $taxon->save();
        $this->affectedId($taxon->id);

        return;
    }
}
