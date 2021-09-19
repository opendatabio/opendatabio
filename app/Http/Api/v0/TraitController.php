<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Api\v0;

use App\Jobs\ImportTraits;
use Illuminate\Http\Request;
use App\Models\ODBTrait;
use App\Models\UserJob;
use App\Models\ODBFunctions;
use App\Models\Language;
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
        $traits = ODBTrait::select("*");
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

        $traits = $traits->cursor();


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
        $fields = ($request->fields ? $request->fields : 'simple');
        $possible_fields = config('api-fields.odbtraits');
        $field_sets = array_keys($possible_fields);

        if (in_array($fields,$field_sets)) {
          $fields = implode(",",$possible_fields[$fields]);
        }
        $field_arr = explode(",",$fields);
        $has_category = in_array('categories',$field_arr);
        if ($has_category) {
          $categories = [];
          foreach ($traits as $thetrait) {
              $catarr = "";
              if (in_array($thetrait['type'],[ODBTrait::CATEGORICAL, ODBTrait::CATEGORICAL_MULTIPLE, ODBTrait::ORDINAL])) {
                    $cats = $thetrait['categories'];
                    $catarr = [];
                    foreach($cats as $cat) {
                      $catarr[] = array('id' => $cat->id,'name' => $cat->translate(0,$lang), 'description' => $cat->translate(1,$lang), 'rank' => $cat->rank);
                    }
                    $categories[$thetrait->id] = $catarr;
              }
          }
        }

        if ($request->bibreference) {
           $fields .= ",bibreference";
        }
        if ($fields=="id") {
          $final = $traits->pluck('id')->toArray();
        } else {
          $traits = $this->setFields($traits, $fields,null);
          //add translations and transalated categories if the case
          $final = [];
          foreach($traits as $key => $trait) {
            if ($request->language) {
              $trait['name'] = $names[$key]['name'];
              $trait['description'] = $names[$key]['description'];
            }
            if ($has_category and isset($categories[$trait['id']])) {
                $trait['categories'] = $categories[$trait['id']];
            }
            $final[] = $trait;
          }
        }


        return $this->wrap_response($final);
    }



    public function store(Request $request)
    {
        $this->authorize('create', ODBTrait::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportTraits::class, ['data' => $request->post()]);

        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }
}
