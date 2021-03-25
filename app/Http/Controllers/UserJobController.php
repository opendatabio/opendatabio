<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use App\UserJob;
use Illuminate\Support\Arr;
use Auth;
use Lang;
use File;
use Storage;
use Queue;

class UserJobController extends Controller
{
    public function __construct()
    {
        // needs this for Auth::user
        $this->middleware('auth');
    }

    public function index()
    {
        $jobs = Auth::user()->userjobs()->orderBy('id', 'DESC')->paginate(20);

        return view('userjobs.index', compact('jobs'));
    }


    //delete files of user downloads and exports when deleting its job
    //files MUST BE STORED WITH PREFIX 'job-'.$id."_whatever.*'
    public static function deleteJobFiles($id)
    {
      $files = scandir(public_path('downloads_temp'));
      $todelete = Arr::where($files, function ($value, $key) use($id) {
          $fn = explode("_",$value);
          if ($fn[0] == "job-".$id) {
            return $value;
          }
      });
      //should be just one file found
      if (count($todelete)) {
        $filename = public_path('downloads_temp/'.array_values($todelete)[0]);
        File::delete($filename);
        if (!file_exists($filename)) {
          return true;
        }
      }
      return false;
    }


    public function destroy($id)
    {
        $userjob = UserJob::findOrFail($id);
        $this->authorize('delete', $userjob);
        try {
            $job_id = $userjob->job_id;
            $userjob->delete();
            if (!empty($job_id)) {
                Queue::deleteReserved(config('queue.default'), $job_id);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()
                ->withErrors([Lang::get('messages.fk_error')])->withInput();
        }
        $file_deleted = self::deleteJobFiles($id);

        /* if job is a web interface import it has a file */
        if ($userjob->submitted_file) {
          $path = storage_path('app/public/tmp/'.$userjob->submitted_file);
          File::delete($path);
        }

        return redirect('userjobs')->withStatus(Lang::get('messages.removed'));
    }

    public function cancel($id)
    {
        $job = UserJob::findOrFail($id);
        $this->authorize('update', $job);
        $job->status = 'Cancelled';
        $job_id = $job->job_id;
        $job->job_id = null;
        $job->save();
        if (!empty($job_id)) {
            Queue::deleteReserved(config('queue.default'), $job_id);
        }

        return redirect('userjobs')->withStatus(Lang::get('messages.saved'));
    }

    public function retry($id)
    {
        $job = UserJob::findOrFail($id);
        $this->authorize('update', $job);
        $job->retry();

        return redirect('userjobs')->withStatus(Lang::get('messages.saved'));
    }

    public function show($id)
    {
        $job = UserJob::findOrFail($id);
        $this->authorize('view', $job);

        return view('userjobs.show', compact('job'));
    }
}
