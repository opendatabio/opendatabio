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
        $locations = Location::select('*')->withGeom()->noWorld();
        if ($request->root) {
            $root_loc = Location::select('lft', 'rgt')->where('id', $request->root)->get()->first();
            $locations->where('lft', '>=', $root_loc['lft'])->where('rgt', '<=', $root_loc['rgt'])->orderBy('lft');
        }
        if ($request->id) {
            $locations->whereIn('id', explode(',', $request->id));
        }
        if ($request->parent_id) {
            $locations->whereIn('parent_id', explode(',', $request->parent_id));
        }
        if ($request->name) {
            $this->advancedWhereIn($locations, 'name', $request->name);
        }
        if (isset($request->adm_level)) {
            $locations->whereIn('adm_level', explode(',', $request->adm_level));
        }
        if ($request->limit) {
            $locations->limit($request->limit);
        }
        // For lat / long searches
        if ($request->querytype and isset($request->lat) and isset($request->long)) {
            $geom = "POINT($request->long $request->lat)";
            if ('exact' == $request->querytype) {
                $locations->whereRaw('AsText(geom) = ?', [$geom]);
            }
            if ('parent' == $request->querytype) {
                $parent = Location::detectParent($geom, 100, false);
                if ($parent) {
                    $locations->where('id', $parent->id);
                } else {
                    // no results should be shown
                    $locations->whereRaw('1 = 0');
                }
            }
            if ('closest' == $request->querytype) {
                $locations->withDistance($geom)->orderBy('distance', 'ASC');
                if (!isset($request->limit)) {
                    $locations->limit(10);
                }
            }
        }
        $locations = $locations->get();

        // Hide world id
        foreach ($locations as $location) {
            if ('Country' === $location->levelName) {
                unset($location->parent_id);
            }
        }

        $fields = ($request->fields ? $request->fields : 'simple');
        // NOTE that "distance" as a field is only defined for querytype='closest', but it is ignored for other queries
        $locations = $this->setFields($locations, $fields, ['id', 'name', 'levelName', 'geom', 'distance']);

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
