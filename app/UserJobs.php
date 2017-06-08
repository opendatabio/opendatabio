<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;

class UserJobs extends Model
{
	protected $fillable = ['dispatcher', 'log', 'status', 'rawdata', 'user_id'];

	// event fired from job on success
	public function setSuccess($log = null) {
		if (!is_null($log))
			$this->log .= $log . "\n";
		$this->status = 'Success';
		$this->save();
	}
	// event fired from job on failure
	public function setFailed($log = null) {
		if (!is_null($log))
			$this->log .= $log . "\n";
		$this->status = 'Failed';
		$this->save();
	}
	// event fired from job when it starts processing
	public function setProcessing() {
		$this->status = 'Processing';
		$this->save();
	}
	// user sent a "retry" from the interface
	public function retry() {
		$this->status = 'Submitted';
		$this->log = '';
		$rawdata = unserialize($this->rawdata);
		$this->save();
		switch ($this->dispatcher) { // TODO: avoid code duplication
		case 'importbibreferences':
			$job = new \App\Jobs\ImportBibReferences($rawdata['contents'], $rawdata['standardize'], $this);
			break;
		default:
		}
		$this->job_id = dispatch($job); // saves the dispatched id
		$this->save();
	}

	// entry point for jobs. place the job on queue
	static public function dispatch($dispatcher, $rawdata) {
		// create Job entry
		$userjob = new UserJobs;
		$userjob->dispatcher = $dispatcher;
		$userjob->rawdata = serialize($rawdata);
		$userjob->user_id = Auth::user()->id;
		$userjob->save(); /// NEEDS to be 'saved' to generate an id for use in new \App\Jobs\etc
		// actually dispatch the job
		switch ($dispatcher) {
		case 'importbibreferences':
			$job = new \App\Jobs\ImportBibReferences($rawdata['contents'], $rawdata['standardize'], $userjob);
			break;
		default: // what to do here?
			throw (new \Exception ("Wrong dispatcher specified at UserJob"));
		}
		$userjob->job_id = dispatch($job); // saves the dispatched id
		$userjob->save();
	}
}
