<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Http\Request;
use App\Plant;
use App\Location;
use App\Taxon;
use App\Identification;
use App\Project;
use App\UserJob;
use Response;
use Auth;
use DB;
use App\Jobs\ImportPlants;

class PlantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $plant = Plant::select('plants.id', 'plants.created_at', 'plants.updated_at', 'plants.location_id', 'plants.tag', 'plants.date', 'plants.notes', DB::raw('AsText(plants.relative_position) as relativePosition'), 'plants.project_id')->with(['location']);
        if ($request->id) {
            $plant = $plant->whereIn('plants.id', explode(',', $request->id));
        }
        if ($request->location) {
            $locations = $this->asIdList($request->location, Location::class, 'name');
            if (count($locations))
                $plant = $plant->whereIn('location_id', $locations);
        }
        if ($request->tag) {
            $plant = $plant->where('tag', 'LIKE', '%'.$request->tag.'%');
        }
        if ($request->taxon) {
            $taxon = Taxon::select('id')->whereRaw('odb_txname(name, level, parent_id) LIKE ?', ['%'.$request->taxon.'%'])->get();
            $identification = Identification::select('object_id')->where('object_type', '=', 'App\\Plant')->whereIn('taxon_id', $taxon);
            $plant = $plant->whereIn('plants.id', $identification);
        }
        if ($request->project) {
            $projects = $this->asIdList($request->project, Project::class, 'name');
            if (count($projects))
                $plant = $plant->whereIn('project_id', $projects);
        }
        if ($request->limit) {
            $plant->limit($request->limit);
        }
        $plant = $plant->get();

        $fields = ($request->fields ? $request->fields : 'simple');
        $plant = $this->setFields($plant, $fields, ['fullName', 'taxonName', 'id', 'location_id', 'locationName', 'tag', 'date', 'notes', 'projectName', 'relativePosition'
        ]);

        return $this->wrap_response($plant);
    }

    /**
     * Interprets $value as a value to search at a given table and $class as the class that is associated with the table.
     * If $value has a number or a list of numbers separeted by comma, this method converts this list to an array of numbers.
     * Otherwise, this method search the table for registries that has the $names LIKE '%'.$value.'%'. Additinally,
     * $names could be an array of fields, then the method find in all fields listed in this array, in this case if a single
     * registry matches more than one name (in $names) this method returns only once that registry.
     *
     * Example: asIdList('Rafael', 'Person', array('full_name', 'abbreviation', 'email') returns an array with the id of
     * all registry at table persons where full_name, or abbreviation or email contains Rafael.
     */
    public static function asIdList($value, $class, $names)
    {
        if (preg_match("/\d+(,\d+)*/", $value))
            return explode(',', $value);
        if (!is_array($names))
            return array ($class::select('id')->where($names, 'LIKE', '%'.$value.'%')->get()->first()->id);
        $ids = array();
        foreach ($names as $name) {
            $found = $class::select('id')->where($name, 'LIKE', '%'.$value.'%')->get();
            foreach ($found as $registry) {
                array_push($ids, $registry->id);
            }
        }
        return array_unique($ids);
    }
    
    public function store(Request $request)
    {
        $this->authorize('create', Plant::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportPlants::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
