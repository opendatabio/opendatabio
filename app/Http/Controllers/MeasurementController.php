<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Measurement;
use App\Plant;
use App\Voucher;
use App\Taxon;
use App\Location;
use App\ODBTrait;
use App\Person;
use App\Dataset;
use App\BibReference;
use Auth;
use Validator;
use Lang;

class MeasurementController extends Controller
{
    // The usual index method is hidden to provide a common interface to all requests
    // coming from different nested routes
    protected function index($object) {
        $measurements = $object->measurements()->orderBy('date','asc')->paginate(10);
        $measurements->load('odbtrait');
        return view('measurements.index', compact('object', 'measurements'));
    }
    public function indexPlants($id) {
        $plant = Plant::findOrFail($id);
        return $this->index($plant);
    }
    public function indexLocations($id) {
        $location = Location::findOrFail($id);
        return $this->index($location);
    }
    public function indexVouchers($id) {
        $voucher = Voucher::findOrFail($id);
        return $this->index($voucher);
    }
    public function indexTaxons($id) {
        $taxon = Taxon::findOrFail($id);
        return $this->index($taxon);
    }

    protected function create($object, $traits) {
        $persons = Person::all();
        $references = BibReference::all();
	    $datasets = Auth::user()->datasets;
        // TODO: better handling here
        if (! $datasets->count())
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_dataset_error')]);
        if (! $traits->count())
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_trait_error')]);
        return view('measurements.create', compact('object', 'traits', 'references', 'datasets', 'persons'));
    }
    public function createPlants($id) {
        $object = Plant::findOrFail($id);
        $traits = ODBTrait::appliesTo(Plant::class)->get();
        return $this->create($object, $traits);
    }
    public function createVouchers($id) {
        $object = Voucher::findOrFail($id);
        $traits = ODBTrait::appliesTo(Voucher::class)->get();
        return $this->create($object, $traits);
    }
    public function createLocations($id) {
        $object = Location::findOrFail($id);
        $traits = ODBTrait::appliesTo(Location::class)->get();
        return $this->create($object, $traits);
    }
    public function createTaxons($id) {
        $object = Taxon::findOrFail($id);
        $traits = ODBTrait::appliesTo(Taxon::class)->get();
        return $this->create($object, $traits);
    }

    public function show($id) {
        $measurement = Measurement::findOrFail($id);
        return view('measurements.show', compact('measurement'));
    }

    public function customValidate (Request $request) {
	    $rules = [
            'trait_id' => 'required|integer',
            'measured_id' => 'required|integer',
            'measured_type' => 'required|string',
            'date_year' => 'required|integer',
            'dataset_id' => 'required|integer',
            'person_id' => 'required|integer',
            'value' => 'required', // required IF trait type...
        ];
	    $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request) {
            $odbtrait = ODBTrait::findOrFail($request->trait_id);
            if (! $odbtrait->valid_type( $request->measured_type))
                    $validator->errors()->add('trait_id', Lang::get('messages.invalid_trait_type_error'));
            $colldate = [$request->date_month, $request->date_day, $request->date_year];
            if (!Measurement::checkDate($colldate))
                    $validator->errors()->add('date_day', Lang::get('messages.invalid_date_error'));
            // measurement date must be in the past or today
            if (!Measurement::beforeOrSimilar($colldate, date('Y-m-d')))
                    $validator->errors()->add('date_day', Lang::get('messages.date_future_error'));
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
        $dataset = Dataset::findOrFail($request->dataset_id);
        $this->authorize('create', [Measurement::class, $dataset]);
	    $validator = $this->customValidate($request);
	    if ($validator->fails()) {
		    return redirect()->back()
			    ->withErrors($validator)
			    ->withInput();
	    }
        $measurement = new Measurement($request->only([
            'trait_id', 'measured_id', 'measured_type', 'dataset_id', 'person_id', 'bibreference_id',
        ]));
        $measurement->valueActual = $request->value;
        $measurement->setDate($request->date_month, $request->date_day, $request->date_year);
        $measurement->save();

        return redirect('measurements/' . $measurement->id)->withStatus(Lang::get('messages.stored'));
    }

    public function edit($id) {
        $measurement = Measurement::findOrFail($id);
        $object = $measurement->measured;
        $traits = ODBTrait::appliesTo($measurement->measured_type)->get();
        $persons = Person::all();
        $references = BibReference::all();
	    $datasets = Auth::user()->datasets;
        // TODO: better handling here
        if (! $datasets->count())
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_dataset_error')]);
        if (! $traits->count())
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_trait_error')]);
        return view('measurements.create', compact('measurement', 'object', 'traits', 'references', 'datasets', 'persons'));
    }
    public function update(Request $request, $id)
    {
        $measurement = Measurement::findOrFail($id);
        $this->authorize('update', $measurement);
        $validator = $this->customValidate($request);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $measurement->update($request->only([
            'trait_id', 'dataset_id', 'person_id', 'bibreference_id',
        ]));
        $measurement->valueActual = $request->value;
        $measurement->setDate($request->date_month, $request->date_day, $request->date_year);
        $measurement->save();
        return redirect('measurements/' . $id)->withStatus(Lang::get('messages.saved'));
    }
}