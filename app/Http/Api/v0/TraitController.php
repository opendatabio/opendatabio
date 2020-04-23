<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Jobs\ImportTraits;
use Illuminate\Http\Request;
use App\ODBTrait;
use App\UserJob;
use App\ODBFunctions;
use App\Language;
use DB;
use Lang;
use Response;

class TraitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    //TODO: get should include in name and descriptions the arrays containing values for each translation
    //same for categories

    public function index(Request $request)
    {
        $traits = ODBTrait::select('*',DB::raw('odb_traittypename(type) as typename'));
        if ($request->id) {
            $traits->whereIn('id', explode(',', $request->id));
        }
        if ($request->limit) {
            $traits->limit($request->limit);
        }
        $traits = $traits->get();

        $fields = ($request->fields ? $request->fields : 'simple');
        $traits = $this->setFields($traits, $fields, ['id', 'type', 'typename','export_name','unit', 'range_min', 'range_max', 'link_type','name','description']);
        return $this->wrap_response($traits);
    }



    public function store(Request $request)
    {
        $this->authorize('create', ODBTrait::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportTraits::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}