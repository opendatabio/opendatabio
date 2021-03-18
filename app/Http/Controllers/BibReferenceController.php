<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BibReference;
use App\ExternalAPIs;
use App\UserJob;
use Validator;
use Illuminate\Support\Facades\Lang;
use App\Jobs\ImportBibReferences;
use App\DataTables\BibReferencesDataTable;
use App\DataTables\ActivityDataTable;
use Response;

class BibReferenceController extends Controller
{
    // Functions for autocompleting bib references, used in dropdowns. Expects a $request->query input
    public function autocomplete(Request $request)
    {
        $references = BibReference::where('bibtex', 'LIKE', ['%'.$request->input('query').'%'])
            ->selectRaw('id as data, odb_bibkey(bibtex) as value')
            ->orderBy('value', 'ASC')
            ->get();

        return Response::json(['suggestions' => $references]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(BibReferencesDataTable $dataTable)
    {
        return $dataTable->render('references.index', []);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect('references');
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
        $this->authorize('create', BibReference::class);
        $this->authorize('create', UserJob::class);
        if ($request->rfile) {
            $contents = file_get_contents($request->rfile->getRealPath());
        } else {
            $this->validate($request, ['references' => 'required|string']);
            $contents = $request->references;
        }
        UserJob::dispatch(ImportBibReferences::class,[
            'contents' => $contents,
            'standardize' => $request->standardize,
            'doi' => null,
            ]
        );
        return redirect('references')->withStatus(Lang::get('messages.dispatched'));
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
        $reference = BibReference::findOrFail($id);

        return view('references.show', compact('reference'));
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
        $reference = BibReference::findOrFail($id);

        return view('references.edit', compact('reference'));
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
        $reference = BibReference::findOrFail($id);
        $this->authorize('update', $reference);
        $validator = Validator::make($request->all(), [
            'bibtex' => 'required|string',
        ]);

        $validator->after(function ($validator) use ($reference, $request) {
            if ($request->doi and !BibReference::isValidDoi($request->doi)) {
                $validator->errors()->add('doi', Lang::get('messages.incorrect_doi'));
            }
            if ('@' != substr(trim($request->bibtex), 0, 1)) {
                $validator->errors()->add('bibtex', Lang::get('messages.bibtex_at_error'));
            }
            if (!$reference->validBibtex($request->bibtex)) {
                $validator->errors()->add('bibtex', Lang::get('messages.bibtex_error'));
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $reference->bibtex = $request->bibtex;
        $reference->setDoi($request->doi);
        $reference->save();

        return redirect('references/'.$id)->withStatus(Lang::get('messages.saved'));
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
        $reference = BibReference::findOrFail($id);
        $this->authorize('delete', $reference);
        try {
            $reference->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()
                ->withErrors([Lang::get('messages.fk_error')])->withInput();
        }

        return redirect('references')->withStatus(Lang::get('messages.removed'));
    }



    public function activity($id, ActivityDataTable $dataTable)
    {
      $object = BibReference::findOrFail($id);
      return $dataTable->with('bibreference', $id)->render('common.activity',compact('object'));
    }

    public function findBibtexFromDoi(Request $request)
    {
        $errors = [];
        $bibtex = null;
        if (!$request->doi) {
            $errors[] = 'DOI not informed';
        }
        $bibtex = ExternalAPIs::getBibtexFromDoi($request->doi);
        if (null == $bibtex) {
            $errors[] = 'Sorry, could not find the BibReference using the informed doi:'.$request->doi;
        }
        $err = null;
        if (count($errors)) {
          $err = implode("\n",$errors);
        }
        return Response::json(['bibtex' => $bibtex, 'errors' => $err]);
    }
}
