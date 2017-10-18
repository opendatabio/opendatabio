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
use App\Voucher;
use Auth;
use Lang;
use Log;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vouchers = Voucher::with(['parent'])->paginate(10);

        return view('vouchers.index', compact('vouchers'));
    }

    public function customValidate(Request $request, Voucher $voucher = null)
    {
        // for checking duplicates
        $voucherid = null;
        if ($voucher) {
            $voucherid = $voucher->id;
        }

        $rules = [
            'parent_type' => 'required|string',
            'collector' => 'array|nullable',
            'identification_notes' => 'required_with:herbarium_id',
            'number' => [ // collector / number must be unique
                'required',
                'string',
                'max:191',
                Rule::unique('vouchers')->ignore($voucherid)
                ->where(function ($query) use ($request) {
                    $query->where('person_id', $request->person_id);
                }),
            ],
        ];
        $validator = Validator::make($request->all(), $rules);
        // Some fields that may be required if the parent_type is right
        $validator->sometimes('parent_plant_id', 'required', function ($data) { return "App\Plant" == $data->parent_type; });
        $validator->sometimes('parent_location_id', 'required', function ($data) { return "App\Location" == $data->parent_type; });
        $validator->sometimes('project_id', 'required', function ($data) { return "App\Location" == $data->parent_type; });
        $validator->sometimes('taxon_id', 'required', function ($data) { return "App\Location" == $data->parent_type; });
        $validator->sometimes('identifier_id', 'required', function ($data) { return "App\Location" == $data->parent_type; });
        $validator->after(function ($validator) use ($request) {
            $colldate = [$request->date_month, $request->date_day, $request->date_year];
            $iddate = [$request->identification_date_month, $request->identification_date_day, $request->identification_date_year];
            if (!Voucher::checkDate($colldate)) {
                $validator->errors()->add('date_day', Lang::get('messages.invalid_date_error'));
            }
            if ("App\Location" == $request->parent_type and !Voucher::checkDate($iddate)) {
                $validator->errors()->add('identification_date_day', Lang::get('messages.invalid_identification_date_error'));
            }
            // collection date must be in the past or today
            if (!Voucher::beforeOrSimilar($colldate, date('Y-m-d'))) {
                $validator->errors()->add('date_day', Lang::get('messages.date_future_error'));
            }
            // identification date must be in the past or today AND equal or after collection date
            if ("App\Location" == $request->parent_type and !(
                Voucher::beforeOrSimilar($iddate, date('Y-m-d')) and
                Voucher::beforeOrSimilar($colldate, $iddate))) {
                $validator->errors()->add('identification_date_day', Lang::get('messages.identification_date_future_error'));
            }
        });

        return $validator;
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
        $taxons = Taxon::leaf()->valid()->get();
        $herbaria = Herbarium::all();
        $locations = Location::all();
        $persons = Person::all();
        $plants = Plant::with('location')->get();
        $projects = Auth::user()->projects;
        // TODO: better handling here
        if (!$projects->count()) {
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_project_error')]);
        }

        return view('vouchers.create', compact('taxons', 'persons', 'locations', 'projects', 'herbaria', 'plants'));
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
        if ("App\Location" == $request->parent_type) {
            $project = Project::findOrFail($request->project_id);
        } else {
            $project = Plant::findOrFail($request->parent_plant_id)->project;
        }
        $this->authorize('create', [Voucher::class, $project]);
        $validator = $this->customValidate($request);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        if ("App\Location" == $request->parent_type) {
            $voucher = new Voucher(array_merge(
                $request->only(['person_id', 'number', 'notes', 'project_id', 'parent_type']), [
                    'parent_id' => $request->parent_location_id,
                ]));
            $voucher->setDate($request->date_month, $request->date_day, $request->date_year);
            $voucher->save();
            $voucher->identification = new Identification([
                'object_id' => $voucher->id,
                'object_type' => 'App\Voucher',
                'person_id' => $request->identifier_id,
                'taxon_id' => $request->taxon_id,
                'modifier' => $request->modifier,
                'herbarium_id' => $request->herbarium_id,
                'notes' => $request->identification_notes,
            ]);
            $voucher->identification->setDate($request->identification_date_month,
                $request->identification_date_day,
                $request->identification_date_year);
            $voucher->identification->save();
        } else { // Plant
            $plant = Plant::findOrFail($request->parent_plant_id);
            $voucher = new Voucher(array_merge(
                $request->only(['person_id', 'number', 'notes', 'parent_type']), [
                    'project_id' => $plant->project_id,
                    'parent_id' => $request->parent_plant_id,
                ]));
            $voucher->setDate($request->date_month, $request->date_day, $request->date_year);
            $voucher->save();
        }

        // common:
        if ($request->collector) {
            foreach ($request->collector as $collector) {
                Log::info('registering collector '.$collector);
                $voucher->collectors()->create(['person_id' => $collector]);
            }
        }

        $voucher->setHerbariaNumbers($request->herbarium);

        return redirect('vouchers/'.$voucher->id)->withStatus(Lang::get('messages.stored'));
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
        $voucher = Voucher::findOrFail($id);
        $identification = $voucher->parent instanceof Plant ? $voucher->parent->identification : $voucher->identification;
        $collectors = $voucher->collectors;

        return view('vouchers.show', compact('voucher', 'identification', 'collectors'));
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
        if (!Auth::user()) {
            return view('common.unauthorized');
        }
        $voucher = Voucher::findOrFail($id);
        $taxons = Taxon::leaf()->valid()->get();
        $herbaria = Herbarium::all();
        $locations = Location::all();
        $persons = Person::all();
        $plants = Plant::with('location')->get();
        $projects = Auth::user()->projects;
        // TODO: better handling here
        if (!$projects->count()) {
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_project_error')]);
        }

        return view('vouchers.create', compact('voucher', 'taxons', 'persons', 'locations', 'projects', 'herbaria', 'plants'));
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
        $voucher = Voucher::findOrFail($id);
        $this->authorize('update', $voucher);
        $validator = $this->customValidate($request, $voucher);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        if ("App\Location" == $request->parent_type) {
            $voucher->update(array_merge(
                $request->only(['person_id', 'number', 'notes', 'project_id', 'parent_type']), [
                    'parent_id' => $request->parent_location_id,
                ]));
            $voucher->setDate($request->date_month, $request->date_day, $request->date_year);
            $voucher->save();

            $ident_array = [
                'object_id' => $voucher->id,
                'object_type' => 'App\Voucher',
                'person_id' => $request->identifier_id,
                'taxon_id' => $request->taxon_id,
                'modifier' => $request->modifier,
                'herbarium_id' => $request->herbarium_id,
                'notes' => $request->identification_notes,
            ];
            if ($voucher->identification()->count()) {
                $voucher->identification()->update($ident_array);
            } else {
                $voucher->identification = new Identification($ident_array);
            }
            $voucher->identification->setDate($request->identification_date_month,
                $request->identification_date_day,
                $request->identification_date_year);
            $voucher->identification->save();
        } else { // Plant
            $plant = Plant::findOrFail($request->parent_plant_id);
            $voucher->update(array_merge(
                $request->only(['person_id', 'number', 'notes', 'parent_type']), [
                    'project_id' => $plant->project_id,
                    'parent_id' => $request->parent_plant_id,
                ]));
            $voucher->setDate($request->date_month, $request->date_day, $request->date_year);
            $voucher->save();
            if ($voucher->identification()->count()) {
                $voucher->identification()->delete();
            }
        }

        // common:
        if ($request->collector) {
            // sync collectors. See app/Project.php / setusers()
            $current = $voucher->collectors->pluck('person_id');
            $detach = $current->diff($request->collector)->all();
            $attach = collect($request->collector)->diff($current)->all();
            $voucher->collectors()->whereIn('person_id', $detach)->delete();
            foreach ($attach as $collector) {
                $voucher->collectors()->create(['person_id' => $collector]);
            }
        }

        $voucher->setHerbariaNumbers($request->herbarium);

        return redirect('vouchers/'.$voucher->id)->withStatus(Lang::get('messages.saved'));
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
