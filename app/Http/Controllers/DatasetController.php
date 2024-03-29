<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;
use Illuminate\Support\Facades\Storage;
use App\DataTables\DatasetsDataTable;
use App\Jobs\DownloadDataset;
use App\Models\Tag;
use App\Models\BibReference;
use App\Models\Dataset;
use App\Models\Measurement;
use App\Models\User;
use App\Models\Collector;
use App\Models\Project;
use App\Models\Person;
use DB;
use Auth;
use Lang;
use Gate;
use Mail;
use App\Models\UserJob;
use Log;
use Validator;

use Activity;
use App\Models\ActivityFunctions;
use App\DataTables\ActivityDataTable;


class DatasetController extends Controller
{

    // Functions for autocompleting project
    // filter by those the user is an admin or collabs
    public function autocomplete(Request $request)
    {
        $datasets = Auth::user()->editableDatasets()->where('datasets.name', 'LIKE', ['%'.$request->input('query').'%'])
            ->selectRaw('datasets.id as data, datasets.name as value')
            ->orderBy('datasets.name', 'ASC')
            ->get();
        return Response::json(['suggestions' => $datasets]);
    }



    /**
     * Display a listing of the resource.download_dispatched
     *
     * @return \Illuminate\Http\Response
     */
    public function index(DatasetsDataTable $dataTable)
    {
        $mydatasets = null;
        if (Auth::user() and Auth::user()->datasets()->count()) {
            $mydatasets = Auth::user()->datasets;
        }

        return $dataTable->render('datasets.index', compact('mydatasets'));
    }

    public function indexProjects($id, DatasetsDataTable $dataTable)
    {

        $object = Project::findOrFail($id);
        $mydatasets = null;
        return $dataTable->with([
            'project' => $id,
        ])->render('datasets.index', compact('object','mydatasets'));
    }


    public function indexTags($id,DatasetsDataTable $dataTable)
    {
        $object = Tag::findOrFail($id);
        $mydatasets = null;
        return $dataTable->with('tag', $id)->render('datasets.index', compact('object','mydatasets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $fullusers = User::where('access_level', '=', User::USER)->orWhere('access_level', '=', User::ADMIN)->get();
        $allusers = User::all();
        $tags = Tag::all();
        $persons = Person::all();
        $references = BibReference::select('*',DB::raw('odb_bibkey(bibtex) as bibkey'))->get();

        return view('datasets.create', compact('fullusers', 'allusers', 'tags', 'references','persons'));
    }



    /**
     * Validate dataset request
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return Validator
     */
    public function customValidate(Request $request, $id = null)
    {
        /* define rules */
        $fullusers = User::where('access_level', '=', User::USER)
        ->orWhere('access_level', '=', User::ADMIN)->get()->pluck('id');
        $fullusers = implode(',', $fullusers->all());
        $licenses = implode(',',config('app.creativecommons_licenses'));
        $rules = [
            'name' => 'required|string|max:50',
            'privacy' => 'required|integer',
            'admins' => 'required_unless:privacy,'.Dataset::PRIVACY_PROJECT.'|array|min:1',
            'admins.*' => 'numeric|in:'.$fullusers,
            'collabs' => 'nullable|array',
            'collabs.*' => 'numeric|in:'.$fullusers,
            'title' => 'nullable|string|max:191|required_if:privacy,'.Dataset::PRIVACY_REGISTERED.','.Dataset::PRIVACY_PUBLIC,
            'license' => 'nullable|string|max:191|in:'.$licenses.'|required_if:privacy,'.Dataset::PRIVACY_REGISTERED.','.Dataset::PRIVACY_PUBLIC,
            'project_id' => 'nullable|integer|required_if:privacy,'.Dataset::PRIVACY_PROJECT,
            'description' => 'nullable|string|max:500',
            'authors' => 'nullable|array'
        ];
        $validator = Validator::make($request->all(), $rules);

        /*check for duplicated entries */
        $validator->after(function ($validator) use ($request,  $id) {
            $query = "datasets.name like '".$request->name."'";
            if ($request->title) {
                $query = "(".$query."  OR title like '".$request->title."')";
            }
            if (!is_null($id)) {
              $query .= " AND datasets.id<>".$id;
            }
            $has_similar = Dataset::whereRaw($query)->cursor();
            if ($has_similar->count() > 0) {
              $validator->errors()->add('name', Lang::get('messages.dataset_name_or_title_duplicated'));
              return;
            }
        });

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
        $this->authorize('create', Dataset::class);
        $validator = $this->customValidate($request);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        //add version to license field
        $newlicense = null;
        if (isset($request->license)) {
          $version = isset($request->license_version) ? (string) $request->license_version : config('app.creativecommons_version')[0];
          $newlicense = $request->license." ".$version;
        }
        //store the new dataset record
        $data = $request->only(['name', 'description', 'privacy', 'policy','metadata','title','license','project_id']);
        $data['license'] = $newlicense;
        $dataset = Dataset::create($data);
        //if not a project dataset, set users
        if ($request->privacy != Dataset::PRIVACY_PROJECT) {
            $dataset->setusers($request->viewers, $request->collabs, $request->admins);
        }
        //link any informed keywords
        $dataset->tags()->attach($request->tags);

        /*mandatory references */
        $references = [];
        if (is_array($request->references)) {
          foreach($request->references as $bib_reference_id) {
              $references[] = [
                'bib_reference_id' => $bib_reference_id,
                'mandatory' => 1
              ];
          }
        }
        /* aditional references */
        if (is_array($request->references_aditional)) {
          foreach($request->references_aditional as $bib_reference_id) {
            if (!in_array($bib_reference_id,$request->references)) {
              $references[] = [
                'bib_reference_id' => $bib_reference_id,
                'mandatory' => 0
              ];
            }
          }
        }
        if (count($references)>0) {
          $dataset->references()->attach($references);
        }

        //authors
        if (isset($request->authors) and is_array($request->authors)) {
          $first = true;
          foreach ($request->authors as $author) {
              $theauthor = new Collector(['person_id' => $author]);
              if ($first) {
                  $theauthor->main = 1;
              }
              $dataset->authors()->save($theauthor);
              $first = false;
          }
        }
        return redirect('datasets/'.$dataset->id)->withStatus(Lang::get('messages.stored'));
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
        $dataset = Dataset::findOrFail($id);

        //with(['measurements.measured', 'measurements.odbtrait'])->
        //Summarize data set (only direct links are reported)
        return view('datasets.show', compact('dataset'));
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
        $tags = Tag::all();
        $references = BibReference::select('*',DB::raw('odb_bibkey(bibtex) as bibkey'))->get();
        $dataset = Dataset::findOrFail($id);
        $fullusers = User::where('access_level', '=', User::USER)->orWhere('access_level', '=', User::ADMIN)->get();
        $allusers = User::all();
        $persons = Person::all();

        return view('datasets.create', compact('dataset', 'fullusers', 'allusers', 'tags', 'references','persons'));
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
        $dataset = Dataset::findOrFail($id);
        $this->authorize('update', $dataset);
        $validator = $this->customValidate($request,$id);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        //add version to license field
        $newlicense = null;
        if (isset($request->license)) {
          $version = isset($request->license_version) ? (string) $request->license_version : config('app.creativecommons_version')[0];
          $newlicense = $request->license." ".$version;
        }
        $data = $request->only(['name', 'description', 'privacy', 'policy','metadata','title','license','project_id']);
        $data['license'] = $newlicense;
        $dataset->update($data);
        $dataset->setusers($request->viewers, $request->collabs, $request->admins);
        $dataset->tags()->sync($request->tags);

        /*mandatory references */
        $references = array();
        if (is_array($request->references)) {
          foreach($request->references as $bib_reference_id) {
              $references[] = array(
                'bib_reference_id' => $bib_reference_id,
                'mandatory' => 1
              );
          }
        }
        if (is_array($request->references_aditional)) {
          foreach($request->references_aditional as $bib_reference_id) {
            if (!in_array($bib_reference_id,$request->references)) {
              $references[] = array(
                'bib_reference_id' => $bib_reference_id,
                'mandatory' => 0
              );
            }
          }
        }
        $dataset->references()->detach();
        if (count($references)>0) {
          $dataset->references()->sync($references);
        }

        //did authors changed?
        $current = $dataset->authors->pluck('person_id');
        $detach = $current->diff($request->authors)->all();
        $attach = collect($request->authors)->diff($current)->all();
        if (count($detach) or count($attach)) {
            //delete old authors
            $dataset->authors()->delete();
            //save authors and identify first author
            $first = true;
            foreach ($request->authors as $author) {
                $theauthor = new Collector(['person_id' => $author]);
                if ($first) {
                    $theauthor->main = 1;
                }
                $dataset->authors()->save($theauthor);
                $first = false;
            }
        }
        //log authors changed if any
        ActivityFunctions::logCustomPivotChanges($dataset,$current->all(),$request->authors,'dataset','authors updated',$pivotkey='person');

        return redirect('datasets/'.$id)->withStatus(Lang::get('messages.saved'));
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


    public function activity($id, ActivityDataTable $dataTable)
    {
        $object = Dataset::findOrFail($id);
        return $dataTable->with('dataset', $id)->render('common.activity',compact('object'));
    }


    /* DOWNLOAD AND REQUEST DATASET FUNCTIONS */
    public function datasetRequestForm($id)
    {
        $dataset = Dataset::findOrFail($id);
        //if (Auth::user()) {
          return view('datasets.export', compact('dataset'));
        //} else {
          //return redirect('login')->withStatus(Lang::get('messages.dataset_request_nouser'));
        //}
    }

    //send email to user
    public function sendEmail($id,Request $request)
    {
        if (!Auth::user() and !isset($request->email)) {
          $msg = Lang::get('messages.email_mandatory');
          return redirect('datasets/'.$id)->withStatus($msg);
        }

        $dataset = Dataset::findOrFail($id);

        //send to the first dataset admin with cc to rest
        $admins = $dataset->admins()->pluck('email')->toArray();
        $person = $dataset->admins()->first()->person;
        $to_email = $admins[0];
        if (isset($person)) {
            $to_name = $person->full_name;
        } else {
            $to_name = $to_email;
        }
        //with copy cc to requester and other admins
        if (Auth::user()) {
          $from_email =  Auth::user()->email;
          if (isset(Auth::user()->person)) {
            $from_name= Auth::user()->person->fullname;
          } else {
            $from_name = Auth::user()->email;
          }
        } else {
          $from_email = $request->email;
          $from_name = $request->email;
        }
        $admins[0] = $from_email;
        $cc_email = $admins;


        //prep de content html to send as the email text
        $content = Lang::get('messages.dataset_request_to_admins')."  <strong>".$dataset->name."</strong> ".Lang::get('messages.from')."  <strong>".
              htmlentities($from_name)
        ."</strong> ".Lang::get('messages.from')." <a href='".env('APP_URL')."'>".htmlentities(env('APP_URL'))."</a>.";
        $content .= "<hr><strong>".Lang::get('messages.dataset_request_use')."</strong>: ".$request->dataset_use_type;
        $content .= "<br>";
        $content .= "<strong>".Lang::get('messages.description')."</strong><br>".$request->dataset_use_description;
        $content .= "<br><hr>";
        if (isset($dataset->license)) {
          $content .= "<strong>".Lang::get('messages.license')."</strong><br>".$dataset->license;
        }
        if (isset($dataset->policy)) {
          $content .= "<br><strong>".Lang::get('messages.data_policy')."</strong><br>".$dataset->policy;
        }
        if ($dataset->references->where('mandatory',1)->count()) {
         $content .= "<br><br><strong>".Lang::get('messages.dataset_bibreferences_mandatory')."</strong><ul>";
         foreach($dataset->references->where('mandatory',1)  as $reference) {
          $content .= "<li>".htmlentities($reference->first_author.". ".$reference->year." ".$reference->title.". ".$reference->doi)."</li>";
          }
          $content .= "</ul>";
        }
        /*
        $content .= "<strong>".$from_name."</strong> ".Lang::get('messages.dataset_request_agreed_text').":<ul>";
        foreach($request->dataset_agreement as $value) {
          $content .=  "<li>".$value."</li>";
        }
        $content .= "</ul><hr>";
        */
        $content .= "</ul><br><br>**".Lang::get('messages.no_reply_email')."**<br>";
        $subject = Lang::get('messages.dataset_request').' - '.$dataset->name.' - '.env('APP_NAME');
        $data = array(
          'to_name' => $to_name,
          'content' => $content
        );
        //send email
        try {
          Mail::send('common.email', $data, function($message) use ($to_name, $to_email, $subject,$cc_email) {
              $message->to($to_email, $to_name)->cc($cc_email)->subject($subject);
          });
        } catch (\Exception $e) {
          $msg = Lang::get('messages.error_sending_email');
          return redirect('datasets/'.$id)->withStatus($msg);
        }



        //log dataset request for tracking use history
        /*
        $logName  = 'dataset_requests';
        $tolog = [
            'attributes' => [
              'dataset_id' => $dataset->id,
              'user_id' => Auth::user()->id,
              'dataset_request_use' => $request->dataset_use_type,
              'description' => $request->dataset_use_description,
              'dataset_request_agreement' => $request->dataset_agreement,
            ],
            'old' => NULL
        ];
        activity($logName)
          ->performedOn($dataset)
          ->withProperties($tolog)
          ->log('Dataset request');
        */

        //return to view with message
        $msg = Lang::get('messages.dataset_request_email_sent');
        return redirect('datasets/'.$id)->withStatus($msg);



    }


    public function prepDownloadFile($id)
    {
      $dt = Dataset::findOrFail($id);
      if (!Gate::denies('export', $dt)) {
      //$this->authorize('export',Auth::user(),$dataset);
        UserJob::dispatch(DownloadDataset::class,
          [
          'data' => ['data' => array('id' => $id)]
          ]
        );
        $msg = Lang::get('messages.download_dispatched');
      } else {
        $msg = Lang::get('messages.no_permission');
      }
      return redirect('datasets/'.$id)->withStatus($msg);
    }


    public function summarize_identifications($id)
    {
      $dataset = Dataset::findOrFail($id);
      $identification_summary = $dataset->identification_summary();
      $taxonomic_summary = $dataset->taxonomic_summary();
      $html = view("datasets.taxoninfo",compact('dataset','identification_summary','taxonomic_summary'))->render();
      return $html;
    }

    public function summarize_contents($id)
    {
      $dataset = Dataset::findOrFail($id);
      $trait_summary = $dataset->traits_summary();
      $data_included = $dataset->data_included();
      $plot_included = $dataset->plot_included();
      $html = view("datasets.summary",compact('dataset','trait_summary','data_included','plot_included'))->render();
      return $html;
    }



}
