<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\DataTables\VouchersDataTable;
use App\Biocollection;
use App\ExternalAPIs;
use Illuminate\Support\Facades\Lang;
use App\Jobs\ImportBiocollections;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\UserJob;

class BiocollectionController extends Controller
{

  public function autocomplete(Request $request)
  {
    $biocollections = Biocollection::select("id as data")->addSelect("acronym as value")->where('acronym','like',$request->input('query')."%")->take(30)->get();
    return Response::json(['suggestions' => $biocollections]);
  }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $biocollections = Biocollection::orderBy('acronym')->paginate(10);

        return view('biocollections.index', compact('biocollections'));
    }

    public function checkih(Request $request)
    {
        if (is_null($request['acronym'])) {
            return Response::json(['error' => Lang::get('messages.acronym_error')]);
        }
        $apis = new ExternalAPIs();
        $ihdata = $apis->getIndexHerbariorum($request->acronym);
        if (is_null($ihdata)) {
            return Response::json(['error' => Lang::get('messages.acronym_not_found')]);
        }

        return Response::json(['ihdata' => $ihdata]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect('biocollections');
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
        $this->authorize('create', Biocollection::class);
        $this->validate($request, [
        'name' => 'required|max:191',
        'acronym' => 'required|max:20|unique:biocollections',
        'irn' => 'required',
        ]);

        $herb = Biocollection::create([
          'name' => $request->name,
          'irn' => $request->irn,
          'acronym' => mb_strtoupper($request->acronym),
        ]);

        return redirect('biocollections/'.$herb->id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id, VouchersDataTable $dataTable)
    {
        $biocollection = Biocollection::findOrFail($id);

        return $dataTable->with([
               'biocollection_id' => $id
           ])->render('biocollections.show', compact('biocollection'));
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
        $biocollection = Biocollection::findOrFail($id);
        $this->authorize('delete', $biocollection);
        try {
            $biocollection->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()
                ->withErrors([Lang::get('messages.fk_error')]);
        }

        return redirect('biocollections')->withStatus(Lang::get('messages.removed'));
    }


    public function importJob(Request $request)
    {
      $this->authorize('create', Biocollection::class);
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
          if (is_array($data)) {
            UserJob::dispatch(ImportBiocollections::class,[
              'data' => ['data' => $data]
            ]);
            $message = Lang::get('messages.dispatched');
          } else {
            $message = 'Something wrong with file';
          }
        }
      }
      return redirect('import/biocollections')->withStatus($message);
    }

}
