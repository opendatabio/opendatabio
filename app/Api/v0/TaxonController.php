<?php

namespace App\Api\v0;

use Illuminate\Http\Request;
use App\Taxon;
use App\TaxonExternal;
use App\Person;
use App\BibReference;
use App\ExternalAPIs;
use Response;
use Lang;
use Log;
use Validator;
use Illuminate\Support\MessageBag;
use App\DataTables\TaxonsDataTable;
use App\Http\Controllers\Controller;

class TaxonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $taxons = Taxon::query()->with(['author_person','reference']);
        if ($request->level)
            $taxons = $taxons->where('level', '=', $request->level);
        if ($request->valid)
            $taxons = $taxons->valid();
        if ($request->external)
            $taxons = $taxons->with('externalrefs');
        if ($request->limit)
            $taxons->limit($request->limit);
        $taxons = $taxons->get();

        $fields = ($request->fields ? $request->fields : "simple");
        if ($fields == "simple")
            $fields = ['id', 'fullname', 'levelName', 'authorSimple', 'bibreferenceSimple', 'valid', 'senior_id', 'parent_id'];
        else 
            $fields = explode(',',$fields);
        if ($fields != "all")
            $taxons = $taxons->map(function ($obj) use ($fields) {
                return collect($obj->toArray())
                    ->only($fields)
                    ->all();
            });
        return Response::json(['data' => $taxons]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
            $taxon = Taxon::findOrFail($id);
            if ($taxon->author_id)
                    $author = Person::findOrFail($taxon->author_id);
            else
                    $author = null;
            if ($taxon->bibreference_id)
                    $bibref = BibReference::findOrFail($taxon->bibreference_id);
            else
                    $bibref = null;

	    return view('taxons.show', [
                'taxon' => $taxon,
                'author' => $author,
                'bibref' => $bibref,
	    ]);
    }
}
