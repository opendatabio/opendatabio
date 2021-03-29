<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Models\Language;
use App\Models\ODBFunctions;
use Illuminate\Http\Request;
use Response;

class LanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $languages = Language::select('*')->get();

        $fields = ($request->fields ? $request->fields : 'simple');

        $languages = $this->setFields($languages, $fields, ['id','name','code']);

        return $this->wrap_response($languages);
    }

}
