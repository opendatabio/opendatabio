<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Location;
use App\DataTables\LocationsDataTable;
use Validator;
use DB;
use Lang;
use Log;

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
    }

    // Validates the user input for CREATE or UPDATE requests
    // Notice that the fields that will be used are different based on the
    // adm_level declared
    public function customValidate (Request $request) {
	    $rules = [
		    'name' => 'required|string|max:191',
		    'adm_level' => 'required|integer',
		    'altitude' => 'integer|nullable',
		    'parent_id' => 'required_unless:adm_level,0',
	    ];
	    if ($request->adm_level == Location::LEVEL_PLOT) { // PLOT
		    $rules = array_merge($rules, [
			    'lat1' => 'required|numeric|min:0',
			    'long1' => 'required|numeric|min:0',
			    'lat2' => 'numeric|nullable|min:0',
			    'long2' => 'numeric|nullable|min:0',
			    'lat3' => 'numeric|nullable|min:0',
			    'long3' => 'numeric|nullable|min:0',
			    'x' => 'required|numeric',
			    'y' => 'required|numeric',
		    ]);
	    } elseif ($request->adm_level == Location::LEVEL_POINT) { //POINT
		    $rules = array_merge($rules, [
			    'lat1' => 'required|numeric|min:0',
			    'long1' => 'required|numeric|min:0',
			    'lat2' => 'numeric|nullable|min:0',
			    'long2' => 'numeric|nullable|min:0',
			    'lat3' => 'numeric|nullable|min:0',
			    'long3' => 'numeric|nullable|min:0',
		    ]);
	    } else { // All other
		    $rules = array_merge($rules, [
			    'geom' => 'required|string',
		    ]);
	    }
	    $validator = Validator::make($request->all(), $rules);
	    // Now we check if the geometry received is valid, and if it falls inside the parent geometry
	    $validator->after(function ($validator) use ($request) {
		    if ($request->adm_level == Location::LEVEL_PLOT or $request->adm_level == Location::LEVEL_POINT) return;
		    // Dimension returns NULL for invalid geometries
		    $valid = DB::select('SELECT Dimension(GeomFromText(?)) as valid', [$request->geom]);

		    if (is_null ($valid[0]->valid)) 
			    $validator->errors()->add('geom', Lang::get('messages.geom_error'));
	    });
	    $validator->after(function ($validator) use ($request) {
		    if ($request->parent_id < 1) return; // don't validate if parent = 0 for none, -1 for autodetect
		    $geom = $request->geom;
		    if ($request->adm_level == Location::LEVEL_PLOT or $request->adm_level == Location::LEVEL_POINT) {
			    // copied from app\Locations, normalize
			    $values = $request;
			    $lat = $values['lat1'] + $values['lat2'] / 60 + $values['lat3'] / 3600;
			    $long = $values['long1'] + $values['long2'] / 60 + $values['long3'] / 3600;
			    if ( $values['longO'] == 0) $long *= -1;
			    if ( $values['latO'] == 0) $lat *= -1;
			    $geom = "POINT(" . $long . " " . $lat . ")";
		    }

		    $valid = DB::select('SELECT ST_Within(GeomFromText(?), geom) as valid FROM locations where id = ?', [$geom, $request->parent_id]);

		    if ($valid[0]->valid != 1) 
			    $validator->errors()->add('geom', Lang::get('messages.geom_parent_error'));
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
	    $this->authorize('create', Location::class);
	    $validator = $this->customValidate($request);
	    if ($validator->fails()) {
		    return redirect()->back()
			    ->withErrors($validator)
			    ->withInput();
	    }
	    if ($request->adm_level == Location::LEVEL_PLOT) { // plot
		    $newloc = new Location($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y', 'startx', 'starty']));
		    $newloc->setGeomFromParts($request->only([
			    'lat1','lat2','lat3','latO',
			    'long1','long2','long3','longO',
		    ]));
	    } else {
		    // discard x, y data from locations that are not PLOTs
		    $newloc = new Location($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes']));
		    if ($request->adm_level == Location::LEVEL_POINT) { // point
			    $newloc->setGeomFromParts($request->only([
				    'lat1','lat2','lat3','latO',
				    'long1','long2','long3','longO',
			    ]));
		    } else { // others
			    $newloc->geom = $request->geom;
		    }

	    }

	    $parent = $request['parent_id'];
	    // AUTODETECT PARENT & UC
	    if ($parent == -1) {
		    // TODO: make it work for plots/points
		    $possibles = Location::where('adm_level', '!=', Location::LEVEL_UC)
			                   ->where('adm_level', '<', [$request->adm_level])
			    		   ->whereRaw('ST_Within(GeomFromText(?), geom)', [$request->geom])
			    	           ->orderBy('adm_level', 'desc')->get();
		    
		    if ($possibles->isNotEmpty()) {
			    $newloc->parent_id = $possibles[0]->id;
		    } else {
			    return redirect()->back()
				    ->withErrors(['parent_id' => Lang::get('messages.unable_autodetect')])
				    ->withInput();
		    }
	    }
	    if ($parent !== 0) {
		    $newloc->parent_id = $parent;
	    }
	    if ($request->uc_id !== 0) {
		    $newloc->uc_id = $request->uc_id;
	    }
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
	    $location = Location::with(['plants.identification.taxon'])->findOrFail($id);
        $plants = $location->plants;

        if ($location->x) {
            if ($location->x > $location->y) {
                $width = 400;
                $height = 400 / $location->x * $location->y;
            } else {
                $height = 400;
                $width = 400 / $location->y * $location->x;
            }
    
            $chartjs = app()->chartjs
                ->name('LocationPlants')
                ->type('scatter')
                ->size(['width' => $width, 'height' => $height ])
                ->labels($plants->map(function($x) {return $x->tag;})->all())
                ->datasets([
                    [
                        "label" => "Plants in location",
                        "showLine" => false,
                        'backgroundColor' => "rgba(38, 185, 154, 0.31)",
                        'borderColor' => "rgba(38, 185, 154, 0.7)",
                        "pointBorderColor" => "rgba(38, 185, 154, 0.7)",
                        "pointBackgroundColor" => "rgba(38, 185, 154, 0.7)",
                        "pointHoverBackgroundColor" => "rgba(220,220,20,0.7)",
                        "pointHoverBorderColor" => "rgba(220,220,20,1)",
                        'data' =>  $plants->map(function($x) {return ['x' => $x->x, 'y' => $x->y];})->all()
                    ]
                ])
                ->options([
                    "maintainAspectRatio" => true,
                ]);
            return view('locations.show', compact('chartjs', 'location'));
        } // else
        return view('locations.show', compact('location'));
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
        return view('locations.create', compact('locations', 'location', 'uc_list'));
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
	    $this->authorize('update', $location);
	    $validator = $this->customValidate($request);
	    if ($validator->fails()) {
		    return redirect()->back()
			    ->withErrors($validator)
			    ->withInput();
	    }
	    if ($request->adm_level == Location::LEVEL_PLOT) {
		    $location->update($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y', 'startx', 'starty']));
		    $location->setGeomFromParts($request->only([
			    'lat1','lat2','lat3','latO',
			    'long1','long2','long3','longO',
		    ]));
	    } else {
		    // discard x, y data from locations that are not PLOTs
		    $location->update($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes']));
		    if ($request->adm_level == Location::LEVEL_POINT) { // point
			    $location->setGeomFromParts($request->only([
				    'lat1','lat2','lat3','latO',
				    'long1','long2','long3','longO',
			    ]));
		    } else { // others
			    $location->geom = $request->geom;
		    }
	    }
	    $parent = $request['parent_id'];
	    if ($parent != 0) {
		    try{
			    $location->makeChildOf($parent);
		    } catch (\Baum\MoveNotPossibleException $e) {
			    return redirect()->back()
				    ->withInput()
				    ->withErrors(Lang::get('messages.movenotpossible'));
		    }
	    }
	    if ($request->uc_id !== 0) {
		    $location->uc_id = $request->uc_id;
	    }
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
	    $location = Location::findOrFail($id);
	    $this->authorize('delete', $location);
	    try {
		    $location->delete();
	    } catch (\Illuminate\Database\QueryException $e) {
		    return redirect()->back()
			    ->withErrors([Lang::get('messages.fk_error')])->withInput();
	    }
	return redirect('locations')->withStatus(Lang::get('messages.removed'));
    }
}
