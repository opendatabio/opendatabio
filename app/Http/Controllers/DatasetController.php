<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\DataTables\DatasetsDataTable;
use App\DataTables\ActivityDataTable;
use App\Jobs\DownloadDataset;
use App\Tag;
use App\BibReference;
use App\Dataset;
use App\Measurement;
use App\User;
use App\Project;
use Activity;
use DB;
use Auth;
use Lang;
use Gate;
use Mail;
use App\UserJob;
use Log;


class DatasetController extends Controller
{

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
        $references = BibReference::select('*',DB::raw('odb_bibkey(bibtex) as bibkey'))->get();

        return view('datasets.create', compact('fullusers', 'allusers', 'tags', 'references'));
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
        $fullusers = User::where('access_level', '=', User::USER)
            ->orWhere('access_level', '=', User::ADMIN)->get()->pluck('id');
        $fullusers = implode(',', $fullusers->all());
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'privacy' => 'required|integer',
            'admins' => 'required|array|min:1',
            'admins.*' => 'numeric|in:'.$fullusers,
            'collabs' => 'nullable|array',
            'collabs.*' => 'numeric|in:'.$fullusers,
        ]);
        $dataset = Dataset::create($request->only(['name', 'description', 'privacy', 'bibreference_id','policy','metadata']));
        $dataset->setusers($request->viewers, $request->collabs, $request->admins);
        $dataset->tags()->attach($request->tags);

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
        /* aditional references */
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
        if (count($references)>0) {
          $dataset->references()->attach($references);
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
        $trait_summary = $dataset->traits_summary();
        return view('datasets.show', compact('dataset','trait_summary'));
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

        return view('datasets.create', compact('dataset', 'fullusers', 'allusers', 'tags', 'references'));
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
        $fullusers = User::where('access_level', '=', User::USER)
            ->orWhere('access_level', '=', User::ADMIN)->get()->pluck('id');
        $fullusers = implode(',', $fullusers->all());
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'privacy' => 'required|integer',
            'admins' => 'required|array|min:1',
            'admins.*' => 'numeric|in:'.$fullusers,
            'collabs' => 'nullable|array',
            'collabs.*' => 'numeric|in:'.$fullusers,
        ]);
        $dataset->update($request->only(['name', 'description', 'privacy', 'bibreference_id','policy','metadata']));
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
        if (Auth::user()) {
          return view('datasets.export', compact('dataset'));
        } else {
          return redirect('login')->withStatus(Lang::get('messages.dataset_request_nouser'));
        }
    }

    //send email to user
    public function sendEmail($id,Request $request)
    {
        $dataset = Dataset::findOrFail($id);

        //send to the first dataset admin with cc to rest
        $admins = $dataset->admins()->get()->pluck('email')->toArray();
        $to_email = $admins[0];
        if (isset($dataset->admins()->get()->first()->person->fullname)) {
            $to_name = $dataset->admins()->get()->first()->person->full_name;
        } else {
            $to_name = $to_email;
        }
        //with copy cc to requester and other admins
        $admins[0] = Auth::user()->email;
        $cc_email = $admins;
        if (isset(Auth::user()->person->fullname)) {
          $from_name= Auth::user()->person->fullname;
        } else {
          $from_name = Auth::user()->email;
        }

        //prep de content html to send as the email text
        $content = Lang::get('messages.dataset_request_to_admins')."  <strong>".$dataset->name."</strong> ".Lang::get('messages.from')."  <strong>".
              htmlentities($from_name)
        ."</strong> ".Lang::get('messages.from')." <a href='".env('APP_URL')."'>".htmlentities(env('APP_URL'))."</a>.";
        $content .= "<hr><strong>".Lang::get('messages.dataset_request_use')."</strong>: ".$request->dataset_use_type;
        $content .= "<br>";
        $content .= "<strong>".Lang::get('messages.description')."</strong><br>".$request->dataset_use_description;
        $content .= "<br><hr>";
        if (isset($dataset->policy)) {
          $content .= "<strong>".Lang::get('messages.dataset_policy')."</strong><br>".$dataset->policy;
        }
        if ($dataset->references->where('mandatory',1)->count()) {
         $content .= "<br><br><strong>".Lang::get('messages.dataset_bibreferences_mandatory')."</strong><ul>";
         foreach($dataset->references->where('mandatory',1)  as $reference) {
          $content .= "<li>".htmlentities($reference->first_author.". ".$reference->year." ".$reference->title.". ".$reference->doi)."</li>";
          }
          $content .= "</ul>";
        }
        $content .= "<strong>".$from_name."</strong> ".Lang::get('messages.dataset_request_agreed_text').":<ul>";
        foreach($request->dataset_agreement as $value) {
          $content .=  "<li>".$value."</li>";
        }
        $content .= "</ul><hr>";
        $content .= "</ul><br><br>**".Lang::get('messages.no_reply_email')."**<br>";
        $subject = Lang::get('messages.dataset_request').' - '.$dataset->name.' - '.env('APP_NAME');
        $data = array(
          'to_name' => $to_name,
          'content' => $content
        );
        //send email
        Mail::send('common.email', $data, function($message) use ($to_name, $to_email, $subject,$cc_email) {
            $message->to($to_email, $to_name)->cc($cc_email)->subject($subject);
        });

        //log dataset request for tracking use history
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
      $html = view("datasets.taxoninfo",compact('dataset'))->render();
      return $html;
    }

}
