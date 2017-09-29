<?php

namespace App\Api\v0;
use App\Api\v0\Controller;

class TestController extends Controller
{
    public function index()
    {
        return $this->wrap_response('Success!');
    }
}
