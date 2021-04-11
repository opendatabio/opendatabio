<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use App\DataTables\TraitsDataTable;
use App\DataTables\ActivityDataTable;
use App\Models\ODBTrait;
use Illuminate\Http\Request;
use App\Models\Language;
use Lang;
use Response;

use App\Jobs\ImportTraits;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\Models\UserJob;


class TraitController extends Controller
{
    // Functions for autocompleting person names, used in dropdowns. Expects a $request->query input
    public function autocomplete(Request $request)
    {
        $traits = ODBTrait::whereHas('translations', function ($query) use ($request) {
            $query->where('translation', 'LIKE', ['%'.$request->input('query').'%']);
        })
            ->appliesTo($request->type)
            ->get();
        $traits = collect($traits)->transform(function ($odbtrait) {
            $odbtrait->data = $odbtrait->id;
            $odbtrait->value = $odbtrait->name;

            return $odbtrait;
        });

        return Response::json(['suggestions' => $traits]);
    }

    // Returns the partial view for filling a given trait
    public function getFormElement(Request $request)
    {
        $odbtrait = ODBTrait::findOrFail($request->id);
        if ($request->measurement) {
            $measurement = Measurement::findOrFail($request->measurement);
        } else {
            $measurement = NULL;
        }
        $html = view('traits.elements.'.$odbtrait->type, compact('odbtrait', 'measurement'))->render();

        return Response::json(array('html' => $html));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(TraitsDataTable $dataTable)
    {
        return $dataTable->render('traits.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $languages = Language::all();

        return view('traits.create', compact('languages'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', ODBTrait::class);
        $this->validate($request, ODBTrait::rules());
        $odbtrait = ODBTrait::create($request->only(['export_name', 'type','bibreference_id']));
        $odbtrait->setFieldsFromRequest($request);

        return redirect('traits/'.$odbtrait->id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $odbtrait = ODBTrait::findOrFail($id);

        return view('traits.show', compact('odbtrait'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $languages = Language::all();
        $odbtrait = ODBTrait::findOrFail($id);

        return view('traits.create', compact('languages', 'odbtrait'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $odbtrait = ODBTrait::findOrFail($id);
        $this->authorize('update', $odbtrait);
        $this->validate($request, ODBTrait::rules($id));
        $odbtrait->update($request->only(['export_name', 'type','bibreference_id']));
        $odbtrait->setFieldsFromRequest($request);

        return redirect('traits/'.$id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }



    public function activity($id, ActivityDataTable $dataTable)
    {
      $object = ODBTrait::findOrFail($id);
      return $dataTable->with('odbtrait', $id)->render('common.activity',compact('object'));
    }

    public function importJob(Request $request)
    {

      $this->authorize('create', ODBTrait::class);
      $this->authorize('create', UserJob::class);

      if (!$request->hasFile('data_file')) {
          $message = Lang::get('messages.invalid_file_missing');
      } else {
        /*
            Validate attribute file
            Validate file extension and maintain original if valid or else
            Store may save a csv as a txt, and then the Reader will fail
        */
        $valid_ext = array("csv","ods",'xlsx');
        $ext = mb_strtolower($request->file('data_file')->getClientOriginalExtension());
        if (!in_array($ext,$valid_ext)) {
          $message = Lang::get('messages.invalid_file_extension');
        } else {
          try {
            $data = SimpleExcelReader::create($request->file('data_file'),$ext)->getRows()->toArray();
          } catch (\Exception $e) {
            $data = [];
            $message = json_encode($e);
          }
        }
        $translations = null;
        if ($request->hasFile('trait_categories_file')) {
            $ext = mb_strtolower($request->file('trait_categories_file')->getClientOriginalExtension());
            if (!in_array($ext,$valid_ext)) {
              $message = Lang::get('messages.invalid_file_extension')." Translation file.";
            } else {
              try {
                  $translations = SimpleExcelReader::create($request->file('trait_categories_file'),$ext)->getRows()->toArray();
                  //$data = file_get_contents($request->file('data_file'));
              } catch (Exception $e) {
                  $translations = [];
                  $message = json_encode($e);
              }
            }
        }
        if (count($data)>0) {
          UserJob::dispatch(ImportTraits::class,[
            'data' => [
                'data' => $data,
                'translations' => $translations,
                ]
          ]);
          $message = Lang::get('messages.dispatched');
        } else {
          $message = 'Something wrong with file';
        }
      }
      return redirect('import/traits')->withStatus($message);
    }



}
