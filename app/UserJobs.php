<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

class UserJobs extends Model
{
	protected $fillable = ['dispatcher', 'log', 'status', 'rawdata', 'user_id'];

	// event fired from job on success
	public function setSuccess() {
		$this->status = 'Success';
		$this->save();
	}
	// event fired from job on failure
	public function setFailed() {
		$this->status = 'Failed';
		$this->save();
	}
	// event fired from job when it starts processing
	public function setProcessing() {
		$this->status = 'Processing';
		$this->save();
	}
	// user sent a "cancel" from the interface. attempt to remove job from queue
	public function cancel() {
	}
	// entry point for jobs. place the job on queue
	static public function dispatch($dispatcher, $rawdata) {
		// create Job entry
		$job = new UserJobs;
		$job->status = 'Submitted';
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
	public function sendLog() {
	}
}
