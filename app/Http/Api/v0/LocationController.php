<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Http\Request;
use Response;
use App\Location;
use App\UserJob;
use App\Jobs\ImportLocations;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $locations = Location::query()->withGeom();
        if ($request->id) {
            $locations = $locations->whereIn('id', explode(',', $request->id));
        }
        if ($request->search) {
            $locations = $locations->where('name', 'LIKE', '%'.$request->search.'%');
        }
        if ($request->adm_level) {
            $locations = $locations->where('adm_level', '=', $request->level);
        }
        if ($request->limit) {
            $locations->limit($request->limit);
        }
        $locations = $locations->get();

        $fields = ($request->fields ? $request->fields : 'simple');
        $locations = $this->setFields($locations, $fields, ['id', 'name', 'levelName', 'geom']);

        return $this->wrap_response($locations);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Location::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportLocations::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
