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
use DB;
use App\Jobs\ImportPlants;

class PlantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $plant = Plant::select('plants.id', 'plants.created_at', 'plants.updated_at', 'plants.location_id', 'plants.tag', 'plants.date', 'plants.notes', DB::raw('AsText(plants.relative_position) as relativePosition'), 'plants.project_id')->with(['location']);
        if ($request->id) {
            $plant->whereIn('plants.id', explode(',', $request->id));
        }
        if ($request->location) {
            $locations = $this->asIdList($request->location, Location::select('id'), 'name');
            $plant->whereIn('location_id', $locations);
        }
        if ($request->tag) {
            $this->advancedWhereIn($plant, 'tag', $request->tag);
        }
        if ($request->taxon) {
            $taxon = $this->asIdList(
                    $request->taxon,
                    Taxon::select('id'),
                    'odb_txname(name, level, parent_id)',
                    true);
            $identification = Identification::select('object_id')
                    ->where('object_type', '=', 'App\\Plant')
                    ->whereIn('taxon_id', $taxon)
                    ->get();
            $plant->whereIn('plants.id', $identification);
        }
        if ($request->project) {
            $projects = $this->asIdList($request->project, Project::select('id'), 'name');
            $plant->whereIn('project_id', $projects);
        }
        if ($request->limit) {
            $plant->limit($request->limit);
        }
        $plant = $plant->get();

        $fields = ($request->fields ? $request->fields : 'simple');
        $plant = $this->setFields($plant, $fields, ['fullName', 'taxonName', 'id', 'location_id', 'locationName', 'tag', 'date', 'notes', 'projectName', 'relativePosition',
        ]);

        return $this->wrap_response($plant);
    }
    
    public function store(Request $request)
    {
        $this->authorize('create', Plant::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportPlants::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
