<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Person;
use App\Models\Biocollection;
use Illuminate\Support\Facades\Lang;
//use Illuminate\Support\Facades\Request;
use App\DataTables\PersonsDataTable;
use App\DataTables\ActivityDataTable;

use App\Jobs\ImportPersons;
use Spatie\SimpleExcel\SimpleExcelReader;

use Response;
use App\Models\UserJob;


class PersonController extends Controller
{
    // Functions for autocompleting person names, used in dropdowns. Expects a $request->query input
    public function autocomplete(Request $request)
    {
        $persons = Person::where('full_name', 'LIKE', ['%'.$request->input('query').'%'])
            ->orWhere('abbreviation', 'LIKE', ['%'.$request->input('query').'%'])
            ->selectRaw("id as data, CONCAT(full_name, ' [',abbreviation, ']') as value")
            ->orderBy('value', 'ASC')
            ->get();

        return Response::json(['suggestions' => $persons]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PersonsDataTable $dataTable)
    {
        $biocollections = Biocollection::all();

        return $dataTable->render('persons.index', [
            'biocollections' => $biocollections,
    ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect('persons');
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
        $this->authorize('create', Person::class);
        $this->checkValid($request);
        // checks for duplicates, except if the request is already confirmed
        if (!$request->confirm) {
            $dupes = Person::duplicates($request->full_name, $request->abbreviation);
            if (sizeof($dupes)) {
                $request->flash();

                return view('persons.confirm', compact('dupes'));
            }
        }
        $person = Person::create($request->all());

        return redirect('persons/'.$person->id)->withStatus(Lang::get('messages.stored'));
    }

    protected function checkValid(Request $request, $id = null)
    {
        $this->validate($request, [
        'full_name' => 'required|max:191',
        'abbreviation' => ['required', 'max:191', 'regex:'.config('app.valid_abbreviation'), 'unique:persons,abbreviation,'.$id],
        'email' => ['nullable', 'max:191', 'email', 'unique:persons,email,'.$id],
    ]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $person = Person::findOrFail($id);
        /*
        // TODO: obtaining collected is complicated when number of records is too large
        The portion below was commented and it may not be needed except for the count of
        collected.objects. So, this could be modified to show only counts.
        $person->load('collected.object');
        $vouchers = $person->vouchers;
        $vouchers->load(['identification', 'parent']);
        $collected = collect($person->vouchers)->merge($person->collected->map(function ($x) {return $x->object; }));
        $collected = $collected->reject(function ($x) {return is_null($x); });
        return view('persons.show', compact('person', 'collected'));
        */
        return view('persons.show', compact('person'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $person = Person::findOrFail($id);
        $biocollections = Biocollection::all();
        $taxons = $person->taxons();

        return view('persons.edit', compact('person', 'biocollections', 'taxons'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $person = Person::findOrFail($id);
        $this->authorize('update', $person);
        $this->checkValid($request, $id);

        $person->update($request->only(['full_name', 'abbreviation', 'email', 'institution', 'biocollection_id','notes']));
        // add/remove specialists
        $person->taxons()->sync($request->specialist);

        return redirect('persons/'.$id)->withStatus(Lang::get('messages.saved'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $person = Person::findOrFail($id);
        $this->authorize('delete', $person);
        try {
            $person->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()
                ->withErrors([Lang::get('messages.fk_error')])->withInput();
        }

        return redirect('persons')->withStatus(Lang::get('messages.removed'));
    }


    public function activity($id, ActivityDataTable $dataTable)
    {
      $object = Person::findOrFail($id);
      return $dataTable->with('person', $id)->render('common.activity',compact('object'));
    }


    public function importJob(Request $request)
    {
      $this->authorize('create', Person::class);
      $this->authorize('create', UserJob::class);
      if (!$request->hasFile('data_file')) {
          $message = Lang::get('messages.invalid_file_missing');
      } else {
        /*
            Validate attribute file
            Validate file extension and maintain original if valid or else
            Store may save a csv as a txt, and then the Reader will fail
        */
        $valid_ext = array("CSV","csv","ODS","ods","XLSX",'xlsx');
        $ext = $request->file('data_file')->getClientOriginalExtension();
        if (!in_array($ext,$valid_ext)) {
          $message = Lang::get('messages.invalid_file_extension');
        } else {
          try {
            $data = SimpleExcelReader::create($request->file('data_file'),$ext)->getRows()->toArray();
          } catch (\Exception $e) {
            $data = [];
            $message = json_encode($e);
          }
          if (count($data)>0) {
            UserJob::dispatch(ImportPersons::class,[
              'data' => ['data' => $data]
            ]);
            $message = Lang::get('messages.dispatched');
          } else {
            $message = 'Something wrong with file';
          }
        }
      }
      return redirect('import/persons')->withStatus($message);
    }

}
