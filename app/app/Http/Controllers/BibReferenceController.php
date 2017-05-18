<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BibReference;
use Validator;

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
	$this->checkValid($request);
	$person = Person::create($request->all());
	return redirect('persons');
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
	    BibReference::find($id)->delete();
	return redirect('references');
    }
}
