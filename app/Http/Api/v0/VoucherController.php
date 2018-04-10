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
use Response;
use Auth;
use DB;
use App\Jobs\ImportVouchers;

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
        if ($request->location) {
            $locations = ODBFunctions::asIdList($request->location, Location::select('id'), 'name');
            if ($request->plant) { // if request has location, plant refers to the plant_tag
                $plants = Plant::select('plants.id')->whereIn('location_id', $locations);
                ODBFunctions::advancedWhereIn($plants, 'plants.tag', $request->plant);
                $voucher->where('parent_type', '=', 'App\\Plant')->whereIn('parent_id', $plants);
            } else // gives only vouchers of the specified locations
            /*
            */
                $voucher->where('parent_type', '=', 'App\\Location')->whereIn('parent_id', $locations);
        } else {
            if ($request->plant) { // plant without location refers to plant.id
                if ('*' === $request->plant) // especial case that means all vouchers of plant
                    $voucher->where('parent_type', '=', 'App\\Plant');
                else
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
        if ($request->taxon) { // taxon may refers to identification of the voucher requested by the client, or refers to identification of plant refered to the voucher requested by the client.
            $taxon = ODBFunctions::asIdList($request->taxon, Taxon::select('id'), 'odb_txname(name, level, parent_id)', true);
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
        if ($request->limit) {
            $voucher->limit($request->limit);
        }
        $voucher = $voucher->get();

        $fields = ($request->fields ? $request->fields : 'simple');
        $voucher = $this->setFields($voucher, $fields, ['fullname', 'taxonName', 'id', 'parent_type', 'parent_id', 'date', 'notes', 'project_id']);

        return $this->wrap_response($voucher);
    }

    public static function idListByFullname($variable, $class)
    {
        if (preg_match("/\d+(,\d+)*/", $variable))
            return explode(',', $variable);
        return array ($class::byFullNameAttribute($variable)->id);
    }
    
    public function store(Request $request)
    {
        $this->authorize('create', Voucher::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportVouchers::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
