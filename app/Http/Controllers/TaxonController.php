<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Taxon;
use App\Person;
use App\BibReference;
use App\ExternalAPIs;
use Response;
use Lang;
use Log;
use Illuminate\Support\MessageBag;

class TaxonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
	    $taxons = Taxon::all();
	    $persons = Person::all();
	    $references = BibReference::all();
	    return view('taxons.create', [
		    'taxons' => $taxons,
		    'persons' => $persons,
		    'references' => $references,
	    ]);
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
	    $taxon = Taxon::findOrFail($id);
	    return view('taxons.show', [
		    'taxon' => $taxon,
	    ]);
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function checkapis(Request $request)
    {
            if(is_null($request['name']))
                    return Response::json(['error' => Lang::get('messages.name_error')]);
            $apis = new ExternalAPIs;
            $mobotdata = $apis->getMobot($request->name);
            if(is_null($mobotdata))
                    return Response::json(['error' => Lang::get('messages.mobot_error')]);
            if($mobotdata[0] == 1)
                    return Response::json(['error' => Lang::get('messages.mobot_not_found')]);
            $bag = new MessageBag;
            // TODO: trata os infos do mobot dentro da message bag

            Log::info($mobotdata);

            // estrutura do objeto de resposta:
            // 0 -> rank
            // 1 -> author
            // 2 -> valid
            // 3 -> reference
            $rank = Taxon::getRank($mobotdata[1]->RankAbbreviation);
            $valid = $mobotdata[1]->NomenclatureStatusName == "Legitimate";

            return Response::json(['bag' => $bag, 
                    'apidata' => [
                            $rank,
                            $mobotdata[1]->Author,
                            $valid,
                    ]
            ]);
    }
}
