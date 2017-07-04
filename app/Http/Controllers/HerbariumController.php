<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use App\Herbarium;
use App\ExternalAPIs;
use Illuminate\Support\Facades\Lang;

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
		    return Response::json(['error' => Lang::get('messages.acronym_error')]);
	    $apis = new ExternalAPIs;
	    $ihdata = $apis->getIndexHerbariorum($request->acronym);
	    if(is_null($ihdata))
		    return Response::json(['error' => Lang::get('messages.acronym_not_found')]);
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
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
	$this->authorize('create', Herbarium::class);
	$this->validate($request, [
		'name' => 'required|max:191',
		'acronym' => 'required|max:20|unique:herbaria',
		'irn' => 'required',
	]);
	Herbarium::create($request->all());
	return redirect('herbaria')->withStatus(Lang::get('messages.stored'));
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
	    $herbarium = Herbarium::findOrFail($id);
	    $this->authorize('delete', $herbarium);
	    try {
		    $herbarium->delete();
	    } catch (\Illuminate\Database\QueryException $e) {
		    return redirect()->back()
			    ->withErrors([Lang::get('messages.fk_error')]);
	    }

	return redirect('herbaria')->withStatus(Lang::get('messages.removed'));
    }
}
