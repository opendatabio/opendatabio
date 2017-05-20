<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
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
		print_r($request->toArray());
	    if ($request->submit == 'checkih') return $this->checkih();
	    return "OH NO"; 
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
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
