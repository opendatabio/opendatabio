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
        $this->userjob->setProgressMax(count($data));
        foreach($data as $taxon) {
            // has this job been cancelled?
            // calls "fresh" to make sure we're not receiving a cached object
            if ($this->userjob->fresh()->status == "Cancelled") {
                $this->appendLog ("WARNING: received CANCEL signal");
                break;
            }
            $this->userjob->tickProgress();

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
                $this->appendLog ("WARNING: taxon " . $taxon['name'] . " already imported to database");
                continue;
            } 
            // Arrived here: let's import it!!
            try {
                $this->import($taxon);
                $this->appendLog ($taxon['name'] . " imported");
            } catch (Exception $e) { $this->appendLog("Exception ".$e->message() . " on taxon " . $taxon['name']); }
        }
    }
    public function import($taxon) {
        // First, the easy case. We receive name, level and parent, and don't attempt to guess anything
        $name = $taxon['name'];
        $level = array_key_exists('level', $taxon) ? $taxon['level'] : null;
        $level = Taxon::getRank($level);
        $parent = array_key_exists('parent_name', $taxon) ? $taxon['parent_name'] : null;
        if ($parent) {
            $parent_obj = Taxon::whereRaw('odb_txname(name, level, parent_id) = ?', [$parent])->get();
            $parent = $parent_obj->count() ? $parent_obj->first()->id : null;
        }
        Taxon::create(['name' => $name, 'level' => $level, 'parent_id' => $parent, 'valid' => 1]);
    }
}
