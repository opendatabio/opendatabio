<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use App\UserJob;
use Auth;
use Lang;
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
        $jobs = Auth::user()->userjobs()->paginate(20);

        return view('userjobs.index', compact('jobs'));
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
