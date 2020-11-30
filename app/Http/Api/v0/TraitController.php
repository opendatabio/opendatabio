<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Jobs\ImportTraits;
use Illuminate\Http\Request;
use App\ODBTrait;
use App\UserJob;
use App\ODBFunctions;
use App\Language;
use DB;
use Lang;
use Response;
use Illuminate\Support\Facades\App;

class TraitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    //TODO: get should include in name and descriptions the arrays containing values for each translation
    //same for categories

    public function index(Request $request)
    {
        $traits = ODBTrait::select('*',DB::raw('odb_traittypename(type) as typename'));
        if ($request->id) {
            $traits->whereIn('id', explode(',', $request->id));
        }
        if ($request->name) {
            $odbtraits = ODBFunctions::asIdList($request->name, ODBTrait::select('id'), 'export_name');
            $traits->whereIn('id', $odbtraits);
        }


        if ($request->limit && $request->offset) {
            $traits->offset($request->offset)->limit($request->limit);
        } else {
          if ($request->limit) {
            $traits->limit($request->limit);
          }
        }

        $traits = $traits->with('bibreference');

        $traits = $traits->get();

        //the list of simple Fields
        $simple = ['id', 'type', 'typename','export_name','unit', 'range_min', 'range_max', 'link_type','value_length','name','description','objects','categories'];

        //if language is specified, need to mutate name and description for current language
        if ($request->language) {
           $lang = ODBFunctions::validRegistry(Language::select('id'),$request->language, ['id', 'code','name'])->id;
           //get requested translation to apply below
           $names = $traits->map(function($trait) use($lang) { $name = $trait->translate(0,$lang); $description =  $trait->translate(1,$lang); unset($trait->translations);return ['name' =>$name,'description' => $description];})->toArray();
        } else {
           //if not indicated, then assumes first language in Language table
           $lang = 1;
        }

        //add categories for categorical traits
        //there is probably a more elegant way;
        foreach ($traits as $thetrait) {
            $catarr = "";
            if (in_array(  $thetrait->type,[ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL])) {
                  $cats = $thetrait->categories;
                  $catarr = array();
                  foreach($cats as $cat) {
                    $catarr[] = array('id' => $cat->id,'name' => $cat->translate(0,$lang), 'description' => $cat->translate(1,$lang), 'rank' => $cat->rank);
                  }
            }
            unset($thetrait->categories);
            $thetrait->categories = $catarr;
        }

        $fields = ($request->fields ? $request->fields : 'simple');

        //include here to be able to add mutators and categories
        if ('all' == $fields) {
            $keys = array_keys($traits->first()->toArray());
            $fields = array_merge($simple,$keys);
            $fields =  implode(',',$fields);
        }
        if ($request->bibreference) {
            if ('simple' == $fields) {
              $simple[] = 'bibreference';
            } else {
              $fields .= ",bibreference";
            }
        }

        $traits = $this->setFields($traits, $fields, $simple);

        //this is placed here because otherwise setFields will change to default language
        if ($request->language) {
          foreach($traits as $key => $trait) {
            $trait['name'] = $names[$key]['name'];
            $trait['description'] = $names[$key]['description'];
            $traits[$key] = $trait;
          }
        }


        return $this->wrap_response($traits);
    }



    public function store(Request $request)
    {
        $this->authorize('create', ODBTrait::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportTraits::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
