<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;
use App\Models\Individual;
use App\Models\Person;
use App\Models\Collector;
use App\Models\Project;
use App\Models\Taxon;
use App\Models\Location;
use App\Models\Biocollection;
use App\Models\Identification;
use DB;
use App\Models\IndividualLocation;
use App\DataTables\IndividualsDataTable;

use App\Models\Dataset;
use App\Models\Summary;
use Auth;
use Response;
use Lang;
use App\Models\UserJob;
use App\Jobs\BatchUpdateIndividuals;
use App\Jobs\ImportIndividuals;
use Spatie\SimpleExcel\SimpleExcelReader;

use Activity;
use App\Models\ActivityFunctions;
use App\DataTables\ActivityDataTable;
use App\DataTables\IndividualLocationsDataTable;

class IndividualController extends Controller
{
    /**
      * Autocompleting in dropdowns. Expects a $request->query input
    **/
    public function autocomplete(Request $request)
    {

      $individuals = Individual::select(DB::raw('id as data, odb_ind_fullname(id,tag) as value'))->where('tag','like',$request->input('query')."%")->take(30)->get();

      /*
      $individuals = Individual::selectRaw("individuals.id as data, tag as value")
                ->where('tag','like',$request->input('query')."%")->orWhereHas('collectors',function($collectors) use($request) {
                  $collectors->whereHas('person',function($person) use($request) {
                    $person->where('full_name','like',$request->input('query')."%");
                  });
                })
                ->get();

      $individuals = collect($individuals)->transform(function ($individual) {
          $individual->value = $individual->fullname;
          return $individual;
      });
      */
      //$individuals = array('id' => 1, 'value' => "This is a test");
      return Response::json(['suggestions' => $individuals]);
    }

    /*
      * Batch update identifications through the web interface
    */
    public function batchidentifications(Request $request)
    {
        $this->authorize('create', Individual::class);
        $this->authorize('create', UserJob::class);
        UserJob::dispatch(BatchUpdateIndividuals::class,
        [
          'data' => ['data' => $request->all(),
          'header' => ['not_external' => 1]
          ]
        ]);
        return redirect('individuals')->withStatus(Lang::get('messages.dispatched'));
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndividualsDataTable $dataTable)
    {
        $biocollections = Biocollection::all();
        return $dataTable->render('individuals.index',compact('biocollections'));
    }

    public function indexLocations($id, IndividualsDataTable $dataTable)
    {
        $object = Location::findOrFail($id);
        return $dataTable->with('location', $id)->render('individuals.index', compact('object'));
    }

    public function indexLocationsProjects($id, IndividualsDataTable $dataTable)
    {
        $ids = explode('|',$id);
        $object = Location::findOrFail($ids[0]);
        $object_second = Project::findOrFail($ids[1]);
        return $dataTable->with(['location' => $ids[0],'project' => $ids[1]])->render('individuals.index', compact('object','object_second'));
    }
    public function indexLocationsDatasets($id, IndividualsDataTable $dataTable)
    {
        $ids = explode('|',$id);
        $object = Location::findOrFail($ids[0]);
        $object_second = Dataset::findOrFail($ids[1]);
        return $dataTable->with(['location' => $ids[0],'dataset' => $ids[1]])->render('individuals.index', compact('object','object_second'));
    }

    public function indexTaxons($id, IndividualsDataTable $dataTable)
    {
        $object = Taxon::findOrFail($id);

        return $dataTable->with('taxon', $id)->render('individuals.index', compact('object'));
    }

    public function indexTaxonsProjects($id, IndividualsDataTable $dataTable)
    {
        $ids = explode('|',$id);
        $object = Taxon::findOrFail($ids[0]);
        $object_second = Project::findOrFail($ids[1]);
        return $dataTable->with(['taxon' => $ids[0],'project' => $ids[1]])->render('individuals.index', compact('object','object_second'));
    }
    public function indexTaxonsDatasets($id, IndividualsDataTable $dataTable)
    {
        $ids = explode('|',$id);
        $object = Taxon::findOrFail($ids[0]);
        $object_second = Dataset::findOrFail($ids[1]);
        return $dataTable->with(['taxon' => $ids[0],'dataset' => $ids[1]])->render('individuals.index', compact('object','object_second'));
    }

    public function indexTaxonsLocations($id, IndividualsDataTable $dataTable)
    {
        $ids = explode('|',$id);
        $object = Taxon::findOrFail($ids[0]);
        $object_second = Location::findOrFail($ids[1]);
        return $dataTable->with(['taxon' => $ids[0],'location' => $ids[1]])->render('measurements.index', compact('object','object_second'));
    }


    public function indexProjects($id, IndividualsDataTable $dataTable)
    {
        $object = Project::findOrFail($id);
        return $dataTable->with('project', $id)->render('individuals.index', compact('object'));
    }

    public function indexPersons($id, IndividualsDataTable $dataTable)
    {
        $object = Person::findOrFail($id);
        return $dataTable->with('person', $id)->render('individuals.index', compact('object'));
    }

    public function indexDatasets($id, IndividualsDataTable $dataTable)
    {
        $object = Dataset::findOrFail($id);
        return $dataTable->with('dataset', $id)->render('individuals.index',compact('object'));
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Location $location = null, IndividualLocationsDataTable $dataTable)
    {
        if (!Auth::user()) {
            return view('common.unauthorized');
        }
        $biocollections = Biocollection::all();
        $persons = Person::all();
        $projects = Auth::user()->projects;

        return $dataTable->with('individual_id',1)->render('individuals.create', compact('persons', 'projects', 'biocollections', 'location'));
    }

    // Route for quicly creating an individual from a Location page
    public function createLocations($id)
    {
        $location = Location::findOrFail($id);
        return $this->create($location);
    }


    /* VALIDATE CREATION OF INDIVIDUALS */
    public function customValidate(Request $request, Individual $individual = null)
    {
        //fields to check for duplicates
        $individualid = null;
        $locationid = null;
        /* edition */
        if ($individual) {
          $individualid = $individual->id;
          $locationid = $individual->location_first->first()->id;
          $rules = [];
        } else {
          //if creating must have a location
          $locationid = $request->location_id;
          $rules = ['location_id' => 'required|integer'];
        }
        $location = Location::find($locationid);
        $rules = array_merge((array) $rules, (array) [
            'project_id' => 'required|integer',
            'collector' => "required|array",
            'tag' => [ // tag / location must be unique
                'required',
                'string',
                'max:191'
            ],
        ]);
        //identification is not mandatory, but if informed must have some fields
        if ($request->taxon_id) {
           $rules = array_merge((array) $rules, (array)
            [
              'identifier_id' => 'required',
              'taxon_id' => 'required',
              'biocollection_reference' => 'required_with:biocollection_id'
            ]);
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
          return $validator;
        }

        $validator->after(function ($validator) use ($request, $location, $individual) {

            //valide unique identifiers
            $isunique = Individual::where('tag',$request->tag)->whereHas('collector_main', function ($query) use ($request) {
                $query->where('person_id', $request->collector[0]);
            })->whereHas('location_first',function ($query) use ($location) {
                $query->where('location_id', $location->id);
            });
            if ($individual) {
              $isunique = $isunique->where('id','<>',$individual->id);
            }
            if ($isunique->count()) {
              $validator->errors()->add('tag', Lang::get('messages.duplicate_individual_identifier'));
            }

            // validates date
            if (isset($request->date_month)) {
              $colldate = [$request->date_month, $request->date_day, $request->date_year];
            } else {
              $colldate = $request->date;
            }
            if (!Individual::checkDate($colldate)) {
                $validator->errors()->add('date_day', Lang::get('messages.invalid_date_error'));
            }
            // collection date must be in the past or today
            if (!Individual::beforeOrSimilar($colldate, date('Y-m-d'))) {
                $validator->errors()->add('date_day', Lang::get('messages.date_future_error'));
            }
            //if it has identification, then date is mandatory
            if ($request->taxon_id) {
              if (isset($request->identification_date_month)) {
                $iddate = [$request->identification_date_month, $request->identification_date_day, $request->identification_date_year];
              } elseif (isset($request->identification_date)){
                $iddate = $request->identification_date;
              } else {
                $iddate = null;
              }
              if (null != $iddate) {
                if (!Individual::checkDate($iddate)) {
                  $validator->errors()->add('identification_date_day', Lang::get('messages.invalid_identification_date_error'));
                }
              // identification date must be in the past or today AND equal or after collection date
                if (!(Individual::beforeOrSimilar($iddate, date('Y-m-d')) and
                    Individual::beforeOrSimilar($colldate, $iddate))) {
                      $validator->errors()->add('identification_date_day', Lang::get('messages.identification_date_future_error'));
                    }
              }
            }
            //if not editing then location must have additon checks
            if (!$individual and ($request->distance or $request->x)) {
              // validates xy / angdist
              if (Location::LEVEL_POINT == $location->adm_level) {
                if ($request->distance < 0 or $request->angle < 0 or $request->angle > 360 or null == self::validateRelativePosition($request->angle,$request->distance,$location->adm_level)) {
                    $validator->errors()->add('distance', Lang::get('messages.individual_ang_dist_error'));
                }
              } elseif (100 == $request->location_type or 101 == $request->location_type and $location) {
                if ($request->x < 0 or $request->y < 0 or $request->x > $location->x or $request->y > $location->y or null == self::validateRelativePosition($request->x,$request->y,$location->adm_level)) {
                    $validator->errors()->add('x', Lang::get('messages.individual_xy_error'));
                }
              }
           }
        });
        return $validator;
    }

    public function validateRelativePosition($x, $y = null,$location_type)
    {
        if (is_null($x) or is_null($y)) {
          return null;
        }
        //https://math.stackexchange.com/questions/143932/calculate-point-given-x-y-angle-and-distance
        if (Location::LEVEL_POINT == $location_type) {
            //convert angle to radians ()
            $angle =  $x * M_PI / 180;
            $distance = $y;
            // converts the angle and distance to x/y
            $x = $distance * cos($angle);
            $y = $distance * sin($angle);
        }

        // MariaDB returns 1 for invalid geoms from ST_IsEmpty ref: https://mariadb.com/kb/en/mariadb/st_isempty/
        $invalid = DB::select("SELECT ST_IsEmpty(GeomFromText('POINT($y $x)')) as val")[0]->val;
        if ($invalid) {
            return null;
        }
        return DB::raw("GeomFromText('POINT($y $x)')");
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
        $this->authorize('create', [Individual::class, $project]);
        $validator = $this->customValidate($request);
        if ($validator->fails()) {
          if ($request->from_the_api) {
            return implode(" | ",$validator->errors()->all());
          }
          return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $individual = new Individual($request->only(['tag', 'project_id', 'notes']));

        //set incomplete date
        if (isset($request->date_month)) {
          $individual->setDate($request->date_month, $request->date_day, $request->date_year);
        } else {
          $individual->setDate($request->date);
        }
        $individual->save();
        $routeId = $individual->id;
        //save collectors and identify main collector
        $first = true;
        foreach ($request->collector as $collector) {
            $thecollector = new Collector(['person_id' => $collector]);
            if ($first) {
                $thecollector->main = 1;
            }
            $individual->collectors()->save($thecollector);
            $first = false;
        }

        //save location, there should be only one
        $firstlocation = [
          'date_time' => $request->location_date_time,
          'altitude' => $request->altitude,
          'notes' => $request->location_notes,
          'first' => 1
        ];
        $location = Location::find($request->location_id);
        if (Location::LEVEL_POINT == $location->adm_level) {
          $firstlocation['relative_position'] = self::validateRelativePosition($request->angle,$request->distance,$location->adm_level);
        } else {
          $firstlocation['relative_position'] = self::validateRelativePosition($request->x,$request->y,$location->adm_level);
        }
        $tosync = [];
        $tosync[$request->location_id] = $firstlocation;
        $individual->locations()->syncWithoutDetaching($tosync);
        //$individual->save();
        $newtaxon_id = null;
        if ($request->identification_individual_id) {
          //other identification
          $individual->identification_individual_id = $request->identification_individual_id;
          $individual->save();
          $newtaxon_id = Individual::findOrFail($request->identification_individual_id)->identification->taxon_id;
        } elseif ($request->taxon_id) {
            //self identification
            $individual->identification_individual_id = $individual->id;
            $individual->save();
            $newtaxon_id = $request->taxon_id;
            $individual->identificationSet = new Identification([
              'object_id' => $individual->id,
              'object_type' => 'App\Models\Individual',
              'person_id' => $request->identifier_id,
              'taxon_id' => $request->taxon_id,
              'modifier' => $request->modifier,
              'biocollection_id' => $request->biocollection_id,
              'biocollection_reference' => $request->biocollection_reference,
              'notes' => $request->identification_notes,
            ]);
            if (isset($request->identification_date_month)) {
              $individual->identificationSet->setDate($request->identification_date_month,
                $request->identification_date_day,
                $request->identification_date_year);
            } else {
              $individual->identificationSet->setDate($request->identification_date);
            }
            $individual->identificationSet->save();

        }


        /* UPDATE SUMMARY COUNTS */
        $newvalues =  [
             "taxon_id" => $newtaxon_id,
             "location_id" => $request->location_id,
             "project_id" => $request->project_id
        ];

        $oldvalues =  [
            "taxon_id" => null,
            "location_id" => null,
            "project_id" => null,
        ];

        $target = 'individuals';
        $datasets = null;
        /*Summary::updateSummaryCounts($newvalues,$oldvalues,$target,$datasets,$measurements_count=0);
        /* END SUMMARY UPDATE */

        /*if this is called in a job do not redirect and return object instead */
        if ($request->from_the_api) {
           return $individual;
        }
        return redirect('individuals/'.$individual->id)->withStatus(Lang::get('messages.stored'));
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id, IndividualLocationsDataTable $dataTable)
    {
        $individual = Individual::findOrFail($id);
        $identification = $individual->identification;
        $collectors = $individual->collectors;
        $media = $individual->media();
        if ($media->count()) {
          $media = $media->paginate(3);
        } else {
          $media = null;
        }
        return $dataTable->with([
              'individual' => $id,
              'noaction' => 1])->render('individuals.show', compact('individual', 'identification', 'collectors','media'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id, IndividualLocationsDataTable $dataTable)
    {
        $individual = Individual::findOrFail($id);
        if (!Auth::user()) {
            return view('common.unauthorized');
        }
        $biocollections = Biocollection::all();
        $persons = Person::all();
        $projects = Auth::user()->projects;
        return $dataTable->with('individual', $id)->render('individuals.create', compact('individual', 'persons', 'projects', 'biocollections'));
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
        $individual = Individual::findOrFail($id);
        $this->authorize('update', $individual);
        $validator = $this->customValidate($request, $individual);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        /* array for summaries table updates */
        $oldtaxon_id = null;
        if ($individual->identification) {
          $oldtaxon_id = $individual->identification->taxon_id;
        }
        $oldvalues =  [
            "taxon_id"  =>  $oldtaxon_id,
            "project_id" => $individual->project_id,
            "location_id" => $request->oldlocation_id,
        ];


        $individual->update($request->only(['tag', 'project_id', 'notes']));
        $individual->setDate($request->date_month, $request->date_day, $request->date_year);
        $individual->save();

        //did collectors changed?
        // "sync" collectors. See app/Project.php / setusers()
        $current = $individual->collectors->pluck('person_id');
        $detach = $current->diff($request->collector)->all();
        $attach = collect($request->collector)->diff($current)->all();
        if (count($detach) or count($attach)) {
            //delete old collectors
            $individual->collectors()->delete();
            //save collectors and identify main collector
            $first = true;
            foreach ($request->collector as $collector) {
                $thecollector = new Collector(['person_id' => $collector]);
                if ($first) {
                    $thecollector->main = 1;
                }
                $individual->collectors()->save($thecollector);
                $first = false;
            }
        }
        //log changes in collectors if any
        ActivityFunctions::logCustomPivotChanges($individual,$current->all(),$request->collector,'individual','collector updated',$pivotkey='person');


        //it may have an older self identification or only a link to another individual
        //so the update options are:
        //1. is linked to other and changed to another
        //2. is linked to other and changed to self identification
        //3. has self identification and is now linked to other
        //4. has self identification but it changed
        //5. identification is not yet set and may be either possibility
        //is this is set

        //new is self? opetions 2, 4 or 5
        $identifiers = null;
        $oldidentification = null;
        $newtaxon_id = null;
        if ($request->taxon_id) {
          $identifiers = [
            'person_id' => $request->identifier_id,
            'taxon_id' => $request->taxon_id,
            'modifier' => $request->modifier,
            'biocollection_id' => $request->biocollection_id,
            'biocollection_reference' => $request->biocollection_reference,
            'notes' => $request->identification_notes,
          ];
          $newtaxon_id = $request->taxon_id;
          //specifiy is self
          $individual->identification_individual_id = $individual->id;
          $individual->save();
          //has old update or else create
          if ($individual->identificationSet) {
              $oldidentification = $individual->identificationSet()->first()->toArray();
              //update
              $individual->identificationSet()->update($identifiers);
          } else {
              $individual->identificationSet = new Identification(array_merge($identifiers, ['object_id' => $individual->id, 'object_type' => 'App\Models\Individual']));
          }
          $individual->identificationSet->setDate($request->identification_date_month,$request->identification_date_day,$request->identification_date_year);
          $individual->identificationSet->save();
          //log identification changes if any
          $identifiers['date'] = $individual->identificationSet->date;
          ActivityFunctions::logCustomChanges($individual,$oldidentification,$identifiers,'individual','identification updated',null);
        } else {
          //other identification (not self) or none
          if ($request->identification_individual_id) {
            $individual->identification_individual_id = $request->identification_individual_id;
            $individual->save();

            $newtaxon_id = Individual::findOrFail($request->identification_individual_id)->taxon_id;
          } else {
            //no identification (clean old values if any)
            $individual->identification_individual_id = null;
            $individual->save();
          }
          //delete if old identification was self
          if ($individual->identificationSet) {
            $oldidentification = $individual->identificationSet()->first()->toArray();
            $individual->identificationSet->delete();
          }
        }

        /* UPDATE SUMMARY COUNTS */
        $newvalues =  [
             "taxon_id" => $newtaxon_id,
             "project_id" => $request->project_id,
             "location_id" => $individual->locations->last()->id,
        ];
        $target = 'individuals';
        $datasets = array_unique($individual->measurements()->withoutGlobalScopes()->pluck('dataset_id')->toArray());
        $measurements_count = $individual->measurements()->withoutGlobalScopes()->count();
        Summary::updateSummaryCounts($newvalues,$oldvalues,$target,$datasets,$measurements_count);
        /* END SUMMARY UPDATE */

        return redirect('individuals/'.$id)->withStatus(Lang::get('messages.saved'));
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
        $object = Individual::findOrFail($id);
        return $dataTable->with('individual', $id)->render('common.activity',compact('object'));
    }

    public function importJob(Request $request)
    {
      $this->authorize('create', Individual::class);
      $this->authorize('create', UserJob::class);
      if (!$request->hasFile('data_file')) {
          $message = Lang::get('messages.invalid_file_missing');
      } else {
        $valid_ext = array("csv","ods",'xlsx');
        $ext = mb_strtolower($request->file('data_file')->getClientOriginalExtension());
        if (!in_array($ext,$valid_ext)) {
          $message = Lang::get('messages.invalid_file_extension');
        } else {
          $filename = uniqid().".".$ext;
          $request->file('data_file')->storeAs("public/tmp",$filename);
          UserJob::dispatch(ImportIndividuals::class,[
            'data' => [
                'data' => null,
                'filename' => $filename,
                'filetype' => $ext,
              ],
          ]);
          $message = Lang::get('messages.dispatched');
        }
      }
      return redirect('import/individuals')->withStatus($message);
    }


    // USED WHEN EDITING AN INDIVIDUAL LOCATION
    public function getIndividualLocation(Request $request)
    {
        $indloc = IndividualLocation::findOrFail($request->id);
        if (null != $indloc->date_time) {
          $date_time = explode(' ',$indloc->date_time);
        } else {
          $date_time = [];
        }
        $result = [
          $indloc->id,
          $indloc->location_id,
          $indloc->location->name,
          $indloc->location->adm_level,
          $indloc->x,
          $indloc->y,
          $indloc->angle,
          $indloc->distance,
          $indloc->altitude,
          $indloc->notes,
          (isset($date_time[0]) ?  $date_time[0] : null),
          (isset($date_time[1]) ?  $date_time[1] : null),
        ];
        return Response::json(
        [
          'indLocation' => $result
        ]);
    }


    public function validateIndividualLocation(Request $request)
    {
      $rules = [
        'location_id' => 'required|integer',
        'altitude' => 'integer|nullable',
        'date_time' => 'date_format:Y-m-d H:i:s|nullable',
      ];
      $validator = Validator::make($request->all(), $rules);
      $location = Location::find($request->location_id);
      $validator->after(function ($validator) use ($request, $location) {
        if ($request->distance or $request->x) {
          // validates xy / angdist
          if (Location::LEVEL_POINT == $location->adm_level) {
            if ($request->distance < 0 or $request->angle < 0 or $request->angle > 360 or null == self::validateRelativePosition($request->angle,$request->distance,$location->adm_level)) {
                $validator->errors()->add('distance', Lang::get('messages.individual_ang_dist_error'));
            }
          } elseif (100 == $request->location_type or 101 == $request->location_type and $location) {
            if ($request->x < 0 or $request->y < 0 or $request->x > $location->x or $request->y > $location->y or null == self::validateRelativePosition($request->x,$request->y,$location->adm_level)) {
                $validator->errors()->add('x', Lang::get('messages.individual_xy_error'));
            }
          }
       }
      });
      return $validator;
    }


    // USED WHEN EDITING OR ADDING A NEW INDIVIDUAL LOCATION
    public function saveIndividualLocation(Request $request)
    {
        $individual = Individual::findOrFail($request->individual_id);
        //$this->authorize('update', $individual);
        $validator = $this->validateIndividualLocation($request);
        if ($validator->fails()) {
            $errors = "";
            foreach ($validator->errors()->all() as $error) {
                  $errors .= $error."\n";
            }
            return Response::json(['saved' => $errors, 'errors' => 1]);
        }
          //save or add location
          $location = Location::find($request->location_id);
          if (Location::LEVEL_POINT == $location->adm_level) {
            $relative_position = self::validateRelativePosition($request->angle,$request->distance,$location->adm_level);
          } else {
            $relative_position = self::validateRelativePosition($request->x,$request->y,$location->adm_level);
          }
          //is this an edition?
          if ($request->id) {
            $indloc = IndividualLocation::findOrFail($request->id);
            $indloc->location_id = $request->location_id;
            $indloc->date_time = $request->date_time;
            $indloc->altitude = $request->altitude;
            $indloc->notes = $request->notes;
            $indloc->relative_position = $relative_position;
            $indloc->save();
          } else {
            //this is a new location for the individual add
            $values = [
              'date_time' => $request->date_time,
              'altitude' => $request->altitude,
              'notes' => $request->notes,
              'relative_position' => $relative_position
            ];
            $tosync = [];
            $tosync[$request->location_id] = $values;
            $individual->locations()->syncWithoutDetaching($tosync);
          }
          return Response::json(['saved' => Lang::get('messages.individual_location_save'), 'errors' => 0]);
    }

    // delete individual_location
    public function deleteIndividualLocation(Request $request)
    {
      $individual = Individual::findOrFail($request->individual_id);
      if ($individual->locations->count()>1) {
        $indloc = IndividualLocation::findOrFail($request->id);
        $newfirst = false;
        if ($indloc->first == 1) {
          $newfirst = true;
        }
        $indloc->delete();
        if ($newfirst) {
          $indloc = IndividualLocation::where('individual_id',$individual->id)->first();
          $indloc->first = 1;
          $indloc->save();
        }
        return Response::json(['deleted' => Lang::get('messages.individual_location_deleted')]);
      } else {
        return Response::json(['deleted' => Lang::get('messages.individual_location_cannotdelete')]);
      }
    }


    public function getIndividualForVoucher(Request $request)
    {
      $individual = Individual::findOrFail($request->id);
      if ($individual->identification) {
        $taxonname = $individual->identification->taxon->full_name;
      }
      $collectors = implode(" | ",$individual->collectors->map(function($q) { return $q->person->abbreviation;})->toArray());
      $colldate = $individual->date;
      $result = [
          $taxonname,
          $collectors,
          $individual->date,
          $individual->tag,
          $individual->project_id,
      ];
      return Response::json(
      [
        'individual' => $result
      ]);
    }

}
