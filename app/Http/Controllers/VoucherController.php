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

    public function customValidate (Request $request, Voucher $voucher = null) {
        // for checking duplicates
        $voucherid = null;
        if ($voucher) $voucherid = $voucher->id;

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
        $validator->sometimes('parent_plant_id', 'required', function($data) { return $data->parent_type == "App\Plant"; });
        $validator->sometimes('parent_location_id', 'required', function($data) { return $data->parent_type == "App\Location"; });
        $validator->sometimes('project_id', 'required', function($data) { return $data->parent_type == "App\Location"; });
        $validator->sometimes('taxon_id', 'required', function($data) { return $data->parent_type == "App\Location"; });
        $validator->sometimes('identifier_id', 'required', function($data) { return $data->parent_type == "App\Location"; });
	    $validator->after(function ($validator) use ($request) {
            // if date is complete, it must check as valid
            if ($request->date_day and !checkdate( $request->date_month, $request->date_day, $request->date_year))
                    $validator->errors()->add('date_day', Lang::get('messages.invalid_date_error'));
            if ($request->parent_type == "App\Location" and $request->identification_date_day and !checkdate( $request->identification_date_month, $request->identification_date_day, $request->identification_date_year))
                $validator->errors()->add('identification_date_day', Lang::get('messages.invalid_identification_date_error'));
            // if month is unknown, day must be unknown too
            if ($request->date_day and ! $request->date_month)
                $validator->errors()->add('date_day', Lang::get('messages.invalid_date_error'));
            if ($request->parent_type == "App\Location" and $request->identification_date_day and ! $request->identification_date_month)
                $validator->errors()->add('identification_date_day', Lang::get('messages.invalid_identification_date_error'));
            // collection date must be in the past or today
            $colldate = strtotime( $request->date_year ."-". $request->date_month ."-". $request->date_day );
            if ($colldate > strtotime(date('Y-m-d')))
                    $validator->errors()->add('date_day', Lang::get('messages.date_future_error'));
            // identification date must be in the past or today AND equal or after collection date
            $iddate = strtotime( $request->identification_date_year ."-". $request->identification_date_month ."-". $request->identification_date_day);
            if ($request->parent_type == "App\Location" and ($iddate > strtotime(date('Y-m-d')) or $iddate < $colldate))
                    $validator->errors()->add('identification_date_day', Lang::get('messages.identification_date_future_error'));
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
        if (! Auth::user())
            return view('common.unauthorized');
	    $taxons = Taxon::leaf()->valid()->get();
	    $herbaria = Herbarium::all();
	    $locations = Location::all();
	    $persons = Person::all();
	    $plants = Plant::with('location')->get();
	    $projects = Auth::user()->projects;
	    $h_v = null;
        // TODO: better handling here
        if (! $projects->count())
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_project_error')]);
	    return view('vouchers.create', compact( 'taxons', 'persons', 'locations', 'projects', 'herbaria', 'plants', 'h_v'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->parent_type == "App\Location") {
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
        if ($request->parent_type == "App\Location") {
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
        }

        // common:
        if ($request->collector)
            foreach($request->collector as $collector){
                Log::info('registering collector '.$collector);
                $voucher->collectors()->create(['person_id' => $collector]);
            }

        return redirect('vouchers/'.$voucher->id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
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
