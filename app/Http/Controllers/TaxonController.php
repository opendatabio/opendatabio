<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Taxon;
use App\Models\Person;
use App\Models\Project;
use App\Models\Location;
use App\Models\Dataset;
use App\Models\BibReference;
use App\Models\ExternalAPIs;
use App\Models\Language;
use App\Models\Tag;
use Response;
use Lang;
use Validator;
use Illuminate\Support\MessageBag;
use App\DataTables\TaxonsDataTable;
use Activity;
use App\Models\ActivityFunctions;
use App\DataTables\ActivityDataTable;

use App\Models\UserJob;

use App\Jobs\ImportTaxons;
use Spatie\SimpleExcel\SimpleExcelReader;
use DB;
use Auth;


class TaxonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(TaxonsDataTable $dataTable)
    {
        return $dataTable->render('taxons.index', [
    ]);
    }

    /* for scope Project */
    public function indexProjects($id, TaxonsDataTable $dataTable)
    {
        $object = Project::findOrFail($id);
        return $dataTable->with([
            'project' => $id
        ])->render('taxons.index', compact('object'));
    }

    /* for scope Dataset */
    public function indexDatasets($id, TaxonsDataTable $dataTable)
    {
        $object = Dataset::findOrFail($id);
        return $dataTable->with([
            'dataset' => $id
        ])->render('taxons.index', compact('object'));
    }

    /* for scope Location */
    public function indexLocations($id, TaxonsDataTable $dataTable)
    {
        $object = Location::findOrFail($id);
        return $dataTable->with([
            'location' => $id
        ])->render('taxons.index', compact('object'));
    }

    /* for scope Location */
    public function indexLocationsDatasets($id, TaxonsDataTable $dataTable)
    {
        $ids = explode("|",$id);
        $object = Location::findOrFail($ids[0]);
        $object_second = Dataset::findOrFail($ids[1]);
        return $dataTable->with([
            'location' => $ids[0],
            'dataset' => $ids[1],
        ])->render('taxons.index', compact('object','object_second'));
    }

    /* for scope Location */
    public function indexLocationsProjects($id, TaxonsDataTable $dataTable)
    {
        $ids = explode("|",$id);
        $object = Location::findOrFail($ids[0]);
        $object_second = Project::findOrFail($ids[1]);
        return $dataTable->with([
            'location' => $ids[0],
            'project' => $ids[1],
        ])->render('taxons.index', compact('object','object_second'));
    }


    public function indexTaxons($id, TaxonsDataTable $dataTable)
    {
        $object = Taxon::findOrFail($id);
        return $dataTable->with([
            'taxon' => $id
        ])->render('taxons.index', compact('object'));
    }

    public function indexTaxonsDatasets($id, TaxonsDataTable $dataTable)
    {
        $ids = explode("|",$id);
        $object = Taxon::findOrFail($ids[0]);
        $object_second = Dataset::findOrFail($ids[1]);
        return $dataTable->with([
            'taxon' => $ids[0],
            'dataset' => $ids[1],
        ])->render('taxons.index', compact('object','object_second'));
    }
    public function indexTaxonsProjects($id, TaxonsDataTable $dataTable)
    {
        $ids = explode("|",$id);
        $object = Taxon::findOrFail($ids[0]);
        $object_second = Project::findOrFail($ids[1]);
        return $dataTable->with([
            'taxon' => $ids[0],
            'project' => $ids[1],
        ])->render('taxons.index', compact('object','object_second'));
    }

    public function indexTaxonsLocations($id, TaxonsDataTable $dataTable)
    {
        $ids = explode('|',$id);
        $object = Taxon::findOrFail($ids[0]);
        $object_second = Location::findOrFail($ids[1]);
        return $dataTable->with(['taxon' => $ids[0],'location' => $ids[1]])->render('measurements.index', compact('object','object_second'));
    }



    // Functions for autocompleting taxon names, used in dropdowns. Expects a $request->query input
    // MAY receive optional "$request->full" to return all names; default is to return only valid names
    public function autocomplete(Request $request)
    {
       //orderBy('fullname', 'ASC')
        $taxons = Taxon::noRoot()->with('parent')->whereRaw('odb_txname(taxons.name, taxons.level, taxons.parent_id) LIKE ?', ['%'.$request->input('query').'%'])
            ->selectRaw('id as data, odb_txname(taxons.name, taxons.level, taxons.parent_id) as fullname, taxons.level, taxons.valid')
            ->take(30);
        if (!$request->full) {
            $taxons = $taxons->valid();
        }
        $taxons = $taxons->get();
        $taxons = collect($taxons)->transform(function ($taxon) {
            $taxon->value = $taxon->qualifiedFullname;
            if ($taxon->level >= 180 and $taxon->parent) { // append family name to display
                $taxon->value .= ' ['.$taxon->family.']';
            }

            return $taxon;
        });

        return Response::json(['suggestions' => $taxons]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $references = BibReference::select('*',DB::raw('odb_bibkey(bibtex) as bibkey'))->get();
        return view('taxons.create',compact('references'));
    }

    public function customValidate(Request $request, $id = 0) // id used for checking duplicates
    {
        $rules = [
            'name' => 'required|string|max:191',
            'level' => 'required|integer',
            'bibreference' => 'nullable|string|max:191',
        ];
        $validator = Validator::make($request->all(), $rules);
        if (($request->level > 180 or $request->level == -100) and !$request->parent_id) {
            $validator->after(function ($validator) {
                $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_required_error'));
            });
        }
        if ($request->level > 180) {
            $parent = Taxon::findOrFail($request->parent_id);
            if ($parent->level < 170) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_genus_error'));
                });
            }
        }
        if (in_array($request->level, [220, 240, 270]) and $request->parent_id) {
            $parent = Taxon::findOrFail($request->parent_id);
            if (210 != $parent->level) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_species_error'));
                });
            }
        }
        if ($request->parent_id) {
            $parent = Taxon::findOrFail($request->parent_id);
            while ($parent->level == -100 and $parent->parent) {
                $parent = $parent->parent;
            }
            $validator->after(function ($validator) use ($request, $parent) {
                if ($request->level <= $parent->level and ($request->level != -100 and $parent->level != -100)) {
                    $validator->errors()->add('parent_id', Lang::get('messages.taxon_parent_level_error'));
                }
            });
        }
        if ('on' != $request->valid and $request->senior_id) {
            $senior = Taxon::findOrFail($request->senior_id);
            $validator->after(function ($validator) use ($request, $senior) {
                if (abs($request->level - $senior->level) > 20) {
                    $validator->errors()->add('senior_id', Lang::get('messages.taxon_senior_level_error'));
                }
                if (!$senior->valid) {
                    $validator->errors()->add('senior_id', Lang::get('messages.taxon_senior_invalid_error'));
                }
            });
        }
        // author is required EXCEPT for clades
        if (((!$request->author_id and 'on' == $request->unpublished)
                or ('on' != $request->unpublished and !$request->author))
            and $request->level != -100) {
            $validator->after(function ($validator) {
                $validator->errors()->add('author_id', Lang::get('messages.taxon_author_error'));
            });
        }

        //no reason why not to allow both
        //if (($request->bibreference_id and $request->bibreference)
          //      or
        if (!$request->bibreference_id and !$request->bibreference and 'on' != $request->unpublished)
        {
            $validator->after(function ($validator) {
                $validator->errors()->add('bibreference_id', Lang::get('messages.taxon_bibref_error'));
            });
        }
        // checks for [name, parent] matches and [name, parent, author] matches (ref issue #61)
        $reqname = trim(substr($request->name, strrpos($request->name, ' ') - strlen($request->name)));
        $exact_match =
            Taxon::where('author', $request->author)
            ->where('author_id', $request->author_id)
            ->where('name', $reqname)
            ->where('parent_id', $request->parent_id)
            ->where('id', '!=', $id);
        if ($exact_match->count()) {
            $validator->after(function ($validator) {
                $validator->errors()->add('name', Lang::get('messages.taxon_duplicate'));
            });
        }
        $name_match =
            Taxon::where('name', $reqname)
            ->where('parent_id', $request->parent_id)
            ->where('valid', true)
            ->where('id', '!=', $id);
        if ('on' == $request->valid and $name_match->count()) {
            $validator->after(function ($validator) {
                $validator->errors()->add('name', Lang::get('messages.taxon_duplicate'));
            });
        }

        return $validator;
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
        $this->authorize('create', Taxon::class);
        $validator = $this->customValidate($request);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        // Laravel sends checkbox as On??
        if ('on' == $request['valid']) {
            $request['valid'] = true;
        } else {
            $request['valid'] = false;
        }
        // cannot have both valid and senior_id
        if ($request->valid) {
            $request->senior_id = null;
        }

        if ('on' == $request['unpublished']) {
            $request['valid'] = true;
            $taxon = new Taxon($request->only(['level', 'valid', 'parent_id',
                    'author_id', 'notes', ]));
            $taxon->fullname = $request['name'];
            $taxon->save(); // we need to save it here to have an id to use on the next methods
        } else {
            $taxon = new Taxon($request->only(['level', 'valid', 'parent_id', 'senior_id', 'author',
                    'bibreference', 'bibreference_id', 'notes', ]));
            $taxon->fullname = $request['name'];
            $taxon->save(); // we need to save it here to have an id to use on the next methods
            $taxon->setapikey('Mobot', $request['mobotkey']);
            $taxon->setapikey('IPNI', $request['ipnikey']);
            $taxon->setapikey('Mycobank', $request['mycobankkey']);
            $taxon->setapikey('GBIF', $request['gbifkey']);
            $taxon->setapikey('ZOOBANK', $request['zoobankkey']);

            $taxon->save();

            $references = [];
            if (is_array($request->references_aditional)) {
              foreach($request->references_aditional as $bib_reference_id) {
                  $references[] = array(
                    'bib_reference_id' => $bib_reference_id
                  );
              }
            }
            $taxon->references()->detach();
            if (count($references)>0) {
              $taxon->references()->sync($references);
            }
        }

        return redirect('taxons/'.$taxon->id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id,TaxonsDataTable $dataTable)
    {

        $taxon = Taxon::with('identifications.object')->findOrFail($id);
        if ($taxon->author_id) {
            $author = Person::findOrFail($taxon->author_id);
        } else {
            $author = null;
        }
        if ($taxon->bibreference_id) {
            $bibref = BibReference::findOrFail($taxon->bibreference_id);
        } else {
            $bibref = null;
        }
        $media = $taxon->mediaDescendantsAndSelf();
        if ($media->count()) {
          $media = $media->paginate(3);
        } else {
          $media = null;
        }


        return $dataTable->with([
            'taxon' => $id,
            'related_taxa' => 1,
        ])->render('taxons.show', compact('taxon', 'author', 'bibref','media'));
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
        $taxon = Taxon::findOrFail($id);
        $references = BibReference::select('*',DB::raw('odb_bibkey(bibtex) as bibkey'))->get();
        return view('taxons.create', compact('taxon','references'));
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
        $taxon = Taxon::findOrFail($id);
        $this->authorize('update', $taxon);
        $validator = $this->customValidate($request, $id);
        if ($validator->fails()) {
            return redirect()->back()
                            ->withErrors($validator)
                            ->withInput();
        }
        // Laravel sends checkbox as On??
        if ('on' == $request['valid']) {
            $request['valid'] = true;
        } else {
            $request['valid'] = false;
        }
        // cannot have both valid and senior_id
        if ($request->valid) {
            $request->senior_id = null;
        }

        if ('on' == $request['unpublished']) {
            $request['valid'] = true;
            $request['author'] = null;
            $request['bibreference'] = null;
            $request['bibreference_id'] = null;
            $request['senior_id'] = null;
            $taxon->update($request->only(['level', 'valid', 'parent_id', 'author', 'senior_id',
                        'author_id', 'bibreference', 'bibreference_id', 'notes', ]));
            $taxon->fullname = $request['name'];
            // update external keys
            $taxon->externalrefs()->delete();
            $taxon->save();
        } else {
            $request['author_id'] = null;
            $taxon->update($request->only(['level', 'valid', 'parent_id', 'senior_id', 'author', 'author_id','bibreference', 'bibreference_id', 'notes', ]));
            $taxon->fullname = $request['name'];
            $taxon->setapikey('Mobot', $request['mobotkey']);
            $taxon->setapikey('IPNI', $request['ipnikey']);
            $taxon->setapikey('Mycobank', $request['mycobankkey']);
            $taxon->setapikey('GBIF', $request['gbifkey']);
            $taxon->setapikey('ZOOBANK', $request['zoobankkey']);

            $taxon->save();


            $references = [];
            if (is_array($request->references_aditional)) {
              foreach($request->references_aditional as $bib_reference_id) {
                  $references[] = array(
                    'bib_reference_id' => $bib_reference_id
                  );
              }
            }
            $taxon->references()->detach();
            if (count($references)>0) {
              $taxon->references()->sync($references);
            }
            //$newexternal = Taxon::findOrFail($id)->externalrefs()->get()->toArray();

        }

        return redirect('taxons/'.$id)->withStatus(Lang::get('messages.saved'));
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
    }

    public function checkapis(Request $request)
    {
        if (is_null($request['name'])) {
            return Response::json(['error' => Lang::get('messages.name_error')]);
        }
        $apis = new ExternalAPIs();
        $mobotdata = $apis->getMobot($request->name);

        // // TODO: IPNI has changed and will implement an API
        //$ipnidata = $apis->getIpni($request->name);
        $ipnidata= null;

        // WARNING: MYCOBANK API OUT OF SERVICE 18-11-2020
        //THEY PROVIDE A STATIC ZIP FILE THAT COULD USED LOCALLY TO SEARCH names
        //WAIT FOR API, as no rush
        //only search fungi if not found as Individual
        /*
        if ($mobotdata[0] == ExternalAPIs::NOT_FOUND  and $ipnidata[0] == ExternalAPIs::NOT_FOUND) {
          $mycobankdata = $apis->getMycobank($request->name);
        } else {

        }
        */
        $mycobankdata = [ExternalAPIs::NOT_FOUND];
        $gbifdata = [ExternalAPIs::NOT_FOUND];
        $zoobankdata = [ExternalAPIs::NOT_FOUND];
        if (null == $ipnidata) {
          $ipnidata = [ExternalAPIs::NOT_FOUND];
        }
        if (null == $mobotdata) {
          $mobotdata = [ExternalAPIs::NOT_FOUND];
        }


        //this is for animal names (and fungi), so only if not found previously
        if ($mobotdata[0] == ExternalAPIs::NOT_FOUND  and $ipnidata[0] == ExternalAPIs::NOT_FOUND and $mycobankdata[0] == ExternalAPIs::NOT_FOUND) {
          $gbifdata = $apis->getGBIF($request->name);
          $zoobankdata = $apis->getZOOBANK($request->name);
          if (is_null($zoobankdata)) {
            $zoobankdata = [ExternalAPIs::NOT_FOUND];
          }
        }



        // includes the messages in the return object
        $bag = new MessageBag();
        if (is_null($mobotdata)) {
            $bag->add('e1', Lang::get('messages.mobot_error'));
        }
        if (is_null($ipnidata)) {
            $bag->add('e5', Lang::get('messages.ipni_error'));
        }
        if (is_null($mycobankdata)) {
            $bag->add('e8', Lang::get('messages.mycobank_error'));
        }
        if (is_null($gbifdata)) {
            $bag->add('e8', Lang::get('messages.gbif_error'));
        }

        if (($mobotdata[0] == ExternalAPIs::NOT_FOUND) and
                ($ipnidata[0] == ExternalAPIs::NOT_FOUND) and
                ($mycobankdata[0] == ExternalAPIs::NOT_FOUND) and
                ($gbifdata[0] == ExternalAPIs::NOT_FOUND) and
                ($zoobankdata[0] == ExternalAPIs::NOT_FOUND)
            ) {
            $bag->add('e2', Lang::get('messages.apis_not_found'));
        }
        if ($mobotdata[0] & ExternalAPIs::MULTIPLE_HITS) {
            $bag->add('e3', Lang::get('messages.mobot_multiple_hits'));
        }
        if ($mobotdata[0] & ExternalAPIs::NONE_SYNONYM) {
            $bag->add('e4', Lang::get('messages.mobot_none_synonym'));
        }
        if ($ipnidata[0] & ExternalAPIs::MULTIPLE_HITS) {
            $bag->add('e7', Lang::get('messages.ipni_multiple_hits'));
        }
        if ($mycobankdata[0] & ExternalAPIs::MULTIPLE_HITS) {
            $bag->add('e10', Lang::get('messages.mycobank_multiple_hits'));
        }

        // 0 -> rank
        // 1 -> author
        // 2 -> valid
        // 3 -> reference
        // 4 -> parent
        // 5 -> senior
        // 6 -> mobot key
        // 7 -> ipni key
        // 8 -> mycobank key
        $rank = null;
        if (!is_null($ipnidata) && array_key_exists('rank', $ipnidata)) {
            $rank = $ipnidata['rank'];
        }
        if (!is_null($mobotdata) && array_key_exists('rank', $mobotdata)) {
            $rank = $mobotdata['rank'];
        }
        if (!is_null($mycobankdata) && array_key_exists('rank', $mycobankdata)) {
            $rank = $mycobankdata['rank'];
        }
        if (is_null($rank) and !is_null($gbifdata) and array_key_exists('rank', $gbifdata)) {
            $rank = $gbifdata['rank'];
        }
        if (is_null($rank) and !is_null($zoobankdata) and array_key_exists('rank', $zoobankdata)) {
            $rank = $zoobankdata['rank'];
        }


        $author = null;
        if (!is_null($ipnidata) && array_key_exists('author', $ipnidata)) {
            $author = $ipnidata['author'];
        }
        if (!is_null($mobotdata) && array_key_exists('author', $mobotdata)) {
            $author = $mobotdata['author'];
        }
        if (!is_null($mycobankdata) && array_key_exists('author', $mycobankdata)) {
            $author = $mycobankdata['author'];
        }
        //gbif only if not found before, as Individuals and animals may share names
        if (is_null($author) and !is_null($gbifdata) && array_key_exists('author', $gbifdata)) {
            $author = $gbifdata['author'];
        }
        if (is_null($author) and !is_null($zoobankdata) and array_key_exists('author', $zoobankdata)) {
            $author = $zoobankdata['author'];
        }

        $reference = null;
        if (!is_null($ipnidata) && array_key_exists('reference', $ipnidata)) {
            $reference = $ipnidata['reference'];
        }
        if (!is_null($mobotdata) && array_key_exists('reference', $mobotdata)) {
            $reference = $mobotdata['reference'];
        }
        if (!is_null($mycobankdata) && array_key_exists('reference', $mycobankdata)) {
            $reference = $mycobankdata['reference'];
        }

        $rank = Taxon::getRank($rank);
        $valid = null;
        if (!is_null($mobotdata) && array_key_exists('valid', $mobotdata)) {
            $valid = in_array($mobotdata['valid'], [
                    'Legitimate',
                    'nom. cons.',
                    'No opinion',
                ]);
        }
        if (!is_null($mycobankdata) && array_key_exists('valid', $mycobankdata)) {
            $valid = in_array($mycobankdata['valid'], [
                    'Legitimate',
                    'nom. cons.',
                    'No opinion',
                ]);
        }

        $getparent = null;
        $parent = null;
        if (!is_null($mobotdata) and array_key_exists('parent', $mobotdata)) {
            $getparent = $mobotdata['parent'];
        }
        if (is_null($getparent) and !is_null($ipnidata) and array_key_exists('parent', $ipnidata)) {
            $getparent = $ipnidata['parent'];
        }
        if (is_null($getparent) and !is_null($gbifdata) and array_key_exists('parent', $gbifdata)) {
            $getparent = $gbifdata['parent'];
        }
        //zoobank does not provide parent for genus, only species
        if (is_null($getparent) and !is_null($zoobankdata) and array_key_exists('parent', $zoobankdata)) {
            $getparent = $zoobankdata['parent'];
        }

        $parent = Taxon::getParent($request['name'], $rank, $getparent);
        if (!is_null($mycobankdata) && array_key_exists('parent', $mycobankdata)) {
            $parent = $mycobankdata['parent'];
        } // mycobank api already returns a "getParent"

        if (!is_null($parent) and !is_array($parent)) {
            $bag->add('parent_id', Lang::get('messages.parent_not_registered', ['name' => $parent]));
            $parent = null;
        }

        $senior = null;
        if (!is_null($mobotdata) && array_key_exists('senior', $mobotdata) and !is_null($mobotdata['senior'])) {
            $tosenior = Taxon::valid()
            ->whereRaw('odb_txname(taxons.name, taxons.level, taxons.parent_id) = ?', [$mobotdata['senior']])
            ->first();
            if ($tosenior) {
                $senior = [$tosenior->id, $tosenior->fullname];
            } else {
                $bag->add('senior_id', Lang::get('messages.senior_not_registered', ['name' => $mobotdata['senior']]));
            }
        }
        if (!is_null($mycobankdata) && array_key_exists('senior', $mycobankdata) and !is_null($mycobankdata['senior'])) {
            $tosenior = Taxon::valid()
            ->whereRaw('odb_txname(taxons.name, taxons.level, taxons.parent_id) = ?', [$mycobankdata['senior']])
            ->first();
            if ($tosenior) {
                $senior = [$tosenior->id, $tosenior->fullname];
            } else {
                $bag->add('senior_id', Lang::get('messages.senior_not_registered', ['name' => $mycobankdata['senior']]));
            }
        }

        $mobotkey = null;
        if (!is_null($mobotdata) && array_key_exists('key', $mobotdata)) {
            $mobotkey = $mobotdata['key'];
        }
        $ipnikey = null;
        if (!is_null($ipnidata) && array_key_exists('key', $ipnidata)) {
            $ipnikey = $ipnidata['key'];
        }
        $mycobankkey = null;
        if (!is_null($mycobankdata) && array_key_exists('key', $mycobankdata)) {
            $mycobankkey = $mycobankdata['key'];
        }
        $gbifkey = null;
        if (!is_null($gbifdata) && array_key_exists('key', $gbifdata)) {
            $gbifkey = $gbifdata['key'];
        }
        $zoobankkey = null;
        if (!is_null($zoobankdata) && array_key_exists('key', $zoobankdata)) {
            $zoobankkey = $zoobankdata['key'];
        }
        return Response::json(['bag' => $bag,
                    'apidata' => [
                            $rank,
                            $author,
                            $valid,
                            $reference,
                            $parent,
                            $senior,
                            $mobotkey,
                            $ipnikey,
                            $mycobankkey,
                            $gbifkey,
                            $zoobankkey,
                    ],
            ]);
    }


    public function activity($id, ActivityDataTable $dataTable)
    {
        $object = Taxon::findOrFail($id);
        return $dataTable->with('taxon', $id)->render('common.activity',compact('object'));
    }


    public function importJob(Request $request)
    {
      $this->authorize('create', Taxon::class);
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
            $data = SimpleExcelReader::create($request->file('data_file'),$ext)
            ->getRows()->toArray();
          } catch (\Exception $e) {
            $data = [];
            $message = json_encode($e);
          }
          if (count($data)>0) {
            UserJob::dispatch(ImportTaxons::class,[
              'data' => ['data' => $data]
            ]);
            $message = Lang::get('messages.dispatched');
          } else {
            $message = 'Something wrong with file';
          }
        }
      }
      return redirect('import/taxons')->withStatus($message);
    }



}
