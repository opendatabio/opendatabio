<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Config;
use Session;

class WelcomeController extends Controller
{
    public function index()
    {
        return view('welcome');
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
