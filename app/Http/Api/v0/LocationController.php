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
use App\ODBFunctions;
use App\Jobs\ImportLocations;
use Log;

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
            ODBFunctions::advancedWhereIn($locations, 'name', $request->name);
        }
        if (isset($request->adm_level)) {
            $locations->whereIn('adm_level', explode(',', $request->adm_level));
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
                $locations = $locations->withDistance($geom)->orderBy('distance', 'ASC');
                if (!isset($request->limit)) {
                    $locations->limit($request->limit);
                }
            }
        }

        if ($request->project) {
            $project_id = $request->project;
            $locations = $locations->whereHas('summary_counts',function($count) use($project_id) {
                          $count->where('scope_id',"=",$project_id)->where('scope_type',"=","App\Project")->where('value',">",0);
                        });
        }
        if ($request->dataset) {
            $dataset_id = $request->dataset;
            $locations = $locations->whereHas('summary_counts',function($count) use($dataset_id) {
              $count->where('scope_id',"=",$dataset_id)->where('scope_type',"=","App\Dataset")->where('value',">",0);
            });
        }


        if ($request->limit) {
            $locations->limit($request->limit);
        }


        // Hide world id
        foreach ($locations as $location) {
            if ('Country' === $location->levelName) {
                $location->parent_id = null;
            }
        }

        $fields = ($request->fields ? $request->fields : 'simple');
        // NOTE that "distance" as a field is only defined for querytype='closest', but it is ignored for other queries
        $simple =  ['id', 'name', 'levelName', 'geom', 'distance','parentName','parent_id','x','y','startx','starty','centroid_raw','area'];
        //include here to be able to add mutators and categories
        if ('all' == $fields) {
            $keys = array_keys($locations->first()->toArray());
            $fields = array_merge($simple,$keys);
            $fields =  implode(',',$fields);
        }

        $locations = $locations->cursor();
        if ($fields=="id") {
          $locations = $locations->pluck('id')->toArray();
        } else {
          $locations = $this->setFields($locations, $fields, $simple);
        }

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
