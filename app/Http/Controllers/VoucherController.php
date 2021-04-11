<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\DataTables\VouchersDataTable;
use Validator;
use App\Models\Individual;
use App\Models\Person;
use App\Models\Project;
use App\Models\Location;
use App\Models\Biocollection;
use App\Models\Identification;
use App\Models\Voucher;
use App\Models\Taxon;
use App\Models\Dataset;
use App\Models\Summary;
use Auth;
use Lang;
use Activity;
use Response;
use App\Models\ActivityFunctions;
use App\DataTables\ActivityDataTable;

use App\Models\UserJob;
use App\Jobs\ImportVouchers;
use Spatie\SimpleExcel\SimpleExcelReader;



class VoucherController extends Controller
{

    /**
      * Autocompleting in dropdowns. Expects a $request->query input
    **/
    public function autocomplete(Request $request)
    {
      $query = $request->input('query');
      $vouchers = Voucher::join('persons','person_id','=','persons.id')->where('number', 'like', ["{$query}%"])->orWhere('persons.full_name','like',["%{$query}%"])->take(30)->get();
      $vouchers = collect($vouchers)->transform(function ($voucher) {
          $voucher->value = $voucher->fullname;
          return $voucher;
      });
      return Response::json(['suggestions' => $vouchers]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(VouchersDataTable $dataTable)
    {
        return $dataTable->render('vouchers.index', []);
    }


    public function indexBioCollections($id, VouchersDataTable $dataTable)
    {
        $object = Biocollection::findOrFail($id);
        return $dataTable->with('biocollection_id', $id)->render('vouchers.index', compact('object'));
    }

    public function indexTaxons($id, VouchersDataTable $dataTable)
    {
        $object = Taxon::findOrFail($id);

        return $dataTable->with('taxon', $id)->render('vouchers.index', compact('object'));
    }
    public function indexTaxonsProjects($id, VouchersDataTable $dataTable)
    {
        $ids = explode('|',$id);
        $object = Taxon::findOrFail($ids[0]);
        $object_second = Project::findOrFail($ids[1]);
        return $dataTable->with(['taxon' => $ids[0],'project' => $ids[1]])->render('vouchers.index', compact('object','object_second'));
    }
    public function indexTaxonsDatasets($id, VouchersDataTable $dataTable)
    {
        $ids = explode('|',$id);
        $object = Taxon::findOrFail($ids[0]);
        $object_second = Dataset::findOrFail($ids[1]);
        return $dataTable->with(['taxon' => $ids[0],'dataset' => $ids[1]])->render('vouchers.index', compact('object','object_second'));
    }

    public function indexTaxonsLocations($id, VouchersDataTable $dataTable)
    {
        $ids = explode('|',$id);
        $object = Taxon::findOrFail($ids[0]);
        $object_second = Location::findOrFail($ids[1]);
        return $dataTable->with(['taxon' => $ids[0],'location' => $ids[1]])->render('measurements.index', compact('object','object_second'));
    }

    public function indexProjects($id, VouchersDataTable $dataTable)
    {
        $object = Project::findOrFail($id);

        return $dataTable->with('project', $id)->render('vouchers.index', compact('object'));
    }

    public function indexPersons($id, VouchersDataTable $dataTable)
    {
        $object = Person::findOrFail($id);

        return $dataTable->with('person', $id)->render('vouchers.index', compact('object'));
    }

    public function indexLocations($id, VouchersDataTable $dataTable)
    {
        $object = Location::findOrFail($id);

        return $dataTable->with('location', $id)->render('vouchers.index', compact('object'));
    }

    public function indexLocationsProjects($id, VouchersDataTable $dataTable)
    {
        $ids = explode('|',$id);
        $object = Location::findOrFail($ids[0]);
        $object_second = Project::findOrFail($ids[1]);
        return $dataTable->with(['location' => $ids[0],'project' => $ids[1]])->render('vouchers.index', compact('object','object_second'));
    }
    public function indexLocationsDatasets($id, VouchersDataTable $dataTable)
    {
        $ids = explode('|',$id);
        $object = Location::findOrFail($ids[0]);
        $object_second = Dataset::findOrFail($ids[1]);
        return $dataTable->with(['location' => $ids[0],'dataset' => $ids[1]])->render('vouchers.index', compact('object','object_second'));
    }


    public function indexIndividuals($id, VouchersDataTable $dataTable)
    {
        $object = Individual::findOrFail($id);

        return $dataTable->with('individual', $id)->render('vouchers.index', compact('object'));
    }

    public function indexDatasets($id, VouchersDataTable $dataTable)
    {
        $object = Dataset::findOrFail($id);
        return $dataTable->with('dataset', $id)->render('vouchers.index',compact('object'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createIndividuals($id)
    {
        $individual = Individual::findOrFail($id);

        return $this->create($individual);
    }

    public function createLocations($id)
    {
        $location = Location::findOrFail($id);

        return $this->create($location);
    }

    protected function create($individual=null)
    {
        if (!Auth::user()) {
            return view('common.unauthorized');
        }
        $biocollections = Biocollection::orderBy('acronym')->cursor();
        $persons = Person::all();
        $projects = Auth::user()->projects;

        return view('vouchers.create', compact('persons','biocollections','individual','projects'));
    }



    //validate request values when storing or updating records
    public function customValidate(Request $request, Voucher $voucher = null)
    {
        // to check for duplicates
        $voucherid = null;
        if ($voucher) {
            //if editing ignores self
            $voucherid = $voucher->id;
        }

        //these are mandatory in any case
        $rules = [
            'individual_id' => 'required|integer',
            'biocollection_id' => 'required|integer',
            'biocollection_type' => 'required|integer',
            'project_id' => 'required|integer'
        ];
        $checkcollector = false;
        //if number or collector is provided, then this required different validation
        if (null != $request->number or null != $request->collector) {
            $rules['collector'] = 'array';
            $rules['number'] = [
                'required',
                'string',
                'max:191'
            ];
            $checkcollector = true;
        }
        //apply rules
        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request,$checkcollector,$voucherid) {
            //validate if collector, number and date if provided
            if ($checkcollector) {
              //validate date
              // validates date
              if (isset($request->date_month)) {
                $colldate = [$request->date_month, $request->date_day, $request->date_year];
              } else {
                $colldate = $request->date;
              }
              if (null == $colldate) {
                $validator->errors()->add('date_day', Lang::get('messages.missing_date'));
              } else {
                if (!Voucher::checkDate($colldate)) {
                  $validator->errors()->add('date_day', Lang::get('messages.invalid_date_error'));
                }
                // collection date must be in the past or today
                if (!Voucher::beforeOrSimilar($colldate, date('Y-m-d'))) {
                    $validator->errors()->add('date_day', Lang::get('messages.date_future_error'));
                  }
              }

              //validate unique records
              //vouchers belong to individuals which already controls uniqueness of collector_main+numberOrtag+location
              //a new voucher with a collector info, can not have: a) an individual other then selfwith same location, tag=number and same main collector; b) a voucher with same number and main collector and same biocollection_id+biocollection_number
              $individual = Individual::findOrFail($request->individual_id);
              $locationid = $individual->locations()->pluck('locations.id')->toArray();
              $number = $request->number;
              $maincollector= $request->collector[0];
              $hasotherindividual = Individual::withoutGlobalScopes()->where('tag',$number)->whereHas('collector_main',function($q) use($maincollector) {
                $q->where('collectors.person_id',$maincollector);
              })->whereHas('location_first',function($q) use($locationid){
                $q->whereIn('location_id',$locationid);
              })->where('individuals.id',"<>",$individual->id)->count();

              $isunique = Voucher::whereHas('collector_main',function($q) use($maincollector) {
                $q->where('collectors.person_id',$maincollector);
              })->where('number',$number)->where('biocollection_id',$request->biocollection_id);
              if ($voucherid) {
                $isunique = $isunique->where('id',"<>",$voucherid);
              }
              if ($request->biocollection_number) {
                $isunique = $isunique->where('biocollection_number',$request->biocollection_number);
              } else {
                $isunique = $isunique->whereNull('biocollection_number');
              }
              $isunique = ($isunique->count()) + ($hasotherindividual);
              if ($isunique > 0) {
                $validator->errors()->add('number', Lang::get('messages.voucher_duplicate_identifier'));
              }
            }

            $isunique = Voucher::where('individual_id',$request->individual_id)->where('biocollection_id',$request->biocollection_id);
            //ignore if editing;
            if ($voucherid) {
              $isunique = $isunique->where('id',"<>",$voucherid);
            }
            if ($request->biocollection_number) {
              $isunique = $isunique->where('biocollection_number',$request->biocollection_number);
            } else {
              $isunique = $isunique->whereNull('biocollection_number');
            }
            if ($isunique->count() > 0) {
              $validator->errors()->add('biocollection_id', Lang::get('messages.voucher_duplicate_identifier'));
            }
        });

        return $validator;
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
        $identification = $voucher->identification;
        $collectors = $voucher->collectors;
        $media = $voucher->media();
        if ($media->count()) {
          $media = $media->paginate(3);
        } else {
          $media = null;
        }
        return view('vouchers.show', compact('voucher', 'identification', 'collectors','media'));
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
        $biocollections = Biocollection::all();
        $persons = Person::all();
        $projects = Auth::user()->projects;
        return view('vouchers.create', compact('voucher', 'persons', 'projects', 'biocollections'));
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
        $this->authorize('create', [Voucher::class, $project]);
        $validator = $this->customValidate($request);
        if ($validator->errors()->count()) {
            if ($request->from_the_api) {
              return implode(" | ",$validator->errors()->all());
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $voucher = new Voucher($request->only(['individual_id','biocollection_id','biocollection_type','biocollection_number','number', 'notes', 'project_id']));

        //date and collector only if provided as they may be that of the individual
        if (isset($request->date_month)) {
          $voucher->setDate($request->date_month, $request->date_day, $request->date_year);
        } elseif (isset($request->date)) {
          $voucher->setDate($request->date);
        }
        $voucher->save();

        // common:
        if ($request->collector) {
            $idx = 0;
            foreach ($request->collector as $collector) {
                if ($idx==0) {
                  $values = [
                    'person_id' => $collector,
                    'main' => 1,
                  ];
                } else {
                  $values = [
                    'person_id' => $collector
                  ];
                }
                $voucher->collectors()->create($values);
                $idx = $idx+1;
            }
        }

        //for summary count updates
        /*
        $individual = Individual::findOrFail($request->individual_id);
        $newvalues =  [
                 "taxon_id" => $individual->identification->taxon_id,
                 "location_id" => $individual->locations->last()->id,
                 "project_id" => $request->project_id
        ];

         //UPDATE SUMMARY COUNTS
        $oldvalues =  [
              "taxon_id" => null,
              "location_id" => null,
              "project_id" => null,
        ];
        $target = 'vouchers';
        $datasets = null;

        Summary::updateSummaryCounts($newvalues,$oldvalues,$target,$datasets,0);
        */
        /* END SUMMARY UPDATE */

        if ($request->from_the_api) {
           return $voucher;
        }
        return redirect('vouchers/'.$voucher->id)->withStatus(Lang::get('messages.stored'));
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

        //for summary counts
        $oldindividual = $voucher->individual;
        $oldvalues = [
            'project_id' => $voucher->project_id,
            'location_id' => $oldindividual->locations->last()->id,
            'taxon_id' => $oldindividual->identification->taxon_id
        ];

        //for summary counts
        $individual = Individual::findOrFail($request->individual_id);
        $newvalues = [
            'location_id' => $individual->locations->last()->id,
            'taxon_id' => $individual->identification->taxon_id,
            'project_id' => $request->project_id
        ];

        $voucher->update($request->only(['individual_id','biocollection_id','biocollection_type','biocollection_number','number', 'notes', 'project_id']));

        if ($request->date_year) {
          $voucher->setDate($request->date_month, $request->date_day, $request->date_year);
          $voucher->save();
        } else {
          $voucher->date = null;
          $voucher->save();
        }

        // COLLECTOR UPDATE:
        if ($request->collector) {
          //did collectors changed?
          // "sync" collectors. See app/Project.php / setusers()
          $current = $voucher->collectors->pluck('person_id');
          $detach = $current->diff($request->collector)->all();
          $attach = collect($request->collector)->diff($current)->all();
          if ($detach->count() or $attach->count()) {
              //delete old collectors
              $voucher->collectors()->delete();
              //save collectors and identify main collector
              $first = true;
              foreach ($request->collector as $collector) {
                  $thecollector = new Collector(['person_id' => $collector]);
                  if ($first) {
                      $thecollector->main = 1;
                  }
                  $voucher->collectors()->save($thecollector);
                  $first = false;
              }
          }
          //log changes in collectors if any
          ActivityFunctions::logCustomPivotChanges($voucher,$current->all(),$request->collector,'voucher','collector updated',$pivotkey='person');
        } else {
        //no more collectors?
        if ($voucher->collectors->count()) {
            $current = $voucher->collectors->pluck('person_id');
            $voucher->collectors()->delete();
            ActivityFunctions::logCustomPivotChanges($voucher,$current->all(),[],'voucher','collector updated',$pivotkey='person');
        }
      }


        /* UPDATE SUMMARY COUNTS */
        $target = 'vouchers';
        $datasets = array_unique($voucher->measurements()->withoutGlobalScopes()->pluck('dataset_id')->toArray());
        $measurements_count = $voucher->measurements()->withoutGlobalScopes()->count();
        Summary::updateSummaryCounts($newvalues,$oldvalues,$target,$datasets,$measurements_count);
        /* END SUMMARY UPDATE */


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



    public function activity($id, ActivityDataTable $dataTable)
    {
      $object = Voucher::findOrFail($id);
      return $dataTable->with('voucher', $id)->render('common.activity',compact('object'));
    }


        public function importJob(Request $request)
        {
          $this->authorize('create', Voucher::class);
          $this->authorize('create', UserJob::class);
          if (!$request->hasFile('data_file')) {
              $message = Lang::get('messages.invalid_file_missing');
          } else {
            /*
                Validate attribute file
                Validate file extension and maintain original if valid or else
                Store may save a csv as a txt, and then the Reader will fail
            */
            $valid_ext = array("csv","ods",'xlsx');
            $ext = mb_strtolower($request->file('data_file')->getClientOriginalExtension());
            if (!in_array($ext,$valid_ext)) {
              $message = Lang::get('messages.invalid_file_extension');
            } else {
              try {
                $data = SimpleExcelReader::create($request->file('data_file'),$ext)->getRows()->toArray();
              } catch (\Exception $e) {
                $data = [];
                $message = json_encode($e);
              }
              if (count($data)>0) {
                UserJob::dispatch(ImportVouchers::class,[
                  'data' => ['data' => $data],
                ]);
                $message = Lang::get('messages.dispatched');
              } else {
                $message = 'Something wrong with file'.$message;
              }
            }
          }
          return redirect('import/vouchers')->withStatus($message);
        }

}
