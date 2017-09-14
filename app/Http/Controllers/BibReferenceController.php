<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BibReference;
use App\UserJobs;
use Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Yajra\Datatables\Datatables;

class BibReferenceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
	    $references = BibReference::orderBy(DB::raw('odb_bibkey(bibtex)'))->paginate(10);
	    return view('references.index', [
		    'references' => $references
	    ]);
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
	    $this->authorize('create', BibReference::class);
	    $this->authorize('create', UserJobs::class);
	    $contents = file_get_contents($request->rfile->getRealPath());
	    UserJobs::dispatch ("importbibreferences", ['contents' => $contents, 'standardize' => $request->standardize]);
	return redirect('references')->withStatus(Lang::get('messages.dispatched'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
	    $reference = BibReference::findOrFail($id);
	    return view('references.show', [
		    'reference' => $reference
	    ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
	    $reference = BibReference::findOrFail($id);
	    return view('references.edit', [
		    'reference' => $reference
	    ]);
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
	    $reference = BibReference::findOrFail($id);
	    $this->authorize('update', $reference);
	    $validator = Validator::make($request->all(), [
		    'bibtex' => 'required|string',
	    ]);

	    $validator->after(function ($validator) use ($reference, $request) {
		    if ( substr(trim($request->bibtex),0,1) != '@')
			    $validator->errors()->add('bibtex', Lang::get('messages.bibtex_at_error'));
		    if (! $reference->validBibtex($request->bibtex)) 
			    $validator->errors()->add('bibtex', Lang::get('messages.bibtex_error'));
	    });

	    if ($validator->fails()) {
		    return redirect()->back()
			    ->withErrors($validator)
			    ->withInput();
	    }

	    $reference->bibtex = $request->bibtex;
	    $reference->save();
	    return redirect('references/' . $id)->withStatus(Lang::get('messages.saved'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
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
}
