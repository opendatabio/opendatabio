<?php

namespace App\Http\Controllers;

use App\ODBTrait;
use Illuminate\Http\Request;
use App\Language;

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
        //
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
     * @param  \App\ODBTrait  $oDBTrait
     * @return \Illuminate\Http\Response
     */
    public function edit(ODBTrait $odbtrait)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ODBTrait  $oDBTrait
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ODBTrait $odbtrait)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ODBTrait  $oDBTrait
     * @return \Illuminate\Http\Response
     */
    public function destroy(ODBTrait $odbtrait)
    {
        //
    }
}
