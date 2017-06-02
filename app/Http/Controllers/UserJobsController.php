<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserJobs;
use Auth;

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

    // TODO
}
