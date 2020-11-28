<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Project;
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
        $projects = Project::withCount(['plants', 'vouchers']);
        if ($request->id) {
            $projects->whereIn('id', explode(',', $request->id));
        }
        $projects = $projects->get();

        $fields = ($request->fields ? $request->fields : 'simple');

        $projects = $this->setFields($projects, $fields, ['id', 'fullname', 'notes', 'privacyLevel','contactEmail','plants_count','vouchers_count']);

        return $this->wrap_response($projects);
    }

}
