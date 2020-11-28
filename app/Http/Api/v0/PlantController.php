<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Plant;
use App\Location;
use App\Taxon;
use App\Identification;
use App\Project;
use App\UserJob;
use App\ODBFunctions;
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
        $plant = Plant::select('*', DB::raw('AsText(plants.relative_position) as relativePosition'))->with(['location']);
        if ($request->id) {
            $plant->whereIn('plants.id', explode(',', $request->id));
        }
        if ($request->location or $request->location_root) {
            if ($request->location) {
              $location_query= $request->location;
            } else {
              $location_query =  $request->location_root;
            }
            $locations_ids = ODBFunctions::asIdList($location_query, Location::select('id'), 'name');
            if ($request->location_root) {
              $locations = Location::whereIn('id',$locations_ids);
              $locations_ids = Arr::flatten($locations->cursor()->map(function($location) { return $location->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray());
            }
            $plant->whereIn('location_id', $locations_ids);
        }
        if ($request->tag) {
            ODBFunctions::advancedWhereIn($plant, 'tag', $request->tag);
        }
        if ($request->taxon or $request->taxon_root) {
            if ($request->taxon) {
              $taxon_query= $request->taxon;
            } else {
              $taxon_query =  $request->taxon_root;
            }
            $taxons = ODBFunctions::asIdList(
                    $taxon_query,
                    Taxon::select('id'),
                    'odb_txname(name, level, parent_id)');
            if ($request->taxon_root) {
              $taxons_ids = Arr::flatten($taxons->cursor()->map(function($taxon) { return $taxon->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray());
            } else {
              $taxons_ids = $taxons->get()->pluck('id')->toArray();
            }

            $identification = Identification::select('object_id')
                    ->where('object_type', '=', 'App\\Plant')
                    ->whereIn('taxon_id', $taxons_ids)
                    ->get();
            $plant->whereIn('plants.id', $identification);
        }
        if ($request->project) {
            $projects = ODBFunctions::asIdList($request->project, Project::select('id'), 'name');
            $plant->whereIn('project_id', $projects);
        }
        if ($request->dataset) {
            $datasets = ODBFunctions::asIdList($request->dataset, Dataset::select('id'), 'name');
            $plant->whereHas('measurements', function($measurement) use($datasets){
                $measurement->whereIn('dataset_id',$datasets);
              }
            );
        }

        if ($request->limit && $request->offset) {
            $plant->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $plant->limit($request->limit);
          }
        }
        $plant = $plant->get();
        $fields = ($request->fields ? $request->fields : 'simple');
        $plant = $this->setFields($plant, $fields, ['id','fullName', 'taxonName', 'taxonFamily','location_id', 'locationName', 'locationParentName','tag', 'date', 'notes', 'projectName', 'relativePosition','xInParentLocation','yInParentLocation']);

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
