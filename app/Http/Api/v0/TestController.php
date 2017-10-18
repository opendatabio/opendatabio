<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Auth;

class TestController extends Controller
{
    public function index()
    {
        $user = Auth::user() ? Auth::user()->email : null;

        return $this->wrap_response(['message' => 'Success!', 'user' => $user]);
    }
}
