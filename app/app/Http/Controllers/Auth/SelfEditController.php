<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Person;
use Auth;
use Validator;

class SelfEditController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function selfedit() {
	    $persons = Person::orderBy('abbreviation')->get();
	    return view('auth.selfedit', [
		    'persons' => $persons,
	    ]);
    }
    public function selfupdate(Request $request) {
		// Checks the e-mail and new_password for validity. new_password is OPTIONAL
		// Repeats some of the validation from Auth/RegisterController
		$validator = Validator::make($request->all(), [
			'email' => 'required|string|email|max:191|unique:users,email,'.Auth::user()->id,
			'new_password' => 'nullable|string|min:6|confirmed',
		]);
		
		// checks the old password against the old e-mail
		$credentials = ['email' => Auth::user()->email, 'password' => $request->password];
		$validator->after(function ($validator) use ($credentials) {
			if (! Auth::validate($credentials)) 
				$validator->errors()->add('password', 'The old password is missing or incorrect!');
		});

		if ($validator->fails()) {
			return redirect('selfedit')
				->withErrors($validator)
				->withInput($request->only(['email', 'person_id']));
		}

		// if the validation has succeeded...
		Auth::user()->email = $request->email;
		if (! is_null($request->new_password))
			Auth::user()->password = bcrypt($request->new_password);
		if ($request->person_id > 0)
			Auth::user()->person_id = $request->person_id;
		else
			Auth::user()->person_id = null;
		Auth::user()->save();

	    return redirect()->route('home');
    }
}
