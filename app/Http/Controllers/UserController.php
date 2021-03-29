<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\UsersDataTable;
use Illuminate\Support\Facades\Lang;
use App\Models\User;
use App\Models\Project;
use App\Models\Dataset;
use Response;

class UserController extends Controller
{
    public function autocomplete(Request $request) // Shows authorizes users
    {
        $users = User::where('email', 'LIKE', ['%'.$request->input('query').'%'])
            ->whereIn('access_level', [User::USER, User::ADMIN])
            ->select(['id as data', 'email as value'])
            ->orderBy('email', 'ASC')->take(30)->get();

        return Response::json(['suggestions' => $users]);
    }

    public function autocompleteAll(Request $request) // Show ALL users
    {
        $users = User::where('email', 'LIKE', ['%'.$request->input('query').'%'])
            ->select(['id as data', 'email as value'])
            ->orderBy('email', 'ASC')->take(30)->get();

        return Response::json(['suggestions' => $users]);
    }

    public function index(UsersDataTable $dataTable)
    {
        $this->authorize('show', User::class);

        return $dataTable->render('users.index');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function show($id)
    {
        return $this->edit($id);
    }

    public function update(Request $request, $id)
    {
        // This method is called when a system administrator edits another user:
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        // ATTENTION, the system admin may set any password for a user, regardless of other restrictions elsewhere
        $this->validate($request, [
            'email' => ['max:191', 'email', 'unique:users,email,'.$id],
        ]);
        if (USER::REGISTERED == $request->access_level) { // if he got demoted...
            // Reference: https://stackoverflow.com/questions/40104319/laravel-5-update-all-pivot-entries
            $user->datasets()->newPivotStatement()->where('user_id', '=', $user->id)->update(['access_level' => Project::VIEWER]);
            $user->projects()->newPivotStatement()->where('user_id', '=', $user->id)->update(['access_level' => Project::VIEWER]);
        }
        $user->email = $request->email;
        if (!is_null($request->password)) {
            $user->password = bcrypt($request->password);
        }
        $user->access_level = $request->access_level;
        if (User::ADMIN == $user->access_level or User::USER == $user->access_level) {
            if (!$user->projects()->count()) { // this user is not member of any project, let's create one for him/her
                $proj = Project::create([
                    'name' => substr($user->email, 0, strpos($user->email, '@')).' Workspace',
                ]);
                $proj->users()->attach($user, ['access_level' => Project::ADMIN]);
            }
            if (!$user->datasets()->count()) { // this user is not member of any project, let's create one for him/her
                $dataset = Dataset::create([
                    'name' => substr($user->email, 0, strpos($user->email, '@')).' Data Workspace',
                ]);
                $dataset->users()->attach($user, ['access_level' => Project::ADMIN]);
            }
        }
        $user->save();

        return redirect('users/'.$id)->withStatus(Lang::get('messages.saved'));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);
        try {
            $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()
                ->withErrors([Lang::get('messages.fk_error')])->withInput();
        }

        return redirect('users')->withStatus(Lang::get('messages.removed'));
    }
}
