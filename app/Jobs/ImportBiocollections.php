<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Biocollection;
use App\ExternalAPIs;

class ImportBiocollections extends AppJob
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
        foreach ($data as $biocollection) {
            if ($this->isCancelled()) {
                break;
            }
            $this->userjob->tickProgress();
            if ($this->validateData($biocollection)) {
                // Arrived here: let's import it!!
                try {
                    $this->import($biocollection);
                } catch (\Exception $e) {
                    $this->setError();
                    $this->appendLog('Exception '.$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on biocollection '.$biocollection['acronym'].$e->getTraceAsString());
                }

            }
        }
    }

    protected function validateData(&$biocollection)
    {
        if (!$this->hasRequiredKeys(['acronym'], $biocollection)) {
            return false;
        }

        if (!$this->validateAcronym($biocollection)) {
            return false;
        }

        return true;

    }

    protected function validateAcronym(&$biocollection)
    {
        $upperacronym = mb_strtoupper($biocollection['acronym']);
        $hasalready = Biocollection::whereRaw('UPPER(acronym) = "'.$upperacronym.'"');
        $hasname = isset($biocollection['name']) ? $biocollection['name'] : null;
        if ($hasalready->count()) {
            $this->skipEntry($biocollection, 'Acronym '.$upperacronym.' already exists');
            return false;
        }
        //check api
        $apis = new ExternalAPIs();
        $ihdata = $apis->getIndexHerbariorum($upperacronym);
        if (is_null($ihdata) and is_null($hasname)) {
            $this->skipEntry($biocollection, 'Acronym '.$upperacronym.' was not found in getIndexHerbariorum. Therefore, Name must also be informed');
            return false;
        }
        $biocollection['acronym'] = $upperacronym;
        $biocollection['irn']  = !is_null($ihdata) ? $ihdata[0] : -1;
        $biocollection['name']  = !is_null($ihdata) ? $ihdata[1] : $hasname;
        return true;
    }

    public function import($biocollection)
    {
        $values = [
            'name' =>   $biocollection['name'],
            'acronym' =>   $biocollection['acronym'],
            'irn' =>   $biocollection['irn'],
        ];
        $biocollection = Biocollection::create($values);
        //$this->appendLog('os valores sao: '.json_encode($values));
        $this->affectedId($biocollection->id);
        return;
    }
}
