<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Http\Request;
use App\Voucher;
use App\Plant;
use App\Location;
use App\Taxon;
use App\Identification;
use App\Project;
use App\Person;
use App\UserJob;
use App\ODBFunctions;
use Response;
use App\Jobs\ImportSamples;

class SampleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sample = Voucher::select('*');
        if ($request->id) {
            $sample->whereIn('id', explode(',', $request->id));
        }
        if ($request->number) {
            ODBFunctions::advancedWhereIn($sample, 'number', $request->number);
        }
        if ($request->location) {
            $locations = ODBFunctions::asIdList($request->location, Location::select('id'), 'name');
            if ($request->plant) { // if request has location, plant refers to the plant_tag
                $plants = Plant::select('plants.id')->whereIn('location_id', $locations);
                ODBFunctions::advancedWhereIn($plants, 'plants.tag', $request->plant);
                $sample->where('parent_type', '=', 'App\\Plant')->whereIn('parent_id', $plants);
            } else { // gives only samples of the specified locations
                $sample->where('parent_type', '=', 'App\\Location')->whereIn('parent_id', $locations);
            }
        } else {
            if ($request->plant) { // plant without location refers to plant.id
                if ('*' === $request->plant) { // especial case that means all samples of plant
                    $sample->where('parent_type', '=', 'App\\Plant');
                } else {
                    $sample->where('parent_type', '=', 'App\\Plant')->whereIn('parent_id', explode(',', $request->plant));
                }
            }
        }
        if ($request->collector) {
            $main_collector = ODBFunctions::asIdList($request->collector, Person::select('id'), 'abbreviation');
            $sample->whereIn('person_id', $main_collector);
        }
        if ($request->project) {
            $projects = ODBFunctions::asIdList($request->project, Project::select('id'), 'name');
            $sample->whereIn('project_id', $projects);
        }
        if ($request->taxon) { // taxon may refers to identification of the sample requested by the client, or refers to identification of plant refered to the sample requested by the client.
            $taxon = ODBFunctions::asIdList($request->taxon, Taxon::select('id'), 'odb_txname(name, level, parent_id)', true);
            $sample->where(function ($query) use ($taxon) {
                $identifications = Identification::select('object_id')
                        ->where('object_type', '=', 'App\\Voucher')
                        ->whereIn('taxon_id', $taxon);
                $query->whereIn('id', $identifications);
                $query->orWhere(function ($internalQuery) use ($taxon) {
                    $plants = Identification::select('object_id')
                            ->where('object_type', '=', 'App\\Plant')
                            ->whereIn('taxon_id', $taxon);
                    $internalQuery->where('parent_type', '=', 'App\\Plant')
                            ->whereIn('parent_id', $plants);
                });
            });
        }
        if ($request->limit && $request->offset) {
            $sample->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $sample->limit($request->limit);
          }
        }
        $sample = $sample->get();

        $fields = ($request->fields ? $request->fields : 'simple');
        $sample = $this->setFields($sample, $fields, ['fullname', 'taxonName', 'id', 'parent_type', 'parent_id', 'date', 'notes', 'project_id']);

        return $this->wrap_response($sample);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Voucher::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportSamples::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
