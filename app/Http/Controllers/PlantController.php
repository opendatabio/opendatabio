<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;
use App\Plant;
use App\Person;
use App\Project;
use App\Taxon;
use App\Location;
use App\Herbarium;
use App\Identification;
use Auth;
use Lang;

class PlantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
	    $plants = Plant::with(['location','identification.taxon'])->paginate(10);
	    return view('plants.index', [
		    'plants' => $plants,
	    ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! Auth::user())
            return view('common.unauthorized');
	    $taxons = Taxon::leaf()->valid()->get();
	    $herbaria = Herbarium::all();
	    $locations = Location::all();
	    $persons = Person::all();
	    $projects = Auth::user()->projects;
        // TODO: better handling here
        if (! $projects->count())
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_project_error')]);
	    return view('plants.create', [
		    'taxons' => $taxons,
		    'persons' => $persons,
		    'locations' => $locations,
		    'projects' => $projects,
            'herbaria' =>$herbaria,
	    ]);
    }

    public function customValidate (Request $request, Plant $plant = null) {
        $plantid = null;
        if ($plant) $plantid = $plant->id;
	    $rules = [
            'location_id' => 'required|integer',
            'project_id' => 'required|integer',
            'collector' => 'required|array',
            'date' => 'required',
            'identifier_id' => 'required',
            'taxon_id' => 'required',
            'identification_date' => 'required',
            'identification_notes' => 'required_with:herbarium_id',
            'tag' => [ // tag / location must be unique
                'required',
                'string',
                'max:191', 
                Rule::unique('plants')->ignore($plantid)
                ->where(function ($query) use ($request) {
                    $query->where('location_id', $request->location_id);
                }),
            ],
        ];
	    $validator = Validator::make($request->all(), $rules);
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
        $project = Project::findOrFail($request->project_id);
        $this->authorize('create', [Plant::class, $project]);
	    $validator = $this->customValidate($request);
	    if ($validator->fails()) {
		    return redirect()->back()
			    ->withErrors($validator)
			    ->withInput();
	    }
        $plant = Plant::create($request->only([
            'tag', 'location_id', 'project_id', 'date', 'notes', 
        ]));
        // TODO: relative position
        foreach($request->collector as $collector)
            $plant->collectors()->create(['person_id' => $collector]);
        $plant->identification()->create([
            'person_id' => $request->identifier_id,
            'taxon_id' => $request->taxon_id,
            'date' => $request->identification_date,
            'modifier' => $request->modifier,
            'herbarium_id' => $request->herbarium_id,
            'notes' => $request->notes,
        ]);
        return redirect('plants')->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $plant = Plant::findOrFail($id);
        $identification = $plant->identification;
        $collectors = $plant->collectors;
        return view('plants.show', [
            'plant' => $plant,
            'identification' => $identification,
            'collectors' => $collectors,
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
        $plant = Plant::findOrFail($id);
        if (! Auth::user())
            return view('common.unauthorized');
        $taxons = Taxon::leaf()->valid()->get();
	    $herbaria = Herbarium::all();
	    $locations = Location::all();
	    $persons = Person::all();
	    $projects = Auth::user()->projects;
        // TODO: better handling here
        if (! $projects->count())
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_project_error')]);
	    return view('plants.create', [
            'plant' => $plant,
		    'taxons' => $taxons,
		    'persons' => $persons,
		    'locations' => $locations,
		    'projects' => $projects,
            'herbaria' =>$herbaria,
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
        $plant = Plant::findOrFail($id);
        $this->authorize('update', $plant);
        $validator = $this->customValidate($request, $plant);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $plant->update($request->only([
            'tag', 'location_id', 'project_id', 'date', 'notes', 
        ]));
        // TODO: relative position
        
        // "sync" collectors. See app/Project.php / setusers()
        $current = $plant->collectors->pluck('person_id');
        $detach = $current->diff($request->collector)->all();
        $attach = collect($request->collector)->diff($current)->all();
        $plant->collectors()->whereIn('person_id', $detach)->delete();
        foreach($attach as $collector)
            $plant->collectors()->create(['person_id' => $collector]);

        $identifiers = [
            'person_id' => $request->identifier_id,
            'taxon_id' => $request->taxon_id,
            'date' => $request->identification_date,
            'modifier' => $request->modifier,
            'herbarium_id' => $request->herbarium_id,
            'notes' => $request->identification_notes,
        ];
        if ($plant->identification) {
            $plant->identification()->update($identifiers);
        } else {
            $plant->identification()->create($identifiers);
        }
        return redirect('plants')->withStatus(Lang::get('messages.saved'));
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
