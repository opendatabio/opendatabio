<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;
use App\User;

class UserController extends Controller
{
    public function index()
    {
	    $this->authorize('show', User::class);
	    $users = User::orderBy('email')->paginate(10);
	    return view('users.index', [
        'users' => $users
    ]);
    }
    public function edit($id) {
	    $user = User::findOrFail($id);
	    $this->authorize('update', $user);
	    return view('users.edit', [
	    	'user' => $user
	]);
    }
    public function show($id) {
	    $this->authorize('show', User::class);
	    return redirect ('users/'.$id.'/edit');
    }
    public function update(Request $request, $id)
    {
	    // This method is called when a system administrator edits another user:
	    $user = User::findOrFail($id);
	    $this->authorize('update', $user);
	    // ATTENTION, the system admin may set any password for a user, regardless of other restrictions elsewhere
	    $this->validate($request, [
		'email' => ['max:191', 'email', 'unique:users,email,'.$id]
	]);
	    $user->email = $request->email;
	    if (! is_null($request->password))
	    	$user->password = bcrypt($request->password);
	    $user->access_level = $request->access_level;
	    $user->save();
	return redirect('users')->withStatus(Lang::get('messages.saved'));
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
