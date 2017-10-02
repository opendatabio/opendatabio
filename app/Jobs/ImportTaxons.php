<?php

namespace App\Jobs;

use Illuminate\Database\Eloquent\Model;
use App\Taxon;
use App\Jobs\AppJob;
use Log;

class ImportTaxons extends AppJob
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function inner_handle()
    {
        $data = $this->userjob->data['data'];
        if (! count($data)) {
            $this->setError();
            $this->appendLog ("ERROR: data received is empty!");
            return;
        }
        foreach($data as $taxon) {
            if (! is_array($taxon)) {
                $this->setError();
                $this->appendLog ("ERROR: taxon entry is not formatted as array!" . serialize($taxon));
                continue;
            }
            if (! array_key_exists('name', $taxon)) {
                $this->setError();
                $this->appendLog ("ERROR: entry needs a name: " . implode(';',$taxon));
                continue;
            }
            // Is this taxon already imported? 
            if(Taxon::whereRaw('odb_txname(name, level, parent_id) = ?', [$taxon['name']])->count() > 0) {
                $this->setError();
                $this->appendLog ("WARNING: taxon " . $slug . " already imported to database");
                continue;
            } 
            // Arrived here: let's import it!!
            $level = array_key_exists('level', $taxon) ? $taxon['level'] : null;

            $this->appendLog ($taxon['name'] . " imported");
        }
    }
}
