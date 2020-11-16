<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\DataTables\ProjectsDataTable;
use App\Project;
use App\User;
use Auth;
use Lang;
use App\Tag;
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

        return view('projects.create', compact('fullusers', 'allusers','tags'));
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
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'privacy' => 'required|integer',
            'admins' => 'required|array|min:1',
            'admins.*' => 'numeric|in:'.$fullusers,
            'collabs' => 'nullable|array',
            'collabs.*' => 'numeric|in:'.$fullusers,
            'url' => 'nullable|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
        ]);

        if (filter_var($request->url, FILTER_VALIDATE_URL) !== false) {
          $request->url = null;
          $message .= Lang::get('messages.invalid_url');
        }

        $project = new Project($request->only(['name', 'description', 'privacy','details', 'url']));
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

        return view('projects.create', compact('project', 'fullusers', 'allusers','tags','logo'));
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

        $this->validate($request, [
            'name' => 'required|string|max:191',
            'privacy' => 'required|integer',
            'admins' => 'required|array|min:1',
            'admins.*' => 'numeric|in:'.$fullusers,
            'collabs' => 'nullable|array',
            'collabs.*' => 'numeric|in:'.$fullusers,
        ]);
        if (filter_var($request->url, FILTER_VALIDATE_URL) !== false) {
          $request->url = null;
          $message .= Lang::get('messages.invalid_url');
        }
        $project->update($request->only(['name', 'description', 'privacy','details','url']));
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


    public function summary($id)
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


}
