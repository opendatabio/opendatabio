<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Taxon;
use App\Person;
use App\BibReference;
use App\ExternalAPIs;
use Response;
use Lang;
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
    }

    // Functions for autocompleting taxon names, used in dropdowns. Expects a $request->query input
    // MAY receive optional "$request->full" to return all names; default is to return only valid names
    public function autocomplete(Request $request)
    {
        $taxons = Taxon::with('parent')->whereRaw('odb_txname(name, level, parent_id) LIKE ?', ['%'.$request->input('query').'%'])
            ->selectRaw('id as data, odb_txname(name, level, parent_id) as fullname, level, valid')
            ->orderBy('fullname', 'ASC');
        if (!$request->full) {
            $taxons = $taxons->valid();
        }
        $taxons = $taxons->get();
        $taxons = collect($taxons)->transform(function ($taxon) {
            $taxon->value = $taxon->qualifiedFullname;
            if ($taxon->level >= 180 and $taxon->parent) { // append family name to display
                $parent = $taxon->parent;
                while ($parent->parent and $parent->level > 120) {
                    $parent = $parent->parent;
                }
                $taxon->value .= ' ['.$parent->name.']';
            }

            return $taxon;
        });

        return Response::json(['suggestions' => $taxons]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('taxons.create', [
        ]);
    }

    public function customValidate(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:191',
            'level' => 'required|integer',
            'bibreference' => 'nullable|string|max:191',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($request->level > 180 and !$request->parent_id) {
            $validator->after(function ($validator) {
                $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_required_error'));
            });
        }
        if ($request->level > 180) {
            $parent = Taxon::findOrFail($request->parent_id);
            if ($parent->level < 170) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_genus_error'));
                });
            }
        }
        if (in_array($request->level, [220, 240, 270]) and $request->parent_id) {
            $parent = Taxon::findOrFail($request->parent_id);
            if (210 != $parent->level) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_species_error'));
                });
            }
        }
        if ($request->parent_id) {
            $parent = Taxon::findOrFail($request->parent_id);
            $validator->after(function ($validator) use ($request, $parent) {
                if ($request->level <= $parent->level and ($request->level != -100 and $parent->level != -100)) {
                    $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_level_error'));
                }
            });
        }
        if ($request->senior_id) {
            $senior = Taxon::findOrFail($request->senior_id);
            $validator->after(function ($validator) use ($request, $senior) {
                if (abs($request->level - $senior->level) > 20) {
                    $validator->errors()->add('senior_id', Lang::get('messages.taxon_senior_level_error'));
                }
                if ('on' == $request->valid) {
                    $validator->errors()->add('senior_id', Lang::get('messages.taxon_senior_valid_error'));
                }
                if (!$senior->valid) {
                    $validator->errors()->add('senior_id', Lang::get('messages.taxon_senior_invalid_error'));
                }
            });
        }
        if (($request->author_id and $request->author)
                or
             (!$request->author_id and !$request->author)
        ) {
            $validator->after(function ($validator) {
                $validator->errors()->add('author_id', Lang::get('messages.taxon_author_error'));
            });
        }
        if (($request->bibreference_id and $request->bibreference)
                or
             (!$request->bibreference_id and !$request->bibreference)
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
     * @param \Illuminate\Http\Request $request
     *
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
        if ('on' == $request['valid']) {
            $request['valid'] = true;
        } else {
            $request['valid'] = false;
        }

        $taxon = new Taxon($request->only(['level', 'valid', 'parent_id', 'senior_id', 'author',
                'author_id', 'bibreference', 'bibreference_id', 'notes', ]));
        $taxon->fullname = $request['name'];
        $taxon->save(); // we need to save it here to have an id to use on the next methods
        $taxon->setapikey('Mobot', $request['mobotkey']);
        $taxon->setapikey('IPNI', $request['ipnikey']);
        $taxon->setapikey('Mycobank', $request['mycobankkey']);
        $taxon->save();

        return redirect('taxons/'.$taxon->id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $taxon = Taxon::with('identifications.object')->findOrFail($id);
        $plants = $taxon->getPlants();
        $vouchers = $taxon->getVouchers();
        if ($taxon->author_id) {
            $author = Person::findOrFail($taxon->author_id);
        } else {
            $author = null;
        }
        if ($taxon->bibreference_id) {
            $bibref = BibReference::findOrFail($taxon->bibreference_id);
        } else {
            $bibref = null;
        }

        return view('taxons.show', compact(
                'taxon',
                'author',
                'bibref',
                'plants',
                'vouchers'
            ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $taxon = Taxon::findOrFail($id);

        return view('taxons.create', [
            'taxon' => $taxon,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
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
        if ('on' == $request['valid']) {
            $request['valid'] = true;
        } else {
            $request['valid'] = false;
        }

        $taxon->update($request->only(['level', 'valid', 'parent_id', 'senior_id', 'author',
                    'author_id', 'bibreference', 'bibreference_id', 'notes', ]));
        $taxon->fullname = $request['name'];
        // update external keys
        $taxon->setapikey('Mobot', $request['mobotkey']);
        $taxon->setapikey('IPNI', $request['ipnikey']);
        $taxon->setapikey('Mycobank', $request['mycobankkey']);
        $taxon->save();

        return redirect('taxons/'.$id)->withStatus(Lang::get('messages.saved'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }

    public function checkapis(Request $request)
    {
        if (is_null($request['name'])) {
            return Response::json(['error' => Lang::get('messages.name_error')]);
        }
        $apis = new ExternalAPIs();
        $mobotdata = $apis->getMobot($request->name);
        $ipnidata = $apis->getIpni($request->name);
        $mycobankdata = $apis->getMycobank($request->name);

        // includes the messages in the return object
        $bag = new MessageBag();
        if (is_null($mobotdata)) {
            $bag->add('e1', Lang::get('messages.mobot_error'));
        }
        if (is_null($ipnidata)) {
            $bag->add('e5', Lang::get('messages.ipni_error'));
        }
        if (is_null($mycobankdata)) {
            $bag->add('e8', Lang::get('messages.mycobank_error'));
        }

        if (
                ($mobotdata[0] & ExternalAPIs::NOT_FOUND) and
                ($ipnidata[0] & ExternalAPIs::NOT_FOUND) and
                ($mycobankdata[0] & ExternalAPIs::NOT_FOUND)
            ) {
            $bag->add('e2', Lang::get('messages.apis_not_found'));
        }
        if ($mobotdata[0] & ExternalAPIs::MULTIPLE_HITS) {
            $bag->add('e3', Lang::get('messages.mobot_multiple_hits'));
        }
        if ($mobotdata[0] & ExternalAPIs::NONE_SYNONYM) {
            $bag->add('e4', Lang::get('messages.mobot_none_synonym'));
        }
        if ($ipnidata[0] & ExternalAPIs::MULTIPLE_HITS) {
            $bag->add('e7', Lang::get('messages.ipni_multiple_hits'));
        }
        if ($mycobankdata[0] & ExternalAPIs::MULTIPLE_HITS) {
            $bag->add('e10', Lang::get('messages.mycobank_multiple_hits'));
        }

        // 0 -> rank
        // 1 -> author
        // 2 -> valid
        // 3 -> reference
        // 4 -> parent
        // 5 -> senior
        // 6 -> mobot key
        // 7 -> ipni key
        // 8 -> mycobank key
        $rank = null;
        if (!is_null($ipnidata) && array_key_exists('rank', $ipnidata)) {
            $rank = $ipnidata['rank'];
        }
        if (!is_null($mobotdata) && array_key_exists('rank', $mobotdata)) {
            $rank = $mobotdata['rank'];
        }
        if (!is_null($mycobankdata) && array_key_exists('rank', $mycobankdata)) {
            $rank = $mycobankdata['rank'];
        }
        $author = null;
        if (!is_null($ipnidata) && array_key_exists('author', $ipnidata)) {
            $author = $ipnidata['author'];
        }
        if (!is_null($mobotdata) && array_key_exists('author', $mobotdata)) {
            $author = $mobotdata['author'];
        }
        if (!is_null($mycobankdata) && array_key_exists('author', $mycobankdata)) {
            $author = $mycobankdata['author'];
        }
        $reference = null;
        if (!is_null($ipnidata) && array_key_exists('reference', $ipnidata)) {
            $reference = $ipnidata['reference'];
        }
        if (!is_null($mobotdata) && array_key_exists('reference', $mobotdata)) {
            $reference = $mobotdata['reference'];
        }
        if (!is_null($mycobankdata) && array_key_exists('reference', $mycobankdata)) {
            $reference = $mycobankdata['reference'];
        }

        $rank = Taxon::getRank($rank);

        $valid = null;
        if (!is_null($mobotdata) && array_key_exists('valid', $mobotdata)) {
            $valid = in_array($mobotdata['valid'], [
                    'Legitimate',
                    'nom. cons.',
                    'No opinion',
                ]);
        }
        if (!is_null($mycobankdata) && array_key_exists('valid', $mycobankdata)) {
            $valid = in_array($mycobankdata['valid'], [
                    'Legitimate',
                    'nom. cons.',
                    'No opinion',
                ]);
        }

        $getparent = null;
        $parent = null;
        if (!is_null($mobotdata) && array_key_exists('parent', $mobotdata)) {
            $getparent = $mobotdata['parent'];
        }
        $parent = Taxon::getParent($request['name'], $rank, $getparent);
        if (!is_null($mycobankdata) && array_key_exists('parent', $mycobankdata)) {
            $parent = $mycobankdata['parent'];
        } // mycobank api already returns a "getParent"
        if (!is_null($parent) and !is_array($parent)) {
            $bag->add('parent_id', Lang::get('messages.parent_not_registered', ['name' => $parent]));
            $parent = null;
        }

        $senior = null;
        if (!is_null($mobotdata) && array_key_exists('senior', $mobotdata) and !is_null($mobotdata['senior'])) {
            $tosenior = Taxon::valid()->whereRaw('odb_txname(name, level, parent_id) = ?', [$mobotdata['senior']])->first();
            if ($tosenior) {
                $senior = [$tosenior->id, $tosenior->fullname];
            } else {
                $bag->add('senior_id', Lang::get('messages.senior_not_registered', ['name' => $mobotdata['senior']]));
            }
        }
        if (!is_null($mycobankdata) && array_key_exists('senior', $mycobankdata) and !is_null($mycobankdata['senior'])) {
            $tosenior = Taxon::valid()->whereRaw('odb_txname(name, level, parent_id) = ?', [$mycobankdata['senior']])->first();
            if ($tosenior) {
                $senior = [$tosenior->id, $tosenior->fullname];
            } else {
                $bag->add('senior_id', Lang::get('messages.senior_not_registered', ['name' => $mycobankdata['senior']]));
            }
        }

        $mobotkey = null;
        if (!is_null($mobotdata) && array_key_exists('key', $mobotdata)) {
            $mobotkey = $mobotdata['key'];
        }
        $ipnikey = null;
        if (!is_null($ipnidata) && array_key_exists('key', $ipnidata)) {
            $ipnikey = $ipnidata['key'];
        }
        $mycobankkey = null;
        if (!is_null($mycobankdata) && array_key_exists('key', $mycobankdata)) {
            $mycobankkey = $mycobankdata['key'];
        }

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
                            $mycobankkey,
                    ],
            ]);
    }
}
