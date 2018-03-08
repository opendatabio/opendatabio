<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Http\Request;
use App\Plant;
use App\Location;
use App\Taxon;
use App\Identification;
use App\Project;
use App\UserJob;
use Response;
use Auth;
//use App\Jobs\ImportPlant;

class PlantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $plant = Plant::select('plants.id', 'plants.created_at', 'plants.updated_at', 'plants.location_id', 'plants.tag', 'plants.date', 'plants.notes', 'plants.relative_position', 'plants.project_id', 'projects.name AS project')->with(['location']);
        if ($request->id) {
            $plant = $plant->whereIn('plants.id', explode(',', $request->id));
        }
        if ($request->location) {
            $locations = Location::select('id')->where('name', 'LIKE', '%'.$request->location.'%')->get();
            if (count($locations))
                $plant = $plant->whereIn('location_id', $locations);
        }
        if ($request->tag) {
            $plant = $plant->where('tag', 'LIKE', '%'.$request->tag.'%');
        }
        if ($request->taxon) {
            $taxon = Taxon::select('id')->whereRaw('odb_txname(name, level, parent_id) LIKE ?', ['%'.$request->taxon.'%'])->get();
            $identification = Identification::select('object_id')->where('object_type', '=', 'App\\Plant')->whereIn('taxon_id', $taxon);
            $plant = $plant->whereIn('plants.id', $identification);
        }
        if ($request->project) {
            $project = Project::select('id')->where('name', 'LIKE', '%'.$request->project.'%')->get();
            $plant = $plant->whereIn('project_id', $project);
        }
        if ($request->limit) {
            $plant->limit($request->limit);
        }
        $plant = $plant->get();

        $fields = ($request->fields ? $request->fields : 'simple');
        $plant = $this->setFields($plant, $fields, ['fullName', 'taxonName', 'id', 'location_id', 'locationName', 'tag', 'date', 'notes', 'project'//, 'relative_position'
        ]);

        return $this->wrap_response($plant);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Plant::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportPlant::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
