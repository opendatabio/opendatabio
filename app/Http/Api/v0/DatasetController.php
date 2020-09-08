<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Dataset;
use Illuminate\Http\Request;
use Response;

class DatasetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       //with(['users', 'tags.translations'])
        $datasets =   Dataset::withCount(['measurements'])->get();


        $fields = ($request->fields ? $request->fields : 'simple');

        $datasets = $this->setFields($datasets, $fields, ['id', 'name', 'notes', 'privacyLevel','measurements_count','contactEmail','taggedWidth']);

        return $this->wrap_response($datasets);
    }

}
