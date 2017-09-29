<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Closure;
use Auth;
use App\User;

class AuthWithToken
{
	/**
     * Authenticates an API request if a token has been presented. If there is no token in the
     * request, simply proceed anonymously. If a token has been presented, but does not match
     * any valid user, refuse connection with Forbidden 403.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');
        if ($token) {
            $users = User::where('api_token', '=', $token)->get();
            if ($users->count()) {
                Auth::loginUsingId($users->first()->id);
            } else {
                return Response::json(
                    ['error' => 'Authentication failed (token provided is incorrect or expired)'], 
                    403);
            }
        } // if no token, proceed anonymously 
		return $next($request);
	}
}
