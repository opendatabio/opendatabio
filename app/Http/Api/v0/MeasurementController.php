<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Support\Arr;
use App\Jobs\ImportMeasurements;
use Illuminate\Http\Request;
use App\Models\Measurement;
use App\Models\UserJob;
use App\Models\ODBTrait;
use App\Models\ODBFunctions;
use App\Models\Location;
use App\Models\Taxon;
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
            $measurements = $measurements->whereIn('id', explode(',', $request->id));
        }
        if ($request->trait) {
            $odbtraits = ODBFunctions::asIdList($request->trait, ODBTrait::select('id'), 'export_name');
            $measurements = $measurements->whereIn('trait_id', $odbtraits);
        }
        if ($request->dataset) {
          $measurements = $measurements->where('dataset_id',$request->dataset);
        }
        if ($request->measured_type) {
          $measurements = $measurements->where('measured_type', 'like', "%".$request->measured_type."%");
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
            //asked for descendants
            if ($request->taxon_root) {
              $taxons = Taxon::whereIn('id',$taxons_ids);
              $taxons_ids = Arr::flatten($taxons->cursor()->map(function($taxon) { return $taxon->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray());
            }
            $measurements = $measurements->where(function($subquery) use($taxons_ids) {
              $subquery->whereHasMorph('measured',['App\Models\Individual','App\Models\Voucher'],function($mm) use($taxons_ids) { $mm->whereHas('identification',function($idd) use($taxons_ids)  { $idd->whereIn('taxon_id',$taxons_ids);});})->orWhereRaw('measured_type = "App\Models\Taxon" AND measured_id='.$this->taxon);
            });

        }
        if ($request->location) {
            $measurements = $measurements->where('measured_type', 'App\Models\Location')->whereIn('measured_id', explode(',', $request->location));
        }
        if ($request->location_root) {
            $locations_ids = Location::where('id','=',$request->location_root)->first()->getDescendantsAndSelf()->pluck('id')->toArray();
            $measurements = $measurements->where('measured_type', 'App\ModelsLocation')->whereIn('measured_id', $locations_ids);
        }
        if ($request->individual) {
            $measurements = $measurements->where('measured_type', 'App\Models\Individual')->whereIn('measured_id', explode(',', $request->individual));
        }
        if ($request->voucher) {
            $measurements = $measurements->where('measured_type', 'App\Models\Voucher')->whereIn('measured_id', explode(',', $request->voucher));
        }

        if ($request->limit && $request->offset) {
            $measurements = $measurements->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $measurements = $measurements->limit($request->limit);
          }
        }

        $fields = ($request->fields ? $request->fields : 'simple');
        $simple = ['id', 'measured_type', 'measured_id', 'traitName', 'valueActual','valueDate','traitUnit','datasetName'];
        $other = ['measuredFullname', 'measuredTaxonName','measuredTaxonFamily','measuredProject'];
        //include here to be able to add mutators and categories
        if ('all' == $fields) {
            $keys = array_keys($measurements->first()->toArray());
            $fields = array_merge($simple,$keys,$other);
            $fields =  implode(',',$fields);
        }

        $measurements = $measurements->cursor();
        if ($fields=="id") {
          $measurements = $measurements->pluck('id')->toArray();
        } else {
          $measurements = $this->setFields($measurements, $fields, $simple);
        }
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
