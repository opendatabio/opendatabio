<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Location;
use App\Project;
use App\Dataset;
use App\DataTables\LocationsDataTable;
use Validator;
use DB;
use Lang;
use Response;
use Storage;
use App\UserJob;
use App\Jobs\ImportLocations;
use Spatie\SimpleExcel\SimpleExcelReader;

use Illuminate\Support\Facades\Input;
use Activity;
use App\ActivityFunctions;
use App\DataTables\ActivityDataTable;


class LocationController extends Controller
{
    // Functions for autocompleting location names, used in dropdowns. Expects a $request->query input
    // MAY receive optional "$request->scope" to return only UCs; default is to return all locations?
    public function autocomplete(Request $request)
    {
        $locations = Location::where('name', 'LIKE', ['%'.$request->input('query').'%'])->noWorld()
                        ->orderBy('name', 'ASC')->take(30);
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
            $location->value = $location->fullname;

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

        $parent = Location::detectParent($geom, $request->adm_level, false);
        if (null == $parent) {
            return Response::json(['error' => Lang::get('messages.autodetect_error')]);
        }

        $uc_ac = null;
        $uc_id = null;
        if (Location::LEVEL_PLOT == $request->adm_level or Location::LEVEL_POINT == $request->adm_level) {
            $uc = Location::detectParent($geom, $request->adm_level, true);
            if ($uc) {
                $uc_ac = $uc->fullname;
                $uc_id = $uc->id;
            }
        }

        return Response::json(['detectdata' => [$parent->fullname, $parent->id, $uc_ac, $uc_id]]);
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
            'parent_id' => 'required_unless:adm_level,0',
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
        } elseif (Location::LEVEL_POINT == $request->adm_level) { //POINT
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
        // Now we check if the geometry received is valid, and if it falls inside the parent geometry
        $validator->after(function ($validator) use ($request) {
            if ((Location::LEVEL_PLOT == $request->adm_level and 'point' == $request->geom_type) or Location::LEVEL_POINT == $request->adm_level) {
                // we check if this exact geometry is already registered
                $geom = Location::geomFromParts($request);
                $exact = Location::whereRaw("geom=geomfromtext('$geom')")->get();
                if (sizeof($exact)) {
                    $validator->errors()->add('geom', Lang::get('messages.geom_duplicate'));
                }

                return;
            }
            // Dimension returns NULL for invalid geometries
            $valid = DB::select('SELECT Dimension(GeomFromText(?)) as valid', [$request->geom]);

            if (is_null($valid[0]->valid)) {
                $validator->errors()->add('geom', Lang::get('messages.geom_error'));
            }
        });
        $validator->after(function ($validator) use ($request) {
            if ($request->parent_id < 1 or 0 == $request->adm_level) {
                return;
            } // don't validate if parent = 0 for none
            $geom = $request->geom;
            if ((Location::LEVEL_PLOT == $request->adm_level and 'point' == $request->geom_type) or Location::LEVEL_POINT == $request->adm_level) {
                $geom = Location::geomFromParts($request);
            }
            $parent = Location::withGeom()->findOrFail($request->parent_id);
            // skip validation if parent is dimensionless
            if ('point' == $parent->geomType) {
                return;
            }

            $valid = DB::select('SELECT ST_Within(GeomFromText(?), geom) as valid FROM locations where id = ?', [$geom, $request->parent_id]);

            if (1 != $valid[0]->valid) {
                $validator->errors()->add('geom', Lang::get('messages.geom_parent_error'));
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
        if ($request->adm_level > 99 and !$request->confirm and 'point' == $request->geom_type) {
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
        if ($request->uc_id and $request->adm_level > 99) {
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
        $plot_children = $location->children->map(function ($c) { if ($c->adm_level > 99) { return Location::withGeom()->find($c->id); } });
        //$plot_children = null;
        if (null !== $location->parent->geom and $location->adm_level>0) {
          $parent = Location::noWorld()->select('*')->withGeom()->findOrFail($location->parent->id);
        } else {
          $parent = null;
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
                ->name('LocationPlants')
                ->type('scatter')
                ->size(['width' => $width, 'height' => $height])
                ->labels($location->plants->map(function ($x) {return $x->tag; })->all())
                ->datasets([
                    [
                        'label' => 'Plants in location',
                        'showLine' => false,
                        'backgroundColor' => 'rgba(38, 185, 154, 0.31)',
                        'borderColor' => 'rgba(38, 185, 154, 0.7)',
                        'pointBorderColor' => 'rgba(38, 185, 154, 0.7)',
                        'pointBackgroundColor' => 'rgba(38, 185, 154, 0.7)',
                        'pointHoverBackgroundColor' => 'rgba(220,220,20,0.7)',
                        'pointHoverBorderColor' => 'rgba(220,220,20,1)',
                        'data' => $location->plants->map(function ($x) {return ['x' => $x->x, 'y' => $x->y]; })->all(),
                    ],
                ])
                ->options([
                    'maintainAspectRatio' => true,
                ]);

             return $dataTable->with([
                    'location' => $id
                ])->render('locations.show', compact('chartjs', 'location', 'plot_children','parent'));
        } // else
        return $dataTable->with([
               'location' => $id
           ])->render('locations.show', compact('location', 'plot_children','parent'));
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

        if ($request->uc_id and $request->adm_level > 99) {
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
        if (0 === $request->adm_level) {
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
        $valid_ext = array("CSV","csv","ODS","ods","XLSX",'xlsx');
        $ext = $request->file('data_file')->getClientOriginalExtension();
        if (!in_array($ext,$valid_ext)) {
          $message = Lang::get('messages.invalid_file_extension');
        } else {
          $data = SimpleExcelReader::create($request->file('data_file'))->getRows()->toArray();
          if (count($data)>0) {
            UserJob::dispatch(ImportLocations::class,[
              'data' => $data,
            ]);
            $message = Lang::get('messages.dispatched');
          } else {
            $message = 'Something wrong with file';
          }
        }
      }
      return redirect('import/locations')->withStatus($message);
    }
}
