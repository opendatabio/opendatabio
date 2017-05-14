<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SelfEditController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function selfedit() {
	    return view('auth.selfedit');
    }
    public function selfupdate() {
	    return view('auth.selfedit');
    }
//    public function update(Request $request, $id)
//    {
//	    // This method is called when a system administrator edits another user:
//	    $user = User::find($id);
//	    // ATTENTION, the system admin may set any password for a user, regardless of other restrictions elsewhere
//	    $this->validate($request, [
//		'email' => ['max:191', 'email', 'unique:users,email,'.$id]
//	]);
//	    $user->email = $request->email;
//	    if (! is_null($request->password))
//	    	$user->password = bcrypt($request->password);
//	    $user->save();
//	return redirect('users');
//    }
}
