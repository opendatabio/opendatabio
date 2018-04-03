<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Http\Request;
use Response;
use Auth;

class UserJobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!Auth::user()) {
            return Response::json(['message' => 'Unauthenticated', 401]);
        }
        $jobs = Auth::user()->userjobs();
        if ($request->status) {
            $jobs->where('status', '=', $request->status);
        }
        if ($request->id) {
            $jobs->whereIn('id', explode(',', $request->id));
        }
        $jobs = $jobs->get();

        $fields = ($request->fields ? $request->fields : 'simple');
        $jobs = $this->setFields($jobs, $fields, ['id', 'dispatcher', 'status', 'percentage', 'created_at', 'updated_at']);

        return $this->wrap_response($jobs);
    }
}
