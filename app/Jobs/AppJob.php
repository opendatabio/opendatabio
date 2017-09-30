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
    protected $userjob, $log, $errors;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(UserJob $userjob)
    {
	    $this->userjob = $userjob;
	    $this->log = "";
	    $this->errors = false;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function inner_handle() {
        Log::info("Running inner handle");
        Log::info ("id: #" . $this->job->getJobId() . "#");
        Log::info ("queue: #" . $this->job->getQueue() . "#");
	    // Virtual!!
    }
    public function setError() {
	    $this->errors = true;
    }
    public function appendLog($text) {
	    $this->log .= $text;
    }
    public function handle()
    {
	    $this->userjob->setProcessing();
        $this->userjob->job_id = $this->job->getJobId();
        $this->userjob->save();
	    DB::beginTransaction();
	    try {
		    $this->inner_handle();
		    if ($this->errors) {
			    DB::rollback();
			    $this->userjob->setFailed($this->log);
		    } else {
			    DB::commit();
			    $this->userjob->setSuccess($this->log);
		    }
	    } catch (\Exception $e) {
			    DB::rollback();
			    $this->log .= "EXCEPTION " . $e->getMessage();
			    $this->userjob->setFailed($this->log);
	    }
    }
}
