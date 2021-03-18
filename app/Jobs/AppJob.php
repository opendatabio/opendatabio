<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Illuminate\Database\Eloquent\Model;
use App\UserJob;
use DB;
use Log;
use Auth;

// All app jobs must extend this:
// This class intermediates between the jobs dispatched and the UserJob model
class AppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $userjob;
    protected $errors;
    protected $header;

    /**
     * Create a new job instance.
     */
    public function __construct(UserJob $userjob)
    {
        $this->userjob = $userjob;
        $this->userjob->log = json_encode([]);
        $this->userjob->affected_ids = $this->userjob->affected_ids ? $this->userjob->affected_ids : json_encode([]);
        $this->userjob->save();
        $this->errors = false;
    }

    /**
     * Execute the job.
     */
    public function inner_handle()
    {
        // Virtual method!! Should be implemented by all jobs
        Log::info('Running inner handle');
        Log::info('id: #'.$this->job->getJobId().'#');
        Log::info('queue: #'.$this->job->getQueue().'#');
    }

    public function setError()
    {
        $this->errors = true;
    }

    public function appendLog($text)
    {
        $log = json_decode($this->userjob->fresh()->log, true);
        array_push($log, $text);
        $this->userjob->log = json_encode($log);
        $this->userjob->save();
        Log::info($text);
    }

    public function affectedId($id)
    {
        $ids = json_decode($this->userjob->fresh()->affected_ids, true);
        array_push($ids, $id);
        $this->userjob->affected_ids = json_encode($ids);
        $this->userjob->save();
    }

    public function handle()
    {
        // temporarily removing rollback capabilities:
        Auth::loginUsingId($this->userjob->user_id);
        $this->userjob->setProcessing();
        $this->userjob->job_id = $this->job->getJobId();
        $this->userjob->save();
        //	    DB::beginTransaction();
        try {
            $this->inner_handle();
            // mark jobs with reported errors as "Failed", EXCEPT if they have already been cancelled
            if ($this->errors and 'Cancelled' != $this->userjob->fresh()->status) {
                //			    DB::rollback();
                $this->userjob->setFailed();
            } else {
                //			    DB::commit();
                $this->userjob->setSuccess();
            }
        } catch (\Exception $e) {
            //			    DB::rollback();
            $this->appendLog('BLOCKING EXCEPTION '.$e->getMessage());
            Log::warning($e->getTraceAsString());
            $this->userjob->setFailed();
        }
    }

    public function extractEntrys()
    {
        $data = $this->userjob->data['data'];
        $this->header = array_key_exists('header', $data) ? $data['header'] : array();

        return $data['data'];
    }

    public function setProgressMax($data)
    {
        if (!count($data)) {
            $this->setError();
            $this->appendLog('ERROR: data received is empty!');

            return false;
        }
        $this->userjob->setProgressMax(count($data));

        return true;
    }

    public function isCancelled()
    {
        // calls "fresh" to make sure we're not receiving a cached object
        if ('Cancelled' == $this->userjob->fresh()->status) {
            $this->appendLog('WARNING: received CANCEL signal');

            return true;
        }

        return false;
    }

    public function removeHeaderSuppliedKeys(array $keys)
    {
        $notPresent = array();
        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->header)) {
                $notPresent[] = $key;
            }
        }

        return $notPresent;
    }

    public function hasRequiredKeys($requiredKeys, $entry)
    {
        // if $entry is not an array it has not the $requiredKeys
        if (!is_array($entry)) {
            $this->skipEntry($entry, 'entry is not formatted as array');

            return false;
        }
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $entry) or (null === $entry[$key])) {
                $this->skipEntry($entry, 'entry needs a '.$key);

                return false;
            }
        }

        return true;
    }

    public function skipEntry($entry, $cause)
    {
        if (is_array($entry)) {
            $entry = json_encode($entry);
        } elseif ('object' == gettype($entry)) {
            $entry = serialize($entry);
        }
        $this->appendLog('ERROR: '.$cause.'. Skipping import of '.$entry);
    }
}
