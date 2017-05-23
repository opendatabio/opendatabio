<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use App\Herbarium;
use App\ExternalAPIs;

class HerbariumController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
	    $herbaria = Herbarium::orderBy('acronym')->paginate(10);
	    return view('herbaria.index', [
        'herbaria' => $herbaria
	]);
    }

    public function checkih(Request $request)
    {
	    if(is_null($request['acronym']))
		    return Response::json(['error' => 'You must provide an acronym!']);
	    $apis = new ExternalAPIs;
	    $ihdata = $apis->getIndexHerbariorum($request->acronym);
	    if(is_null($ihdata))
		    return Response::json(['error' => 'Acronym not found or error accessing IH site!']);
	    return Response::json(['ihdata' => $ihdata]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
	    return redirect('herbaria');
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
	$this->validate($request, [
		'name' => 'required|max:191',
		'acronym' => 'required|max:20|unique:herbaria',
		'irn' => 'required',
	]);
	Herbarium::create($request->all());
	return redirect('herbaria')->withStatus('Herbarium stored!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
	    $herbarium = Herbarium::findOrFail($id);
	    return view('herbaria.show', [
		    'herbarium' => $herbarium
	    ]);
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
		    Herbarium::findOrFail($id)->delete();
	    } catch (\Illuminate\Database\QueryException $e) {
		    return redirect()->back()
			    ->withErrors(['This herbarium is associated with other objects and cannot be removed']);
	    }

	return redirect('herbaria')->withStatus('Herbarium removed!');
        //
    }
}
