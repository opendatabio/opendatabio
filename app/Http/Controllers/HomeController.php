<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use App\Plant;
use App\Voucher;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
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
