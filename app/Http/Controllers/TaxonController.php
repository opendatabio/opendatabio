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
        //allows species only to be linked to a non-genus parent
        if ($request->level > 180 and $request->level!=210) {
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
        if (!$request->bibreference_id and !$request->bibreference and 'on' != $request->unpublished and $request->level != -100)
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

    //get first match of key in array of arrays
    public static function filterSearchData($arrays,$key) {
        $found = null;
        foreach($arrays as $values) {
            $value = isset($values[$key]) ? $values[$key] : null;
            if (isset($value)) {
                $found = $value;
                break;
            }
        }
        return $found;
    }

    public function checkapis(Request $request)
    {
        if (!isset($request->name)) {
            return Response::json(['error' => Lang::get('messages.name_error')]);
        }
        //check first GBIF which may have already tropicos and ipni

        $name = ucfirst(mb_strtolower($request->name));

        $apis = new ExternalAPIs();
        $gbifsearch = $apis->getGBIF($name);

        $finaldata = [];

        if (!is_null($gbifsearch)) {
          $gbif_record = $gbifsearch['gbif_record'];
          $gbif_senior = $gbifsearch['gbif_senior'];
          $externalkeys = $gbifsearch['keys'];
          $finaldata = [
              "name" => $gbif_record["canonicalName"],
              "rank" => isset($gbif_record["rank"]) ? mb_strtolower($gbif_record['rank']) : null,
              "author" => isset($gbif_record["authorship"]) ? $gbif_record['authorship'] : null,
              "valid" => isset($gbif_record["taxonomicStatus"]) ? $gbif_record['taxonomicStatus'] : null,
              "reference" => isset($gbif_record["publishedIn"]) ? $gbif_record['publishedIn'] : null,
              "parent" => isset($gbif_record["parent"]) ? $gbif_record['parent'] : null,
              "senior" => isset($gbif_senior) ? $gbif_senior['canonicalName'] : null,
              "mobot" => isset($externalkeys["tropicos"]) ? $externalkeys['tropicos'] : null,
              "ipni" => isset($externalkeys["ipni"]) ? $externalkeys['ipni'] : null,
              'mycobank' => null,
              'gbif' => isset($gbif_record["nubKey"]) ? $gbif_record['nubKey'] : null,
              'zoobank' => null,
          ];
          $name = $gbif_record["scientificName"];
        }
        $mobotdata = null;
        $ipnidata = null;
        if (count($finaldata)==0 or is_null($finaldata['mobot'])) {
            $mobotdata = $apis->getMobot($name );
            if (count($finaldata)>0 and $mobotdata[0]!=ExternalAPIs::NOT_FOUND) {
              $finaldata['mobot'] = $mobotdata['key'];
            }
        }

        // // TODO: IPNI has changed and will implement an API (capture by gbif)
        /*
        if (count($finaldata)==0 or is_null($finaldata['ipni'])) {
            $ipnidata = $apis->getIpni($request->name);
            if (count($finaldata)>0 and (null !== $ipnidata)) {
              $finaldata['ipni'] = $ipnidata['key'];
            }
        }
        */
        if (!isset($ipnidata)) {
          $ipnidata = [ExternalAPIs::NOT_FOUND];
        }
        if (!isset($mobotdata))  {
          $mobotdata = [ExternalAPIs::NOT_FOUND];
        }

        // WARNING: MYCOBANK API OUT OF SERVICE 18-11-2020
            /*
            if ($mobotdata[0] == ExternalAPIs::NOT_FOUND  and $ipnidata[0] == ExternalAPIs::NOT_FOUND) {
              $mycobankdata = $apis->getMycobank($request->name);
            } else {

            }
            */
        $mycobankdata = [ExternalAPIs::NOT_FOUND];

        //zoobank only if not found a plant or fungi
        $zoobankdata = [ExternalAPIs::NOT_FOUND];

        if ($mobotdata[0] == ExternalAPIs::NOT_FOUND and $ipnidata[0] == ExternalAPIs::NOT_FOUND and $mycobankdata[0] == ExternalAPIs::NOT_FOUND) {
          $zoobankdata = $apis->getZOOBANK($name);
          if (is_null($zoobankdata)) {
            $zoobankdata = [ExternalAPIs::NOT_FOUND];
          } elseif (count($finaldata)>0) {
            $finaldata['zoobank'] = isset($zoobankdata['key'])  ? $zoobankdata['key'] : null;
          }
        }



        //assemble data if does not exists (i.e. not found on GBIF)
        if (count($finaldata)==0) {
            $alldata = ['mobot' => $mobotdata,'ipni' => $ipnidata,'mycobank' => $mycobankdata, 'gbif' => [], 'zoobank' => $zoobankdata];
            $keystoget = ["rank","author","valid","reference","parent","senior"];
            foreach($keystoget as $key) {
                $value = self::filterSearchData($alldata,$key);
                if ($value) {
                  $finaldata[$key] = $value;
                }
            }
            //get external ids if they exist
            if (count($finaldata)>0) {
              foreach($alldata as $name => $apidata) {
                $finaldata[$name] = isset($apidata['key']) ? $apidata['key'] : null;
              }
            }
        }

        if (count($finaldata)==0) {
            return Response::json(['error' => Lang::get('messages.apis_not_found_or_multiple_hits')]);
        }

        $bag = new MessageBag();

        $finaldata['rank'] = Taxon::getRank($finaldata['rank']);
        $finaldata['valid'] = in_array($finaldata['valid'],['Legitimate','nom. cons.','No opinion','ACCEPTED']);

        //is parent registered?
        $parent = $finaldata['parent'];
        if (!is_null($parent)) {

            $finalparent = $finaldata['parent'];

            //gbif may report accepted taxon as parent for a synonym
            $nwords = explode(" ",$name);
            if (count($nwords)>1 and $parent != null) {
              $pattern = "/".$parent."/i";
              $is_parent = preg_match($pattern, $name);
              //if it is not the parent in this cases get correct parent
              if (!$is_parent) {
                if (count($nwords)>=3) {
                  $finalparent = $nwords[0]." ".$nwords[1];
                } else {
                  $finalparent = $nwords[0];
                }
              }
            }

            if (is_numeric($parent)) {
                $finalparent = null;
                $hasParent = Taxon::where('id',$parent)->get();
            } else {
                $hasParent = Taxon::whereRaw('odb_txname(name, level, parent_id) = ?', [$finalparent])->get();
            }
            if ($hasParent->count()==1) {
                $finaldata['parent'] =  [$hasParent->first()->id, $hasParent->first()->fullname];
            } else {
                $parent_id = null;
                //should import parent path?
                if ($request->importparents==1) {
                    $gbifsearch = $apis->getGBIF($finalparent);
                    if (!is_null($gbifsearch)) {
                        $gbifkey = isset($gbifsearch['gbif_record']['nubKey']) ? $gbifsearch['gbif_record']['nubKey'] : $gbifsearch['gbif_record']['key'] ;
                      if (null != $gbifkey) {
                          $related_data = ExternalAPIs::getGBIFParentPathData($gbifkey,$include_first=true);
                          $parent_id = self::importParents($related_data);
                      } else {
                        $related_data = ExternalAPIs::getMobotParentPath($finalparent,$include_first=true);
                        if (count($related_data)>0) {
                          $parent_id = self::importParents($related_data);
                        } else {
                          $bag->add('e1', Lang::get('messages.parent_not_registered', ['name' => $finalparent]));
                        }
                      }
                    } else {
                      $bag->add('e1', Lang::get('messages.parent_not_registered', ['name' => $finalparent]));
                    }
                } else {
                  $bag->add('e1', Lang::get('messages.parent_not_registered', ['name' => $finalparent]));
                }
                $finaldata['parent'] = [$parent_id,$finalparent];
            }
        }
        //if is a synonym - check if accepted is registered
        $senior = $finaldata['senior'];
        if (!is_null($senior)) {
            $finalsenior = $finaldata['senior'];
            if (is_numeric($senior)) {
                $hasSenior = Taxon::where('id',$senior)->get();
                $finalsenior = null;
            } else {
                $hasSenior = Taxon::whereRaw('odb_txname(name, level, parent_id) = ?', [$senior])->get();
            }
            if ($hasSenior->count()==1) {
                $finaldata['senior'] =  [$hasSenior->first()->id, $hasSenior->first()->fullname];
            } else {
                /* need to store senior and return relationship */
                $senior_id = null;
                //should import parent path?
                if ($request->importparents==1) {
                    $gbifsearch = $apis->getGBIF($finalsenior);
                    if (!is_null($gbifsearch)) {
                      $gbifkey = isset($gbifsearch['gbif_record']['nubKey']) ? $gbifsearch['gbif_record']['nubKey'] : $gbifsearch['gbif_record']['key'] ;
                      if (null != $gbifkey) {
                          $related_data = ExternalAPIs::getGBIFParentPathData($gbifkey,$include_first=true);
                          $senior_id = self::importParents($related_data);
                      } else {
                        $related_data = ExternalAPIs::getMobotParentPath($finalsenior,$include_first=true);
                        if (count($related_data)>0) {
                          $senior_id = self::importParents($related_data);
                        } else {
                          $bag->add('e2', Lang::get('messages.senior_not_registered', ['name' => $finalsenior]));
                        }
                      }
                    } else {
                      $bag->add('e2', Lang::get('messages.senior_not_registered', ['name' => $finalsenior]));
                    }
                } else {
                  $bag->add('e2', Lang::get('messages.senior_not_registered', ['name' => $finalsenior]));
                }
                $finaldata['senior'] =[$senior_id,$finalsenior];
            }
        }

        return Response::json(['bag' => $bag,'apidata' => $finaldata]);
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
        $valid_ext = array("csv","ods",'xlsx');
        $ext = mb_strtolower($request->file('data_file')->getClientOriginalExtension());
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


    /* similar to function in ImportTaxons, may merge */
    public static function importParents($parents_array)
    {
      $previous_id = null;
      foreach($parents_array as $related) {
            if ($related['parent_id']) {
              $previous_id = $related['parent_id'];
            } else {
              $parent = $related['parent'];
              if ($parent) {
                $hadtaxon = Taxon::whereRaw('odb_txname(name, level, parent_id) = ?', [$parent]);
                if ($hadtaxon->count()) {
                  $previous_id = $hadtaxon->first()->id;
                }
              }
            }
            $values = [
                'level' => $related['rank'],
                'parent_id' => $previous_id,
                'valid' => $related['valid'],
                'author' => $related['author'],
                'bibreference' => $related['reference'],
            ];
            $newtaxon = new Taxon($values);
            $newtaxon->fullname = $related['name'];
            $newtaxon->save();
            if (isset($related['mobot'])) {
              $newtaxon->setapikey('Mobot', $related['mobot']);
            }
            if (isset($related['ipni'])) {
              $newtaxon->setapikey('IPNI', $related['ipni']);
            }
            if (isset($related['gbif'])) {
              $newtaxon->setapikey('GBIF', $related['gbif']);
            }
            if (isset($related['zoobank'])) {
              $newtaxon->setapikey('ZOOBANK', $related['zoobank']);
            }
            if (isset($related['mycobank'])) {
              $newtaxon->setapikey('Mycobank', $related['zoobank']);
            }
            $newtaxon->save();
            $previous_id = $newtaxon->id;
    }
    return $previous_id;
   }



}
