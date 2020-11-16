<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use App\DataTables\TraitsDataTable;
use App\DataTables\ActivityDataTable;
use App\ODBTrait;
use Illuminate\Http\Request;
use App\Language;
use Lang;
use Response;

class TraitController extends Controller
{
    // Functions for autocompleting person names, used in dropdowns. Expects a $request->query input
    public function autocomplete(Request $request)
    {
        $traits = ODBTrait::whereHas('translations', function ($query) use ($request) {
            $query->where('translation', 'LIKE', ['%'.$request->input('query').'%']);
        })
            ->appliesTo($request->type)
            ->get();
        $traits = collect($traits)->transform(function ($odbtrait) {
            $odbtrait->data = $odbtrait->id;
            $odbtrait->value = $odbtrait->name;

            return $odbtrait;
        });

        return Response::json(['suggestions' => $traits]);
    }

    // Returns the partial view for filling a given trait
    public function getFormElement(Request $request)
    {
        $odbtrait = ODBTrait::findOrFail($request->id);
        if ($request->measurement) {
            $measurement = Measurement::findOrFail($request->measurement);
        } else {
            $measurement = NULL;
        }
        $html = view('traits.elements.'.$odbtrait->type, compact('odbtrait', 'measurement'))->render();

        return Response::json(array('html' => $html));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(TraitsDataTable $dataTable)
    {
        return $dataTable->render('traits.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $languages = Language::all();

        return view('traits.create', compact('languages'));
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
        $this->authorize('create', ODBTrait::class);
        $this->validate($request, ODBTrait::rules());
        $odbtrait = ODBTrait::create($request->only(['export_name', 'type','bibreference_id']));
        $odbtrait->setFieldsFromRequest($request);

        return redirect('traits/'.$odbtrait->id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $odbtrait = ODBTrait::findOrFail($id);

        return view('traits.show', compact('odbtrait'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $languages = Language::all();
        $odbtrait = ODBTrait::findOrFail($id);

        return view('traits.create', compact('languages', 'odbtrait'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $odbtrait = ODBTrait::findOrFail($id);
        $this->authorize('update', $odbtrait);
        $this->validate($request, ODBTrait::rules($id));
        $odbtrait->update($request->only(['export_name', 'type','bibreference_id']));
        $odbtrait->setFieldsFromRequest($request);

        return redirect('traits/'.$id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }



    public function activity($id, ActivityDataTable $dataTable)
    {
      $object = ODBTrait::findOrFail($id);
      return $dataTable->with('odbtrait', $id)->render('common.activity',compact('object'));
    }


}
