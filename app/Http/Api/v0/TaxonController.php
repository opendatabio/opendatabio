<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Jobs\ImportTaxons;
use Illuminate\Http\Request;
use App\Models\Taxon;
use App\Models\UserJob;
use App\Models\Location;
use App\Models\ODBFunctions;
use Response;

class TaxonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $taxons = Taxon::query()->with(['author_person', 'reference']);
        if ($request->root) {
            $root_tx = Taxon::select('lft', 'rgt')->where('id', $request->root)->get()->first();
            $taxons->where('lft', '>=', $root_tx['lft'])->where('rgt', '<=', $root_tx['rgt'])->orderBy('lft');
        }
        if ($request->id) {
            $taxons->whereIn('id', explode(',', $request->id));
        }
        if ($request->name) {
            ODBFunctions::advancedWhereIn($taxons,
                    'odb_txname(name, level, parent_id)',
                    $request->name,
                    true);
        }
        if (isset($request->level)) {
            $taxons->where('level', '=', $request->level);
        }
        if (isset($request->valid)) {
            $taxons->valid();
        }
        if ($request->external) {
            $taxons->with('externalrefs');
        }

        if ($request->project) {
          $project_ids = ODBFunctions::asIdList($request->dataset,Project::select('id'),'name',false);
          $all_taxons_ids = Project::whereIn('id',$project_ids)->cursor()->map(function($d) {
            return $d->all_taxons_ids();
          })->toArray();
          if (count($all_taxons_ids)) {
            $taxons = $taxons->whereIn('id',$all_taxons_ids);
          } else {
            $request->limit=0;
            $request->offset=0;
          }
        }

        if ($request->dataset) {
          $dataset_ids = ODBFunctions::asIdList($request->dataset,Dataset::select('id'),'name',false);
          $all_taxons_ids = Dataset::whereIn('id',$dataset_ids)->cursor()->map(function($d) {
            return $d->all_taxons_ids();
          })->toArray();
          if (count($all_taxons_ids)) {
            $taxons = $taxons->whereIn('id',$all_taxons_ids);
          } else {
            $request->limit=0;
            $request->offset=0;
          }
        }



        if ($request->location_root) {
            $taxon_ids = Location::find($request->location_root)->taxonsIDS();
            $taxons = $taxons->whereIn('id',$taxon_ids);
        }


        if ($request->limit && $request->offset) {
            $taxons->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $taxons->limit($request->limit);
          }
        }

        $fields = ($request->fields ? $request->fields : 'simple');
        $possible_fields = config('api-fields.taxons');
        $field_sets = array_keys($possible_fields);
        if (in_array($fields,$field_sets)) {
            $fields = implode(",",$possible_fields[$fields]);
        }

        $taxons = $taxons->cursor();
        if ($fields=="id") {
          $taxons = $taxons->pluck('id')->toArray();
        } else {
          $taxons = $this->setFields($taxons, $fields, null);
        }
        return $this->wrap_response($taxons);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Taxon::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportTaxons::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
