<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Models\Person;
use App\Models\UserJob;
use App\Models\ODBFunctions;
use App\Jobs\ImportPersons;
use Illuminate\Http\Request;
use Response;

class PersonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $persons = Person::select('*');
        if ($request->id) {
            $persons->whereIn('id', explode(',', $request->id));
        }
        if ($request->search) {
            ODBFunctions::moreAdvancedWhereIn($persons, ['full_name', 'abbreviation', 'email'], '*'.$request->search.'*');
        }
        if ($request->name) {
            ODBFunctions::advancedWhereIn($persons, 'full_name', $request->name);
        }
        if ($request->abbrev) {
            ODBFunctions::advancedWhereIn($persons, 'abbreviation', $request->abbrev);
        }
        if ($request->email) {
            ODBFunctions::advancedWhereIn($persons, 'email', $request->email);
        }
        if ($request->limit) {
            $persons->limit($request->limit);
        }

        $fields = ($request->fields ? $request->fields : 'simple');
        $possible_fields = config('api-fields.persons');
        $field_sets = array_keys($possible_fields);
        if (in_array($fields,$field_sets)) {
            $fields = implode(",",$possible_fields[$fields]);
        }
        $persons = $persons->cursor();
        if ($fields=="id") {
          $persons = $persons->pluck('id')->toArray();
        } else {
          $persons = $this->setFields($persons, $fields, $simple);
        }

        return $this->wrap_response($persons);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Person::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportPersons::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
