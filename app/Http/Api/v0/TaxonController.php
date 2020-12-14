<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Jobs\ImportTaxons;
use Illuminate\Http\Request;
use App\Taxon;
use App\UserJob;
use App\Location;
use App\ODBFunctions;
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
            $project_id = $request->project;
            $taxons = $taxons->whereHas('summary_counts',function($count) use($project_id) {
                          $count->where('scope_id',"=",$project_id)->where('scope_type',"=","App\Project")->where('value',">",0);
                        });
        }
        if ($request->dataset) {
            $dataset_id = $request->dataset;
            $taxons = $taxons->whereHas('summary_counts',function($count) use($dataset_id) {
              $count->where('scope_id',"=",$dataset_id)->where('scope_type',"=","App\Dataset")->where('value',">",0);
            });
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
        $simple =  ['id', 'fullname', 'levelName', 'authorSimple', 'bibreferenceSimple', 'valid', 'senior_id', 'parent_id','author_id','family'];
        //include here to be able to add mutators and categories
        if ('all' == $fields) {
            $keys = array_keys($taxons->first()->toArray());
            $fields = array_merge($simple,$keys);
            $fields =  implode(',',$fields);
        }

        $taxons = $taxons->cursor();
        if ($fields=="id") {
          $taxons = $taxons->pluck('id')->toArray();
        } else {
          $taxons = $this->setFields($taxons, $fields, $simple);
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
