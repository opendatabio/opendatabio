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
        $voucher = Voucher::select('*');
        if ($request->id) {
            $voucher->whereIn('id', explode(',', $request->id));
        }
        if ($request->number) {
            ODBFunctions::advancedWhereIn($voucher, 'number', $request->number);
        }
        if ($request->location or $request->location_root) {
            $locations = ODBFunctions::asIdList($request->location, Location::select('id'), 'name');
            if ($request->location_root and !($request->location)) {
                $locations = Location::whereIn('id',$locations);
                $locations = Arr::flatten($locations->cursor()->map(function($location) { return $location->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray());
            }
            //there are both direct and indirect location links, get both
            $plants = Plant::select('plants.id')->whereIn('location_id', $locations);
            if ($request->plant_tag) {
              ODBFunctions::advancedWhereIn($plants, 'tag', $request->plant_tag);
            } else {
              $plants = $plants->cursor()->pluck('id')->toArray();
            }
            $voucher->where('parent_type', '=', 'App\\Plant')->whereIn('parent_id', $plants);
            if (!($request->plant) and !($request->plant_tag)) {
              $voucher->orWhere('parent_type', '=', 'App\\Location')->whereIn('parent_id', $locations);
            }
        }
        if ($request->plant and !($request->plant_tag)) { // plant without location refers to plant.id
            if ('*' === $request->plant) { // especial case that means all vouchers of plant
              $voucher->where('parent_type', '=', 'App\\Plant');
            } else {
              $voucher->where('parent_type', '=', 'App\\Plant')->whereIn('parent_id', explode(',', $request->plant));
            }
        }

        if ($request->collector) {
            $main_collector = ODBFunctions::asIdList($request->collector, Person::select('id'), 'abbreviation');
            $voucher->whereIn('person_id', $main_collector);
        }
        if ($request->project) {
            $projects = ODBFunctions::asIdList($request->project, Project::select('id'), 'name');
            $voucher->whereIn('project_id', $projects);
        }
        if ($request->taxon or $request->taxon_root) {
            // taxon may refers to identification of the voucher requested by the client, or refers to identification of plant refered to the voucher requested by the client.
            //and taxon_root defines to get descendants
            $taxon = ODBFunctions::asIdList($request->taxon, Taxon::select('id'), 'odb_txname(name, level, parent_id)', true);
            if ($request->taxon_root and !($request->taxon)) {
                $taxon = Taxon::whereIn('id',$taxon);
                $taxon = Arr::flatten($taxon->cursor()->map(function($tx) { return $tx->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray());
            }
            $voucher->where(function ($query) use ($taxon) {
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
            $voucher->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $voucher->limit($request->limit);
          }
        }
        if($request->with_collectors) {
          $voucher = $voucher->with('collectors');
        }
        if ($request->with_herbaria) {
          $voucher = $voucher->with('herbaria');
        }
        $voucher = $voucher->get();

        $simple = ['id','fullname','collectorMain','number','collectorsAll','date','taxonName','taxonNameWithAuthor','taxonFamily','identificationDate','identifiedBy','identificationNotes','depositedAt','isType','locationName','locationFullname','longitudeDecimalDegrees','latitudeDecimalDegrees','coordinatesPrecision','coordinatesWKT','plantTag','projectName','notes'];
        $fields = ($request->fields ? $request->fields : 'simple');
        if ('all' == $fields) {
          $keys = array_keys($voucher->first()->toArray());
          $fields = implode(',',array_merge($simple,$keys));
        }
        if ($request->with_identification) {
          if ('simple' == $fields) {
            $fields = implode(',',array_merge($simple,array('identificationObject')));
          } else {
            $fields = $fields.",identificationObject";
          }
        }


        $voucher = $this->setFields($voucher, $fields, $simple);

        return $this->wrap_response($voucher);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Voucher::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportVouchers::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
