<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Herbarium;
use App\ODBFunctions;
use Illuminate\Http\Request;
use Response;

class HerbariumController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $herbaria = Herbarium::select('*');

        if ($request->id) {
            $herbaria->whereIn('id', explode(',', $request->id));
        }
        if ($request->acronym) {
            $herbaria->whereIn('acronym', explode(',', $request->acronym));
        }
        $herbaria = $herbaria->get();


        $fields = ($request->fields ? $request->fields : 'simple');

        $herbaria = $this->setFields($herbaria, $fields, ['id', 'acronym', 'name', 'irn']);

        return $this->wrap_response($herbaria);
    }

}
