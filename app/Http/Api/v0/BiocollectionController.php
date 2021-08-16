<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Models\Biocollection;
use App\Models\ODBFunctions;
use App\Jobs\ImportBiocollections;
use Illuminate\Http\Request;
use Response;

class BiocollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $biocollections = Biocollection::select('*');

        if ($request->id) {
            $biocollections->whereIn('id', explode(',', $request->id));
        }
        if ($request->acronym) {
            $biocollections->whereIn('acronym', explode(',', $request->acronym));
        }


        $fields = ($request->fields ? $request->fields : 'simple');
        $possible_fields = config('api-fields.biocollections');
        $field_sets = array_keys($possible_fields);
        if (in_array($fields,$field_sets)) {
            $fields = implode(",",$possible_fields[$fields]);
        }


        $biocollections = $biocollections->cursor();
        if ($fields=="id") {
          $biocollections = $biocollections->pluck('id')->toArray();
        } else {
          $biocollections = $this->setFields($biocollections, $fields, null);
        }
        return $this->wrap_response($biocollections);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Biocollection::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportBiocollections::class, ['data' => $request->post()]);
        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
