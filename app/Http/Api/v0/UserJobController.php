<?php

namespace App\Http\Api\v0;

use App\Http\Api\v0\Controller;
use Illuminate\Http\Request;
use App\UserJob;
use Lang;
use Log;
use Validator;
use Response;
use Auth;
use Illuminate\Support\MessageBag;

class UserJobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (! Auth::user())
            return Response::json(['message' => 'Unauthenticated', 401]);
        $jobs = Auth::user()->userjobs();
        if ($request->status)
            $jobs = $jobs->where('status', '=', $request->status);
        if ($request->id)
            $jobs = $jobs->where('id', '=', $request->id);
        $jobs = $jobs->get();

        $fields = ($request->fields ? $request->fields : "simple");
        $jobs = $this->setFields($jobs, $fields, ['id', 'dispatcher', 'status', 'percentage', 'created_at', 'updated_at']);
        return $this->wrap_response($jobs);
    }
}
