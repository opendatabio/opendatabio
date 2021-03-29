<?php
/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Models\Picture;
use Illuminate\Http\Request;
use Response;

class PictureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

    }

    public function store(Request $request)
    {
        $this->authorize('create', Picture::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportPictures::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
