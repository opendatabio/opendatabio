<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tag;
use App\Language;
use App\UserTranslation;
use Lang;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tags = Tag::paginate(10);
        return view('tags.index', compact('tags'));
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Tag::class);
        $this->validate($request, [
            'translation' => 'required|array',
        ]);
        $tag = Tag::create();
        foreach ($request->translation as $key => $translation) {
            $tag->translations()
                ->save(new UserTranslation(['language_id' => $key, 'translation' => $translation]));
        }
        return redirect('tags')->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // TODO
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);
        $this->authorize('update', $tag);
        $this->validate($request, [
            'translation' => 'required|array',
        ]);
        foreach ($request->translation as $key => $translation) {
            if ($translation and $tag->translations()->where('language_id', '=', $key)->count()) {
                $tag->translations()->where('language_id', '=', $key)->update(['translation' => $translation]);
            } elseif ($translation) {
                $tag->translations()
                    ->save(new UserTranslation(['language_id' => $key, 'translation' => $translation]));
            } else {
                $tag->translations()->where('language_id', '=', $key)->delete();
            }
        }
        return redirect('tags')->withStatus(Lang::get('messages.saved'));
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
}
