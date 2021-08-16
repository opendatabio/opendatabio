<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Http\Request;
use App\Models\Voucher;
use App\Models\Individual;
use App\Models\Location;
use App\Models\Taxon;
use App\Models\Identification;
use App\Models\Project;
use App\Models\Person;
use App\Models\UserJob;
use App\Models\ODBFunctions;
use App\Models\Dataset;
use Response;
use App\Jobs\ImportVouchers;
use Illuminate\Support\Arr;
use Log;
use DB;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $vouchers = Voucher::select(
          'vouchers.id',
          'vouchers.number',
          'vouchers.individual_id',
          'vouchers.dataset_id',
          'vouchers.date',
          'vouchers.notes',
          'vouchers.biocollection_id',
          'vouchers.biocollection_type',
          'vouchers.biocollection_number',
          DB::raw('odb_voucher_fullname(vouchers.id,vouchers.number,vouchers.individual_id,vouchers.biocollection_id,vouchers.biocollection_number) as fullname')
        )->with('collectors');
        //query();


        #select('*')->addSelect('odb_voucher_fullname(vouchers.id,vouchers.number,vouchers.individual_id,vouchers.biocollection_id) as fullname');
        if ($request->id) {
            $vouchers->whereIn('id', explode(',', $request->id));
        }
        if ($request->number) {
            //cleans request and build query
            //multiple number may be provided separated by comma
            ODBFunctions::advancedWhereIn($vouchers, 'number', $request->number);
        }
        //location may have two options, root means all descentants
        if ($request->location or $request->location_root) {
            if ($request->location) {
              $location_query= $request->location;
            } else {
              $location_query =  $request->location_root;
            }
            //check location by name or id
            $locations_ids = ODBFunctions::asIdList($location_query, Location::select('id'), 'name');
            if ($request->location_root) {
              $locations = Location::whereIn('id',$locations_ids);
              $locations_ids = Arr::flatten($locations->cursor()->map(function($location) { return $location->getDescendantsAndSelf()->pluck('id')->toArray();})->toArray());
            }
            $vouchers->whereHas('location_first',function($q) use($locations_ids) {
                $q->whereIn('location_id',$locations_ids);
            });
        }

        //individual can be queried by ids or fullname
        if ($request->individual) {
            $individual_ids = ODBFunctions::asIdList(
                  $request->individual,
                  Individual::select('id'),
                  'odb_ind_fullname(id,tag)');
            $vouchers->whereIn('individual_id',$individual_ids);
        }


        if ($request->collector) {
            $main_collector = ODBFunctions::asIdList($request->collector, Person::select('id'), 'abbreviation');
            $vouchers->where(function($q) use($main_collector) {
              $q->whereHas('collector_main',function($col) use($main_collector)
                {
                  $col->whereIn('person_id',$main_collector);
                })
                ->orWhereHas('individual',function($ind) use($main_collector)
                {
                  $ind->whereHas('collector_main',function($col) use($main_collector)
                  {
                    $col->whereIn('person_id',$main_collector);
                  });
              });
            });
        }

        if ($request->project) {
            $projects = ODBFunctions::asIdList($request->project, Project::select('id'), 'name');
            $vouchers->whereHas('dataset', function($d) use($projects) {
              $d->whereIn('project_id',$projects);
            });
        }

        if ($request->taxon or $request->taxon_root) {
            // taxon may refers to identification of the individuals that have vouchers for that taxon only
            //and taxon_root defines to get descendants
            //maybe name or id
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
            $vouchers->whereHas('identification', function ($q) use ($taxon_ids) {
              $q->whereIn('taxon_id',$taxon_ids);
            });
        }

        if ($request->dataset) {
            $datasets = ODBFunctions::asIdList($request->dataset, Dataset::select('id'), 'name');
            $vouchers->whereIn('dataset_id',$datasets);              
        }


        if ($request->limit && $request->offset) {
            $vouchers->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $vouchers->limit($request->limit);
          }
        }
        /*
        if($request->with_collectors) {
          $vouchers = $vouchers->with('collectors');
        }
        if ($request->with_biocollections) {
          $vouchers = $vouchers->with('biocollections');
        }
        */
        $vouchers = $vouchers->cursor();
        $fields = ($request->fields ? $request->fields : 'simple');
        $possible_fields = config('api-fields.vouchers');
        $field_sets = array_keys($possible_fields);
        if (in_array($fields,$field_sets)) {
            $fields = implode(",",$possible_fields[$fields]);
        }
        if ($fields=="id") {
          $vouchers = $vouchers->pluck('id')->toArray();
        } else {
          $vouchers = $this->setFields($vouchers, $fields, null);
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
