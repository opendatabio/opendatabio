<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\DataTables\ProjectsDataTable;
use App\Models\Project;
use App\Models\Dataset;
use App\Models\User;
use App\Models\Collector;
use Auth;
use Lang;
use App\Models\Tag;
use App\Models\Person;
use Mail;
use App\Models\UserJob;

use Activity;
use App\Models\ActivityFunctions;
use App\DataTables\ActivityDataTable;



class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ProjectsDataTable $dataTable)
    {
        $myprojects = null;
        if (Auth::user() and Auth::user()->projects()->count()) {
            $myprojects = Auth::user()->projects;
        }

        return $dataTable->render('projects.index', compact('myprojects'));
    }

    public function indexTags($id,ProjectsDataTable $dataTable)
    {
        $myprojects = null;
        $object = Tag::findOrFail($id);
        return $dataTable->with('tag', $id)->render('projects.index', compact('object','myprojects'));
    }

    public function indexDatasets($id,ProjectsDataTable $dataTable)
    {
        $myprojects = null;
        $object = Dataset::findOrFail($id);
        return $dataTable->with('dataset', $id)->render('projects.index', compact('object','myprojects'));
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
        $persons=Person::all();

        return view('projects.create', compact('fullusers', 'allusers','tags','persons'));
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


        $message = "";
        $this->authorize('create', Project::class);
        $fullusers = User::where('access_level', '=', User::USER)
            ->orWhere('access_level', '=', User::ADMIN)->get()->pluck('id');
        $fullusers = implode(',', $fullusers->all());
        $licenses = implode(',',config('app.creativecommons_licenses'));
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'privacy' => 'required|integer',
            'admins' => 'required|array|min:1',
            'admins.*' => 'numeric|in:'.$fullusers,
            'collabs' => 'nullable|array',
            'collabs.*' => 'numeric|in:'.$fullusers,
            'url' => 'nullable|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
            'title' => 'nullable|string|max:191',
            'license' => 'nullable|string|max:191|in:'.$licenses,
        ]);
        //add version to license field
        $license = null;
        if (isset($request->license)) {
          if ($request->license != "CC0") {
              //THEN TITLE AND AUTHORS ARE required
              if (!isset($request->title) or !isset($request->authors)) {
                $message .= Lang::get('messages.missing_project_title_author');
                return redirect('projects/'.$project->id)->withStatus(Lang::get('messages.stored')." ".$message);
              }
          }
          $version = (null != $request->license_version) ? (string) $request->license_version : config('app.creativecommons_version')[0];
          $license = $request->license." ".$version;
        }
        if (filter_var($request->url, FILTER_VALIDATE_URL) !== false) {
          $request->url = null;
          $message .= Lang::get('messages.invalid_url');
        }
        $data = $request->only(['name', 'description', 'privacy','details', 'url','license','title','policy']);
        $data['license'] = $license;
        $project = new Project($data);
        $project->save(); // needed to generate an id?
        $project->setusers($request->viewers, $request->collabs, $request->admins);
        $project->tags()->attach($request->tags);

        /* store logo if exists */
        $valid_ext = array("PNG","png","GIF","gif","jpg",'jpeg',"JPG","JPEG");
        if ($request->hasFile('logo')) {
          $logopath = $request->file('logo')->getRealPath();
          $ext = $request->file('logo')->getClientOriginalExtension();
          if (!in_array($ext,$valid_ext)) {
            $message .= Lang::get('messages.invalid_image_extension');
          }
          try {
              $project->saveLogo($logopath);
            } catch (\Intervention\Image\Exception\NotReadableException $e) {
              $message .= " ". Lang::get('messages.invalid_image');
            }
        }

        //authors
        if ($request->authors) {
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
        return redirect('projects/'.$project->id)->withStatus(Lang::get('messages.stored')." ".$message);
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
        $project = Project::findOrFail($id);

        $logo_file = 'upload_pictures/project_'.$project->id."_logo.jpg";
        $logo = null;
        if (file_exists(public_path($logo_file))) {
          $logo = $logo_file;
       }
       return view('projects.show', compact('project','logo'));
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
        $project = Project::findOrFail($id);
        $fullusers = User::where('access_level', '=', User::USER)->orWhere('access_level', '=', User::ADMIN)->get();
        $allusers = User::all();
        $tags = Tag::all();
        $logo_file = 'upload_pictures/project_'.$project->id."_logo.jpg";
        $logo = null;
        if (file_exists(public_path($logo_file))) {
          $logo = $logo_file;
        }
        $persons=Person::all();
        return view('projects.create', compact('project', 'fullusers', 'allusers','tags','logo','persons'));
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



        $project = Project::findOrFail($id);
        $this->authorize('update', $project);
        $fullusers = User::where('access_level', '=', User::USER)
            ->orWhere('access_level', '=', User::ADMIN)->get()->pluck('id');
        $fullusers = implode(',', $fullusers->all());
        $message = "";
        $licenses = implode(',',config('app.creativecommons_licenses'));
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'privacy' => 'required|integer',
            'admins' => 'required|array|min:1',
            'admins.*' => 'numeric|in:'.$fullusers,
            'collabs' => 'nullable|array',
            'collabs.*' => 'numeric|in:'.$fullusers,
            'title' => 'nullable|string|max:191',
            'license' => 'nullable|string|max:191|in:'.$licenses,
        ]);
        /*
        if (filter_var($request->url, FILTER_VALIDATE_URL) !== false) {
          $request->url = null;
          $message .= Lang::get('messages.invalid_url');
        }
        */
        //add version to license field
        $license = null;
        if (isset($request->license)) {
          if ($request->license != "CC0") {
              //THEN TITLE AND AUTHORS ARE required
              if (!isset($request->title) or !isset($request->authors)) {
                $message .= Lang::get('messages.missing_project_title_author');
                return redirect('projects/'.$id)->withStatus(Lang::get('messages.stored')." ".$message);
              }
          }
          $version = isset($request->license_version) ? (string) $request->license_version : config('app.creativecommons_version')[0];
          $license = $request->license." ".$version;
        }
        $data = $request->only(['name', 'description', 'privacy','details','url','title','license','policy']);
        $data['license'] = $license;
        $project->update($data);
        $project->setusers($request->viewers, $request->collabs, $request->admins);
        $project->tags()->sync($request->tags);

        /* store logo if exists */
        $valid_ext = array("PNG","png","GIF","gif","jpg",'jpeg',"JPG","JPEG");

        if ($request->hasFile('logo')) {
          $logopath = $request->file('logo')->getRealPath();
          $ext = $request->file('logo')->getClientOriginalExtension();
          if (!in_array($ext,$valid_ext)) {
            $message .= Lang::get('messages.invalid_image_extension');
          } else {
          try {
              $project->saveLogo($logopath);
            } catch (\Intervention\Image\Exception\NotReadableException $e) {
              $message .= " ". Lang::get('messages.invalid_image');
            }
          }
        }

        //did authors changed?
        if ($request->authors) {
          $current = $project->authors->pluck('person_id');
          $detach = $current->diff($request->authors)->all();
          $attach = collect($request->authors)->diff($current)->all();
          if (count($detach) or count($attach)) {
              //delete old authors
              $project->authors()->delete();
              //save authors and identify first author
              $newauthors = [];
              if ($request->authors) {
                $newauthors = $request->authors;
                $first = true;
                foreach ($request->authors as $author) {
                    $theauthor = new Collector(['person_id' => $author]);
                    if ($first) {
                      $theauthor->main = 1;
                    }
                    $project->authors()->save($theauthor);
                    $first = false;
                  }
              }
              //log authors changed if any
              ActivityFunctions::logCustomPivotChanges($project,$current->all(),$newauthors,'project','authors updated',$pivotkey='person');
          }
        }
        return redirect('projects/'.$id)->withStatus(Lang::get('messages.saved')." ".$message);
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


    public function summarize_project($id)
    {
      $project = Project::findOrFail($id);
      $html = view("projects.summary",compact('project'))->render();
      return $html;
    }

    public function activity($id, ActivityDataTable $dataTable)
    {
        $object = Project::findOrFail($id);
        return $dataTable->with('project', $id)->render('common.activity',compact('object'));
    }

    public function summarize_identifications($id)
    {
      $project = Project::findOrFail($id);
      $html = view("projects.taxoninfo",compact('project'))->render();
      return $html;
    }

    /* DOWNLOAD AND REQUEST DATASET FUNCTIONS */
    public function projectRequestForm($id)
    {
        $project = Project::findOrFail($id);
        return view('projects.export', compact('project'));
    }

    //send email to user
    public function sendEmail($id,Request $request)
    {
        if (!Auth::user() and !isset($request->email)) {
          $msg = Lang::get('messages.email_mandatory');
          return redirect('projects/'.$id)->withStatus($msg);
        }

        $project = Project::findOrFail($id);

        //send to the first dataset admin with cc to rest
        $admins = $project->admins()->pluck('email')->toArray();
        $person = $project->admins()->first()->person;
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
        $content = Lang::get('messages.dataset_request_to_admins')."  <strong>".$project->name."</strong> ".Lang::get('messages.from')."  <strong>".
              htmlentities($from_name)
        ."</strong> ".Lang::get('messages.from')." <a href='".env('APP_URL')."'>".htmlentities(env('APP_URL'))."</a>.";
        $content .= "<hr><strong>".Lang::get('messages.dataset_request_use')."</strong>: ".$request->dataset_use_type;
        $content .= "<br>";
        $content .= "<strong>".Lang::get('messages.description')."</strong><br>".$request->dataset_use_description;
        $content .= "<br><hr>";
        if (isset($project->license)) {
          $content .= "<strong>".Lang::get('messages.license')."</strong><br>".$project->license;
        }
        if (isset($project->policy)) {
          $content .= "<br><strong>".Lang::get('messages.data_policy')."</strong><br>".$project->policy;
        }
        $content .= "</ul><br><br>**".Lang::get('messages.no_reply_email')."**<br>";
        $subject = Lang::get('messages.dataset_request').' - '.$project->name.' - '.env('APP_NAME');
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
          return redirect('projects/'.$id)->withStatus($msg);
        }
        $msg = Lang::get('messages.dataset_request_email_sent');
        return redirect('projects/'.$id)->withStatus($msg);
    }


}
