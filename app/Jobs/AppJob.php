<?php

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

// All app jobs must extend this:
// This class intermediates between the jobs dispatched and the UserJob model
class AppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $userjob, $errors;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(UserJob $userjob)
    {
	    $this->userjob = $userjob;
        $this->userjob->log = json_encode([]);
        $this->userjob->save();
	    $this->errors = false;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function inner_handle() {
	    // Virtual method!! Should be implemented by all jobs
        Log::info("Running inner handle");
        Log::info("id: #" . $this->job->getJobId() . "#");
        Log::info("queue: #" . $this->job->getQueue() . "#");
    }
    public function setError() {
	    $this->errors = true;
    }
    public function appendLog($text) {
        $log = json_decode($this->userjob->fresh()->log, true);
        array_push($log, $text);
	    $this->userjob->log = json_encode($log);
        $this->userjob->save();
        Log::info($text);
    }
    public function handle()
    {
        // temporarily removing rollback capabilities:
	    $this->userjob->setProcessing();
        $this->userjob->job_id = $this->job->getJobId();
        $this->userjob->save();
//	    DB::beginTransaction();
	    try {
		    $this->inner_handle();
            // mark jobs with reported errors as "Failed", EXCEPT if they have already been cancelled
		    if ($this->errors and $this->userjob->fresh()->status != "Cancelled") {
//			    DB::rollback();
			    $this->userjob->setFailed();
		    } else {
//			    DB::commit();
			    $this->userjob->setSuccess();
		    }
	    } catch (\Exception $e) {
//			    DB::rollback();
			    $this->appendLog("BLOCKING EXCEPTION " . $e->getMessage());
			    $this->userjob->setFailed();
	    }
    }
}
