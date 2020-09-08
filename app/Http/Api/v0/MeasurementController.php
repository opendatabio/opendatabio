<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

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
        if ($request->name) {
            $traits = ODBTrait::select('id');
            ODBFunctions::advancedWhereIn($traits,
                    'export_name',
                    $request->name);
            $measurements->whereIn('trait_id', $traits->get());
        }
        if ($request->dataset) {
          $measurements->where('dataset_id',$request->dataset);
        }
        if ($request->taxon) {
            $measurements->where('measured_type', 'App\\Taxon')->whereIn('measured_id', explode(',', $request->taxon));
        }
        if ($request->location) {
            $measurements->where('measured_type', 'App\\Location')->whereIn('measured_id', explode(',', $request->location));
        }
        if ($request->plant) {
            $measurements->where('measured_type', 'App\\Plant')->whereIn('measured_id', explode(',', $request->plant));
        }
        if ($request->sample) {
            $measurements->where('measured_type', 'App\\Sample')->whereIn('measured_id', explode(',', $request->sample));
        }
        if ($request->limit && $request->offset) {
            $measurements->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $measurements->limit($request->limit);
          }
        }
        $measurements = $measurements->get();

        $fields = ($request->fields ? $request->fields : 'simple');
        $measurements = $this->setFields($measurements, $fields, ['id', 'measured_type', 'measured_id', 'traitName', 'valueActual','valueDate','traitUnit','datasetName','measuredFullname', 'measuredTaxonName','measuredTaxonFamily','measuredProject']);
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
