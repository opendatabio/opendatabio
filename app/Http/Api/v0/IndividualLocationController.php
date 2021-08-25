<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Models\Individual;
use App\Models\IndividualLocation;
use App\Models\ODBFunctions;
use App\Models\Dataset;
use App\Jobs\ImportIndividualLocations;
use Illuminate\Http\Request;
use Response;
use DB;

class IndividualLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $indlocations = IndividualLocation::select(
            'individual_location.id',
            'individual_location.location_id',
            'individual_location.individual_id',
            'individual_location.date_time',
            'individual_location.notes',
            'individual_location.altitude',
            'individual_location.first',
            DB::raw('ST_AsText(relative_position) as relativePosition')
        );
        if ($request->individual) {
            $indlocations->whereIn('individual_id', explode(',', $request->individual));
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
              $taxon_ids = array_unique(Arr::flatten($taxons->cursor()->map(function($taxon) { return $taxon->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray()));
            }
            //the individual identification is directly linked to their vouchers
            $indlocations = $indlocations->whereHas('identification', function ($q) use ($taxon_ids) {
              $q->whereIn('taxon_id',$taxon_ids);
            });
        }
        if ($request->location or $request->location_root) {
            if ($request->location) {
              $location_query= $request->location;
            } else {
              $location_query =  $request->location_root;
            }
            $locations_ids = ODBFunctions::asIdList($location_query, Location::select('id'), 'name');
            if ($request->location_root) {
              $query_locations = Location::whereIn('id',$locations_ids);
              $locations_ids = array_unique(Arr::flatten($query_locations->cursor()->map(function($location) { return $location->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray()));
            }
            $indlocations = $indlocations->whereIn('location_id',$locations_ids);
        }

        if ($request->limit && $request->offset) {
            $indlocations = $indlocations->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $indlocations = $indlocations->limit($request->limit);
          }
        }
        if ($request->dataset) {
            $datasets = ODBFunctions::asIdList($request->dataset, Dataset::select('id'), 'name');
            $indlocations = $indlocations->whereHas('individual', function($d) use($datasets){
                $d->whereIn('individuals.dataset_id',$datasets);
              }
            );
        }


        $fields = ($request->fields ? $request->fields : 'simple');
        $possible_fields = config('api-fields.individuallocations');
        $field_sets = array_keys($possible_fields);
        if (in_array($fields,$field_sets)) {
            $fields = implode(",",$possible_fields[$fields]);
        }
        $indlocations = $indlocations->cursor();
        if ($fields=="id") {
          $indlocations = $indlocations->pluck('id')->toArray();
        } else {
          $indlocations = $this->setFields($indlocations, $fields,null);
        }
        return $this->wrap_response($indlocations);
    }


    public function store(Request $request)
    {
        $this->authorize('create', Individual::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportIndividualLocations::class, ['data' => $request->post()]);
        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
