<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Project;
use App\User;
use Auth;
use Validator;
use Lang;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $projects = Project::paginate(10);
        $myprojects = null;
        if (Auth::user() and Auth::user()->projects()->count())
            $myprojects = Auth::user()->projects;
        return view('projects.index', [
            'projects' => $projects,
            'myprojects' => $myprojects,
        ]);
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
        return view('projects.create', compact('fullusers', 'allusers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);
        $fullusers = User::where('access_level', '=', User::USER)
            ->orWhere('access_level', '=', User::ADMIN)->get()->pluck('id');
        $fullusers = implode(',', $fullusers->all());
        $this->validate($request, [
		    'name' => 'required|string|max:191',
		    'privacy' => 'required|integer',
            'admins' => 'required|array|min:1',
            'admins.*' => 'numeric|in:' . $fullusers,
            'collabs' => 'nullable|array',
            'collabs.*' => 'numeric|in:' . $fullusers,
	    ]);
        $project = new Project($request->only(['name', 'notes', 'privacy']));
        $project->save(); // needed to generate an id?
        $project->setusers($request->viewers, $request->collabs, $request->admins);
        return redirect('projects/' . $project->id )->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $project = Project::findOrFail($id);
        return view('projects.show', [
            'project' => $project,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $project = Project::findOrFail($id);
        $fullusers = User::where('access_level', '=', User::USER)->orWhere('access_level', '=', User::ADMIN)->get();
        $allusers = User::all();
        return view('projects.create', compact('project', 'fullusers', 'allusers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $this->authorize('update', $project);
        $fullusers = User::where('access_level', '=', User::USER)
            ->orWhere('access_level', '=', User::ADMIN)->get()->pluck('id');
        $fullusers = implode(',', $fullusers->all());
        $this->validate($request, [
		    'name' => 'required|string|max:191',
		    'privacy' => 'required|integer',
            'admins' => 'required|array|min:1',
            'admins.*' => 'numeric|in:' . $fullusers,
            'collabs' => 'nullable|array',
            'collabs.*' => 'numeric|in:' . $fullusers,
	    ]);
        $project->update($request->only(['name', 'notes', 'privacy']));
        $project->setusers($request->viewers, $request->collabs, $request->admins);
        return redirect('projects/'.$id)->withStatus(Lang::get('messages.saved'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
