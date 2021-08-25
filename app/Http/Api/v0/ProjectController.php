<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Models\Project;
use Illuminate\Http\Request;
use Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $projects = Project::withCount(['datasets','measurements']);
        if ($request->id) {
            $projects->whereIn('id', explode(',', $request->id));
        }
        $fields = ($request->fields ? $request->fields : 'simple');
        $simple = ['id', 'fullname', 'notes','description','contactEmail','datasets_count','individuals_count','vouchers_count','locations_count','measurements_count','taxons_count','media_count','species_count'];
        //include here to be able to add mutators and categories
        if ('all' == $fields) {
            $keys = array_keys($persons->first()->toArray());
            $fields = array_merge($simple,$keys);
            $fields =  implode(',',$fields);
        }

        $projects = $projects->cursor();
        if ($fields=="id") {
          $projects = $projects->pluck('id')->toArray();
        } else {
          $projects = $this->setFields($projects, $fields, $simple);
        }

        return $this->wrap_response($projects);
    }

}
