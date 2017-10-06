<?php

namespace App\Http\Api\v0;

use App\Http\Api\v0\Controller;
use Illuminate\Http\Request;
use Lang;
use Log;
use Validator;
use Response;
use Illuminate\Support\MessageBag;
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
        if ($request->id)
            $locations = $locations->whereIn('id', explode(',', $request->id));
        if ($request->search)
            $locations = $locations->where('name', 'LIKE', '%' . $request->search . '%');
        if ($request->adm_level)
            $locations = $locations->where('adm_level', '=', $request->level);
        if ($request->limit)
            $locations->limit($request->limit);
        $locations = $locations->get();

        $fields = ($request->fields ? $request->fields : "simple");
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
