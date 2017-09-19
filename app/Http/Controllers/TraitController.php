<?php

namespace App\Http\Controllers;

use App\ODBTrait;
use Illuminate\Http\Request;

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
     * @param  \App\ODBTrait  $oDBTrait
     * @return \Illuminate\Http\Response
     */
    public function show(ODBTrait $ODBTrait)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ODBTrait  $oDBTrait
     * @return \Illuminate\Http\Response
     */
    public function edit(ODBTrait $ODBTrait)
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
    public function update(Request $request, ODBTrait $ODBTrait)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ODBTrait  $oDBTrait
     * @return \Illuminate\Http\Response
     */
    public function destroy(ODBTrait $ODBTrait)
    {
        //
    }
}
