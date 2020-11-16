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
use App\UserJob;
use App\Jobs\ImportMeasurements;
use Spatie\SimpleExcel\SimpleExcelReader;
use Auth;
use Validator;
use Lang;
use App\DataTables\ActivityDataTable;

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
            'taxon' => $id
        ])->render('measurements.index', compact('object'));
    }

    public function indexDatasets($id, MeasurementsDataTable $dataTable)
    {
        //$dataset = Dataset::with(['measurements.measured', 'measurements.odbtrait'])->findOrFail($id);
        //check if dataset and trait are informed in id
        $ids = preg_split("/\|/", $id);
        $with = [
          'dataset' => isset($ids[0]) ? $ids[0] : null,
          'odbtrait' => isset($ids[1]) ? $ids[1] : null,
          'measured_type' => isset($ids[2]) ? $ids[2] : null,
        ];
        if (isset($ids[1])) {
          $odbtrait = ODBTrait::findOrFail($ids[1]);
        } else {
          $odbtrait = null;
        }
        $dataset = Dataset::findOrFail($id);
        return $dataTable->with($with)->render('measurements.index', compact('dataset','odbtrait'));
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
        if (!Auth::user()) {
            return view('common.unauthorized');
        }
        $persons = Person::all();
        $references = BibReference::all();
        $datasets = Auth::user()->datasets;

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
            if (ODBTrait::SPECTRAL !== $odbtrait->type and isset($odbtrait->range_min) and $request->value < $odbtrait->range_min) {
                $validator->errors()->add('value', Lang::get('messages.value_out_of_range'));
            }
            if (ODBTrait::SPECTRAL !== $odbtrait->type and isset($odbtrait->range_max) and $request->value > $odbtrait->range_max) {
                $validator->errors()->add('value', Lang::get('messages.value_out_of_range'));
            }
            // Checks if spectral has the correct number of values and if values are numeric
            if (ODBTrait::SPECTRAL == $odbtrait->type) {
               $spectrum = explode(";",$request->value);
               if (count($spectrum) != $odbtrait->value_length or count($spectrum) != count(array_filter($spectrum, "is_numeric"))) {
                $validator->errors()->add('value', Lang::get('messages.value_spectral').": ".count(explode(";",$request->value))." v.s. ".$odbtrait->value_length);
               }
            }
            // Checks if integer variable is integer type
            if (ODBTrait::QUANT_INTEGER == $odbtrait->type and strval($request->value) != strval(intval($request->value))) {
                $validator->errors()->add('value', Lang::get('messages.value_integer'));
            }
            if (in_array($odbtrait->type, [ODBTrait::QUANT_REAL, ODBTrait::LINK]) and isset($request->value)) {
                if (!is_numeric($request->value)) {
                  $validator->errors()->add('value', Lang::get('messages.value_numeric'));
                }
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
        // Fixes https://github.com/opendatabio/opendatabio/issues/218
        $odbtrait = ODBTrait::findOrFail($request->trait_id);
        if (ODBTrait::QUANT_REAL == $odbtrait->type) {
            $request->value = str_replace(',', '.', $request->value);
        }

        $measurement = new Measurement($request->only([
            'trait_id', 'measured_id', 'measured_type', 'dataset_id', 'person_id', 'bibreference_id', 'notes',
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
        if (!Auth::user()) {
            return view('common.unauthorized');
        }
        $measurement = Measurement::findOrFail($id);
        $object = $measurement->measured;
        $persons = Person::all();
        $references = BibReference::all();
        $datasets = Auth::user()->datasets;

        return view('measurements.create', compact('measurement', 'object', 'references', 'datasets', 'persons'));
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
        // Fixes https://github.com/opendatabio/opendatabio/issues/218
        $odbtrait = ODBTrait::findOrFail($request->trait_id);
        if (ODBTrait::QUANT_REAL == $odbtrait->type) {
            $request->value = str_replace(',', '.', $request->value);
        }
        $measurement->update($request->only([
            'trait_id', 'dataset_id', 'person_id', 'bibreference_id', 'notes',
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

    public function activity($id, ActivityDataTable $dataTable)
    {
        $object = Measurement::findOrFail($id);
        return $dataTable->with('measurement', $id)->render('common.activity',compact('object'));
    }

    public function importJob(Request $request)
    {
      $this->authorize('create', Measurement::class);
      $this->authorize('create', UserJob::class);
      if (!$request->hasFile('data_file')) {
          $message = Lang::get('messages.invalid_file_missing');
      } else {
        /*
            Validate attribute file
            Validate file extension and maintain original if valid or else
            Store may save a csv as a txt, and then the Reader will fail
        */
        $valid_ext = array("CSV","csv","ODS","ods","XLSX",'xlsx');
        $ext = $request->file('data_file')->getClientOriginalExtension();
        if (!in_array($ext,$valid_ext)) {
          $message = Lang::get('messages.invalid_file_extension');
        } else {
          $data = SimpleExcelReader::create($request->file('data_file'))->getRows()->toArray();
          if (count($data)>0) {
            UserJob::dispatch(ImportMeasurements::class,[
              'data' => $data,
            ]);
            $message = Lang::get('messages.dispatched');
          } else {
            $message = 'Something wrong with file';
          }
        }
      }
      return redirect('import/measurements')->withStatus($message);
    }
}
