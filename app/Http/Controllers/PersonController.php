<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Person;
use App\Herbarium;
use App\Taxon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Input;
use App\DataTables\PersonsDataTable;
use Response;

class PersonController extends Controller
{
    // Functions for autocompleting person names, used in dropdowns. Expects a $request->query input
    public function autocomplete(Request $request)
    {
        $persons = Person::where('full_name', 'LIKE', ['%'.$request->input('query').'%'])
            ->orWhere('abbreviation', 'LIKE', ['%'.$request->input('query').'%'])
            ->selectRaw("id as data, CONCAT(full_name, ' [',abbreviation, ']') as value")
            ->orderBy('value', 'ASC')
            ->get();

        return Response::json(['suggestions' => $persons]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PersonsDataTable $dataTable)
    {
        $herbaria = Herbarium::all();

        return $dataTable->render('persons.index', [
            'herbaria' => $herbaria,
    ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect('persons');
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
        $this->authorize('create', Person::class);
        $this->checkValid($request);
        // checks for duplicates, except if the request is already confirmed
        if (!$request->confirm) {
            $dupes = Person::duplicates($request->full_name, $request->abbreviation);
            if (sizeof($dupes)) {
                Input::flash();

                return view('persons.confirm', compact('dupes'));
            }
        }
        $person = Person::create($request->all());

        return redirect('persons/'.$person->id)->withStatus(Lang::get('messages.stored'));
    }

    protected function checkValid(Request $request, $id = null)
    {
        $this->validate($request, [
        'full_name' => 'required|max:191',
        'abbreviation' => ['required', 'max:191', 'regex:'.config('app.valid_abbreviation'), 'unique:persons,abbreviation,'.$id],
        'email' => ['nullable', 'max:191', 'email', 'unique:persons,email,'.$id],
    ]);
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
        $person = Person::findOrFail($id);
        $person->load('collected.object');
        $vouchers = $person->vouchers;
        $vouchers->load(['identification', 'parent']);
        $collected = collect($person->vouchers)->merge($person->collected->map(function ($x) {return $x->object; }));
        $collected = $collected->reject(function ($x) {return is_null($x); });

        return view('persons.show', compact('person', 'collected'));
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
        $person = Person::findOrFail($id);
        $herbaria = Herbarium::all();
        $taxons = Taxon::all();

        return view('persons.edit', compact('person', 'herbaria', 'taxons'));
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
        $person = Person::findOrFail($id);
        $this->authorize('update', $person);
        $this->checkValid($request, $id);
        $person->update($request->only(['full_name', 'abbreviation', 'email', 'institution', 'herbarium_id']));
        // add/remove specialists
        $person->taxons()->sync($request->specialist);

        return redirect('persons/'.$id)->withStatus(Lang::get('messages.saved'));
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
        $person = Person::findOrFail($id);
        $this->authorize('delete', $person);
        try {
            $person->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()
                ->withErrors([Lang::get('messages.fk_error')])->withInput();
        }

        return redirect('persons')->withStatus(Lang::get('messages.removed'));
    }
}
