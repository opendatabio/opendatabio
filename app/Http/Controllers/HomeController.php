<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use App\Plant;
use App\Voucher;

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
        $nplants = Plant::withoutGlobalScopes()->count();
        $nvouchers = Voucher::withoutGlobalScopes()->count();

        return view('home', compact('nplants', 'nvouchers'));
    }
}
