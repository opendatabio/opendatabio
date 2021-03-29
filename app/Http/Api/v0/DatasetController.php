<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Models\Dataset;
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
        $datasets =   Dataset::withCount(['measurements']);

        if ($request->id) {
            $datasets->whereIn('id', explode(',', $request->id));
        }

        $fields = ($request->fields ? $request->fields : 'simple');
        $simple = ['id', 'name', 'notes', 'privacyLevel','policy','description','measurements_count','contactEmail','taggedWidth'];
        //include here to be able to add mutators and categories
        if ('all' == $fields) {
            $keys = array_keys($datasets->first()->toArray());
            $fields = array_merge($simple,$keys);
            $fields =  implode(',',$fields);
        }

        $datasets = $datasets->cursor();
        if ($fields=="id") {
          $datasets = $datasets->pluck('id')->toArray();
        } else {
          $datasets = $this->setFields($datasets, $fields, $simple);
        }

        return $this->wrap_response($datasets);
    }

}
