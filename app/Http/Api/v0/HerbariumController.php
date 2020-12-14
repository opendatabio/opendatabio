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


        $fields = ($request->fields ? $request->fields : 'simple');
        $simple = ['id', 'acronym', 'name', 'irn'];
        //include here to be able to add mutators and categories
        if ('all' == $fields) {
            $keys = array_keys($herbaria->first()->toArray());
            $fields = array_merge($simple,$keys);
            $fields =  implode(',',$fields);
        }

        $herbaria = $herbaria->cursor();
        if ($fields=="id") {
          $herbaria = $herbaria->pluck('id')->toArray();
        } else {
          $herbaria = $this->setFields($herbaria, $fields, $simple);
        }
        return $this->wrap_response($herbaria);
    }

}
