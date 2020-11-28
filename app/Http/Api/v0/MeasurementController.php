<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Support\Arr;
use App\Jobs\ImportMeasurements;
use Illuminate\Http\Request;
use App\Measurement;
use App\UserJob;
use App\ODBTrait;
use App\ODBFunctions;
use Response;

class MeasurementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $measurements = Measurement::select('*','measurements.date as valueDate');
        if ($request->id) {
            $measurements->whereIn('id', explode(',', $request->id));
        }
        if ($request->trait) {
            $odbtraits = ODBFunctions::asIdList($request->trait, ODBTrait::select('id'), 'export_name');
            $measurements->whereIn('trait_id', $odbtraits);
        }
        if ($request->dataset) {
          $measurements->where('dataset_id',$request->dataset);
        }
        if ($request->taxon or $request->taxon_root) {
            //this is tricky as taxon may be related to measurement from different objects and user may want to get descendants as well
            if ($request->taxon) {
              $taxon_query= $request->taxon;
            } else {
              $taxon_query =  $request->taxon_root;
            }
            $taxons_ids = ODBFunctions::asIdList(
                    $taxon_query,
                    Taxon::select('id'),
                    'odb_txname(name, level, parent_id)',
                    true);
            $taxons = Taxon::whereIn('id',$taxons_ids);

            //asked for descendants
            if ($request->taxon_root) {
              $taxons_ids = Arr::flatten($taxons->cursor()->map(function($taxon) { return $taxon->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray());
              $taxons = Taxon::whereIn('id',$taxons_ids);
            }

            $measurements_ids = Arr::flatten($taxons->cursor()->map(function($taxon) {
              $plids = $taxon->plant_measurements()->get()->pluck('id')->toArray();
              $vcids = $taxon->voucher_measurements()->get()->pluck('id')->toArray();
              return array_merge($plids,$vcids);
            })->toArray());
            $measurements->where('measured_type', 'App\\Taxon')->whereIn('measured_id', explode(',', $request->taxon))->orWhereIn('id',$measurements_ids);
        }
        if ($request->location) {
            $measurements->where('measured_type', 'App\\Location')->whereIn('measured_id', explode(',', $request->location));
        }
        if ($request->plant) {
            $measurements->where('measured_type', 'App\\Plant')->whereIn('measured_id', explode(',', $request->plant));
        }
        if ($request->voucher) {
            $measurements->where('measured_type', 'App\\Voucher')->whereIn('measured_id', explode(',', $request->voucher));
        }

        if ($request->limit && $request->offset) {
            $measurements->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $measurements->limit($request->limit);
          }
        }
        $measurements = $measurements->get();

        //$fields = ['id', 'measured_type', 'measured_id', 'traitName', 'valueActual','valueDate','traitUnit','datasetName','measuredFullname', 'measuredTaxonName','measuredTaxonFamily','measuredProject'];

        $fields_simple = ['id', 'measured_type', 'measured_id', 'traitName', 'valueActual','valueDate','traitUnit','datasetName'];

        $fields = ($request->fields ? $request->fields : 'simple');
        $measurements = $this->setFields($measurements, $fields,$fields_simple);
        return $this->wrap_response($measurements);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Measurement::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportMeasurements::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
