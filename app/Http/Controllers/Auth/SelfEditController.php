<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Person;
use App\Models\User;
use Auth;
use Validator;
use Hash;
use Lang;

class SelfEditController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function selfedit()
    {
        $persons = Person::orderBy('abbreviation')->get();
        $projects = Auth::user()->projects;
        $datasets = Auth::user()->datasets;

        return view('auth.selfedit', compact('persons', 'projects', 'datasets'));
    }

    public function token()
    {
        if (is_null(Auth::user()->api_token)) {
            Auth::user()->setToken();
        }

        return view('auth.token');
    }

    public function resetToken(Request $request)
    {
        if (!Hash::check($request->password, Auth::user()->password)) {
            return redirect('token')->withErrors(['password' => Lang::get('messages.wrong_password')]);
        }
        Auth::user()->setToken();

        return redirect('token')->withStatus(Lang::get('messages.saved'));
    }

    public function selfupdate(Request $request)
    {
        // Checks the e-mail and new_password for validity. new_password is OPTIONAL
        // Repeats some of the validation from Auth/RegisterController
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:191|unique:users,email,'.Auth::user()->id,
            'new_password' => 'nullable|string|min:6|confirmed',
            'person_id' => 'unique:users,person_id,'.Auth::user()->id,
        ]);

        // checks the old password against the old e-mail
        $credentials = ['email' => Auth::user()->email, 'password' => $request->password];
        $validator->after(function ($validator) use ($credentials) {
            if (!Auth::validate($credentials)) {
                $validator->errors()->add('password', 'The old password is missing or incorrect!');
            }
        });

        if ($validator->fails() ) {
            return redirect('selfedit')
                ->withErrors($validator)
                ->withInput($request->only(['email', 'person_id', 'project_id', 'dataset_id']));
        }

        // if the validation has succeeded...
        if (!is_null($request->new_password)) {
            Auth::user()->password = bcrypt($request->new_password);
            Auth::user()->save();
        }



        Auth::user()->update($request->only(['email', 'person_id', 'project_id', 'dataset_id']));

        return redirect()->route('home')->withStatus('Profile updated!');
    }
}
