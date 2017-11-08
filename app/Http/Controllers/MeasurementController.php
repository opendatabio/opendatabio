<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\MeasurementsDataTable;
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
    public function indexPlants($id, MeasurementsDataTable $dataTable)
    {
        $object = Plant::findOrFail($id);

        return $dataTable->with([
            'measured_type' => 'App\Plant',
            'measured' => $id,
        ])->render('measurements.index', compact('object'));
    }

    public function indexLocations($id, MeasurementsDataTable $dataTable)
    {
        $object = Location::findOrFail($id);

        return $dataTable->with([
            'measured_type' => 'App\Location',
            'measured' => $id,
        ])->render('measurements.index', compact('object'));
    }

    public function indexVouchers($id, MeasurementsDataTable $dataTable)
    {
        $object = Voucher::findOrFail($id);

        return $dataTable->with([
            'measured_type' => 'App\Voucher',
            'measured' => $id,
        ])->render('measurements.index', compact('object'));
    }

    public function indexTaxons($id, MeasurementsDataTable $dataTable)
    {
        $object = Taxon::findOrFail($id);

        return $dataTable->with([
            'measured_type' => 'App\Taxon',
            'measured' => $id,
        ])->render('measurements.index', compact('object'));
    }

    public function indexDatasets($id, MeasurementsDataTable $dataTable)
    {
        $dataset = Dataset::findOrFail($id);

        return $dataTable->with([
            'dataset' => $id,
        ])->render('measurements.index', compact('dataset'));
    }

    public function indexTraits($id, MeasurementsDataTable $dataTable)
    {
        $odbtrait = ODBTrait::findOrFail($id);

        return $dataTable->with([
            'odbtrait' => $id,
        ])->render('measurements.index', compact('odbtrait'));
    }

    protected function create($object)
    {
        $persons = Person::all();
        $references = BibReference::all();
        $datasets = Auth::user()->datasets;
        // TODO: better handling here
        if (!$datasets->count()) {
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_dataset_error')]);
        }

        return view('measurements.create', compact('object', 'references', 'datasets', 'persons'));
    }

    public function createPlants($id)
    {
        $object = Plant::findOrFail($id);

        return $this->create($object);
    }

    public function createVouchers($id)
    {
        $object = Voucher::findOrFail($id);

        return $this->create($object);
    }

    public function createLocations($id)
    {
        $object = Location::findOrFail($id);

        return $this->create($object);
    }

    public function createTaxons($id)
    {
        $object = Taxon::findOrFail($id);

        return $this->create($object);
    }

    public function show($id)
    {
        $measurement = Measurement::findOrFail($id);

        return view('measurements.show', compact('measurement'));
    }

    public function customValidate(Request $request)
    {
        $rules = [
            'trait_id' => 'required|integer',
            'measured_id' => 'required|integer',
            'measured_type' => 'required|string',
            'date_year' => 'required|integer',
            'dataset_id' => 'required|integer',
            'person_id' => 'required|integer',
        ];
        $validator = Validator::make($request->all(), $rules);
        $validator->sometimes('value', 'required', function ($request) {
            $odbtrait = ODBTrait::findOrFail($request->trait_id);

            return ODBTrait::LINK != $odbtrait->type;
        });
        $validator->sometimes('link_id', 'required', function ($request) {
            $odbtrait = ODBTrait::findOrFail($request->trait_id);

            return ODBTrait::LINK == $odbtrait->type;
        });
        $validator->after(function ($validator) use ($request) {
            $odbtrait = ODBTrait::findOrFail($request->trait_id);
            if (!$odbtrait->valid_type($request->measured_type)) {
                $validator->errors()->add('trait_id', Lang::get('messages.invalid_trait_type_error'));
            }
            $colldate = [$request->date_month, $request->date_day, $request->date_year];
            if (!Measurement::checkDate($colldate)) {
                $validator->errors()->add('date_day', Lang::get('messages.invalid_date_error'));
            }
            // measurement date must be in the past or today
            if (!Measurement::beforeOrSimilar($colldate, date('Y-m-d'))) {
                $validator->errors()->add('date_day', Lang::get('messages.date_future_error'));
            }
            if (isset($odbtrait->range_min) and $request->value < $odbtrait->range_min) {
                $validator->errors()->add('value', Lang::get('messages.value_out_of_range'));
            }
            if (isset($odbtrait->range_max) and $request->value > $odbtrait->range_max) {
                $validator->errors()->add('value', Lang::get('messages.value_out_of_range'));
            }
            if (in_array($odbtrait->type, [ODBTrait::CATEGORICAL, ODBTrait::ORDINAL, ODBTrait::CATEGORICAL_MULTIPLE])) {
                // validates that the chosen category is ACTUALLY from the trait
                $valid = $odbtrait->categories->pluck('id')->all();
                if (is_array($request->value)) {
                    foreach ($request->value as $value) {
                        if (!in_array($value, $valid)) {
                            $validator->errors()->add('value', Lang::get('messages.trait_measurement_mismatch'));
                        }
                    }
                } elseif ($request->value) {
                    if (!in_array($request->value, $valid)) {
                        $validator->errors()->add('value', Lang::get('messages.trait_measurement_mismatch'));
                    }
                }
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
        $measurement->setDate($request->date_month, $request->date_day, $request->date_year);
        $measurement->save();
        if (ODBTrait::LINK == $measurement->type) {
            $measurement->value = $request->value;
            $measurement->value_i = $request->link_id;
        } else {
            $measurement->valueActual = $request->value;
        }
        $measurement->save();

        return redirect('measurements/'.$measurement->id)->withStatus(Lang::get('messages.stored'));
    }

    public function edit($id)
    {
        $measurement = Measurement::findOrFail($id);
        $object = $measurement->measured;
        $traits = ODBTrait::appliesTo($measurement->measured_type)->get();
        $persons = Person::all();
        $references = BibReference::all();
        $datasets = Auth::user()->datasets;
        // TODO: better handling here
        if (!$datasets->count()) {
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_dataset_error')]);
        }
        if (!$traits->count()) {
            return view('common.errors')->withErrors([Lang::get('messages.no_valid_trait_error')]);
        }

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
        if (ODBTrait::LINK == $measurement->type) {
            $measurement->value = $request->value;
            $measurement->value_i = $request->link_id;
        } else {
            $measurement->valueActual = $request->value;
        }
        $measurement->setDate($request->date_month, $request->date_day, $request->date_year);
        $measurement->save();

        return redirect('measurements/'.$id)->withStatus(Lang::get('messages.saved'));
    }
}
