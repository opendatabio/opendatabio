<?php

namespace App\Api\v0;

use App\Api\v0\Controller;
use App\Jobs\ImportTaxons;
use Illuminate\Http\Request;
use App\Taxon;
use App\TaxonExternal;
use App\Person;
use App\BibReference;
use App\UserJob;
use App\ExternalAPIs;
use Lang;
use Log;
use Validator;
use Response;
use Illuminate\Support\MessageBag;

class TaxonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $taxons = Taxon::query()->with(['author_person','reference']);
        if ($request->id)
            $taxons = $taxons->where('id', '=', $request->id);
        if ($request->search)
            $taxons = $taxons->whereRaw('odb_txname(name, level, parent_id) LIKE ?', ['%' . $request->search . '%']);
        if ($request->level)
            $taxons = $taxons->where('level', '=', $request->level);
        if ($request->valid)
            $taxons = $taxons->valid();
        if ($request->external)
            $taxons = $taxons->with('externalrefs');
        if ($request->limit)
            $taxons->limit($request->limit);
        $taxons = $taxons->get();

        $fields = ($request->fields ? $request->fields : "simple");
        if ($fields == "simple")
            $fields = ['id', 'fullname', 'levelName', 'authorSimple', 'bibreferenceSimple', 'valid', 'senior_id', 'parent_id'];
        else 
            $fields = explode(',',$fields);
        if ($fields[0] != "all")
            $taxons = $taxons->map(function ($obj) use ($fields) {
                if (in_array('levelName', $fields))
                    $obj->append('levelName');
                if (in_array('authorSimple', $fields))
                    $obj->append('authorSimple');
                if (in_array('bibreferenceSimple', $fields))
                    $obj->append('bibreferenceSimple');
                return collect($obj->toArray())
                    ->only($fields)
                    ->all();
            });
        return $this->wrap_response($taxons);
    }

    public function store(Request $request) 
    {
	    $this->authorize('create', Taxon::class);
        $this->authorize('create', UserJob::class);
        $jobid = UserJob::dispatch(ImportTaxons::class, ['data' => $request->post()]);
        return Response::json(['message' => 'OK', 'userjob' => $jobid]);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
            $taxon = Taxon::findOrFail($id);
            return $this->wrap_response($taxon);
    }
}
