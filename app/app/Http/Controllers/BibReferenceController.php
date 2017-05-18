<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BibReference;
use Validator;
use RenanBr\BibTexParser\ParseException as ParseException;
use Illuminate\Support\Facades\Log;

class BibReferenceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
	    $references = BibReference::paginate(10);
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
		$person = BibReference::createFromFile($contents);
	    } catch (ParseException $e) {
		    Log::error ("ERROR parsing bibtex input file");

		return redirect('references')->withErrors(['The file could not be parsed as valid BibTex!']);
	    }
	return redirect('references');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
	    $reference = BibReference::find($id);
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
	    $reference = BibReference::find($id);
//	    $this->checkValid($request, $id);
	    $validator = Validator::make($request->all(), [
		    'bibtex' => 'required|string',
	    ]);

	    $validator->after(function ($validator) use ($reference, $request) {
		    if (! $reference->validBibtex($request->bibtex)) 
			    $validator->errors()->add('bibtex', 'The BibTex format is incorrect!');
	    });

	    if ($validator->fails()) {
		    return redirect()->back()
			    ->withErrors($validator)
			    ->withInput();
	    }

	    $reference->bibtex = $request->bibtex;
	    $reference->save();
	    return redirect('references');
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
		    BibReference::find($id)->delete();
	    } catch (\Illuminate\Database\QueryException $e) {
		    return redirect()->back()
			    ->withErrors(['This reference is associated with other objects and cannot be removed'])->withInput();
	    }
	return redirect('references');
    }
}
