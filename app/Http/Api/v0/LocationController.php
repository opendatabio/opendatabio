<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Http\Request;
use Response;
use App\Models\Location;
use App\Models\Project;
use App\Models\Dataset;
use App\Models\UserJob;
use App\Models\ODBFunctions;
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
                $locations->whereRaw('ST_AsText(geom) = ?', [$geom]);
            }
            if ('parent' == $request->querytype) {
                $parent = Location::detectParent($geom, 100, false,false,0);
                if ($parent) {
                    $locations->where('id', $parent->id);
                } else {
                    // no results should be shown
                    $locations->whereRaw('1 = 0');
                }
            }
            if ('closest' == $request->querytype) {
                $locations = $locations->withDistance($geom)->orderBy('distance', 'ASC');
            }
        }

        if ($request->project) {
          $project_ids = ODBFunctions::asIdList($request->dataset,Project::select('id'),'name',false);
          $all_locations_ids = Project::whereIn('id',$project_ids)->cursor()->map(function($d) {
            return $d->all_locations_ids();
          })->toArray();
          if (count($all_locations_ids)) {
            $locations = $locations->whereIn('id',$all_locations_ids);
          } else {
            $request->limit=0;
            $request->offset=0;
          }
        }

        if ($request->dataset) {
          $dataset_ids = ODBFunctions::asIdList($request->dataset,Dataset::select('id'),'name',false);
          $all_locations_ids = Dataset::whereIn('id',$dataset_ids)->cursor()->map(function($d) {
            return $d->all_locations_ids();
          })->toArray();
          if (count($all_locations_ids)) {
            $locations = $locations->whereIn('id',$all_locations_ids);
          } else {
            $request->limit=0;
            $request->offset=0;
          }
        }

        if ($request->limit and $request->offset) {
            $locations->offset($request->offset)->limit($request->limit);
        } elseif ($request->limit) {
            $locations->limit($request->limit);
        }

        $fields = ($request->fields ? $request->fields : 'simple');

        // NOTE that "distance" as a field is only defined for querytype='closest', but it is ignored for other queries
        $possible_fields = config('api-fields.locations');
        $field_sets = array_keys($possible_fields);
        if (in_array($fields,$field_sets)) {
            $fields = implode(",",$possible_fields[$fields]);
        }
        $locations = $locations->cursor();
        if ($fields=="id") {
          $locations = $locations->pluck('id')->toArray();
        } else {
          $locations = $this->setFields($locations, $fields, null);
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
