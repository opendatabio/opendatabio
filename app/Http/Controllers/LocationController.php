<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Location;
use App\Models\Project;
use App\Models\Dataset;
use App\DataTables\LocationsDataTable;
use Validator;
use DB;
use Lang;
use Response;
use Storage;
use App\Models\UserJob;
use App\Jobs\ImportLocations;
use App\Jobs\DeleteMany;
use Spatie\SimpleExcel\SimpleExcelReader;

use Illuminate\Support\Facades\Input;
use Activity;
use App\Models\ActivityFunctions;
use App\DataTables\ActivityDataTable;


class LocationController extends Controller
{
    // Functions for autocompleting location names, used in dropdowns. Expects a $request->query input
    // MAY receive optional "$request->scope" to return only UCs; default is to return all locations?
    public function autocomplete(Request $request)
    {
        $locations = Location::noWorld()->where('name', 'LIKE', ['%'.$request->input('query').'%'])
                        ->orderBy('name', 'ASC')->take(10);
        if ($request->scope) {
            switch ($request->scope) {
            case 'ucs':
                $locations = $locations->ucs();
                break;
            case 'exceptucs':
                $locations = $locations->exceptUcs();
                break;
            default:
                break;
            }
        }
        $locations = $locations->get();
        $locations = collect($locations)->transform(function ($location) {
            $location->data = $location->id;
            $location->value = $location->searchablename;

            return $location->only(['data', 'value', 'adm_level']);
        });

        return Response::json(['suggestions' => $locations]);
    }

    public function autodetect(Request $request)
    {
        $geom = $request->geom;
        if (('point' == $request->geom_type and Location::LEVEL_PLOT == $request->adm_level) or Location::LEVEL_POINT == $request->adm_level) {
            $geom = Location::geomFromParts($request);
        }
        if (!$geom) {
            return Response::json(['error' => Lang::get('messages.autodetect_blank')]);
        }

        $parent = Location::detectParent($geom, $request->adm_level, false,
false,0);
        if (null == $parent) {
            return Response::json(['error' => Lang::get('messages.autodetect_error')]);
        }

        $uc_ac = null;
        $uc_id = null;
        if (Location::LEVEL_PLOT == $request->adm_level or Location::LEVEL_POINT == $request->adm_level) {
            $uc = Location::detectParent($geom, $request->adm_level, true,false,0);
            if ($uc) {
                $uc_ac = $uc->fullname;
                $uc_id = $uc->id;
            }
        }

        //does an exact match exists
        $loc_id =null;
        $loc_name = null;
        $loc_level = null;
        $return_geom = null;
        if (Location::LEVEL_POINT == $request->adm_level) {
          $exists_exact = Location::whereRaw("geom=ST_GeomFromText('$geom')")->cursor();
          if ($exists_exact->count()) {
            $loc_id = $exists_exact->first()->id;
            $loc_name = $exists_exact->first()->searchablename;
            $loc_level = $exists_exact->first()->adm_level;
          } else {
            $return_geom = $geom;
          }
        }


        return Response::json(
        [
          'detectdata' => [$parent->fullname, $parent->id, $uc_ac, $uc_id,$return_geom],
          'detectedLocation' => [$loc_id,$loc_name,$loc_level],
        ]);
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(LocationsDataTable $dataTable)
    {
        return $dataTable->render('locations.index');
    }


    public function indexProjects($id, LocationsDataTable $dataTable)
    {
        $object = Project::findOrFail($id);
        return $dataTable->with([
            'project' => $id
        ])->render('locations.index', compact('object'));
    }

    public function indexDatasets($id, LocationsDataTable $dataTable)
    {
        $object = Dataset::findOrFail($id);
        return $dataTable->with([
            'dataset' => $id
        ])->render('locations.index', compact('object'));
    }





    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $locations = Location::noWorld()->get();
        $uc_list = Location::ucs()->get();

        return view('locations.create', compact('locations', 'uc_list'));
    }

    // Validates the user input for CREATE or UPDATE requests
    // Notice that the fields that will be used are different based on the
    // adm_level declared
    public function customValidate(Request $request, $id = null)
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('locations')->ignore($id)
                ->where(function ($query) use ($request) {
                    $query->where('parent_id', $request->parent_id);
                }),
            ],
            'adm_level' => 'required|integer',
            'altitude' => 'integer|nullable',
            'parent_id' => 'required_unless:adm_level,'.config('app.adm_levels')[0],
        ];
        if (Location::LEVEL_PLOT == $request->adm_level) { // PLOT
            if ('point' == $request->geom_type) {
                $rules = array_merge($rules, [
                    'lat1' => 'required|numeric|min:0',
                    'long1' => 'required|numeric|min:0',
                    'lat2' => 'numeric|nullable|min:0',
                    'long2' => 'numeric|nullable|min:0',
                    'lat3' => 'numeric|nullable|min:0',
                    'long3' => 'numeric|nullable|min:0',
                ]);
            } else {
                $rules = array_merge($rules, [
                    'geom' => 'required|string',
                ]);
            }
            $rules = array_merge($rules, [
                'x' => 'required|numeric',
                'y' => 'required|numeric',
            ]);
            if (100 == $request->parent_type) { // location is a subplot
                $parent = Location::findOrFail($request->parent_id);
                $rules = array_merge($rules, [
                    'startx' => 'required|numeric|min:0|max:'.$parent->x,
                    'starty' => 'required|numeric|min:0|max:'.$parent->y,
                ]);
            }
        } elseif (Location::LEVEL_POINT == $request->adm_level and !isset($request->geom)) { //POINT
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
        // Now we check if the geometry received is valid
        // if it falls inside the parent geometry polygon
        // if an identical geometry already exists
        $validator->after(function ($validator) use ($request) {
            //case point locations, either Plot or Point, then validate exact duplicate geometry
            if ((Location::LEVEL_PLOT == $request->adm_level and 'point' == $request->geom_type)
                or
               (Location::LEVEL_POINT == $request->adm_level))
            {
                if (!isset($request->geom)) {
                  $geom = Location::geomFromParts($request);
                } else {
                  $geom = $request->geom;
                }
            } else {
                $geom = $request->geom;
            }
            //1. check the geometry is valid
            #$valid = DB::select('SELECT ST_IsValid(ST_GeomFromText(?)) as valid', [$geom]);
            // MariaDB returns 1 for invalid geoms from ST_IsEmpty ref: https://mariadb.com/kb/en/mariadb/st_isempty/
            $valid = DB::select("SELECT ST_IsEmpty(ST_GeomFromText('$geom')) as $valid");
            $valid = count($valid) ? $valid[0]->$valid : 1;
            if ($valid == 1) {
               $validator->errors()->add('geom', Lang::get('messages.geom_error'));
               return;
            }

            //2. check if this exact geometry is already registered
            $exact = Location::whereRaw("geom=ST_GeomFromText('$geom')")->get();
            if (sizeof($exact)) {
               $validator->errors()->add('geom', Lang::get('messages.geom_duplicate'));
               return;
            }

            //3. parent validation
            if ($request->parent_id > 1)
            {
              $parent = Location::withGeom()->findOrFail($request->parent_id);
              $parent_dim = !is_null($parent->x) ? (($parent->x >= $parent->y) ? $parent->x : $parent->y) : null;
              if (!is_null($parent_dim)) {
                /* add a buffer to parent point in the ~ size of its dimension if set */
                $buffer_dd = (($parent_dim*0.00001)/1.11);
              } else {
                /* else use config buffer */
                $buffer_dd = config('app.location_parent_buffer');
              }
              //if parent is point location has to consider a buffer
              //this will only happen if geometry is plot and within parent
              if ('point' == $parent->geomType) {
                  $valid = DB::select('SELECT ST_Within(ST_GeomFromText(?), ST_BUFFER(geom,?)) as valid FROM locations where id = ?', [$geom, $buffer_dd, $request->parent_id]);
              } else {
                  //test without buffer
                  $valid = DB::select('SELECT ST_Within(ST_GeomFromText(?), geom) as valid FROM locations where id = ?', [$geom, $request->parent_id]);
                  //if not valid, test with buffer
                  if (1 != $valid[0]->valid) {
                    $valid = DB::select('SELECT ST_Within(ST_GeomFromText(?), ST_BUFFER(geom,?)) as valid FROM locations where id = ?', [$geom, $buffer_dd, $request->parent_id]);
                  }
              }
              if (1 != $valid[0]->valid) {
                $validator->errors()->add('geom', Lang::get('messages.geom_parent_error'));
                return;
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
        $this->authorize('create', Location::class);
        $validator = $this->customValidate($request);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        // checks for duplicates, except if the request is already confirmed
        if ($request->adm_level > Location::LEVEL_UC and !$request->confirm and 'point' == $request->geom_type) {
            $dupes = Location::withDistance(Location::geomFromParts($request))->get()
                ->filter(function ($obj) {
                    return $obj->distance < 0.001;
                });
            if (sizeof($dupes)) {
                Input::flash();

                return view('locations.confirm', compact('dupes'));
            }
        }
        if (Location::LEVEL_PLOT == $request->adm_level) { // plot
            $newloc = new Location($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y']));
            if (100 == $request->parent_type) { // see issue #40
                $newloc->startx = $request->startx;
                $newloc->starty = $request->starty;
            }
            if ('point' == $request->geom_type) {
                $newloc->setGeomFromParts($request->only([
                    'lat1', 'lat2', 'lat3', 'latO',
                    'long1', 'long2', 'long3', 'longO',
                ]));
            } else {
                $newloc->geom = $request->geom;
            }
        } else {
            // discard x, y data from locations that are not PLOTs
            $newloc = new Location($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes']));
            if (Location::LEVEL_POINT == $request->adm_level) { // point
                $newloc->setGeomFromParts($request->only([
                    'lat1', 'lat2', 'lat3', 'latO',
                    'long1', 'long2', 'long3', 'longO',
                ]));
            } else { // others
                $newloc->geom = $request->geom;
            }
        }

        if ($request->parent_id) {
            $newloc->parent_id = $request->parent_id;
        }
        if (0 === $request->adm_level) {
            $world = Location::world();
            $newloc->parent_id = $world->id;
        }
        if ($request->uc_id and $request->adm_level > Location::LEVEL_UC) {
            $newloc->uc_id = $request->uc_id;
        }
        $newloc->save();

        return redirect('locations/'.$newloc->id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id,LocationsDataTable $dataTable)
    {
        $location = Location::noWorld()->select('*')->with('children')->withGeom()->findOrFail($id);
        //$plot_children = $location->children->map(function ($c) { if ($c->adm_level > Location::LEVEL_UC) { return Location::withGeom()->find($c->id); } });
        $plot_children = null;
        //$parent = null;
        if (null !== $location->parent and $location->adm_level>config('app.adm_levels')[0]) {
          $parent = Location::noWorld()->select('*')->withGeom()->findOrFail($location->parent->id);
        } else {
          $parent = null;
        }
        $media = $location->mediaDescendantsAndSelf();
        if ($media->count()) {
          $media = $media->paginate(3);
        } else {
          $media = null;
        }

        if ($location->x) {
            if ($location->x > $location->y) {
                $width = 400;
                $height = 400 / $location->x * $location->y;
            } else {
                $height = 400;
                $width = 400 / $location->y * $location->x;
            }

            $chartjs = app()->chartjs
                ->name('LocationIndividuals')
                ->type('scatter')
                ->size(['width' => $width, 'height' => $height])
                ->labels($location->individuals->map(function ($x) {return $x->tag; })->all())
                ->datasets([
                    [
                        'label' => 'Individuals in location',
                        'showLine' => false,
                        'backgroundColor' => 'rgba(38, 185, 154, 0.31)',
                        'borderColor' => 'rgba(38, 185, 154, 0.7)',
                        'pointBorderColor' => 'rgba(38, 185, 154, 0.7)',
                        'pointBackgroundColor' => 'rgba(38, 185, 154, 0.7)',
                        'pointHoverBackgroundColor' => 'rgba(220,220,20,0.7)',
                        'pointHoverBorderColor' => 'rgba(220,220,20,1)',
                        'data' => $location->individuals->map(function ($x) {return ['x' => $x->x, 'y' => $x->y]; })->all(),
                    ],
                ])
                ->options([
                    'maintainAspectRatio' => true,
                ]);

             return $dataTable->with([
                    'location' => $id
                ])->render('locations.show', compact('chartjs', 'location', 'plot_children','parent','media'));
        } // else
        return $dataTable->with([
               'location' => $id
           ])->render('locations.show', compact('location', 'plot_children','parent','media'));
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
        $locations = Location::all();
        $uc_list = Location::ucs()->get();
        $location = Location::select('*')->withGeom()->findOrFail($id);

        return view('locations.create', compact('locations', 'location', 'uc_list'));
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
        //to ge old geometry as text
        $oldlocation = Location::noWorld()->withGeom()->findOrFail($id);

        $location = Location::findOrFail($id);
        $this->authorize('update', $location);
        $validator = $this->customValidate($request, $id);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        if (Location::LEVEL_PLOT == $request->adm_level) {
            $location->update($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y']));
            if (100 == $request->parent_type) { // see issue #40
                $location->startx = $request->startx;
                $location->starty = $request->starty;
            } else {
                $location->startx = null;
                $location->starty = null;
            }
            if ('point' == $request->geom_type) {
                $location->setGeomFromParts($request->only([
                    'lat1', 'lat2', 'lat3', 'latO',
                    'long1', 'long2', 'long3', 'longO',
                ]));
            } else {
                $location->geom = $request->geom;
            }
        } else {
            // discard x, y data from locations that are not PLOTs
            $location->update($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes']));
            if (Location::LEVEL_POINT == $request->adm_level) { // point
                $location->setGeomFromParts($request->only([
                    'lat1', 'lat2', 'lat3', 'latO',
                    'long1', 'long2', 'long3', 'longO',
                ]));
            } else { // others
                $location->geom = $request->geom;
            }
        }

        if ($request->uc_id and $request->adm_level > Location::LEVEL_UC) {
            if ($location->uc_id and $location->uc_id !== $request->uc_id) {
            $tolog = array('attributes' => ['uc_id' => $request->uc_id], 'old' => ['uc_id' => $location->uc_id]);
            activity('location')
              ->performedOn($location)
              ->withProperties($tolog)
              ->log('UC changed');
            }
            $location->uc_id = $request->uc_id;
        }

        // sets the parent_id in the request, to be picked up by the next try-catch:
        //if adm is lowest defined, set world as parent
        if ( config('app.adm_levels')[0] === $request->adm_level) {
            $world = Location::world();
            $request->parent_id = $world->id;
        }

        if ($request->parent_id and $request->parent_id != $location->parent_id) {
            $oldparentid = $location->parent_id;
            try {
                $location->makeChildOf($request->parent_id);
            } catch (\Baum\MoveNotPossibleException $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(Lang::get('messages.movenotpossible'));
            }
            //log parent difference
            $tolog = array('attributes' => ['parent_id' => $request->parent_id], 'old' => ['parent_id' => $oldparentid]);
            activity('location')
              ->performedOn($location)
              ->withProperties($tolog)
              ->log('parent changed');
        }

        $location->save();

        //log geometry changes if any
        $newlocation = Location::noWorld()->withGeom()->findOrFail($id);
        if ($newlocation->geom !== $oldlocation->geom) {
          $tolog = array('attributes' => ['geom' => $newlocation->geom], 'old' => ['geom' => $oldlocation->geom]);
          activity('location')
            ->performedOn($location)
            ->withProperties($tolog)
            ->log('geometry changed');
        }

        return redirect('locations/'.$id)->withStatus(Lang::get('messages.stored'));
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




    /* WILL SAVE A NEW LOCATION FROM THE Individual MODEL CREATION OR EDITION */
    public function saveForIndividual(Request $request)
    {
        $this->authorize('create', Location::class);

        //if identical geometry exists return
        if ($request->geom) {
          $geom = $request->geom;
          $exact = Location::whereRaw("geom=ST_GeomFromText('$geom')")->get();
          if (sizeof($exact)) {
            return Response::json(
            [
              'savedLocation' => [$exact->id,$exact->searchablename,$exact->adm_level],
            ]);
          }
        }
        $validator = $this->customValidate($request);
        if ($validator->fails()) {
            $text = "<ul>";
            foreach ($validator->errors()->all() as $key => $error) {
                      $text .= "<li>".$error."</li>";
            }
            $text .= "<ul>";
            return Response::json(['error' => $text]);
        }
        $newloc = new Location($request->only(['name', 'adm_level','parent_id']));
        if (isset($request->uc_id)) {
            $newloc->uc_id = $request->uc_id;
        }
        $newloc->geom = $request->geom;
        $newloc->save();

        return Response::json(
        [
          'savedLocation' => [$newloc->id,$newloc->searchablename,$newloc->adm_level],
        ]);
    }






    public function activity($id, ActivityDataTable $dataTable)
    {
        $object = Location::findOrFail($id);
        return $dataTable->with('location', $id)->render('common.activity',compact('object'));
    }




    public function importJob(Request $request)
    {
      $this->authorize('create', Location::class);
      $this->authorize('create', UserJob::class);
      if (!$request->hasFile('data_file')) {
          $message = Lang::get('messages.invalid_file_missing');
      } else {
        /*
            Validate attribute file
            Validate file extension and maintain original if valid or else
            Store may save a csv as a txt, and then the Reader will fail
        */
        $valid_ext = array("csv","ods",'xlsx','geojson');
        $ext = mb_strtolower($request->file('data_file')->getClientOriginalExtension());
        if (!in_array($ext,$valid_ext)) {
          $message = Lang::get('messages.invalid_file_extension');
        } else {
          /* generate a unique id for the file and store it in the public tmp folder */
          $filename = uniqid().".".$ext;
          $request->file('data_file')->storeAs("public/tmp",$filename);
          UserJob::dispatch(ImportLocations::class,[
            'data' => [
                'data' => null,
                'filename' => $filename,
                'filetype' => $ext,
                'parent_options' => $request->parent_options,
              ],
          ]);
          $message = Lang::get('messages.dispatched');
        }
      }
      return redirect('import/locations')->withStatus($message);
    }

    /*
      * Batch delete locations
    */
    public function batchDelete(Request $request)
    {
        $data = $request->all();
        $data['model'] = 'Location';
        UserJob::dispatch(DeleteMany::class,
        [
          'data' => ['data' => $data,]
        ]);
        return redirect('locations')->withStatus(Lang::get('messages.dispatched'));
    }
}
