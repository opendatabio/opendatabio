<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Log;

class UserJobs extends Model
{
	protected $fillable = ['dispatcher', 'log', 'status', 'rawdata', 'user_id'];

	// event fired from job on success
	public function setSuccess() {
		DB::transaction(function () {
		$this->status = 'Success';
		$this->save();
		});
	}
	// event fired from job on failure
	public function setFailed() {
		DB::transaction(function () {
			Log::info ("SETTNIG FAILED");
		$this->status = 'Failed';
		$this->save();
		});
		Log::info ( $this->status);
	}
	// event fired from job when it starts processing
	public function setProcessing($howmuch = 0) {
		DB::transaction(function () use ($howmuch){
		$this->status = 'Processing';
		$this->complete = $howmuch;
		$this->save();
		});
	}
	// user sent a "retry" from the interface
	public function retry() {
		$this->status = 'Submitted';
		$rawdata = unserialize($this->rawdata);
		$this->save();
		switch ($this->dispatcher) {
		case 'importbibreferences':
			dispatch (new \App\Jobs\ImportBibReferences($rawdata['contents'], $rawdata['standardize'], $this));
			break;
		default:
		}
	}

	// entry point for jobs. place the job on queue
	static public function dispatch($dispatcher, $rawdata) {
		// create Job entry
		$job = new UserJobs;
		$job->dispatcher = $dispatcher;
		$job->rawdata = serialize($rawdata);
		$job->user_id = Auth::user()->id;
		$job->save();
		// actually dispatch the job
		switch ($dispatcher) {
		case 'importbibreferences':
			dispatch (new \App\Jobs\ImportBibReferences($rawdata['contents'], $rawdata['standardize'], $job));
			break;
		default:

		}

	}
	// event fired from job when something needs to be logged.
	public function sendLog($text) {
		DB::transaction(function () use ($text) {
		$this->log .= $text . "\n";
		$this->save();
		});
	}
}
