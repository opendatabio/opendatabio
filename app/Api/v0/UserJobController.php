<?php

namespace App\Api\v0;

use App\Api\v0\Controller;
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
        if ($fields == "simple")
            $fields = ['id', 'dispatcher', 'log', 'status'];
        else 
            $fields = explode(',',$fields);
        if ($fields[0] != "all")
            $jobs = $jobs->map(function ($obj) use ($fields) {
                return collect($obj->toArray())
                    ->only($fields)
                    ->all();
            });
        return $this->wrap_response($jobs);
    }
}
