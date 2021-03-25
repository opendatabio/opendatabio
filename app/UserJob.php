<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Auth;
use Log;
use File;

class UserJob extends Model
{
    protected $fillable = ['dispatcher', 'log', 'status', 'rawdata', 'user_id'];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    // event fired from job on success
    public function setSuccess()
    {
        $this->status = 'Success';
        $this->save();
    }

    // event fired from job on failure
    public function setFailed()
    {
        $this->status = 'Failed';
        $this->save();
    }

    // event fired from job when it starts processing
    public function setProcessing()
    {
        $this->status = 'Processing';
        $this->save();
    }

    // user sent a "retry" from the interface
    public function retry()
    {
        $this->update(['status' => 'Submitted']);
        $job = $this->dispatcher::dispatch($this);
    }

    public function getDataAttribute()
    {
        return unserialize($this->rawdata);
    }

    public function setProgressMax($value)
    {
        $this->progress_max = $value;
        $this->progress = 0;
        $this->save();
    }

    public function tickProgress()
    {
        ++$this->progress;
        $this->save();
    }

    // show formatted progress
    public function getPercentageAttribute()
    {
        if (0 == $this->progress_max) {
            return ' - %';
        }

        return round(100 * $this->progress / $this->progress_max).'%';
    }

    // entry point for jobs. place the job on queue
    public static function dispatch($dispatcher, $rawdata)
    {
        // create Job entry
        $userjob = self::create(['dispatcher' => $dispatcher, 'rawdata' => serialize($rawdata), 'user_id' => Auth::user()->id]);
        $job = $dispatcher::dispatch($userjob);

        return $userjob->id;
    }

    public function getSubmittedFileAttribute()
    {
      if (!isset($this->data['data'])) {
        return null;
      }
      $data = $this->data['data'];
      return isset($data['filename']) ? $data['filename'] : null;
    }


}
