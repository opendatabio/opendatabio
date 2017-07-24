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
use App\DataTables\TaxonsDataTable;

class TaxonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(TaxonsDataTable $dataTable)
    {
	    return $dataTable->render('taxons.index', [
    ]);
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
            $this->authorize('create', Taxon::class);
            //TODO: validate parent level < taxon level
            //      validate senior level == taxon level
            //      validate: not valid if senior present
            //      validate: author name OR id, not both
            //      validate: bib ref OR id, not both
        $this->validate($request, [
                'name' => 'required|max:191',
                'level' => 'required',
        ]);
            // Laravel sends checkbox as On??
            if ($request['valid'] == "on") {
                    $request['valid'] = true;
            } else {
                    $request['valid'] = false;
            }

            Taxon::create($request->only(['name', 'level', 'valid', 'parent_id', 'senior_id', 'author', 
                    'author_id', 'bibreference', 'bibreference_id']));
            
            return redirect('taxons')->withStatus(Lang::get('messages.stored'));
            //TODO: add MOBOT key
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
	    $taxons = Taxon::all();
	    $persons = Person::all();
	    $references = BibReference::all();
        $taxon = Taxon::findOrFail($id);
        return view('taxons.create', [
            'taxon' => $taxon,
		    'taxons' => $taxons,
		    'persons' => $persons,
		    'references' => $references,
	    ]);
        //
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
            $taxon = Taxon::findOrFail($id);
            $this->authorize('update', $taxon);
            //TODO: validate parent level < taxon level
            //      validate senior level == taxon level
            //      validate: not valid if senior present
            //      validate: author name OR id, not both
            //      validate: bib ref OR id, not both
        $this->validate($request, [
                'name' => 'required|max:191',
                'level' => 'required',
        ]);
            // Laravel sends checkbox as On??
            if ($request['valid'] == "on") {
                    $request['valid'] = true;
            } else {
                    $request['valid'] = false;
            }

            $taxon->update($request->only(['name', 'level', 'valid', 'parent_id', 'senior_id', 'author', 
                    'author_id', 'bibreference', 'bibreference_id']));
            // TODO: external keys
            return redirect('taxons')->withStatus(Lang::get('messages.saved'));
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
            // 4 -> parent
            // 5 -> senior
            $rank = Taxon::getRank($mobotdata[1]->RankAbbreviation);
            $valid = $mobotdata[1]->NomenclatureStatusName == "Legitimate";

            return Response::json(['bag' => $bag, 
                    'apidata' => [
                            $rank,
                            $mobotdata[1]->Author,
                            $valid,
                            $mobotdata[1]->DisplayReference . " " . $mobotdata[1]->DisplayDate,
                            null, // TODO: what to do here???
                            null, // TODO: idem
                    ]
            ]);
    }
}
