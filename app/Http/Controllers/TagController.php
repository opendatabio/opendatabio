<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;
use App\DataTables\TagsDataTable;
use App\Models\Language;
use App\Models\UserTranslation;
use Lang;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function index(TagsDataTable $dataTable)
    {
        return $dataTable->render('tags.index');
        //$tags = Tag::with('translations')->paginate(10);
        //return view('tags.index', compact('tags'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $languages = Language::all();

        return view('tags.create', compact('languages'));
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
        $this->authorize('create', Tag::class);
        $this->validate($request, [
            'name' => 'required|array',
            'name.*' => 'required',
            'description' => 'required|array',
        ]);
        $tag = Tag::create();
        foreach ($request->name as $key => $translation) {
            $tag->setTranslation(UserTranslation::NAME, $key, $translation);
        }
        foreach ($request->description as $key => $translation) {
            $tag->setTranslation(UserTranslation::DESCRIPTION, $key, $translation);
        }

        return redirect('tags')->withStatus(Lang::get('messages.stored'));
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
        $tag = Tag::with('datasets')->findOrFail($id);
        $media = $tag->media();
        if ($media->count()) {
          $media = $media->paginate(3);
        } else {
          $media = null;
        }
        return view('tags.show', compact('tag','media'));
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
        $languages = Language::all();
        $tag = Tag::findOrFail($id);

        return view('tags.create', compact('languages', 'tag'));
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
        $tag = Tag::findOrFail($id);
        $this->authorize('update', $tag);
        $this->validate($request, [
            'name' => 'required|array',
            'name.*' => 'required',
            'description' => 'required|array',
        ]);
        foreach ($request->name as $key => $translation) {
            $tag->setTranslation(UserTranslation::NAME, $key, $translation);
        }
        foreach ($request->description as $key => $translation) {
            $tag->setTranslation(UserTranslation::DESCRIPTION, $key, $translation);
        }

        return redirect('tags/'.$id)->withStatus(Lang::get('messages.saved'));
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
