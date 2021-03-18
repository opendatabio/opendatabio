<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use App\Individual;
use App\Voucher;
use App\Project;
use App\Dataset;
use App\Measurement;
use Config;
use Session;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $nindividuals = Individual::withoutGlobalScopes()->count();
        $nvouchers = Voucher::withoutGlobalScopes()->count();
        $nprojects = Project::withoutGlobalScopes()->count();
        $ndatasets = Dataset::withoutGlobalScopes()->count();
        $nmeasurements = Measurement::withoutGlobalScopes()->count();

        return view('home', compact('nindividuals', 'nvouchers','nprojects','ndatasets','nmeasurements'));
    }


    // This method sets the locale for the session:
    public function setAppLocale($locale)
    {
        if (array_key_exists($locale, Config::get('languages'))) {
            Session::put('applocale', $locale);
        }

        return redirect()->back();
    }
}
