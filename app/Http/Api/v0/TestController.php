<?php

namespace App\Http\Api\v0;
use App\Http\Api\v0\Controller;
use Auth;

class TestController extends Controller
{
    public function index()
    {
        $user = Auth::user() ? Auth::user()->email : null;
        return $this->wrap_response(['message' => 'Success!', 'user' => $user]);
    }
}
