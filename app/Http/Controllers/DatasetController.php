<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use App\DataTables\DatasetsDataTable;
use Illuminate\Http\Request;
use App\Tag;
use App\BibReference;
use App\Dataset;
use App\User;
use Auth;
use Lang;

class DatasetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(DatasetsDataTable $dataTable)
    {
        $mydatasets = null;
        if (Auth::user() and Auth::user()->datasets()->count()) {
            $mydatasets = Auth::user()->datasets;
        }

        return $dataTable->render('datasets.index', compact('mydatasets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $fullusers = User::where('access_level', '=', User::USER)->orWhere('access_level', '=', User::ADMIN)->get();
        $allusers = User::all();
        $tags = Tag::all();
        $references = BibReference::all();

        return view('datasets.create', compact('fullusers', 'allusers', 'tags', 'references'));
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
        $this->authorize('create', Dataset::class);
        $fullusers = User::where('access_level', '=', User::USER)
            ->orWhere('access_level', '=', User::ADMIN)->get()->pluck('id');
        $fullusers = implode(',', $fullusers->all());
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'privacy' => 'required|integer',
            'admins' => 'required|array|min:1',
            'admins.*' => 'numeric|in:'.$fullusers,
            'collabs' => 'nullable|array',
            'collabs.*' => 'numeric|in:'.$fullusers,
        ]);
        $dataset = Dataset::create($request->only(['name', 'notes', 'privacy', 'bibreference_id']));
        $dataset->setusers($request->viewers, $request->collabs, $request->admins);
        $dataset->tags()->attach($request->tags);

        return redirect('datasets/'.$dataset->id)->withStatus(Lang::get('messages.stored'));
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
        $dataset = Dataset::findOrFail($id);
        //with(['measurements.measured', 'measurements.odbtrait'])->
        return view('datasets.show', compact('dataset'));
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
        $tags = Tag::all();
        $references = BibReference::all();
        $dataset = Dataset::findOrFail($id);
        $fullusers = User::where('access_level', '=', User::USER)->orWhere('access_level', '=', User::ADMIN)->get();
        $allusers = User::all();

        return view('datasets.create', compact('dataset', 'fullusers', 'allusers', 'tags', 'references'));
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
        $dataset = Dataset::findOrFail($id);
        $this->authorize('update', $dataset);
        $fullusers = User::where('access_level', '=', User::USER)
            ->orWhere('access_level', '=', User::ADMIN)->get()->pluck('id');
        $fullusers = implode(',', $fullusers->all());
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'privacy' => 'required|integer',
            'admins' => 'required|array|min:1',
            'admins.*' => 'numeric|in:'.$fullusers,
            'collabs' => 'nullable|array',
            'collabs.*' => 'numeric|in:'.$fullusers,
        ]);
        $dataset->update($request->only(['name', 'notes', 'privacy', 'bibreference_id']));
        $dataset->setusers($request->viewers, $request->collabs, $request->admins);
        $dataset->tags()->sync($request->tags);

        return redirect('datasets/'.$id)->withStatus(Lang::get('messages.saved'));
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
}
