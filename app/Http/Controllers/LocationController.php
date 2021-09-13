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
use App\Models\LocationRelated;
use App\DataTables\LocationsDataTable;
use Validator;
use DB;
use Lang;
use Response;
use Storage;
use Log;
use App\Models\UserJob;
use App\Jobs\ImportLocations;
use App\Jobs\DeleteMany;
use Spatie\SimpleExcel\SimpleExcelReader;

//use Illuminate\Support\Facades\Input;
use Activity;
use App\Models\ActivityFunctions;
use App\DataTables\ActivityDataTable;


class LocationController extends Controller
{
    // Functions for autocompleting location names, used in dropdowns. Expects a $request->query input
    // MAY receive optional "$request->scope" to return only UCs; default is to return all locations?
    public function autocomplete(Request $request)
    {
        $locations = Location::noWorld()
            ->withoutGeom()
            ->where('name', 'LIKE', ['%'.$request->input('query').'%'])
            ->orderBy('name', 'ASC')->take(10);
        if ($request->scope) {
            switch ($request->scope) {
            case 'ucs':
                $locations = $locations->ucs();
                break;
            case 'related':
                $locations = $locations->related();
                break;
            case 'exceptucs':
                $locations = $locations->exceptUcs();
                break;
            default:
                break;
            }
        }
        $locations = $locations->cursor();
        $locations = collect($locations)->transform(function ($location) {
            $location->data = $location->id;
            $location->value = $location->searchablename;

            return $location->only(['data', 'value', 'adm_level']);
        });

        return Response::json(['suggestions' => $locations]);
    }

    public function autocomplete_related(Request $request)
    {
      $request->scope = 'related';
      return $this->autocomplete($request);
    }

    public function autodetect(Request $request)
    {
        //return Response::json(['error' => json_encode($request)]);

        $geom = $request->geom;
        if (('point' == $request->geom_type and Location::LEVEL_PLOT == $request->adm_level) or Location::LEVEL_POINT == $request->adm_level or ('point' == $request->geom_type and Location::LEVEL_TRANSECT == $request->adm_level)) {
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

        /*
        $uc_ac = null;
        $uc_id = null;
        if (Location::LEVEL_PLOT == $request->adm_level or Location::LEVEL_POINT == $request->adm_level) {
            $uc = Location::detectParent($geom, $request->adm_level, true,false,0);
            if ($uc) {
                $uc_ac = $uc->fullname;
                $uc_id = $uc->id;
            }
        }
        */
        //detect whether de the location is within a UC, TI, ENV location type
        $related_locations = Location::detectRelated($geom,$request->adm_level);

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

        //$uc_ac, $uc_id,
        return Response::json(
        [
          'detectdata' => [$parent->fullname, $parent->id,$return_geom],
          'detectrelated' => $related_locations,
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
        //$locations = Location::noWorld()->get();
        //$uc_list = Location::ucs()->get();
        $related_locations = Location::select(['id','name'])->related();
        //return view('locations.create', compact('locations', 'uc_list'));
        return view('locations.create',compact('related_locations'));
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
        /* validate geometry */
        if (Location::LEVEL_PLOT == $request->adm_level or Location::LEVEL_TRANSECT == $request->adm_level) { // PLOT
            $parent = Location::withGeom()->findOrFail($request->parent_id);
            if (Location::LEVEL_PLOT==$parent->adm_level) { // location is a subplot
                //should this be kept mandatory, only when geometry is not informed?
                $rules = array_merge($rules, [
                    'startx' => 'required|numeric|min:0|max:'.$parent->x,
                    'starty' => 'required|numeric|min:0|max:'.$parent->y,
                ]);
            }
            if ('point' == $request->geom_type and !$parent->adm_level==Location::LEVEL_PLOT) {
                $rules = array_merge($rules, [
                    'lat1' => 'required|numeric|min:0',
                    'long1' => 'required|numeric|min:0',
                    'lat2' => 'numeric|nullable|min:0',
                    'long2' => 'numeric|nullable|min:0',
                    'lat3' => 'numeric|nullable|min:0',
                    'long3' => 'numeric|nullable|min:0',
                ]);
            } elseif (!$parent->adm_level==Location::LEVEL_PLOT)  {
                /* geometry is not required only if this is a subplot */
                $rules = array_merge($rules, [
                    'geom' => 'required|string',
                ]);
            }
            if (Location::LEVEL_PLOT == $request->adm_level) {
              $rules = array_merge($rules, [
                'x' => 'required|numeric',
                'y' => 'required|numeric',
              ]);
            } else { //this will be a transect
              $rules = array_merge($rules, [
                'y' => 'required|numeric',
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
        $validator->after(function ($validator) use ($request,  $id) {
            //case point locations, either Plot or Point, then validate exact duplicate geometry
            if ((Location::LEVEL_PLOT == $request->adm_level and 'point' == $request->geom_type)
                or
               (Location::LEVEL_POINT == $request->adm_level)
                 or
               (Location::LEVEL_TRANSECT == $request->adm_level and 'point' == $request->geom_type)
               )
            {
              if (!isset($request->geom) and isset($request->lat1)) {
                $geom = Location::geomFromParts($request);
              } else {
                $geom = $request->geom;
              }
            } else {
              $geom = $request->geom;
            }
            if(isset($request->parent_id) and $geom == null and ($request->adm_level==Location::LEVEL_PLOT or $request->adm_level==Location::LEVEL_TRANSECT)) {
              $parent = Location::withGeom()->findOrFail($request->parent_id);
              if (Location::LEVEL_PLOT==$parent->adm_level) {
                $geom = Location::individual_in_plot($parent->footprintWKT,$request->startx,$request->starty);
              }
            }
            $geomtype =   mb_strtolower(trim(preg_split('/\\(/', $geom)[0]));
            $angle = self::angleFromParent($request);
            if ($geomtype=='point' and $request->adm_level==Location::LEVEL_PLOT) {
                $geom = Location::generate_plot_geometry($geom,$request->x,$request->y,$angle);
            } elseif ($geomtype=='point' and $request->adm_level==Location::LEVEL_TRANSECT) {
                $geom = Location::generate_transect_geometry($geom,$request->x,$angle);
            }

            //1. check the geometry is valid
            #$valid = DB::select('SELECT ST_IsValid(ST_GeomFromText(?)) as valid', [$geom]);
            // MariaDB returns 1 for invalid geoms from ST_IsEmpty ref: https://mariadb.com/kb/en/mariadb/st_isempty/
            $valid = DB::select("SELECT ST_IsEmpty(ST_GeomFromText('$geom')) as valid");
            if (1 == $valid[0]->valid) {
               $validator->errors()->add('geom', Lang::get('messages.geom_error'));
               return;
            }

            //2. check if this exact geometry is already registered (only if not editing)
            if (!is_null($id)) {
             $exact = Location::whereRaw("geom=ST_GeomFromText('$geom')")->where('id','<>',$id)->cursor();
            } else {
             $exact = Location::whereRaw("geom=ST_GeomFromText('$geom')")->cursor();
            }
            if ($exact->count() > 0) {
               $validator->errors()->add('geom', Lang::get('messages.geom_duplicate'));
               return;
            }

            //3. parent validation
            if ($request->parent_id > 1)
            {
              $parent = Location::withGeom()->findOrFail($request->parent_id);
              $parent_dim = null;
              //define a buffer size depending on the parent type
              if (Location::LEVEL_PLOT == $parent->adm_level) { // location is a subplot
                $parent_dim = !is_null($parent->x) ? (($parent->x >= $parent->y) ? $parent->x : $parent->y) : null;
                if (($request->startx+$request->x)>$parent->x or ($request->starty+$request->y)>$parent->y) {
                  $validator->errors()->add('startx', 'invalid subplot dimensions: startx+x > parent->x OR starty+y > parent->x');
                  return;
                }
              } elseif (Location::LEVEL_TRANSECT == $parent->adm_level) {
                $parent_dim = !is_null($parent->y) ? $parent->y : null;
              }
              $parent_geom = $parent->footprintWKT;
              if (!is_null($parent_dim)) {
                /* add a buffer to parent point in the ~ size of its dimension if set */
                $buffer_dd = (($parent_dim*0.00001)/1.11);
              } else {
                /* else use config buffer */
                $buffer_dd = config('app.location_parent_buffer');
              }
              //if parent is point location has to consider a buffer
              //this will only happen if geometry is plot and within parent
              if ($parent->adm_level == Location::LEVEL_POINT) {
                  //$valid = DB::select('SELECT ST_Within(ST_GeomFromText(?), ST_BUFFER(geom,?)) as valid FROM locations where id = ?', [$geom, $buffer_dd, $request->parent_id]);
                  $valid = DB::select("SELECT ST_Within(ST_GeomFromText('".$geom."'), ST_BUFFER(ST_GeomFromText('".$parent_geom."'),".$parent_geom.")) as valid");
              } else {
                  //test without buffer
                  $valid = DB::select("SELECT ST_Within(ST_GeomFromText('".$geom."'),ST_GeomFromText('".$parent_geom."')) as valid");
                  //if not valid, test with buffer
                  if (1 != $valid[0]->valid) {
                    $valid = DB::select("SELECT ST_Within(ST_GeomFromText('".$geom."'), ST_BUFFER(ST_GeomFromText('".$parent_geom."'),".$parent_geom.")) as valid");
                    //$valid = DB::select('SELECT ST_Within(ST_GeomFromText(?), ST_BUFFER(geom,?)) as valid FROM locations where id = ?', [$geom, $buffer_dd, $request->parent_id]);
                  }
              }
              if (1 != $valid[0]->valid) {
                $validator->errors()->add('geom', Lang::get('messages.geom_parent_error'));
                return;
              }
            }

            //4. uc validation
            if ($request->related_locations)
            {
              $valid_related = [];
              foreach($request->related_locations as $related)
              {
                $valid = DB::select('SELECT ST_Within(ST_GeomFromText(?), geom) as valid FROM locations where id = ?', [$geom, $related]);
                if ($valid[0]->valid) {
                  $valid_related[] = $related;
                }
              }
              if (count($valid_related) < count($request->related_locations)) {
                $validator->errors()->add('related_locations', Lang::get('messages.geom_other_parents_error'));
                return;
              }
            }
            if (Location::LEVEL_PLOT==$parent->adm_level and $request->adm_level==Location::LEVEL_PLOT) {
              $valid_dim = (($request->startx+$request->x)>$parent->x or ($request->starty+$request->y)>$parent->y) ? false : true;
              if (!$valid_dim) {
                $validator->errors()->add('dimensions', Lang::get('messages.subplot_dimensions_invalid'));
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
        if (!$request->confirm and 'point' == $request->geom_type) {
            $dupes = Location::withDistance(Location::geomFromParts($request))->get()
                ->filter(function ($obj) {
                    return $obj->distance < 0.001 and $obj->geom_type=='point';
                });
            if (sizeof($dupes)) {
                $request->flash();
                return view('locations.confirm', compact('dupes'));
            }
        }
        $geom = $request->geom;
        if (Location::LEVEL_PLOT == $request->adm_level or Location::LEVEL_TRANSECT == $request->adm_level) { // plot
            $newloc = new Location($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y']));
            $geom = $request->geom;
            if (Location::LEVEL_PLOT == $request->parent_type) { // see issue #40
                $newloc->startx = $request->startx;
                $newloc->starty = $request->starty;
                /* may have to generate subplot first point if geometry is missing */
                if (!isset($request->geom) and !isset($request->lat1) and isset($request->parent_id)) {
                  $parent = Location::withGeom()->findOrFail($request->parent_id);
                  $geom = Location::individual_in_plot($parent->plot_geometry,$request->startx,$request->starty);
                  $request->geom_type = 'nonpoint';
                }
            }
            if ('point' == $request->geom_type) {
                $geom = Location::geomFromParts($request);
            }
            $geomtype =   mb_strtolower(trim(preg_split('/\\(/', $geom)[0]));
            $angle = self::angleFromParent($request);
            if ($geomtype=='point' and $request->adm_level==Location::LEVEL_PLOT) {
                $geom = Location::generate_plot_geometry($geom,$request->x,$request->y,$angle);
            } elseif ($geomtype=='point' and $request->adm_level==Location::LEVEL_TRANSECT) {
                $geom = Location::generate_transect_geometry($geom,$request->x,$angle);
            }
            $newloc->geom = $geom;
        } else {
            // discard x, y data from locations that are not PLOTs
            $newloc = new Location($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes']));
            if (Location::LEVEL_POINT == $request->adm_level) { // point
                $newloc->setGeomFromParts($request->only([
                    'lat1', 'lat2', 'lat3', 'latO',
                    'long1', 'long2', 'long3', 'longO',
                ]));
            } else { // all others
                $newloc->geom = $request->geom;
            }
        }
        if ($request->parent_id) {
            $newloc->parent_id = $request->parent_id;
        }
        if (config('app.adm_levels')[0] === $request->adm_level) {
            $world = Location::world();
            $newloc->parent_id = $world->id;
        }
        $newloc->save();
        if ($request->related_locations) {
          $related_locations  = $request->related_locations;
        } else {
          $related_locations = Location::detectRelated($geom,$request->adm_level);
          $related_locations = collect($related_locations)->pluck('id');
        }
        if ($related_locations) {
          foreach ($related_locations as $related_id) {
              $related = new LocationRelated(['related_id' => $related_id]);
              $newloc->relatedLocations()->save($related);
          }
        }
        $fixed = Location::fixPathAndRelated($newloc->id);
        return redirect('locations/'.$newloc->id)->withStatus(Lang::get('messages.stored'));
    }

    public static function angleFromParent($request)
    {
      $angle = isset($request->angle) ? $request->angle : 0;
      if(isset($request->parent_id)) {
        $parent = Location::withGeom()->findOrFail($request->parent_id);
        /* if subplot angle must fit parent geometry and is retrieved from there */
        if (Location::LEVEL_PLOT==$parent->adm_level) {
          if ($parent->adm_level==Location::LEVEL_PLOT) {
            $parent_wkt = $parent->footprintWKT;
            $pattern = '/\\(|\\)|POLYGON|\\n/i';
            $coordinates = preg_replace($pattern, '', $parent_wkt);
            $coordinates = explode(",",$coordinates);
            $coordA = "POINT(".$coordinates[0].")";
            $coordB = "POINT(".$coordinates[1].")";
            $geotools = new \League\Geotools\Geotools();
            $coordA   = new \League\Geotools\Coordinate\Coordinate(Location::latlong_from_point($coordA));
            $coordB   = new \League\Geotools\Coordinate\Coordinate(Location::latlong_from_point($coordB));
            $angle    =  $geotools->vertex()->setFrom($coordA)->setTo($coordB)->initialBearing();
          }
        }
      }
      return $angle;
    }


    public function mapMe($id)
    {
      $location = Location::noWorld()->select('*')->with('children')->findOrFail($id);
      if (null !== $location->parent and $location->adm_level>config('app.adm_levels')[0]) {
        $parent = Location::noWorld()->select('*')->findOrFail($location->parent->id);
      } else {
        $parent = null;
      }
      return view('locations.map', compact('location','parent'));
    }


      public function maprender(Request $request)
      {
              if ($request->location_id) {
                $location = Location::withGeom()->findOrFail($request->location_id);
                //if ($request->individual_id) {
                    //$location_features = $location->generateFeatureCollection($request->individual_id);
                //} else {
                    $location_features = $location->generateFeatureCollection($request->individual_id);
                //}
                return Response::json(
                [
                  'features' => $location_features,
                ]);
              }
              return Response::json(
              [
                'error' => "Could not map data"
              ]);
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
        $location = Location::withoutGeom()->withGeom()->noWorld()->findOrFail($id);
        //with('children')->with('children')->
        //$plot_children = $location->children->map(function ($c) { if ($c->adm_level > Location::LEVEL_UC) { return Location::withGeom()->find($c->id); } });
        $plot_children = null;
        //$parent = null;
        //if (null !== $location->parent and $location->adm_level>config('app.adm_levels')[0]) {
        //  $parent = Location::noWorld()->select('*')->findOrFail($location->parent->id);
        //} else {
        //  $parent = null;
        //}
        $media = $location->mediaDescendantsAndSelf();
        //$media = collect([]);
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
                ])->render('locations.show', compact('chartjs', 'location', 'plot_children','media'));
        } // else
        return $dataTable->with([
               'location' => $id
           ])->render('locations.show', compact('location', 'plot_children','media'));
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
        //$locations = Location::all();
        //locations
        //$uc_list = Location::ucs()->ge();
        $related_locations = Location::select(['id','name'])->related();
        $location = Location::withGeom()->findOrFail($id);
        return view('locations.create', compact('location','related_locations'));
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
        if (Location::LEVEL_PLOT == $request->adm_level or Location::LEVEL_TRANSECT == $request->adm_level) {
            $location->update($request->only(['name', 'altitude', 'datum', 'adm_level', 'notes', 'x', 'y']));
            $geom = $request->geom;
            if (Location::LEVEL_PLOT == $request->parent_type) { // see issue #40
                $location->startx = $request->startx;
                $location->starty = $request->starty;
                /* may have to generate subplot first point if geometry is missing */
                if (!isset($request->geom) and !isset($request->lat1) and isset($request->parent_id)) {
                  $parent = Location::withGeom()->findOrFail($request->parent_id);
                  $geom = Location::individual_in_plot($parent->footprintWKT,$request->startx,$request->starty);
                  $request->geom_type = 'nonpoint';
                }
            } else {
                $location->startx = null;
                $location->starty = null;
            }
            if ('point' == $request->geom_type) {
                $geom = Location::geomFromParts($request);
            }
            $geomtype =   mb_strtolower(trim(preg_split('/\\(/', $geom)[0]));
            $angle = self::angleFromParent($request);
            if ($geomtype=='point' and $request->adm_level==Location::LEVEL_PLOT) {
                $geom = Location::generate_plot_geometry($geom,$request->x,$request->y,$angle);
            } elseif ($geomtype=='point' and $request->adm_level==Location::LEVEL_TRANSECT) {
                $geom = Location::generate_transect_geometry($geom,$request->x,$angle);
            }
            $location->geom = $geom;
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
        /*
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
        */

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

        $newlocation = Location::noWorld()->withGeom()->findOrFail($id);
        $geom = $newlocation->geom;
        if ($request->related_locations) {
          $related_locations  = $request->related_locations;
        } else {
          $related_locations = Location::detectRelated($geom,$request->adm_level);
          $related_locations = collect($related_locations)->pluck('id');
        }
        $current = $newlocation->relatedLocations->pluck('related_id');
        $detach = $current->diff($related_locations)->all();
        $attach = $related_locations->diff($current)->all();
        if (count($detach) or count($attach)) {
            //delete old
            $newlocation->relatedLocations()->delete();
            //save new
            foreach ($related_locations as $related_id) {
                $related = new LocationRelated(['related_id' => $related_id]);
                $newlocation->relatedLocations()->save($related);
            }
        }


        //log geometry changes if any
        if ($newlocation->geom !== $oldlocation->geom) {
          $fixed = Location::fixPathAndRelated($newlocation->id);
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
        if ($request->related_locations) {
            foreach ($request->related_locations as $related_id) {
                $related = new LocationRelated(['related_id' => $related_id]);
                $newloc->relatedLocations()->save($related);
            }
        }
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
