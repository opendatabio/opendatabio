<?php

namespace App\Http\Controllers;

use App\ODBTrait;
use Illuminate\Http\Request;
use App\Language;
use App\UserTranslation;
use Lang;

class TraitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $traits = ODBTrait::paginate(10);
        return view('traits.index', compact('traits'));
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', ODBTrait::class);
        $this->validate($request, ODBTrait::rules());
        $odbtrait = ODBTrait::create($request->only(['export_name', 'type']));
        $odbtrait->setFieldsFromRequest($request);
        return redirect('traits/' . $odbtrait->id)->withStatus(Lang::get('messages.stored'));
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $odbtrait = ODBTrait::findOrFail($id);
        $this->authorize('update', $odbtrait);
        $this->validate($request, ODBTrait::rules($id));
        $odbtrait->update($request->only(['export_name', 'type']));
        $odbtrait->setFieldsFromRequest($request);
        return redirect('traits/' . $id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
