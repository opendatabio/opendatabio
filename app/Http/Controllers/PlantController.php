<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

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
use App\DataTables\PlantsDataTable;
use Auth;
use Lang;

class PlantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PlantsDataTable $dataTable)
    {
        return $dataTable->render('plants.index', []);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexLocations($id, PlantsDataTable $dataTable)
    {
        $object = Location::findOrFail($id);

        return $dataTable->with('location', $id)->render('plants.index', compact('object'));
    }

    public function indexTaxons($id, PlantsDataTable $dataTable)
    {
        $object = Taxon::findOrFail($id);

        return $dataTable->with('taxon', $id)->render('plants.index', compact('object'));
    }

    public function indexProjects($id, PlantsDataTable $dataTable)
    {
        $object = Project::findOrFail($id);

        return $dataTable->with('project', $id)->render('plants.index', compact('object'));
    }

    public function indexPersons($id, PlantsDataTable $dataTable)
    {
        $object = Person::findOrFail($id);

        return $dataTable->with('person', $id)->render('plants.index', compact('object'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!Auth::user()) {
            return view('common.unauthorized');
        }
        $herbaria = Herbarium::all();
        $persons = Person::all();
        $projects = Auth::user()->projects;
        // TODO: better handling here
        if (!$projects->count()) {
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_project_error')]);
        }

        return view('plants.create', compact('persons', 'projects', 'herbaria'));
    }

    public function customValidate(Request $request, Plant $plant = null)
    {
        // for checking duplicates
        $plantid = null;
        if ($plant) {
            $plantid = $plant->id;
        }

        $location = Location::find($request->location_id);
        $rules = [
            'location_id' => 'required|integer',
            'project_id' => 'required|integer',
            'collector' => 'required|array',
            'identifier_id' => 'required',
            'taxon_id' => 'required',
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
            'x' => 'nullable|numeric|min:0|max:'.(is_null($location) ? '' : $location->x),
            'y' => 'nullable|numeric|min:0|max:'.(is_null($location) ? '' : $location->y),
        ];
        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request) {
            $colldate = [$request->date_month, $request->date_day, $request->date_year];
            $iddate = [$request->identification_date_month, $request->identification_date_day, $request->identification_date_year];
            if (!Plant::checkDate($colldate)) {
                $validator->errors()->add('date_day', Lang::get('messages.invalid_date_error'));
            }
            if (!Plant::checkDate($iddate)) {
                $validator->errors()->add('identification_date_day', Lang::get('messages.invalid_identification_date_error'));
            }
            // collection date must be in the past or today
            if (!Plant::beforeOrSimilar($colldate, date('Y-m-d'))) {
                $validator->errors()->add('date_day', Lang::get('messages.date_future_error'));
            }
            // identification date must be in the past or today AND equal or after collection date
            if (!(Plant::beforeOrSimilar($iddate, date('Y-m-d')) and
                Plant::beforeOrSimilar($colldate, $iddate))) {
                $validator->errors()->add('identification_date_day', Lang::get('messages.identification_date_future_error'));
            }
        });

        return $validator;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
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
        $plant = new Plant($request->only([
            'tag', 'location_id', 'project_id', 'notes',
        ]));
        $plant->setRelativePosition($request->x, $request->y);
        $plant->setDate($request->date_month, $request->date_day, $request->date_year);
        $plant->save();

        foreach ($request->collector as $collector) {
            $plant->collectors()->create(['person_id' => $collector]);
        }
        $plant->identification = new Identification([
            'object_id' => $plant->id,
            'object_type' => 'App\Plant',
            'person_id' => $request->identifier_id,
            'taxon_id' => $request->taxon_id,
            'modifier' => $request->modifier,
            'herbarium_id' => $request->herbarium_id,
            'notes' => $request->identification_notes,
        ]);
        $plant->identification->setDate($request->identification_date_month,
            $request->identification_date_day,
            $request->identification_date_year);
        $plant->identification->save();

        return redirect('plants/'.$plant->id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
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
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $plant = Plant::findOrFail($id);
        if (!Auth::user()) {
            return view('common.unauthorized');
        }
        $herbaria = Herbarium::all();
        $persons = Person::all();
        $projects = Auth::user()->projects;
        // TODO: better handling here
        if (!$projects->count()) {
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_project_error')]);
        }

        return view('plants.create', [
            'plant' => $plant,
            'persons' => $persons,
            'projects' => $projects,
            'herbaria' => $herbaria,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
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
            'tag', 'location_id', 'project_id', 'notes',
        ]));
        $plant->setRelativePosition($request->x, $request->y);
        $plant->setDate($request->date_month, $request->date_day, $request->date_year);
        $plant->save();

        // "sync" collectors. See app/Project.php / setusers()
        $current = $plant->collectors->pluck('person_id');
        $detach = $current->diff($request->collector)->all();
        $attach = collect($request->collector)->diff($current)->all();
        $plant->collectors()->whereIn('person_id', $detach)->delete();
        foreach ($attach as $collector) {
            $plant->collectors()->create(['person_id' => $collector]);
        }

        $identifiers = [
            'person_id' => $request->identifier_id,
            'taxon_id' => $request->taxon_id,
            'modifier' => $request->modifier,
            'herbarium_id' => $request->herbarium_id,
            'notes' => $request->identification_notes,
        ];
        if ($plant->identification) {
            $plant->identification()->update($identifiers);
        } else {
            $plant->identification = new Identification(array_merge($identifiers, ['object_id' => $plant->id, 'object_type' => 'App\Plant']));
        }
        $plant->identification->setDate($request->identification_date_month,
            $request->identification_date_day,
            $request->identification_date_year);
        $plant->identification->save();

        return redirect('plants/'.$id)->withStatus(Lang::get('messages.saved'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }
}
