<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserJobs;
use Auth;
use Lang;
use Queue;

class UserJobsController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
	}
	public function index() {
		$jobs = Auth::user()->userjobs()->paginate(20);

		return view('userjobs.index',[
			'jobs' => $jobs,
	]);
	}
    public function destroy($id)
    {
	    try {// TODO: gate this
		    $userjob = UserJobs::findOrFail($id);
		    $job_id = $userjob->job_id;
		    $userjob->delete();
		    if (!empty($job_id))
			    Queue::deleteReserved(config('queue.default'), $job_id);
	    } catch (\Illuminate\Database\QueryException $e) {
		    return redirect()->back()
			    ->withErrors([Lang::get('messages.fk_error')])->withInput();
	    }
	return redirect('userjobs')->withStatus(Lang::get('messages.removed'));
    }
	public function cancel($id) { // TODO: only allow cancel of Submitted jobs
		$job = UserJobs::findOrFail($id);
		$job->status = 'Cancelled';
		$job_id = $job->job_id;
		$job->job_id = null;
		$job->save();
		if (!empty($job_id))
			Queue::deleteReserved(config('queue.default'), $job_id);
		return redirect('userjobs')->withStatus(Lang::get('messages.saved'));
	}
	public function retry($id) {
		$job = UserJobs::findOrFail($id);
		$job->retry();
		return redirect('userjobs')->withStatus(Lang::get('messages.saved'));
	}
	public function show($id) {
		$job = UserJobs::findOrFail($id);
		return view('userjobs.show',[
			'job' => $job,
		]);
	}
}
