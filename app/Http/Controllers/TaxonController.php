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
use Validator;
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

    public function customValidate(Request $request) {
	    $rules = [
		    'name' => 'required|string|max:191',
		    'level' => 'required|integer',
	    ];
	    $validator = Validator::make($request->all(), $rules);
        if ($request->parent_id) {
                $parent = Taxon::findOrFail($request->parent_id);
                $validator->after(function ($validator) use ($request, $parent) {
                        if ($request->level <= $parent->level) 
                                $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_level_error'));
                });
        }
        if ($request->senior_id) {
                $senior = Taxon::findOrFail($request->senior_id);
                $validator->after(function ($validator) use ($request, $senior) {
                        if (abs($request->level - $senior->level) > 20)
                                $validator->errors()->add('senior_id', Lang::get('messages.taxon_senior_level_error'));
                        if($request->valid == "on")
                                $validator->errors()->add('senior_id', Lang::get('messages.taxon_senior_valid_error'));
                        if(! $senior->valid)
                                $validator->errors()->add('senior_id', Lang::get('messages.taxon_senior_invalid_error'));
                });
        }
        if ($request->author_id) {
                $validator->after(function ($validator) use ($request) {
                        if($request->author)
                                $validator->errors()->add('author_id', Lang::get('messages.taxon_author_error'));
                });
        }
        if ($request->bibreference_id) {
                $validator->after(function ($validator) use ($request) {
                        if($request->bibreference)
                                $validator->errors()->add('bibreference_id', Lang::get('messages.taxon_bibref_error'));
                });
        }
            return $validator;
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
	    $validator = $this->customValidate($request);
	    if ($validator->fails()) {
		    return redirect()->back()
			    ->withErrors($validator)
			    ->withInput();
	    }
            // Laravel sends checkbox as On??
            if ($request['valid'] == "on") {
                    $request['valid'] = true;
            } else {
                    $request['valid'] = false;
            }
        // always saves the name with only the first letter capitalized
        $request['name'] = ucfirst($request['name']);

            Taxon::create($request->only(['name', 'level', 'valid', 'parent_id', 'senior_id', 'author', 
                    'author_id', 'bibreference', 'bibreference_id', 'notes']));
            
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
	    $validator = $this->customValidate($request);
	    if ($validator->fails()) {
		    return redirect()->back()
			    ->withErrors($validator)
			    ->withInput();
	    }
            // Laravel sends checkbox as On??
            if ($request['valid'] == "on") {
                    $request['valid'] = true;
            } else {
                    $request['valid'] = false;
            }
        $request['name'] = ucfirst($request['name']);

            $taxon->update($request->only(['name', 'level', 'valid', 'parent_id', 'senior_id', 'author', 
                    'author_id', 'bibreference', 'bibreference_id', 'notes']));
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

            $senior = null;
            if (sizeof($mobotdata) == 3) { // we have a valid senior reference
                    $tosenior = Taxon::where('name', $mobotdata[2]->ScientificName)->first();
                    if ($tosenior) {
                            $senior = $tosenior->id;
                    } else {
                            $bag->add('senior_id', Lang::get('messages.senior_not_registered', ['name' => $mobotdata[2]->ScientificName]));
                    }
            }
            Log::info($bag);
            return Response::json(['bag' => $bag, 
                    'apidata' => [
                            $rank,
                            $mobotdata[1]->Author,
                            $valid,
                            $mobotdata[1]->DisplayReference . " " . $mobotdata[1]->DisplayDate,
                            null, // TODO: what to do here???
                            $senior, 
                    ]
            ]);
    }
}
