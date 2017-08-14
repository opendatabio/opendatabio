<?php

namespace App\Http\Controllers;

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
        if ($request->level > 180 and ! $request->parent_id) {
                $validator->after(function ($validator) {
                        $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_required_error'));
                });
        }
        if ($request->level > 180) {
            $parent = Taxon::findOrFail($request->parent_id);
                if ($parent->level < 170)
                $validator->after(function ($validator) {
                        $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_genus_error'));
                });
        }
        if (in_array($request->level, [220, 240, 270]) and $request->parent_id) {
                $parent = Taxon::findOrFail($request->parent_id);
                if ($parent->level != 210)
                $validator->after(function ($validator) {
                        $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_species_error'));
                });
        }
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
        if ( ($request->author_id and $request->author)
                or
             (! $request->author_id and ! $request->author)
        ) {
                $validator->after(function ($validator) {
                        $validator->errors()->add('author_id', Lang::get('messages.taxon_author_error'));
                });
        }
        if ( ($request->bibreference_id and $request->bibreference)
                or
             (! $request->bibreference_id and ! $request->bibreference)
        ) {
                $validator->after(function ($validator) {
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
    public function store(Request $request) {
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

        $taxon = new Taxon($request->only(['level', 'valid', 'parent_id', 'senior_id', 'author', 
                'author_id', 'bibreference', 'bibreference_id', 'notes']));
        $taxon->fullname = $request['name'];
        $taxon->save(); // we need to save it here to have an id to use on the next methods
        $taxon->setapikey('Mobot', $request['mobotkey']);
        $taxon->setapikey('IPNI', $request['ipnikey']);
        $taxon->save(); 
        return redirect('taxons')->withStatus(Lang::get('messages.stored'));
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

            $taxon->update($request->only(['level', 'valid', 'parent_id', 'senior_id', 'author', 
                    'author_id', 'bibreference', 'bibreference_id', 'notes']));
            $taxon->fullname = $request['name'];
            // update external keys
            $taxon->setapikey('Mobot', $request['mobotkey']);
            $taxon->setapikey('IPNI', $request['ipnikey']);
            $taxon->save();
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
            $ipnidata = $apis->getIpni($request->name);

            // includes the messages in the return object
            $bag = new MessageBag;
            if(is_null($mobotdata))
                    $bag->add('e1', Lang::get('messages.mobot_error'));
            if($mobotdata[0] & ExternalAPIs::NOT_FOUND)
                    $bag->add('e2', Lang::get('messages.mobot_not_found'));
            if ($mobotdata[0] & ExternalAPIs::MULTIPLE_HITS)
                    $bag->add('e3', Lang::get('messages.mobot_multiple_hits'));
            if ($mobotdata[0] & ExternalAPIs::NONE_SYNONYM)
                    $bag->add('e4', Lang::get('messages.mobot_none_synonym'));
            if(is_null($ipnidata))
                    $bag->add('e5', Lang::get('messages.ipni_error'));
            if($ipnidata[0] & ExternalAPIs::NOT_FOUND)
                    $bag->add('e6', Lang::get('messages.ipni_not_found'));
            if ($ipnidata[0] & ExternalAPIs::MULTIPLE_HITS)
                    $bag->add('e7', Lang::get('messages.ipni_multiple_hits'));

            Log::info($mobotdata);
            Log::info($ipnidata);
            // 0 -> rank
            // 1 -> author
            // 2 -> valid
            // 3 -> reference
            // 4 -> parent
            // 5 -> senior
            // 6 -> mobot key
            // 7 -> ipni key
            $rank = null;
            if (!is_null($ipnidata) && array_key_exists("rank", $ipnidata))
                    $rank = $ipnidata["rank"];
            if (!is_null($mobotdata) && array_key_exists("rank", $mobotdata))
                    $rank = $mobotdata["rank"];
            $author = null;
            if (!is_null($ipnidata) && array_key_exists("author", $ipnidata))
                    $author = $ipnidata["author"];
            if (!is_null($mobotdata) && array_key_exists("author", $mobotdata))
                    $author = $mobotdata["author"];
            $reference = null;
            if (!is_null($ipnidata) && array_key_exists("reference", $ipnidata))
                    $reference = $ipnidata["reference"];
            if (!is_null($mobotdata) && array_key_exists("reference", $mobotdata))
                    $reference = $mobotdata["reference"];

            $rank = Taxon::getRank($rank);

            $valid = null;
            if (!is_null($mobotdata) && array_key_exists("valid", $mobotdata))
		    $valid = in_array($mobotdata["valid"], [
			    "Legitimate", 
			    "nom. cons.",
			    "No opinion",
		    ]); 

            $getparent = null;
            if (!is_null($mobotdata) && array_key_exists("parent", $mobotdata))
                    $getparent = $mobotdata["parent"]; 
            $parent = Taxon::getParent($request["name"], $rank, $getparent);
            if (! is_null($parent) and ! is_int($parent)) {
                $bag->add('parent_id', Lang::get('messages.parent_not_registered', ['name' => $parent]));
            }
            Log::info("parent: " . $parent);

            $senior = null;
            if (!is_null($mobotdata) && array_key_exists("senior", $mobotdata) and !is_null($mobotdata["senior"])) {
                    $tosenior = Taxon::valid()->where('name', $mobotdata["senior"])->first();
                    if ($tosenior) {
                            $senior = $tosenior->id;
                    } else {
                            $bag->add('senior_id', Lang::get('messages.senior_not_registered', ['name' => $mobotdata["senior"]]));
                    }
            }

            $mobotkey = null;
            if (!is_null($mobotdata) && array_key_exists("key", $mobotdata))
                    $mobotkey = $mobotdata["key"];
            $ipnikey = null;
            if (!is_null($ipnidata) && array_key_exists("key", $ipnidata))
                    $ipnikey = $ipnidata["key"];

            return Response::json(['bag' => $bag, 
                    'apidata' => [
                            $rank,
                            $author,
                            $valid,
                            $reference,
                            $parent,
                            $senior, 
                            $mobotkey,
                            $ipnikey,
                    ]
            ]);
    }
}
