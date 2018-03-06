<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use Illuminate\Http\Request;
use App\Person;
use App\UserJob;
use Response;
use App\Jobs\ImportLocations;

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
            $persons = $persons->whereIn('id', explode(',', $request->id));
        }
        if ($request->search) {
            $persons = $persons->where('full_name', 'LIKE', '%'.$request->search.'%')->orWhere('abbreviation', 'LIKE', '%'.$request->search.'%')->orWhere('email', 'LIKE', '%'.$request->search.'%');
        }
        if ($request->limit) {
            $persons->limit($request->limit);
        }
        $persons = $persons->get();

        $fields = ($request->fields ? $request->fields : 'simple');
        $persons = $this->setFields($persons, $fields, ['id', 'full_name', 'abbreviation', 'email', 'institution']);

        return $this->wrap_response($persons);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Person::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportPerson::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
