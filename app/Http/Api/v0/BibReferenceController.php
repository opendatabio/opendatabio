<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\BibReference;
use Illuminate\Http\Request;
use App\UserJob;
use Response;
use DB;

class BibReferenceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $bibrefences = BibReference::select('*', DB::raw('odb_bibkey(bibtex) as bibkey'));
        $fields = ($request->fields ? $request->fields : 'simple');
        $bibrefences = $bibrefences->get();
        $bibrefences = $this->setFields($bibrefences, $fields, ['id', 'bibkey', 'year', 'author','title','doi','url','bibtex']);

        return $this->wrap_response($bibrefences);
    }

}
