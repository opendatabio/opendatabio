<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserJobs;
use Auth;
use Lang;

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
		    UserJobs::findOrFail($id)->delete();
	    } catch (\Illuminate\Database\QueryException $e) {
		    return redirect()->back()
			    ->withErrors([Lang::get('messages.fk_error')])->withInput();
	    }

	return redirect('userjobs')->withStatus(Lang::get('messages.removed'));
    }
	public function cancel($id) {
		$job = UserJobs::findOrFail($id);
		$job->status = 'Cancelled';
		$job->save();
		return redirect('userjobs')->withStatus(Lang::get('messages.saved'));
	}
	public function retry($id) {
		$job = UserJobs::findOrFail($id);
		$job->retry();
		return redirect('userjobs')->withStatus(Lang::get('messages.saved'));
	}

    // TODO
}
