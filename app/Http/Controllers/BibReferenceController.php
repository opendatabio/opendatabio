<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BibReference;
use Validator;
use RenanBr\BibTexParser\ParseException as ParseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

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
        //
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
	    $contents = file_get_contents($request->rfile->getRealPath());

	    try {
		$person = BibReference::createFromFile($contents, $request->standardize);
	    } catch (ParseException $e) {
		return redirect('references')->withErrors([Lang::get('messages.bibtex_error')]);
	    }
	return redirect('references')->withStatus(Lang::get('messages.stored'));
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
	 return redirect('references/'.$id);
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
//	    $this->checkValid($request, $id);
	    $validator = Validator::make($request->all(), [
		    'bibtex' => 'required|string',
	    ]);

	    $validator->after(function ($validator) use ($reference, $request) {
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
	    return redirect('references')->withStatus(Lang::get('messages.saved'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
	    try {
		    BibReference::findOrFail($id)->delete();
	    } catch (\Illuminate\Database\QueryException $e) {
		    return redirect()->back()
			    ->withErrors([Lang::get('messages.fk_error')])->withInput();
	    }
	return redirect('references')->withStatus(Lang::get('messages.removed'));
    }
}
