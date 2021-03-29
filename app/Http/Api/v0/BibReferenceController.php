<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Support\Arr;
use App\Models\BibReference;
use Illuminate\Http\Request;
use App\Models\UserJob;
use App\Models\ODBFunctions;
use Response;
use DB;
use Taxon;

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

        if ($request->id) {
            $bibrefences->whereIn('id', explode(',', $request->id));
        }
        if ($request->bibkey) {
            $keys = explode(',',$request->bibkey);
            $keys = implode("','",$keys);
            $bibrefences->whereRaw(" odb_bibkey(bibtex) IN('".$keys."')");
        }
        if ($request->taxon) {
            $taxons_ids = ODBFunctions::asIdList(
                  $request->taxon,
                  Taxon::select('id'),
                  'odb_txname(name, level, parent_id)',
                  true);
            $taxons = Taxon::whereIn('id',$taxons_ids);
            $bib_ids = Arr::flatten($taxons->cursor()->map(function($taxon) { return $taxon->references()->pluck('id')->toArray();})->toArray());
            $bibrefences->whereIn('id',$bib_ids);
        }

        if ($request->limit && $request->offset) {
            $bibrefences->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $bibrefences->limit($request->limit);
          }
        }


        $fields = ($request->fields ? $request->fields : 'simple');
        $simple = ['id', 'bibkey', 'year', 'author','title','doi','url','bibtex'];
        //include here to be able to add mutators and categories
        if ('all' == $fields) {
            $keys = array_keys($bibrefences->first()->toArray());
            $fields = array_merge($simple,$keys);
            $fields =  implode(',',$fields);
        }

        $bibrefences = $bibrefences->cursor();
        if ($fields=="id") {
          $bibrefences = $bibrefences->pluck('id')->toArray();
        } else {
          $bibrefences = $this->setFields($bibrefences, $fields, $simple);
        }


        return $this->wrap_response($bibrefences);
    }

}
