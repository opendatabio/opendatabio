<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\Individual;
use App\Models\Location;
use App\Models\Taxon;
use App\Models\Identification;
use App\Models\Project;
use App\Models\Dataset;
use App\Models\UserJob;
use App\Models\ODBFunctions;
use Response;
use DB;
//use App\Models\Jobs\ImportIndividuals;

class IndividualController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $individuals = Individual::select(
          'individuals.*',
          DB::raw('odb_ind_relativePosition(individuals.id) as relativePosition'),
          DB::raw('odb_ind_fullname(individuals.id,individuals.tag) as fullname'));
        if ($request->id) {
            $individuals = $individuals->whereIn('individuals.id', explode(',', $request->id));
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
            $individuals = $individuals->whereHas('locations',function($q) use($locations_ids) {
                $q->whereIn('locations.id',$locations_ids);
            });
        }
        if ($request->tag) {
            ODBFunctions::advancedWhereIn($individual, 'tag', $request->tag);
        }

        if ($request->taxon or $request->taxon_root) {
            if ($request->taxon) {
              $taxon_query= $request->taxon;
            } else {
              $taxon_query =  $request->taxon_root;
            }
            $taxon_ids = ODBFunctions::asIdList(
                    $taxon_query,
                    Taxon::select('id'),
                    'odb_txname(name, level, parent_id)');
            if ($request->taxon_root) {
              $taxons = Taxon::whereIn('id',$taxon_ids);
              $taxon_ids = Arr::flatten($taxons->cursor()->map(function($taxon) { return $taxon->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray());
            }
            //the individual identification is directly linked to their vouchers
            $individuals = $individuals->whereHas('identification', function ($q) use ($taxon_ids) {
              $q->whereIn('taxon_id',$taxon_ids);
            });
        }

        if ($request->project) {
            $projects = ODBFunctions::asIdList($request->project, Project::select('id'), 'name');
            $individuals = $individuals->whereIn('project_id', $projects);
        }
        if ($request->dataset) {
            $datasets = ODBFunctions::asIdList($request->dataset, Dataset::select('id'), 'name');
            $individuals = $individuals->whereHas('measurements', function($measurement) use($datasets){
                $measurement->whereIn('dataset_id',$datasets);
              }
            );
        }

        if($request->with_locations) {
          $individuals = $individuals->with('locations');
        }
        if($request->with_collectors) {
          $individuals = $individuals->with('collectors');
        }


        if ($request->limit && $request->offset) {
            $individuals = $individuals->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $individuals = $individuals->limit($request->limit);
          }
        }

        $fields = ($request->fields ? $request->fields : 'simple');
        $possible_fields = config('api-fields.individuals');
        $field_sets = array_keys($possible_fields);
        if (in_array($fields,$field_sets)) {
            $fields = implode(",",$possible_fields[$fields]);
        }
        if($request->with_locations) {
          $fields = $fields.",locations";
        }
        if($request->with_collectors) {
          $fields = $fields.",collectors";
        }

        if ($fields=="id") {
          $individuals = $individuals->cursor()->pluck('id')->toArray();
        } else {
          $individuals = $individuals->cursor();
          $individuals = $this->setFields($individuals, $fields, null);
        }
        return $this->wrap_response($individuals);
    }


    public function store(Request $request)
    {
        $this->authorize('create', Individual::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportIndividuals::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }


}
