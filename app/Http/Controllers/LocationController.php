<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Location;
use App\DataTables\LocationsDataTable;
use Validator;
use DB;
use Lang;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(LocationsDataTable $dataTable)
    {
	    return $dataTable->render('locations.index', [
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
	    $locations = Location::all();
	    $uc_list = Location::ucs()->get();
	    return view('locations.create', [
		    'locations' => $locations,
		    'uc_list' => $uc_list
	    ]);
        //
    }

    // Validates the user input for CREATE or UPDATE requests
    // Notice that the fields that will be used are different based on the
    // adm_level declared
    public function customValidate (Request $request) {
	    $rules = [
		    'name' => 'required|string|max:191',
		    'adm_level' => 'required|integer',
		    'altitude' => 'integer|nullable',
	    ];
	    // TODO: shouldn't hardcode the code for PLOTs
	    if ($request->adm_level == 100) {
		    $rules = array_merge($rules, [
			    'lat1' => 'required|number',
			    'long1' => 'required|number',
			    'x' => 'required|number',
			    'y' => 'required|number',
		    ]);
	    } elseif ($request->adm_level == 999) {
		    $rules = array_merge($rules, [
			    'lat1' => 'required|number',
			    'long1' => 'required|number',
		    ]);
	    } else {
		    $rules = array_merge($rules, [
			    'geom' => 'required|string',
		    ]);
	    }
	    $validator = Validator::make($request->all(), $rules);
	    // MySQL 5.7 has a new IsValid for checking validity, but we must keep compatibility with 5.5
	    $validator->after(function ($validator) use ($request) {
		    if ($request->adm_level == 100 or $request->adm_level == 999) return;
		    $valid = DB::select('SELECT Dimension(GeomFromText(?)) as valid', [$request->geom]);

		    if (is_null ($valid[0]->valid)) 
			    $validator->errors()->add('geom', Lang::get('messages.geom_error'));
	    });

	    return $validator;

    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
	    $validator = $this->customValidate($request);
	    if ($validator->fails()) {
		    return redirect()->back()
			    ->withErrors($validator)
			    ->withInput();
	    }
	    if ($request->adm_level == 100) {
		    $newloc = new Location($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y', 'startx', 'starty']));
	    } else {
		    // discard x, y data from locations that are not PLOTs
		    $newloc = new Location($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes']));
	    }

	    $parent = $request['parent_id'];
	    // AUTODETECT PARENT & UC
//	    if ($parent == -1) {
//		    $possibles = Location::where('adm_level', '!=', 99)->where('adm_level', '<', [$request->adm_level])
//			    		   ->whereRaw('MBRContains(geom, GeomFromText(?))', [$request->geom])
//			    	           ->orderBy('adm_level', 'desc')->get();
//		    dd($possibles);
//	    }
	    if ($parent !== 0) {
		    $newloc->parent_id = $parent;
	    }
	    if ($request->uc_id !== 0) {
		    $newloc->uc_id = $request->uc_id;
	    }
	    $newloc->geom = $request->geom;
	    $newloc->save();
	return redirect('locations')->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
	    $location = Location::with(['ancestors', 'descendants'])->findOrFail($id);
	    $adm_level = config('adm_levels')[$location->adm_level];
	    return view('locations.show', [
		    'location' => $location,
		    'adm_level' => $adm_level
	    ]);
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
	    $locations = Location::all();
	    $uc_list = Location::ucs()->get();
	    $location = Location::findOrFail($id);
	    // TODO: change this view name?
	    return view('locations.create', [
		    'locations' => $locations,
		    'location' => $location,
		    'uc_list' => $uc_list
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
	    $location = Location::findOrFail($id);
	    $validator = $this->customValidate($request);
	    if ($validator->fails()) {
		    return redirect()->back()
			    ->withErrors($validator)
			    ->withInput();
	    }
	    if ($request->adm_level == 100) {
		    $location->update($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y', 'startx', 'starty']));
	    } else {
		    // discard x, y data from locations that are not PLOTs
		    $location->update($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes']));
	    }
	    $parent = $request['parent_id'];
	    if ($parent !== 0) {
		    $location->parent_id = $parent;
	    }
	    if ($request->uc_id !== 0) {
		    $location->uc_id = $request->uc_id;
	    }
	    $location->geom = $request->geom;
	    $location->save();
	return redirect('locations')->withStatus(Lang::get('messages.stored'));
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
