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
use App\Dataset;
use Response;
use App\Jobs\ImportVouchers;
use Illuminate\Support\Arr;
use Log;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $vouchers = Voucher::select('*');
        if ($request->id) {
            $vouchers->whereIn('id', explode(',', $request->id));
        }
        if ($request->number) {
            ODBFunctions::advancedWhereIn($vouchers, 'number', $request->number);
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
            //there are both direct and indirect location links, get both
            $plants = Plant::select('plants.id')->whereIn('location_id', $locations_ids);
            if ($request->plant_tag) {
              ODBFunctions::advancedWhereIn($plants, 'tag', $request->plant_tag);
            } else {
              $plants = $plants->cursor()->pluck('id')->toArray();
            }
            $vouchers->where('parent_type', '=', 'App\\Plant')->whereIn('parent_id', $plants);
            if (!($request->plant) and !($request->plant_tag)) {
              $vouchers->orWhere('parent_type', '=', 'App\\Location')->whereIn('parent_id', $locations_ids);
            }
        }
        if ($request->plant and !($request->plant_tag)) { // plant without location refers to plant.id
            if ('*' === $request->plant) { // especial case that means all vouchers of plant
              $vouchers->where('parent_type', '=', 'App\\Plant');
            } else {
              $vouchers->where('parent_type', '=', 'App\\Plant')->whereIn('parent_id', explode(',', $request->plant));
            }
        }

        if ($request->collector) {
            $main_collector = ODBFunctions::asIdList($request->collector, Person::select('id'), 'abbreviation');
            $vouchers->whereIn('person_id', $main_collector);
        }
        if ($request->project) {
            $projects = ODBFunctions::asIdList($request->project, Project::select('id'), 'name');
            $vouchers->whereIn('project_id', $projects);
        }
        if ($request->taxon or $request->taxon_root) {
            // taxon may refers to identification of the voucher requested by the client, or refers to identification of plant refered to the voucher requested by the client.
            //and taxon_root defines to get descendants
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
            $vouchers->where(function($subquery) use($taxon_ids) {
              $subquery->whereHas('identification', function ($q) use ($taxon_ids) {$q->whereIn('taxon_id',$taxon_ids); })->orWhereHas('plant_identification',function($q) use($taxon_ids) {
                $q->whereIn('taxon_id',$taxon_ids);
            });});            
        }

        if ($request->dataset) {
            $datasets = ODBFunctions::asIdList($request->dataset, Dataset::select('id'), 'name');
            $vouchers->whereHas('measurements', function($measurement) use($datasets){
                $measurement->whereIn('dataset_id',$datasets);
              }
            );
        }


        if ($request->limit && $request->offset) {
            $vouchers->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $vouchers->limit($request->limit);
          }
        }
        if($request->with_collectors) {
          $vouchers = $vouchers->with('collectors');
        }
        if ($request->with_herbaria) {
          $vouchers = $vouchers->with('herbaria');
        }
        $vouchers = $vouchers->cursor();

        $fields = ($request->fields ? $request->fields : 'simple');
        $simple = ['id','fullname','collectorMain','number','collectorsAll','date','taxonName','taxonNameWithAuthor','taxonFamily','identificationDate','identifiedBy','identificationNotes','depositedAt','isType','locationName','locationFullname','longitudeDecimalDegrees','latitudeDecimalDegrees','coordinatesPrecision','coordinatesWKT','plantTag','projectName','notes'];
        if ('all' == $fields) {
          $keys = array_keys($vouchers->first()->toArray());
          $fields = implode(',',array_merge($simple,$keys));
        }
        if ($request->with_identification) {
          if ('simple' == $fields) {
            $fields = implode(',',array_merge($simple,array('identificationObject')));
          } else {
            $fields = $fields.",identificationObject";
          }
        }
        if ($fields=="id") {
          $vouchers = $vouchers->pluck('id')->toArray();
        } else {
          $vouchers = $this->setFields($vouchers, $fields, $simple);
        }
        return $this->wrap_response($vouchers);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Voucher::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportVouchers::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
