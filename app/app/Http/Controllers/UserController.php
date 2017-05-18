<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
	    $users = User::orderBy('email')->paginate(10);
	    return view('users.index', [
        'users' => $users
    ]);
    }
    public function edit($id) {
	    $user = User::find($id);
	    return view('users.edit', [
	    	'user' => $user
	]);
    }
    public function show($id) {
	    return redirect ('users/'.$id.'/edit');
    }
    public function update(Request $request, $id)
    {
	    // This method is called when a system administrator edits another user:
	    $user = User::find($id);
	    // ATTENTION, the system admin may set any password for a user, regardless of other restrictions elsewhere
	    $this->validate($request, [
		'email' => ['max:191', 'email', 'unique:users,email,'.$id]
	]);
	    $user->email = $request->email;
	    if (! is_null($request->password))
	    	$user->password = bcrypt($request->password);
	    $user->save();
	return redirect('users');
    }
    public function destroy($id)
    {
	    try {
		    User::find($id)->delete();
	    } catch (\Illuminate\Database\QueryException $e) {
		    return redirect()->back()
			    ->withErrors(['This user is associated with other objects and cannot be removed'])->withInput();
	    }
	return redirect('users');
    }
}
