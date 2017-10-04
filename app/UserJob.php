<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Log;

class UserJob extends Model
{
	protected $fillable = ['dispatcher', 'log', 'status', 'rawdata', 'user_id'];
	public function user() {
		return $this->belongsTo('App\User');
	}

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
		$this->update(['status' => 'Submitted', 'log' => '']);
        $job = $this->dispatcher::dispatch($this);
	}
    public function getDataAttribute() {
        return unserialize($this->rawdata);
    }

    public function setProgressMax($value) {
        $this->progress_max = $value;
        $this->progress = 0;
        $this->save();
    }

    public function tickProgress() {
        $this->progress++;
        $this->save();
    }

    // show formatted progress
    public function getPercentageAttribute() {
        if ($this->progress_max == 0)
            return " - %";
        return round(100 * $this->progress / $this->progress_max) . "%";
    }

	// entry point for jobs. place the job on queue
	static public function dispatch($dispatcher, $rawdata) {
		// create Job entry
		$userjob = UserJob::create(['dispatcher' => $dispatcher, 'rawdata' => serialize($rawdata), 'user_id' => Auth::user()->id]);
        $job = $dispatcher::dispatch($userjob);
        return $userjob->id;
	}
}